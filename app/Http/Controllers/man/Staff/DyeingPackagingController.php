<?php

namespace App\Http\Controllers\man\Staff;

use App\Models\man\Fabric;
use App\Models\man\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DyeingPackagingController extends ManufacturingStaffController
{
    public function index()
    {
        $pendingCount = Fabric::where('status', 'packed')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('packages')
                    ->whereColumn('packages.fabric_id', 'fabrics.id');
            })
            ->count();

        $recentPackages = Package::with('fabric')
            ->where('operator_id', $this->staff()->id)
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingPackaging/Index', [
            'stats' => [
                'pending' => $pendingCount,
                'total_today' => Package::whereDate('packaged_at', today())->count(),
            ],
            'recentPackages' => $recentPackages,
        ]);
    }

    public function packaging()
    {
        $fabrics = Fabric::with('salesOrder', 'machine', 'operator')
            ->where('status', 'packed')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('packages')
                    ->whereColumn('packages.fabric_id', 'fabrics.id');
            })  // Exclude fabrics that already have a package
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingPackaging/DyeingPackaging', [
            'fabrics' => $fabrics,
        ]);
    }

    public function storePackage(Request $request)
    {
        $validated = $request->validate([
            'fabric_id' => 'required|exists:fabrics,id',
            'quantity'  => 'required|integer|min:1',
            'remarks'   => 'nullable|string',
        ]);

        $fabric = Fabric::findOrFail($validated['fabric_id']);

        // Ensure the fabric is still ready for packaging and not already packaged
        if ($fabric->status !== 'packed' || $fabric->packages()->exists()) {
            return back()->with('error', 'This fabric is no longer available for packaging.');
        }

        $package = Package::create([
            'code'          => $this->generateCode('PACKAGE', Package::class),
            'operator_id'   => $this->staff()->id,
            'shift'         => $this->getShift(),
            'packaged_at'   => now(),
            'status'        => 'pending',
            'fabric_id'     => $fabric->id,
            'quantity'      => $validated['quantity'],   // store the quantity
        ]);

        // Fabric status remains 'packed' – the package existence prevents it from reappearing.

        return redirect()->back()->with('message', 'Package created successfully.');
    }
}