<?php

namespace App\Http\Controllers\Man\Staff;

use App\Models\Man\BomRecord;
use App\Models\Man\LabDipRequest;
use App\Models\Man\LabStockSolution;
use App\Models\Man\LabTest;
use App\Models\Man\LabTrial;
use App\Models\Man\ManufacturingInventoryItem;
use App\Models\Ord\SalesOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DyeingLabChemistController extends ManufacturingStaffController
{
    public function index()
    {
        $pendingCount = LabDipRequest::whereIn('status', ['pending', 'in_progress', 'awaiting_spectro'])->count();
        $recentDips = LabDipRequest::with('salesOrder')
            ->where('operator_id', $this->staff()->id)
            ->latest()
            ->take(10)
            ->get();

        $nextQueue = LabDipRequest::with('salesOrder')
            ->where('status', 'pending')
            ->orderByRaw("FIELD(urgency, 'urgent', 'high', 'normal', 'low')")
            ->orderBy('created_at', 'asc')
            ->take(3)
            ->get(['id', 'code', 'urgency', 'pantone_code', 'created_at']);

        return Inertia::render('Dashboard/MAN/Employee/DyeingLabChemist/Index', [
            'stats' => [
                'pending' => $pendingCount,
                'total_today' => LabDipRequest::whereDate('processed_at', today())->count(),
            ],
            'recentDips' => $recentDips,
            'efficiency' => $this->staffEfficiency(LabDipRequest::class),
            'labMetrics' => $this->labMetrics(),
            'nextQueue' => $nextQueue,
        ]);
    }

    /**
     * Shade development work page: open dip requests with trial history.
     */
    public function shades()
    {
        $dips = LabDipRequest::with(['trials.operator', 'salesOrder'])
            ->whereIn('status', ['pending', 'in_progress', 'awaiting_spectro'])
            ->orderByRaw("FIELD(urgency, 'urgent', 'high', 'normal', 'low')")
            ->orderBy('created_at', 'asc')
            ->get();

        $jobOrders = SalesOrder::where('status', 'in_production')
            ->orderBy('created_at', 'asc')
            ->take(30)
            ->get(['id', 'jo_number', 'color', 'design', 'yarn_type']);

        return Inertia::render('Dashboard/MAN/Employee/DyeingLabChemist/Shades', [
            'dips' => $dips,
            'jobOrders' => $jobOrders,
        ]);
    }

    public function storeDip(Request $request)
    {
        $validated = $request->validate([
            'sales_order_id' => 'nullable|exists:sales_orders,id',
            'customer_ref' => 'nullable|string|max:255',
            'pantone_code' => 'nullable|string|max:64',
            'rgb_lab_values' => 'nullable|string|max:255',
            'swatch_id' => 'nullable|string|max:255',
            'fabric_details' => 'nullable|string|max:255',
            'urgency' => 'required|in:low,normal,high,urgent',
            'remarks' => 'nullable|string',
        ]);

        LabDipRequest::create([
            ...$validated,
            'code' => $this->generateCode('LABDIP', LabDipRequest::class),
            'status' => 'pending',
            'operator_id' => $this->staff()->id,
            'shift' => $this->getShift(),
            'processed_at' => now(),
        ]);

        return redirect()->back()->with('message', 'Lab dip request logged successfully.');
    }

    public function storeTrial(Request $request)
    {
        $validated = $request->validate([
            'dip_request_id' => 'required|exists:lab_dip_requests,id',
            'dyestuffs' => 'nullable|array',
            'dyestuffs.*.name' => 'required_with:dyestuffs|string|max:255',
            'dyestuffs.*.pct' => 'nullable|numeric|min:0',
            'auxiliaries' => 'nullable|array',
            'auxiliaries.*.name' => 'required_with:auxiliaries|string|max:255',
            'auxiliaries.*.gpl' => 'nullable|numeric|min:0',
            'liquor_ratio' => 'nullable|string|max:32',
            'curve' => 'nullable|array',
            'adjustments' => 'nullable|string',
            'delta_e' => 'nullable|numeric|min:0',
            'status' => 'required|in:pending,passed,failed',
        ]);

        $dip = LabDipRequest::findOrFail($validated['dip_request_id']);
        if (in_array($dip->status, ['approved', 'rejected'])) {
            return back()->withErrors(['error' => 'This dip request is already closed.']);
        }

        $trialNo = (LabTrial::where('dip_request_id', $dip->id)->max('trial_no') ?? 0) + 1;

        LabTrial::create([
            'code' => $this->generateCode('TRIAL', LabTrial::class),
            'dip_request_id' => $dip->id,
            'trial_no' => $trialNo,
            'formula' => [
                'dyestuffs' => $validated['dyestuffs'] ?? [],
                'auxiliaries' => $validated['auxiliaries'] ?? [],
                'liquor_ratio' => $validated['liquor_ratio'] ?? null,
                'curve' => $validated['curve'] ?? [],
            ],
            'adjustments' => $validated['adjustments'] ?? null,
            'delta_e' => $validated['delta_e'] ?? null,
            'status' => $validated['status'],
            'operator_id' => $this->staff()->id,
            'shift' => $this->getShift(),
            'processed_at' => now(),
        ]);

        if ($dip->status === 'pending') {
            $dip->update(['status' => 'in_progress']);
        }

        return redirect()->back()->with('message', "Trial {$trialNo} recorded for {$dip->code}.");
    }

    /**
     * Advance dip status (forward-only; rejected is terminal from lab stages).
     */
    public function updateDipStatus(Request $request, LabDipRequest $dip)
    {
        $validated = $request->validate([
            'status' => 'required|in:in_progress,awaiting_spectro,approved,rejected',
        ]);

        $order = ['pending' => 0, 'in_progress' => 1, 'awaiting_spectro' => 2, 'approved' => 3, 'rejected' => 3];
        if ($order[$validated['status']] < $order[$dip->status]) {
            return back()->withErrors(['status' => 'Dip requests cannot move backwards.']);
        }

        $dip->update(['status' => $validated['status']]);

        return redirect()->back()->with('message', "Dip {$dip->code} marked as {$validated['status']}.");
    }

    /**
     * Quality & fastness testing page with CoA dataset.
     */
    public function tests()
    {
        $dips = LabDipRequest::with(['trials', 'tests', 'salesOrder'])
            ->whereIn('status', ['in_progress', 'awaiting_spectro', 'approved'])
            ->latest()
            ->take(50)
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingLabChemist/Tests', [
            'dips' => $dips,
        ]);
    }

    public function storeTest(Request $request)
    {
        $validated = $request->validate([
            'dip_request_id' => 'required|exists:lab_dip_requests,id',
            'test_type' => 'required|in:wash,rub_dry,rub_wet,light,perspiration,ph,absorbency,other',
            'method' => 'nullable|string|max:255',
            'rating' => 'nullable|string|max:16',
            'result' => 'nullable|string',
        ]);

        LabTest::create([
            ...$validated,
            'code' => $this->generateCode('LABTEST', LabTest::class),
            'operator_id' => $this->staff()->id,
            'shift' => $this->getShift(),
            'processed_at' => now(),
        ]);

        return redirect()->back()->with('message', 'Test result logged successfully.');
    }

    /**
     * Lab inventory: read-only dye lots from production inventory +
     * lab stock-solution prep log owned by the lab.
     */
    public function inventory()
    {
        $dyes = ManufacturingInventoryItem::with('material')
            ->where('department', 'dyeing')
            ->where('category', 'Dye')
            ->where('status', '!=', 'depleted')
            ->orderBy('created_at', 'asc')
            ->take(100)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'control_number' => $item->control_number,
                'material_name' => $item->material->name ?? 'Unknown',
                'remaining_quantity' => $item->remaining_quantity,
                'unit' => $item->unit,
            ]);

        $solutions = LabStockSolution::with('preparer')->latest()->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingLabChemist/Inventory', [
            'dyes' => $dyes,
            'solutions' => $solutions,
        ]);
    }

    public function storeSolution(Request $request)
    {
        $validated = $request->validate([
            'material_name' => 'required|string|max:255',
            'concentration' => 'nullable|string|max:64',
            'lot_no' => 'nullable|string|max:255',
            'prepared_at' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:prepared_at',
            'sds_url' => 'nullable|url|max:2048',
            'remarks' => 'nullable|string',
        ]);

        LabStockSolution::create([
            ...$validated,
            'prepared_by' => $this->staff()->id,
        ]);

        return redirect()->back()->with('message', 'Stock solution logged successfully.');
    }

    public function destroySolution(LabStockSolution $solution)
    {
        $solution->delete();

        return redirect()->back()->with('message', 'Stock solution record removed.');
    }

    /**
     * Bulk transfer: approved dips ready for lab-to-bulk sign-off.
     */
    public function transfer()
    {
        $dips = LabDipRequest::with(['trials', 'salesOrder.recipe'])
            ->where('status', 'approved')
            ->latest()
            ->get()
            ->map(function ($dip) {
                $passed = $dip->trials->where('status', 'passed')->values();
                return [
                    'id' => $dip->id,
                    'code' => $dip->code,
                    'pantone_code' => $dip->pantone_code,
                    'sales_order' => $dip->salesOrder ? [
                        'id' => $dip->salesOrder->id,
                        'jo_number' => $dip->salesOrder->jo_number,
                        'color' => $dip->salesOrder->color,
                        'yarn_type' => $dip->salesOrder->yarn_type,
                        'has_recipe' => ! is_null($dip->salesOrder->recipe_id),
                    ] : null,
                    'passed_trials' => $passed->map(fn ($t) => [
                        'id' => $t->id,
                        'trial_no' => $t->trial_no,
                        'delta_e' => $t->delta_e,
                        'formula' => $t->formula,
                    ])->values(),
                ];
            });

        return Inertia::render('Dashboard/MAN/Employee/DyeingLabChemist/Transfer', [
            'dips' => $dips,
        ]);
    }

    /**
     * Sign off a passed trial to the dye-house floor: builds the BomRecord
     * recipe the dyeing_color page already reads and links it to the JO.
     */
    public function signOff(Request $request)
    {
        $validated = $request->validate([
            'dip_request_id' => 'required|exists:lab_dip_requests,id',
            'trial_id' => 'required|exists:lab_trials,id',
            'correction_factor' => 'nullable|numeric|min:0.01|max:10',
        ]);

        $dip = LabDipRequest::with('salesOrder.recipe')->findOrFail($validated['dip_request_id']);
        if ($dip->status !== 'approved') {
            return back()->withErrors(['error' => 'Only approved dip requests can be transferred.']);
        }
        if (! $dip->salesOrder) {
            return back()->withErrors(['error' => 'This dip has no linked job order.']);
        }

        $trial = LabTrial::where('id', $validated['trial_id'])
            ->where('dip_request_id', $dip->id)
            ->where('status', 'passed')
            ->firstOrFail();

        $formula = $trial->formula ?? [];
        $materials = [];
        foreach ($formula['dyestuffs'] ?? [] as $dye) {
            if (! empty($dye['name'])) {
                $materials[$dye['name']] = (float) ($dye['pct'] ?? 0);
            }
        }
        foreach ($formula['auxiliaries'] ?? [] as $aux) {
            if (! empty($aux['name'])) {
                $materials[$aux['name']] = (float) ($aux['gpl'] ?? 0);
            }
        }

        $recipe = BomRecord::create([
            'client_id' => $dip->salesOrder->client_id,
            'product_id' => $dip->salesOrder->recipe?->product_id,
            'yarn_type' => $dip->salesOrder->yarn_type ?? $dip->salesOrder->recipe?->yarn_type,
            'dye_color' => $dip->pantone_code ?? $dip->salesOrder->color,
            'weave_design' => $dip->salesOrder->design ?? $dip->salesOrder->recipe?->weave_design,
            // name => quantity pairs, same shape the dyeing page already decodes
            'materials' => $materials,
        ]);

        $dip->salesOrder->update(['recipe_id' => $recipe->id]);

        return redirect()->back()->with(
            'message',
            "Recipe from trial {$trial->trial_no} signed off to JO {$dip->salesOrder->jo_number} (correction ×" . ($validated['correction_factor'] ?? 1) . ').'
        );
    }

    /**
     * Personal work log — own dip requests only.
     */
    public function history()
    {
        return Inertia::render('Dashboard/MAN/Employee/Common/History', [
            'roleLabel' => 'Dyeing Lab Chemist',
            'historyRoute' => 'man.staff.dyeing-lab-chemist.history',
            'dateColumn' => 'processed_at',
            'jobs' => $this->staffHistory(LabDipRequest::class, ['salesOrder', 'trials']),
        ]);
    }

    /**
     * First-time-right rate + average lab-dip turn time (all lab output).
     */
    protected function labMetrics(): array
    {
        $approved = LabDipRequest::with('trials')->where('status', 'approved')->get();

        $ftrBase = 0;
        $ftrHit = 0;
        $turnHours = [];
        foreach ($approved as $dip) {
            $passed = $dip->trials->where('status', 'passed')->sortBy('trial_no')->values();
            if ($passed->isNotEmpty()) {
                $ftrBase++;
                if ((int) $passed->first()->trial_no === 1) {
                    $ftrHit++;
                }
            }
            if ($dip->created_at && $dip->updated_at) {
                $turnHours[] = $dip->created_at->diffInHours($dip->updated_at);
            }
        }

        return [
            'ftr_rate' => $ftrBase > 0 ? round($ftrHit / $ftrBase * 100, 1) : 0,
            'avg_turn_hours' => count($turnHours) > 0 ? round(array_sum($turnHours) / count($turnHours), 1) : 0,
            'trials_total' => LabTrial::count(),
        ];
    }
}
