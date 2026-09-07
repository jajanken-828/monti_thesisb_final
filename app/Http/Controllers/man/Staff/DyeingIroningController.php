<?php

namespace App\Http\Controllers\man\Staff;

use App\Models\man\IronJob;
use App\Models\man\MachineReport;
use App\Models\man\SqueezerJob;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DyeingIroningController extends ManufacturingStaffController
{
    public function index()
    {
        $pendingCount = SqueezerJob::whereDoesntHave('ironJob')
            ->count();

        $recentJobs = IronJob::with('squeezerJob.softenerJob.fabric')
            ->where('operator_id', $this->staff()->id)
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingIroning/Index', [
            'stats' => [
                'pending' => $pendingCount,
                'total_today' => IronJob::whereDate('processed_at', today())->count(),
            ],
            'recentJobs' => $recentJobs,
        ]);
    }

    public function dyeingIroning()
    {
        // Option B: auto-flow from squeezer (fabric status is set to 'iron'
        // in DyeingSqueezerController@storeSqueezer). Same pattern as
        // iron -> forming which only checks for missing next-stage job.
        $squeezerJobs = SqueezerJob::with('softenerJob.fabric')
            ->whereDoesntHave('ironJob')
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingIroning/DyeingIroning', [
            'squeezerJobs' => $squeezerJobs,
        ]);
    }

    public function storeIron(Request $request)
    {
        $validated = $request->validate([
            'squeezer_job_id' => 'required|exists:squeezer_jobs,id',
            'remarks' => 'nullable|string',
        ]);

        $squeezerJob = SqueezerJob::with('softenerJob.fabric')->findOrFail($validated['squeezer_job_id']);

        IronJob::create([
            'squeezer_job_id' => $validated['squeezer_job_id'],
            'remarks' => $validated['remarks'],
            'operator_id' => $this->staff()->id,
            'shift' => $this->getShift(),
            'code' => $this->generateCode('IRON', IronJob::class),
            'processed_at' => now(),
        ]);

        // Forming step removed: ironing now flows directly to packaging.
        $fabric = $squeezerJob->softenerJob?->fabric;
        if ($fabric) {
            $fabric->update(['status' => 'packed']);
        }

        return redirect()->back()->with('message', 'Ironing recorded successfully.');
    }

    public function reports()
    {
        $myReports = MachineReport::where('reported_by', $this->staff()->id)
            ->with('machine')
            ->latest()
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingIroning/Reports', [
            'myReports' => $myReports,
        ]);
    }

    public function reportMachine(Request $request)
    {
        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'issue' => 'required|string',
        ]);

        MachineReport::create([
            'machine_id' => $validated['machine_id'],
            'reported_by' => $this->staff()->id,
            'issue' => $validated['issue'],
            'status' => 'pending',
        ]);

        return redirect()->back()->with('message', 'Machine issue reported.');
    }
}