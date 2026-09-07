<?php

namespace App\Http\Controllers\man\Staff;

use App\Models\man\DyeJob;
use App\Models\man\DyeJobChemical;
use App\Models\man\Fabric;
use App\Models\man\Machine;
use App\Models\man\MachineReport;
use App\Models\man\ManufacturingInventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DyeingColorController extends ManufacturingStaffController
{
    public function index()
    {
        $pendingCount = Fabric::where('status', 'dyeing')->count();
        $recentJobs   = DyeJob::with('fabric')
            ->where('operator_id', $this->staff()->id)
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingColor/Index', [
            'stats' => [
                'pending'     => $pendingCount,
                'total_today' => DyeJob::whereDate('processed_at', today())->count(),
            ],
            'recentJobs' => $recentJobs,
        ]);
    }

    public function dyeingColor()
    {
        // Fabrics ready for dyeing — eager-load their linked JO + recipe
        $fabrics = Fabric::with(['machine', 'operator', 'salesOrder.recipe'])
            ->where('status', 'dyeing')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($fabric) {
                $salesOrder = $fabric->salesOrder;
                $recipe     = $salesOrder?->recipe;

                // Decode recipe materials if present
                $materialsData = [];
                if ($recipe && $recipe->materials) {
                    $json = json_decode($recipe->materials, true);
                    if (is_array($json)) {
                        foreach ($json as $matId => $qty) {
                            $materialsData[] = ['material_id' => $matId, 'quantity' => $qty];
                        }
                    }
                }

                return [
                    'id'           => $fabric->id,
                    'code'         => $fabric->code,
                    'yarn_type'    => $fabric->yarn_type,
                    'weight'       => $fabric->weight,
                    'shift'        => $fabric->shift,
                    'remarks'      => $fabric->remarks,
                    'processed_at' => $fabric->processed_at?->format('M d, Y g:i A'),
                    'machine'      => $fabric->machine
                        ? ['machine_no' => $fabric->machine->machine_no] : null,
                    'operator'     => $fabric->operator
                        ? ['name' => $fabric->operator->name] : null,

                    // Linked job order info
                    'sales_order'  => $salesOrder ? [
                        'jo_number' => $salesOrder->jo_number,
                        'color'     => $salesOrder->color,
                        'design'    => $salesOrder->design,
                        'quantity'  => $salesOrder->quantity,
                        'yarn_type' => $salesOrder->yarn_type,
                    ] : null,

                    // Recipe — the dye staff uses this to know the color formula
                    'recipe'       => $recipe ? [
                        'id'           => $recipe->id,
                        'yarn_type'    => $recipe->yarn_type,
                        'dye_color'    => $recipe->dye_color,
                        'weave_design' => $recipe->weave_design,
                        'materials'    => $materialsData,
                    ] : null,
                ];
            });

        $machines = Machine::where('type', 'dyeing')
            ->where('status', 'available')
            ->get(['id', 'machine_no']);

        // Dye chemicals available in production inventory (dyeing department)
        $dyes = ManufacturingInventoryItem::with('material')
            ->where('department', 'dyeing')
            ->where('category', 'Dye')
            ->where('status', '!=', 'depleted')
            ->where('remaining_quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($item) => [
                'id'                 => $item->id,
                'control_number'     => $item->control_number,
                'material_name'      => $item->material->name,
                'remaining_quantity' => $item->remaining_quantity,
                'unit'               => $item->unit,
            ]);

        return Inertia::render('Dashboard/MAN/Employee/DyeingColor/DyeingColor', [
            'fabrics'  => $fabrics,
            'machines' => $machines,
            'dyes'     => $dyes,       // NEW — inventory-sourced dye chemicals
        ]);
    }

    /**
     * Record a dye job, consuming chemical lots from production inventory.
     *
     * dyes_used[] mirrors the knitting yarns_used[] pattern:
     *   [{ inventory_item_id, quantity_used }]
     */
    public function storeDye(Request $request)
    {
        $validated = $request->validate([
            'fabric_id'                       => 'required|exists:fabrics,id',
            'machine_id'                      => 'required|exists:machines,id',
            'remarks'                         => 'nullable|string',
            'dyes_used'                       => 'required|array|min:1',
            'dyes_used.*.inventory_item_id'   => 'required|exists:manufacturing_inventory_items,id',
            'dyes_used.*.quantity_used'       => 'required|numeric|min:0.01',
        ]);

        // Pre-validate sufficient quantity for every dye lot
        $insufficient = [];
        foreach ($validated['dyes_used'] as $usage) {
            $item = ManufacturingInventoryItem::with('material')
                ->find($usage['inventory_item_id']);
            if (!$item) {
                return back()->withErrors(['dyes_used' => 'Invalid dye item selected.']);
            }
            if ($item->remaining_quantity < $usage['quantity_used']) {
                $insufficient[] = "{$item->material->name} ({$item->control_number})"
                    . " — only {$item->remaining_quantity} {$item->unit} left";
            }
        }
        if (!empty($insufficient)) {
            return back()->withErrors([
                'dyes_used' => 'Insufficient chemicals: ' . implode(', ', $insufficient),
            ]);
        }

        DB::beginTransaction();
        try {
            // Use the first dye lot as the "primary" for the dye_jobs main columns
            $primaryItem = ManufacturingInventoryItem::with('material')
                ->findOrFail($validated['dyes_used'][0]['inventory_item_id']);

            $dyeJob = DyeJob::create([
                'fabric_id'    => $validated['fabric_id'],
                'machine_id'   => $validated['machine_id'],
                'dye_type'     => $primaryItem->material->name,
                'chemical_no'  => $primaryItem->control_number,
                'remarks'      => $validated['remarks'],
                'operator_id'  => $this->staff()->id,
                'shift'        => $this->getShift(),
                'code'         => $this->generateCode('CHEM', DyeJob::class),
                'processed_at' => now(),
            ]);

            // Create chemical detail rows and deduct from each inventory lot
            foreach ($validated['dyes_used'] as $usage) {
                $item = ManufacturingInventoryItem::with('material')
                    ->findOrFail($usage['inventory_item_id']);

                DyeJobChemical::create([
                    'dye_job_id'        => $dyeJob->id,
                    'inventory_item_id' => $item->id,
                    'dye_type'          => $item->material->name,
                    'control_number'    => $item->control_number,
                    'quantity_used'     => $usage['quantity_used'],
                ]);

                // Deduct quantity from inventory (kg-based, not roll-based)
                $item->remaining_quantity = max(0, $item->remaining_quantity - $usage['quantity_used']);
                $item->status = $item->remaining_quantity <= 0 ? 'depleted' : 'partial';
                $item->save();
            }

            // Advance fabric to next stage
            Fabric::findOrFail($validated['fabric_id'])->update(['status' => 'softener']);

            DB::commit();
            return redirect()->back()->with(
                'message',
                'Dye job recorded. Chemical inventory updated for ' . count($validated['dyes_used']) . ' lot(s).'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to record dye job: ' . $e->getMessage()]);
        }
    }

    public function reports()
    {
        $machines  = Machine::where('type', 'dyeing')->get(['id', 'machine_no', 'status']);
        $myReports = MachineReport::where('reported_by', $this->staff()->id)
            ->with('machine')->latest()->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingColor/Reports', [
            'machines'  => $machines,
            'myReports' => $myReports,
        ]);
    }

    public function reportMachine(Request $request)
    {
        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'issue'      => 'required|string',
        ]);

        MachineReport::create([
            'machine_id'  => $validated['machine_id'],
            'reported_by' => $this->staff()->id,
            'issue'       => $validated['issue'],
            'status'      => 'pending',
        ]);

        return redirect()->back()->with('message', 'Machine issue reported.');
    }
}