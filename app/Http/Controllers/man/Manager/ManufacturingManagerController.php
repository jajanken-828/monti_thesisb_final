<?php

namespace App\Http\Controllers\man\Manager;

use App\Http\Controllers\Controller;
use App\Models\man\Fabric;
use App\Models\man\Machine;
use App\Models\man\ManufacturingOrder;
use App\Models\man\Package;
use App\Models\ord\PurchaseOrder;
use App\Models\ord\SalesOrder;
use App\Models\core\User;
use App\Models\war\Warehouse;
use App\Models\war\WarehouseReject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ManufacturingManagerController extends Controller
{
    /**
     * Get the current authenticated user (supervisor or legacy manager).
     */
    protected function getCurrentManager()
    {
        return Auth::user();
    }

    /**
     * Filter staff list based on supervisor's department.
     * Excludes the logged-in supervisor from the list.
     */
    protected function getFilteredStaff()
    {
        $user = $this->getCurrentManager();

        // Base query: MAN staff with position 'staff'
        $query = User::where('role', 'MAN')
            ->where('position', 'staff')
            ->select('id', 'name', 'email', 'manufacturing_role', 'is_manufacturing_supervisor');

        // If user is a manufacturing supervisor, filter by department roles and exclude self
        if ($user->is_manufacturing_supervisor && $user->supervisor_department) {
            $supervisedRoles = $user->supervised_roles;
            $query->whereIn('manufacturing_role', $supervisedRoles)
                  ->where('id', '!=', $user->id);
        }

        return $query->get()->map(fn($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'manufacturing_role' => $u->manufacturing_role,
            'is_manufacturing_supervisor' => $u->is_manufacturing_supervisor,
        ]);
    }

    public function index()
    {
        $receivedOrders = PurchaseOrder::whereHas('queue', function ($q) {
            $q->where('stage', 'man_production');
        })->count();

        $inProduction = ManufacturingOrder::where('status', 'in_progress')->count();
        $activeMachines = Machine::where('status', 'available')->count();

        $staff = $this->getFilteredStaff();

        return Inertia::render('Dashboard/MAN/Manager/index', [
            'stats' => [
                'receivedOrders' => $receivedOrders,
                'inProduction' => $inProduction,
                'activeMachines' => $activeMachines,
            ],
            'staff' => $staff,
        ]);
    }

    public function production()
    {
        // 1. Fetch Purchase Orders (from SCM) with stage 'man_production'
        //    Exclude those that already have a pending/in_progress manufacturing order
        $purchaseOrders = PurchaseOrder::with(['client', 'items.product'])
            ->whereHas('queue', function ($q) {
                $q->where('stage', 'man_production');
            })
            ->whereDoesntHave('manufacturingOrders', function ($q) {
                $q->whereIn('status', ['pending', 'in_progress']);
            })
            ->get()
            ->map(function ($order) {
                $items = $order->items->map(fn($item) => [
                    'product_name' => $item->product->name ?? 'Unknown',
                    'product_sku'  => $item->product->sku ?? '',
                    'quantity'     => $item->quantity,
                ]);
                return [
                    'type'          => 'purchase_order',
                    'id'            => $order->id,
                    'order_number'  => $order->po_number,
                    'client_name'   => $order->client->company_name ?? 'N/A',
                    'total_quantity' => $order->items->sum('quantity'),
                    'items'         => $items,
                    'created_at'    => $order->created_at,
                ];
            });

        // 2. Fetch Sales Orders (Job Orders) with status 'in_production'
        //    Exclude those that already have a manufacturing order (via sales_order_id)
        $salesOrders = SalesOrder::with(['client', 'recipe'])
            ->where('status', 'in_production')
            ->whereDoesntHave('manufacturingOrder', function ($q) {
                $q->whereIn('status', ['pending', 'in_progress']);
            })
            ->get()
            ->map(function ($order) {
                // Build item representation
                $items = [];
                $productName = trim(($order->color ?? '') . ' ' . ($order->yarn_type ?? ''));
                if (!empty($productName)) {
                    $items[] = [
                        'product_name' => $productName,
                        'quantity'     => $order->quantity ?? 0,
                    ];
                } else {
                    $items[] = [
                        'product_name' => 'Custom Product',
                        'quantity'     => $order->quantity ?? 0,
                    ];
                }

                return [
                    'type'          => 'sales_order',
                    'id'            => $order->id,
                    'order_number'  => $order->jo_number ?? 'JO-' . $order->id,
                    'client_name'   => $order->client->company_name ?? 'N/A',
                    'total_quantity' => $order->quantity ?? 0,
                    'items'         => $items,
                    'created_at'    => $order->created_at,
                ];
            });

        // Merge both collections and sort by created_at descending
        $orders = $purchaseOrders->concat($salesOrders)->sortByDesc('created_at')->values();

        return Inertia::render('Dashboard/MAN/Manager/Production', [
            'orders' => $orders,
        ]);
    }

    /**
     * Show rejected items: both fabrics and form jobs.
     */
   public function rejected()
{
    $user = $this->getCurrentManager();

    // Rejected Fabrics
    $rejectedFabricsQuery = Fabric::with('salesOrder')
        ->where('status', 'rejected');

    if ($user->is_manufacturing_supervisor && $user->supervisor_department) {
        $supervisedRoles = $user->supervised_roles;
        $rejectedFabricsQuery->whereHas('operator', function ($q) use ($supervisedRoles) {
            $q->whereIn('manufacturing_role', $supervisedRoles);
        });
    }

    $rejectedFabrics = $rejectedFabricsQuery->latest()->get()->map(function ($fabric) {
        return [
            'id'             => $fabric->id,
            'code'           => $fabric->code,
            'product_name'   => $fabric->yarn_type ?? 'Fabric',
            'quantity'       => $fabric->weight ?? 1,
            'rejected_by'    => $fabric->operator->name ?? 'System',
            'reason'         => $fabric->rejection_reason ?? 'No reason',
            'rejected_at'    => $fabric->updated_at,
            'type'           => 'fabric',
        ];
    });

    // Forming stage removed: rejected items are fabrics only.
    $rejectedItems = $rejectedFabrics
        ->sortByDesc('rejected_at')
        ->values();

    // Get warehouses for total reject modal
    $warehouses = Warehouse::select('id', 'name', 'location')->get();

    return Inertia::render('Dashboard/MAN/Manager/Rejected', [
        'rejectedItems' => $rejectedItems,
        'warehouses'    => $warehouses,
    ]);
}

    /**
     * Recolor a rejected fabric (send back to dyeing stage).
     */
    public function recolorFabric($fabricId)
    {
        $fabric = Fabric::findOrFail($fabricId);
        
        if ($fabric->status !== 'rejected') {
            return back()->with('error', 'Fabric is not in rejected state.');
        }

        $fabric->update([
            'status' => 'dyeing',
            'rejection_action' => 'recolor',
        ]);

        return redirect()->back()->with('message', 'Fabric sent back for recoloring.');
    }

    /**
     * Totally reject a fabric and send to warehouse as rejected material.
     */
    public function rejectFabricTotally(Request $request, $fabricId)
    {
        $fabric = Fabric::findOrFail($fabricId);
        
        if ($fabric->status !== 'rejected') {
            return back()->with('error', 'Fabric is not in rejected state.');
        }

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        DB::transaction(function () use ($fabric, $validated) {
            // Update fabric
            $fabric->update([
                'rejection_action' => 'total',
                'status' => 'warehouse_rejected', // or keep 'rejected' but mark action
            ]);

            // Create warehouse reject record
            WarehouseReject::create([
                'rejectable_type' => Fabric::class,
                'rejectable_id' => $fabric->id,
                'source' => 'manufacturing',
                'warehouse_id' => $validated['warehouse_id'],
                'quantity' => $fabric->weight,
                'unit' => 'kg',
                'reason' => $fabric->rejection_reason ?? 'Rejected from manufacturing',
                'status' => 'pending_return',
            ]);
        });

        return redirect()->back()->with('message', 'Fabric sent to warehouse as rejected material.');
    }

    /**
     * Forward an order (Purchase Order or Sales Order) to Checker Quality.
     * Creates a ManufacturingOrder record and updates the order's stage/status.
     */
    public function forwardToChecker(Request $request, $orderId)
    {
        $type = $request->input('type', 'purchase_order');
        $user = Auth::user();

        DB::beginTransaction();
        try {
            if ($type === 'purchase_order') {
                $order = PurchaseOrder::findOrFail($orderId);

                // Check if already forwarded
                $existing = ManufacturingOrder::where('purchase_order_id', $order->id)
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->first();
                if ($existing) {
                    return redirect()->back()->with('error', 'This Purchase Order has already been forwarded to production.');
                }

                $totalQuantity = $order->items->sum('quantity');

                ManufacturingOrder::create([
                    'purchase_order_id' => $order->id,
                    'total_quantity'    => $totalQuantity,
                    'remaining_quantity'=> $totalQuantity,
                    'status'            => 'pending',
                    'notes'             => 'Forwarded to manufacturing by ' . $user->name,
                ]);

                // Optionally update queue stage? Already man_production, keep as is.

            } elseif ($type === 'sales_order') {
                $order = SalesOrder::findOrFail($orderId);
                $totalQuantity = $order->quantity ?? 0;

                // Check if already forwarded
                $existing = ManufacturingOrder::where('sales_order_id', $order->id)
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->first();
                if ($existing) {
                    return redirect()->back()->with('error', 'This Job Order has already been forwarded to production.');
                }

                ManufacturingOrder::create([
                    'sales_order_id'     => $order->id,
                    'total_quantity'     => $totalQuantity,
                    'remaining_quantity' => $totalQuantity,
                    'status'             => 'pending',
                    'notes'              => 'Forwarded to manufacturing by ' . $user->name,
                ]);

                // Status remains 'in_production' (already set by SCM push)
            } else {
                abort(400, 'Invalid order type.');
            }

            DB::commit();
            return redirect()->back()->with('message', 'Order forwarded to production successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to forward order: ' . $e->getMessage());
        }
    }

    public function updateStaffRole(Request $request, $staffId)
    {
        $user = $this->getCurrentManager();
        $staff = User::findOrFail($staffId);

        if ($user->is_manufacturing_supervisor) {
            if (!$user->canSuperviseRole($staff->manufacturing_role)) {
                return back()->with('error', 'You are not allowed to update this staff member.');
            }
        }

        $validated = $request->validate([
            'manufacturing_role' => 'required|in:knitting_yarn,dyeing_color,dyeing_fabric_softener,dyeing_squeezer,dyeing_ironing,dyeing_packaging,maintenance_checker,checker_quality',
        ]);

        $staff->update(['manufacturing_role' => $validated['manufacturing_role']]);
        return redirect()->back()->with('message', "Staff role updated.");
    }

    public function sendToLogistics($packageId)
    {
        $package = Package::findOrFail($packageId);
        $package->update(['status' => 'delivered']);

        if ($package->manufacturing_order_id) {
            $order = $package->manufacturingOrder;
            $remaining = $order->remaining_quantity - $package->items->sum('quantity');
            $order->update(['remaining_quantity' => $remaining]);
            if ($remaining <= 0) {
                $order->update(['status' => 'completed']);
            }
        }

        return redirect()->back()->with('message', 'Package sent to logistics.');
    }
}