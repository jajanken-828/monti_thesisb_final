<?php

namespace App\Http\Controllers\scm;

use App\Http\Controllers\Controller;
use App\Models\ord\PurchaseOrder;
use App\Models\ord\SalesOrder;
use App\Models\ord\OrderQueue;
use App\Models\man\BomRecord;
use App\Models\inv\Material;
use App\Models\war\WarehouseStockItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ScmSalesOrderController extends Controller
{
    public function index()
    {
        // ─── Existing Purchase Orders (from ECO via PO) ─────────────────────
        $purchaseOrders = PurchaseOrder::with(['client', 'items.product'])
            ->whereHas('queue', function ($q) {
                $q->whereIn('stage', ['eco_approved', 'scm_received', 'inv_check']);
            })
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($order) {
                $queue = $order->queue;
                return [
                    'type' => 'purchase_order',
                    'id' => $order->id,
                    'po_number' => $order->po_number,
                    'client_name' => $order->client->company_name,
                    'total_amount' => $order->total_amount,
                    'created_at' => $order->created_at,
                    'stage' => $queue ? $queue->stage : 'eco_approved',
                    'inv_check_sufficient' => $queue ? $queue->inv_check_sufficient : null,
                    'items' => $order->items->map(fn($item) => [
                        'product_name' => $item->product->name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                    ]),
                    'sales_order_id' => null,
                ];
            });

        // ─── Sales Orders pushed from ECO (status = pushed_to_scm) ──────────
        $salesOrders = SalesOrder::whereIn('status', ['pushed_to_scm', 'inv_check', 'inv_checked', 'in_production'])
            ->with(['client', 'recipe'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($order) {
                // Determine current stage based on status
                $stage = $order->status;
                $invCheckSufficient = null;
                if ($stage === 'inv_checked') {
                    $invCheckSufficient = true;
                } elseif ($stage === 'inv_check') {
                    $invCheckSufficient = false;
                }

                $clientName = $order->client->company_name ?? 'N/A';
                // Build a single "item" from sales order data
                $items = [];
                if ($order->color || $order->yarn_type) {
                    $productName = trim(($order->color ?? '') . ' ' . ($order->yarn_type ?? ''));
                    $items[] = [
                        'product_name' => $productName ?: 'Custom Product',
                        'quantity' => $order->quantity ?? 0,
                        'unit_price' => $order->total_amount && $order->quantity
                            ? $order->total_amount / $order->quantity
                            : 0,
                    ];
                }
                return [
                    'type' => 'sales_order',
                    'id' => $order->id,
                    'po_number' => $order->jo_number ?? 'JO-' . $order->id,
                    'client_name' => $clientName,
                    'total_amount' => $order->total_amount ?? 0,
                    'created_at' => $order->created_at,
                    'stage' => $stage,
                    'inv_check_sufficient' => $invCheckSufficient,
                    'items' => $items,
                    'sales_order_id' => $order->id,
                    'color' => $order->color,
                    'yarn_type' => $order->yarn_type,
                    'quantity' => $order->quantity,
                ];
            });

        // Merge both collections
        $orders = $purchaseOrders->concat($salesOrders)->sortByDesc('created_at')->values();

        return Inertia::render('Dashboard/SCM/SalesOrder', [
            'orders' => $orders,
        ]);
    }

    /**
     * Check inventory for a Purchase Order (existing)
     */
    public function checkInventory(PurchaseOrder $order)
    {
        $queue = $order->queue;
        if (!$queue) {
            $queue = OrderQueue::create([
                'purchase_order_id' => $order->id,
                'stage' => 'inv_check',
            ]);
        } else {
            $queue->update(['stage' => 'inv_check']);
        }
        return redirect()->back()->with('success', 'Inventory check requested for purchase order.');
    }

    /**
     * Check inventory for a Sales Order (pushed from ECO)
     * Now accepts a 'sufficient' boolean to set the order as ready for production.
     */
    public function checkInventorySalesOrder(Request $request, SalesOrder $salesOrder)
    {
        // Only allowed if currently in 'pushed_to_scm' or 'inv_check' state
        if (!in_array($salesOrder->status, ['pushed_to_scm', 'inv_check'])) {
            return redirect()->back()->withErrors(['error' => 'Order is not in a state that allows inventory check.']);
        }

        $sufficient = $request->boolean('sufficient', false);
        $newStatus = $sufficient ? 'inv_checked' : 'inv_check';

        $salesOrder->update([
            'status' => $newStatus,
            'inv_check_sufficient' => $sufficient,
        ]);

        $message = $sufficient
            ? 'Inventory check passed. Order marked as ready for production.'
            : 'Inventory check completed. Insufficient stock – awaiting procurement.';

        return redirect()->back()->with('success', $message);
    }

    /**
     * Push Purchase Order to Production
     */
    public function pushToProduction(PurchaseOrder $order)
    {
        $queue = $order->queue;
        if (!$queue || $queue->stage !== 'inv_checked' || !$queue->inv_check_sufficient) {
            return redirect()->back()->withErrors(['error' => 'Order cannot be pushed to production. Inventory check must be sufficient first.']);
        }
        $queue->update(['stage' => 'man_production']);
        return redirect()->back()->with('success', 'Purchase order pushed to Manufacturing.');
    }

    /**
     * Push Sales Order to Production
     */
    public function pushToProductionSalesOrder(SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'inv_checked') {
            return redirect()->back()->withErrors(['error' => 'Sales order cannot be pushed to production. Inventory check must be completed and sufficient first.']);
        }
        $salesOrder->update(['status' => 'in_production']);
        return redirect()->back()->with('success', 'Sales order pushed to Manufacturing.');
    }

    /**
     * INSTANT INVENTORY CHECK – Returns JSON with detailed material requirements and stock levels.
     */
    public function checkInventoryInstant($type, $id)
    {
        if ($type === 'purchase_order') {
            $order = PurchaseOrder::with(['client', 'items.product'])->findOrFail($id);
            $materialsNeeded = [];

            foreach ($order->items as $item) {
                // Try to find recipe using fabric (yarn_type), color, design
                $recipe = BomRecord::where('client_id', $order->client_id)
                    ->where('product_id', $item->product_id)
                    ->where('yarn_type', $item->fabric)
                    ->where('dye_color', $item->color)
                    ->where('weave_design', $item->design)
                    ->first();

                // Fallback: match by client and product only
                if (!$recipe) {
                    $recipe = BomRecord::where('client_id', $order->client_id)
                        ->where('product_id', $item->product_id)
                        ->first();
                }

                if ($recipe) {
                    $materials = json_decode($recipe->materials, true);
                    foreach ($materials as $materialId => $qtyPerUnit) {
                        // qtyPerUnit is per kg (or per piece) – multiply by item kilos
                        $required = $qtyPerUnit * $item->kilos;
                        if (!isset($materialsNeeded[$materialId])) {
                            $materialsNeeded[$materialId] = 0;
                        }
                        $materialsNeeded[$materialId] += $required;
                    }
                }
            }

            $orderNumber = $order->po_number;

        } elseif ($type === 'sales_order') {
            $order = SalesOrder::with(['client', 'recipe'])->findOrFail($id);
            $materialsNeeded = [];

            if ($order->recipe) {
                $materials = json_decode($order->recipe->materials, true);
                foreach ($materials as $materialId => $qtyPerUnit) {
                    $required = $qtyPerUnit * $order->quantity;
                    $materialsNeeded[$materialId] = $required;
                }
            }

            $orderNumber = $order->jo_number ?? 'JO-' . $order->id;

        } else {
            abort(404);
        }

        // Get current stock for each material
        $materialIds = array_keys($materialsNeeded);
        $materials = Material::whereIn('id', $materialIds)->get()->keyBy('id');
        $stockItems = WarehouseStockItem::whereIn('material_id', $materialIds)
            ->where('status', 'in_stock')
            ->select('material_id', DB::raw('SUM(quantity) as total_stock'))
            ->groupBy('material_id')
            ->pluck('total_stock', 'material_id');

        $details = [];
        $sufficient = true;

        foreach ($materialsNeeded as $matId => $required) {
            $available = $stockItems[$matId] ?? 0;
            $shortage = max(0, $required - $available);
            if ($shortage > 0) $sufficient = false;

            $details[] = [
                'material_id' => $matId,
                'material_name' => $materials[$matId]->name ?? 'Unknown',
                'unit' => $materials[$matId]->unit ?? '',
                'required' => round($required, 2),
                'available' => round($available, 2),
                'shortage' => round($shortage, 2),
                'status' => $shortage > 0 ? 'insufficient' : 'sufficient',
            ];
        }

        return response()->json([
            'order_type' => $type,
            'order_id' => $id,
            'order_number' => $orderNumber,
            'sufficient' => $sufficient,
            'materials' => $details,
        ]);
    }
}