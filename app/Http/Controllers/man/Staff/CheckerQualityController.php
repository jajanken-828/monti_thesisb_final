<?php

namespace App\Http\Controllers\man\Staff;

use App\Models\man\DyeJob;
use App\Models\man\Fabric;
use App\Models\man\IronJob;
use App\Models\man\ManufacturingOrder;
use App\Models\man\Package;
use App\Models\man\SoftenerJob;
use App\Models\man\SqueezerJob;
use App\Models\war\WarehousePackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CheckerQualityController extends ManufacturingStaffController
{
    public function index()
    {
        $stats = [
            'pending_orders' => ManufacturingOrder::where('status', 'pending')->count(),
            'fabrics_pending' => Fabric::where('status', 'pending')->count(),
            'dye_pending' => DyeJob::whereHas('fabric', fn($q) => $q->where('status', 'dyeing'))->count(),
            'softener_pending' => SoftenerJob::whereHas('fabric', fn($q) => $q->where('status', 'softener'))
                ->count(),
            'squeezer_pending' => SqueezerJob::whereHas('softenerJob.fabric', fn($q) => $q->where('status', 'squeezer'))->count(),
            'iron_pending' => IronJob::whereHas('squeezerJob.softenerJob.fabric', fn($q) => $q->where('status', 'iron'))->count(),
            'packages_pending' => Package::where('status', 'pending')->count(),
        ];

        return Inertia::render('Dashboard/MAN/Employee/CheckerQuality/Index', [
            'stats' => $stats,
        ]);
    }

    public function production()
    {
        $orders = ManufacturingOrder::with(['purchaseOrder.client', 'salesOrder.client'])
            ->where('status', 'pending')
            ->get()
            ->map(function ($order) {
                if ($order->purchaseOrder) {
                    return [
                        'id' => $order->id,
                        'po_number' => $order->purchaseOrder->po_number,
                        'client' => $order->purchaseOrder->client->company_name ?? 'N/A',
                        'total_quantity' => $order->total_quantity,
                        'remaining_quantity' => $order->remaining_quantity,
                        'type' => 'purchase_order',
                    ];
                } elseif ($order->salesOrder) {
                    return [
                        'id' => $order->id,
                        'po_number' => $order->salesOrder->jo_number ?? 'JO-' . $order->salesOrder->id,
                        'client' => $order->salesOrder->client->company_name ?? 'N/A',
                        'total_quantity' => $order->total_quantity,
                        'remaining_quantity' => $order->remaining_quantity,
                        'type' => 'sales_order',
                    ];
                }
                return null;
            })
            ->filter();

        $fabrics = Fabric::with('machine', 'operator', 'salesOrder')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        $dyeJobs = DyeJob::with('fabric.salesOrder', 'machine', 'operator')
            ->whereHas('fabric', fn($q) => $q->where('status', 'dyeing'))
            ->get();

        $softenerJobs = SoftenerJob::with('fabric.salesOrder', 'machine', 'operator', 'squeezerJob')
            ->whereHas('fabric', fn($q) => $q->where('status', 'softener'))
            ->get();

        $squeezerJobs = SqueezerJob::with('softenerJob.fabric.salesOrder', 'machine', 'operator', 'ironJob')
            ->whereHas('softenerJob.fabric', fn($q) => $q->where('status', 'squeezer'))
            ->get();

        $ironJobs = IronJob::with('squeezerJob.softenerJob.fabric.salesOrder', 'operator')
            ->whereHas('squeezerJob.softenerJob.fabric', fn($q) => $q->where('status', 'iron'))
            ->get();

        $packages = Package::with(['fabric.salesOrder.recipe.product', 'operator'])
            ->where('status', 'pending')
            ->get()
            ->map(function ($pkg) {
                $product = $pkg->fabric->salesOrder->recipe->product ?? null;
                return [
                    'id' => $pkg->id,
                    'code' => $pkg->code,
                    'quantity' => $pkg->quantity,
                    'status' => $pkg->status,
                    'product_name' => $product->name ?? 'Unknown Product',
                    'product_sku' => $product->sku ?? '',
                    'fabric_code' => $pkg->fabric->code ?? '',
                    'yarn_type' => $pkg->fabric->yarn_type ?? '',
                    'weight' => $pkg->fabric->weight ?? 0,
                    'operator' => $pkg->operator->name ?? '',
                ];
            });

        return Inertia::render('Dashboard/MAN/Employee/CheckerQuality/Production', [
            'orders' => $orders,
            'fabrics' => $fabrics,
            'dyeJobs' => $dyeJobs,
            'softenerJobs' => $softenerJobs,
            'squeezerJobs' => $squeezerJobs,
            'ironJobs' => $ironJobs,
            'packages' => $packages,
        ]);
    }

    // ========== Order Actions ==========

    public function checkInventory($orderId)
    {
        $order = ManufacturingOrder::findOrFail($orderId);
        return redirect()->back()->with('message', 'Inventory checked. Available: 0 items.');
    }

    public function startProduction($orderId)
    {
        $order = ManufacturingOrder::findOrFail($orderId);
        $order->update(['status' => 'in_progress']);

        if ($order->purchaseOrder && $order->purchaseOrder->queue) {
            $order->purchaseOrder->queue->update(['man_started_at' => now()]);
        }
        if ($order->salesOrder) {
            $order->salesOrder->update(['status' => 'in_production']);
        }

        return redirect()->back()->with('message', 'Production started.');
    }

    // ========== Fabric Actions ==========

    public function passFabric(Request $request, $fabricId)
    {
        $validated = $request->validate([
            'destination' => 'required|in:dyeing,softener',
        ]);

        $fabric = Fabric::findOrFail($fabricId);
        $fabric->update(['status' => $validated['destination']]);

        return redirect()->back()->with('message', 'Fabric passed to ' . $validated['destination']);
    }

    // ========== Dye Actions ==========

    public function passDye(Request $request, $dyeId)
    {
        $validated = $request->validate([
            'action' => 'required|in:quality,reject',
            'rejection_reason' => 'required_if:action,reject|string|nullable|max:500',
        ]);

        $dye = DyeJob::findOrFail($dyeId);
        $fabric = $dye->fabric;

        if ($validated['action'] === 'quality') {
            $fabric->update(['status' => 'softener']);
            return redirect()->back()->with('message', 'Fabric passed to softener stage.');
        } else {
            $fabric->update([
                'status' => 'rejected',
                'rejection_action' => 'recolor',
                'rejection_reason' => $validated['rejection_reason'] ?? null,
            ]);
            return redirect()->back()->with('message', 'Fabric rejected.');
        }
    }

    // ========== Softener Actions ==========

    public function passSoftener(Request $request, $softenerId)
    {
        $validated = $request->validate([
            'action' => 'required|in:quality,resoften',
        ]);

        $softener = SoftenerJob::findOrFail($softenerId);
        $fabric = $softener->fabric;

        if ($validated['action'] === 'quality') {
            $fabric->update(['status' => 'squeezer']);
            return redirect()->back()->with('message', 'Fabric passed to squeezer.');
        } else {
            $fabric->update(['status' => 'softener']);
            $softener->update(['status' => 'resoften']);
            return redirect()->back()->with('message', 'Fabric sent back for re-softening.');
        }
    }

    // ========== Squeezer Actions ==========

    public function passSqueezer(Request $request, $squeezerId)
    {
        $squeezer = SqueezerJob::findOrFail($squeezerId);
        $fabric = $squeezer->softenerJob->fabric;
        $fabric->update(['status' => 'iron']);

        return redirect()->back()->with('message', 'Fabric passed to ironing stage.');
    }

    // ========== Iron Actions ==========

    public function passIron(Request $request, $ironId)
    {
        $validated = $request->validate([
            'action' => 'required|in:pack',
        ]);

        $iron = IronJob::with('squeezerJob.softenerJob.fabric')->findOrFail($ironId);

        $squeezerJob = $iron->squeezerJob;
        if (!$squeezerJob) {
            return back()->with('error', 'This iron job has no linked squeezer job — cannot pack.');
        }

        $softenerJob = $squeezerJob->softenerJob;
        if (!$softenerJob) {
            return back()->with('error', 'This iron job has no linked softener job — cannot pack.');
        }

        $fabric = $softenerJob->fabric;
        if (!$fabric) {
            return back()->with('error', 'This iron job has no linked fabric — cannot pack.');
        }

        if ($validated['action'] === 'pack') {
            $fabric->update(['status' => 'packed']);
        }

        return redirect()->back()->with('message', 'Fabric packed successfully.');
    }

    // ========== Package Actions ==========

    public function assignPackageToOrder(Request $request, $packageId)
    {
        $validated = $request->validate([
            'manufacturing_order_id' => 'required|exists:manufacturing_orders,id',
        ]);

        $package = Package::findOrFail($packageId);
        $order = ManufacturingOrder::findOrFail($validated['manufacturing_order_id']);

        $totalInPackage = $package->items->sum('quantity');
        if ($totalInPackage <= $order->remaining_quantity) {
            $package->update([
                'manufacturing_order_id' => $order->id,
                'status' => 'assigned',
            ]);
            $order->decrement('remaining_quantity', $totalInPackage);
            if ($order->remaining_quantity <= 0) {
                $order->update(['status' => 'completed']);
            }

            return redirect()->back()->with('message', 'Package assigned to order.');
        } else {
            return redirect()->back()->with('error', 'Package quantity exceeds remaining order quantity.');
        }
    }

    /**
     * Push a manufacturing package to logistics (creates a WarehousePackage).
     */
    public function pushToLogistics($packageId)
    {
        $package = Package::with('fabric.salesOrder.recipe.product')->findOrFail($packageId);

        if ($package->status === 'delivered') {
            return back()->with('error', 'Package already sent to logistics.');
        }

        $product = $package->fabric->salesOrder->recipe->product ?? null;
        if (!$product) {
            return back()->with('error', 'No product associated with this package.');
        }

        DB::transaction(function () use ($package, $product) {
            WarehousePackage::create([
                'package_number'         => $package->code,
                'manufacturing_order_id' => $package->manufacturing_order_id,
                'product_id'             => $product->id,
                'quantity'               => $package->quantity,
                'status'                 => 'pushed_to_logistics',
                'pushed_at'              => now(),
                'pushed_by'              => auth()->id(),
            ]);

            $package->update(['status' => 'delivered']);
        });

        return back()->with('message', 'Package sent to logistics.');
    }
}