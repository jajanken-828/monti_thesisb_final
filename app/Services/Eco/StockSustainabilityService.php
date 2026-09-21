<?php

namespace App\Services\Eco;

use App\Models\Inv\Material;
use App\Models\Ord\SalesOrder;
use App\Models\Scm\MaterialRequest;
use App\Models\War\WarehouseStockItem;

/**
 * Decision Support: stock sustainability check for ECO order acceptance.
 *
 * Before ECO accepts (pushes) a job order, this service verifies that live
 * warehouse stock can cover BOTH the new order AND everything already
 * committed to previously accepted orders:
 *
 *   available_to_promise = live_stock − committed_to_accepted_orders
 *
 * Example: 2000kg yarn on hand, accepted orders already consume 1800kg →
 * ATP = 200kg, so a new 300kg order is blocked and procurement is
 * suggested for the shortfall materials.
 *
 * Committed = accepted-but-unfinished job orders (stock not yet consumed).
 * Terminal states (completed / delivered / cancelled) neither commit nor
 * consume in this model — their stock was already deducted at release.
 */
class StockSustainabilityService
{
    /**
     * Sales-order statuses that hold a claim on warehouse stock.
     */
    public const COMMITTED_STATUSES = [
        'pushed_to_scm',
        'pushed_to_ordermgmt',
        'confirmed',
        'in_planning',
        'inv_check',
        'inv_checked',
        'in_production',
        'production_done',
        'ready_for_dispatch',
        'in_transit',
        'on_hold',
    ];

    /**
     * Material requirements for one order: [material_id => required_qty].
     * Derived from the attached recipe (BOM) × order quantity in kg.
     * Returns [] when the order has no usable recipe (verdict: no_recipe).
     */
    public function materialsForOrder(SalesOrder $order): array
    {
        $order->loadMissing('recipe');
        $recipe = $order->recipe;

        if (! $recipe) {
            return [];
        }

        $materials = $recipe->materials;
        if (is_string($materials)) {
            $materials = json_decode($materials, true);
        }
        if (! is_array($materials) || empty($materials)) {
            return [];
        }

        $qty = (float) ($order->quantity ?? 0);
        $needed = [];
        foreach ($materials as $materialId => $qtyPerUnit) {
            $needed[(int) $materialId] = ((float) $qtyPerUnit) * $qty;
        }

        return array_filter($needed, fn ($v) => $v > 0);
    }

    /**
     * Total kg already promised to accepted orders, per material.
     * The order being evaluated is excluded so it isn't double-counted.
     */
    public function committedTotals(?int $excludeId = null): array
    {
        $orders = SalesOrder::whereIn('status', self::COMMITTED_STATUSES)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->with('recipe')
            ->get();

        $totals = [];
        foreach ($orders as $order) {
            foreach ($this->materialsForOrder($order) as $matId => $qty) {
                $totals[$matId] = ($totals[$matId] ?? 0) + $qty;
            }
        }

        return $totals;
    }

    /**
     * Live warehouse stock per material (in_stock only, depleted excluded).
     */
    public function liveStock(array $materialIds): array
    {
        if (empty($materialIds)) {
            return [];
        }

        return WarehouseStockItem::whereIn('material_id', $materialIds)
            ->where('status', 'in_stock')
            ->where('quantity', '>', 0)
            ->selectRaw('material_id, SUM(quantity) as qty')
            ->groupBy('material_id')
            ->pluck('qty', 'material_id')
            ->map(fn ($v) => (float) $v)
            ->toArray();
    }

    /**
     * Full sustainability verdict for one pending order.
     */
    public function evaluate(SalesOrder $order): array
    {
        $required = $this->materialsForOrder($order);

        if (empty($required)) {
            return [
                'verdict' => 'no_recipe',
                'sufficient' => true,
                'warning' => 'No recipe (BOM) attached — material needs could not be verified. Attach a recipe for a precise check.',
                'order_id' => $order->id,
                'jo_number' => $order->jo_number ?? 'JO-'.$order->id,
                'committed_orders' => 0,
                'materials' => [],
            ];
        }

        $committed = $this->committedTotals($order->id);
        $stock = $this->liveStock(array_keys($required));
        $materials = Material::whereIn('id', array_keys($required))->get()->keyBy('id');

        $rows = [];
        $sufficient = true;
        foreach ($required as $matId => $need) {
            $have = (float) ($stock[$matId] ?? 0);
            $taken = (float) ($committed[$matId] ?? 0);
            $atp = $have - $taken;
            $shortage = max(0, $need - $atp);
            if ($shortage > 0) {
                $sufficient = false;
            }

            $rows[] = [
                'material_id' => $matId,
                'material_name' => $materials[$matId]->name ?? 'Unknown material',
                'unit' => $materials[$matId]->unit ?? 'kg',
                'required' => round($need, 2),
                'committed' => round($taken, 2),
                'available' => round($have, 2),
                'atp' => round($atp, 2),
                'shortage' => round($shortage, 2),
                'sufficient' => $shortage <= 0,
            ];
        }

        return [
            'verdict' => $sufficient ? 'sufficient' : 'insufficient',
            'sufficient' => $sufficient,
            'order_id' => $order->id,
            'jo_number' => $order->jo_number ?? 'JO-'.$order->id,
            'committed_orders' => SalesOrder::whereIn('status', self::COMMITTED_STATUSES)->where('id', '!=', $order->id)->count(),
            'materials' => $rows,
        ];
    }

    /**
     * Create procurement suggestions (pending MaterialRequests) for every
     * shortfall material. Idempotent per job order: an existing pending
     * request for the same material + JO is reused, never duplicated.
     *
     * @return array the requests created (with `created` flag each)
     */
    public function suggestProcurement(SalesOrder $order, array $evaluation, ?string $requestedBy = null): array
    {
        $jo = $evaluation['jo_number'] ?? ('JO-'.$order->id);
        $created = [];

        foreach ($evaluation['materials'] ?? [] as $row) {
            if (($row['shortage'] ?? 0) <= 0) {
                continue;
            }

            $existing = MaterialRequest::where('material_id', $row['material_id'])
                ->where('status', 'pending')
                ->where('notes', 'like', '%'.$jo.'%')
                ->first();

            if ($existing) {
                $created[] = ['req_number' => $existing->req_number, 'created' => false] + $row;
                continue;
            }

            $material = Material::find($row['material_id']);
            $mr = MaterialRequest::create([
                'req_number' => 'REQ-'.strtoupper(bin2hex(random_bytes(3))),
                'material_id' => $row['material_id'],
                'material_name' => $row['material_name'],
                'category' => $material->category ?? 'General',
                'unit' => $row['unit'],
                'current_stock' => $row['available'],
                'required_qty' => $row['shortage'],
                'urgency' => 'High',
                'notes' => "DSS auto-suggestion: {$jo} needs {$row['required']}{$row['unit']}, ATP {$row['atp']}{$row['unit']} — short {$row['shortage']}{$row['unit']}.",
                'requested_by' => $requestedBy ?? 'ECO-DSS',
                'requested_at' => now(),
                'status' => 'pending',
            ]);

            $created[] = ['req_number' => $mr->req_number, 'created' => true] + $row;
        }

        return $created;
    }
}
