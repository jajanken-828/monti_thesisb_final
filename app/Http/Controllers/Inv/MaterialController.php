<?php

namespace App\Http\Controllers\Inv;

use App\Http\Controllers\Controller;
use App\Models\Inv\Material;
use App\Models\War\Warehouse;
use App\Models\War\WarehouseStockItem;
use App\Models\Scm\MaterialRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class MaterialController extends Controller
{
    /**
     * Display the materials catalog with stock levels and delivery history.
     */
    public function index()
    {
        $user = auth()->user();

        // 1. Determine warehouse visibility based on role/permissions
        if (in_array($user->position, ['secretary', 'special_officer'])) {
            $warehouses = Warehouse::all();
        } else {
            $warehouses = $user->warehouseAccess()->get();
        }

        $warehouseIds = $warehouses->pluck('id')->toArray();

        // 2. Fetch materials with deep relationships
        // We include stockItems to grab the specific 'control_number' (Lot Number)
        $materials = Material::with([
            'receivingItems.receiving.warehouse', 
            'receivingItems.receiving.purchaseOrder.items',
            'stockItems' 
        ])
        ->orderBy('name')
        ->get();

        $materialsWithStock = $materials->map(function ($material) use ($warehouseIds) {
            // Real-time stock calculation across authorized warehouses
            $totalStock = WarehouseStockItem::where('material_id', $material->id)
                ->whereIn('warehouse_id', $warehouseIds)
                ->where('status', 'in_stock')
                ->sum('quantity');

            $status = ($totalStock <= 0) ? 'out' : (($totalStock <= $material->reorder_point) ? 'low' : 'ok');

            // 3. Map Delivery History from actual Receiving Logs
            $deliveryHistory = $material->receivingItems->map(function ($item) use ($material) {
                $receivingRecord = $item->receiving;
                if (!$receivingRecord) return null;
                
                // Link back to PO items to retrieve the historical unit price
                $poItem = $receivingRecord->purchaseOrder->items
                    ->where('material_id', $item->material_id)
                    ->first();

                $unitPrice = (float) ($poItem->unit_price ?? 0);

                /**
                 * FETCH ACTUAL LOT NUMBER (control_number)
                 * We find the specific stock record created during this receiving event
                 */
                $stockRecord = $material->stockItems
                    ->where('purchase_order_id', $receivingRecord->scm_purchase_order_id)
                    ->where('warehouse_id', $receivingRecord->warehouse_id)
                    ->where('quantity', $item->received_qty)
                    ->first();

                return [
                    'po_number'        => $receivingRecord->purchaseOrder->po_number ?? 'N/A',
                    'receiving_number' => $receivingRecord->receiving_number ?? 'N/A',
                    // TARGET: control_number column from warehouse_stock_items
                    'lot_number'       => $stockRecord->control_number ?? 'LOT-GEN-' . $item->id,
                    'warehouse_name'   => $receivingRecord->warehouse->name ?? 'Primary Hub',
                    'received_date'    => $receivingRecord->received_at 
                                            ? Carbon::parse($receivingRecord->received_at)->format('Y-m-d') 
                                            : 'N/A',
                    'kg'               => (float) $item->received_qty,
                    'price_per_kg'     => $unitPrice,
                    'total_amount'     => (float) ($item->received_qty * $unitPrice),
                ];
            })->filter()->values();

            return [
                'id'               => $material->id,
                'mat_id'           => $material->mat_id,
                'name'             => $material->name,
                'category'         => $material->category,
                'unit'             => $material->unit,
                'reorder_point'    => (float) $material->reorder_point,
                'unit_cost'        => (float) $material->unit_cost,
                'total_stock'      => (float) $totalStock,
                'status'           => $status,
                'delivery_history' => $deliveryHistory,
            ];
        });

        return Inertia::render('Dashboard/Inventory/Materials', [
            'materials' => $materialsWithStock,
        ]);
    }

    public function material() { return $this->index(); }

    /**
     * Handle the Procurement Request (Shopping Cart)
     */
    public function procurement(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        // Validating data sent from the Modal
        $validated = $request->validate([
            'required_qty' => 'required|numeric|min:0.01',
            'urgency'      => 'nullable|in:High,Medium,Low',
            'notes'        => 'nullable|string|max:500',
        ]);

        MaterialRequest::create([
            'req_number'    => 'REQ-' . strtoupper(bin2hex(random_bytes(3))),
            'material_id'   => $material->id,
            'material_name' => $material->name,
            'category'      => $material->category,
            'unit'          => $material->unit,
            'required_qty'  => $validated['required_qty'] ?? $material->reorder_point,
            'urgency'       => $validated['urgency'] ?? 'Medium',
            'notes'         => $validated['notes'] ?? 'Auto-generated from Stock Checker',
            'requested_by'  => auth()->user()->name,
            'requested_at'  => now(),
            'status'        => 'pending',
        ]);

        return redirect()->back()->with('success', 'Procurement request sent to SCM.');
    }

    /**
     * Update material details (Name and Reorder Point)
     */
    public function update(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'reorder_point' => 'required|numeric|min:0',
        ]);

        $material->update($validated);

        return redirect()->back()->with('success', 'Material updated successfully.');
    }

    /**
     * Store a new material in the system.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'category'      => 'required|in:Yarn,Dye,Supplies,Packaging',
            'unit'          => 'required|in:Rolls,Kg,Pcs',
            'reorder_point' => 'required|integer|min:0',
        ]);

        Material::create([
            'mat_id'        => Material::nextMatId(),
            'name'          => $validated['name'],
            'category'      => $validated['category'],
            'unit'          => $validated['unit'],
            'reorder_point' => $validated['reorder_point'],
            'unit_cost'     => 0, 
        ]);

        return redirect()->back()->with('success', 'Material registered successfully.');
    }

    /**
     * Delete material if no stock history exists.
     */
    public function destroy($id)
    {
        $material = Material::findOrFail($id);
        if (WarehouseStockItem::where('material_id', $material->id)->exists()) {
            return redirect()->back()->withErrors(['error' => 'Deletion denied: Material has associated stock records.']);
        }
        $material->delete();
        return redirect()->back()->with('success', 'Material removed.');
    }
}