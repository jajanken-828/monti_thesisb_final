<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Man\ManufacturingInventoryItem;
use App\Models\War\Warehouse;
use App\Models\War\WarehouseSection;
use App\Models\War\WarehouseShelf;
use App\Models\War\WarehouseStockItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MonitorController extends Controller
{
    /**
     * Display the warehouse monitor with grid and stock.
     */
    public function show(Warehouse $warehouse)
    {
        $user = auth()->user();

        if ($warehouse->supervisor_id !== $user->id && $warehouse->manager_id !== $user->id) {
            abort(403, 'You do not have access to this warehouse.');
        }

        // Fetch sections with shelves AND items assigned directly to the section (no shelf)
        $sections = $warehouse->sections()
            ->with(['shelves.stockItems.material', 'stockItemsNoShelf.material'])
            ->get();

        // Fetch stock that has no location assigned yet
        $unassignedStock = WarehouseStockItem::where('warehouse_id', $warehouse->id)
            ->whereNull('shelf_id')
            ->whereNull('section_id')
            ->with('material')
            ->get();

        return Inertia::render('Dashboard/Warehouse/Monitor', [
            'warehouse' => $warehouse,
            'sections' => $sections,
            'unassignedStock' => $unassignedStock,
        ]);
    }

    /**
     * Update grid layout and dimensions.
     * Uses a Sync approach to prevent data loss for existing stock.
     */
    public function updateLayout(Request $request, Warehouse $warehouse)
    {
        $user = auth()->user();
        if ($warehouse->supervisor_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $data = $request->validate([
            'grid_rows' => 'required|integer',
            'grid_cols' => 'required|integer',
            'sections' => 'array',
            'sections.*.id' => 'nullable',
            'sections.*.name' => 'required|string',
            'sections.*.row' => 'required|integer',
            'sections.*.col' => 'required|integer',
            'sections.*.shelves' => 'array',
        ]);

        try {
            DB::beginTransaction();

            $warehouse->update([
                'grid_rows' => $data['grid_rows'],
                'grid_cols' => $data['grid_cols'],
            ]);

            $activeSectionIds = [];

            foreach ($data['sections'] as $sec) {
                $sectionId = (isset($sec['id']) && !str_starts_with($sec['id'], 'temp-')) ? $sec['id'] : null;

                $section = WarehouseSection::updateOrCreate(
                    ['id' => $sectionId, 'warehouse_id' => $warehouse->id],
                    [
                        'name' => $sec['name'],
                        'grid_row' => $sec['row'],
                        'grid_col' => $sec['col'],
                    ]
                );
                $activeSectionIds[] = $section->id;

                $activeShelfIds = [];
                if (isset($sec['shelves'])) {
                    foreach ($sec['shelves'] as $sh) {
                        $shId = (isset($sh['id']) && !str_starts_with($sh['id'], 'ts-')) ? $sh['id'] : null;

                        $shelf = WarehouseShelf::updateOrCreate(
                            ['id' => $shId, 'section_id' => $section->id],
                            ['shelf_number' => $sh['shelf_number']]
                        );
                        $activeShelfIds[] = $shelf->id;
                    }
                }
                $section->shelves()->whereNotIn('id', $activeShelfIds)->delete();
            }

            $warehouse->sections()->whereNotIn('id', $activeSectionIds)->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Warehouse layout and shelves saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Layout error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Creation failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Assign stock to either a specific shelf OR just a section box.
     */
    public function assignToShelf(Request $request)
    {
        $data = $request->validate([
            'stock_item_id' => 'required|exists:warehouse_stock_items,id',
            'shelf_id'      => 'nullable|exists:warehouse_shelves,id',
            'section_id'    => 'nullable|exists:warehouse_sections,id',
        ]);

        $stock = WarehouseStockItem::findOrFail($data['stock_item_id']);
        $user = auth()->user();

        if ($stock->warehouse->supervisor_id !== $user->id) {
            abort(403, 'Unauthorized to assign stock.');
        }

        if (!empty($data['shelf_id'])) {
            $shelf = WarehouseShelf::findOrFail($data['shelf_id']);
            $stock->update([
                'shelf_id' => $shelf->id,
                'section_id' => $shelf->section_id
            ]);
        } elseif (!empty($data['section_id'])) {
            $stock->update([
                'section_id' => $data['section_id'],
                'shelf_id' => null
            ]);
        }

        return redirect()->back()->with('success', 'Material location updated.');
    }

    /**
     * Transfer material to manufacturing production inventory.
     *
     * FIXES APPLIED:
     * 1. Null-safe access to $stockItem->material->category via optional chaining.
     * 2. Wrapped in try/catch so failures surface a real error instead of 500.
     * 3. Explicit load of material relationship before use to avoid lazy-load miss.
     */
    public function useMaterial(Request $request, WarehouseStockItem $stockItem)
    {
        // Eagerly load material to avoid silent null failures
        $stockItem->load('material');

        // Guard: if the material record no longer exists, bail early with a clear message
        if (!$stockItem->material) {
            return redirect()->back()->withErrors([
                'error' => "Material record not found for stock item {$stockItem->control_number}. Cannot transfer.",
            ]);
        }

        $data = $request->validate([
            'quantity' => 'required|numeric|min:0.01|max:' . $stockItem->quantity,
            'manufacturing_department' => 'required|string|in:knitting,dyeing,maintenance,packaging',
        ]);

        $user = auth()->user();
        if ($stockItem->warehouse->supervisor_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $quantityToTransfer = $data['quantity'];
        $department         = $data['manufacturing_department'];

        // Determine the category value to store.
        // Falls back to a safe default if for any reason it's null.
        $category = $stockItem->material->category ?? 'General';

        try {
            DB::transaction(function () use ($stockItem, $quantityToTransfer, $department, $user, $category) {
                // 1. Create manufacturing inventory record.
                //    control_number is unique per stock item, so the same item can only
                //    be transferred once. Attempting to transfer again (after partial deduction)
                //    uses the same control_number → unique constraint fires.
                //    RESOLUTION: we check if the control_number already exists and increment
                //    the existing record's quantity instead of creating a duplicate.
                $existing = ManufacturingInventoryItem::where('control_number', $stockItem->control_number)->first();

                if ($existing) {
                    // Add to the existing manufacturing inventory record
                    $existing->increment('initial_quantity',   $quantityToTransfer);
                    $existing->increment('remaining_quantity',  $quantityToTransfer);
                    if ($existing->status === 'depleted') {
                        $existing->update(['status' => 'available']);
                    }
                } else {
                    ManufacturingInventoryItem::create([
                        'control_number'          => $stockItem->control_number,
                        'material_id'             => $stockItem->material_id,
                        'warehouse_stock_item_id' => $stockItem->id,
                        'initial_quantity'        => $quantityToTransfer,
                        'remaining_quantity'      => $quantityToTransfer,
                        'unit'                    => $stockItem->unit,
                        'category'                => $category,
                        'status'                  => 'available',
                        'department'              => $department,
                        'received_at'             => now(),
                        'received_from'           => $user->id,
                        'notes'                   => "Transferred from warehouse by {$user->name}",
                    ]);
                }

                // 2. Deduct from warehouse stock.
                if ($quantityToTransfer >= $stockItem->quantity) {
                    $stockItem->update([
                        'status'     => 'used',
                        'quantity'   => 0,
                        'shelf_id'   => null,
                        'section_id' => null,
                    ]);
                } else {
                    $stockItem->decrement('quantity', $quantityToTransfer);
                }
            });
        } catch (\Exception $e) {
            Log::error("useMaterial failed for stock item {$stockItem->id}: " . $e->getMessage());
            return redirect()->back()->withErrors([
                'error' => 'Transfer failed: ' . $e->getMessage(),
            ]);
        }

        return redirect()->back()->with(
            'success',
            "{$quantityToTransfer} {$stockItem->unit} of {$stockItem->material->name} transferred to {$department} department."
        );
    }
}