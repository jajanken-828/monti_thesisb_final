<?php

namespace App\Http\Controllers\Eco;

use App\Http\Controllers\Controller;
use App\Models\Ord\OrderQueue;
use App\Models\Ord\PurchaseOrder;
use App\Models\Crm\Client;
use App\Models\Ord\SalesOrder;
use App\Services\Eco\StockSustainabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EcoPushController extends Controller
{
    /**
     * Display the Push Center Dashboard.
     */
    public function index()
    {
        // ── 1. Pending pushes ────────────────────────────────────────────────
        $salesOrders = SalesOrder::where('sales_orders.status', 'pending')

            // Client resolution (direct or via PO)
            ->leftJoin('clients as direct_client', 'sales_orders.client_id', '=', 'direct_client.id')
            ->leftJoin('purchase_orders', 'sales_orders.purchase_order_id', '=', 'purchase_orders.po_number')
            ->leftJoin('clients as po_client', 'purchase_orders.client_id', '=', 'po_client.id')

            // Join bom_records to get recipe details
            ->leftJoin('bom_records', 'sales_orders.recipe_id', '=', 'bom_records.id')

            ->select(
                'sales_orders.*',
                'bom_records.id as recipe_id',
                'direct_client.company_name   as direct_company_name',
                'po_client.company_name       as po_company_name',
                'bom_records.yarn_type        as recipe_yarn_type',
                'bom_records.dye_color        as recipe_dye_color',
                'bom_records.weave_design     as recipe_weave_design',
            )
            ->latest('sales_orders.created_at')
            ->get()
            ->map(function ($order) {
                $data = $order->toArray();
                $companyName = $order->direct_company_name ?? $order->po_company_name ?? 'N/A';
                $data['client'] = ['company_name' => $companyName];

                $recipeId = $order->recipe_id ?? null;
                if ($recipeId) $recipeId = (int) $recipeId;

                $data['extracted_recipes'] = $recipeId ? [$recipeId] : [];
                $data['recipe'] = $recipeId ? [
                    'id'           => $recipeId,
                    'yarn_type'    => $order->recipe_yarn_type    ?? null,
                    'dye_color'    => $order->recipe_dye_color    ?? null,
                    'weave_design' => $order->recipe_weave_design ?? null,
                ] : null;

                return $data;
            });

        // ── 2. Already-pushed orders ────────────────────────────────────────
        $pushedOrders = SalesOrder::where('sales_orders.status', '!=', 'pending')
            ->leftJoin('clients as direct_client', 'sales_orders.client_id', '=', 'direct_client.id')
            ->leftJoin('purchase_orders', 'sales_orders.purchase_order_id', '=', 'purchase_orders.po_number')
            ->leftJoin('clients as po_client', 'purchase_orders.client_id', '=', 'po_client.id')
            ->leftJoin('bom_records', 'sales_orders.recipe_id', '=', 'bom_records.id')
            ->select(
                'sales_orders.*',
                'bom_records.id as recipe_id',
                'direct_client.company_name   as direct_company_name',
                'po_client.company_name       as po_company_name',
                'bom_records.yarn_type        as recipe_yarn_type',
                'bom_records.dye_color        as recipe_dye_color',
                'bom_records.weave_design     as recipe_weave_design',
            )
            ->latest('sales_orders.updated_at')
            ->get()
            ->map(function ($order) {
                $data = $order->toArray();
                $companyName = $order->direct_company_name ?? $order->po_company_name ?? 'N/A';
                $data['client'] = ['company_name' => $companyName];

                $recipeId = $order->recipe_id ?? null;
                if ($recipeId) $recipeId = (int) $recipeId;

                $data['extracted_recipes'] = $recipeId ? [$recipeId] : [];
                $data['recipe'] = $recipeId ? [
                    'id'           => $recipeId,
                    'yarn_type'    => $order->recipe_yarn_type    ?? null,
                    'dye_color'    => $order->recipe_dye_color    ?? null,
                    'weave_design' => $order->recipe_weave_design ?? null,
                ] : null;

                return $data;
            });

        return Inertia::render('Dashboard/ECO/Push', [
            'salesOrders'  => $salesOrders,
            'pushedOrders' => $pushedOrders,
        ]);
    }

    /**
     * DSS preview — quick sustainability check for one pending order.
     * Returns JSON: per-material required / committed / on-hand / ATP /
     * shortage plus the overall verdict. Used by the Push Center modal
     * BEFORE ECO accepts the order.
     */
    public function dssCheck($order, StockSustainabilityService $dss)
    {
        $salesOrder = SalesOrder::findOrFail($order);

        return response()->json($dss->evaluate($salesOrder));
    }

    /**
     * Enforce the DSS gate. Returns an error string when the order must
     * be blocked (and files procurement suggestions), or null to proceed.
     */
    protected function enforceSustainability(SalesOrder $salesOrder, StockSustainabilityService $dss): ?string
    {
        $result = $dss->evaluate($salesOrder);

        if ($result['verdict'] === 'insufficient') {
            $suggested = $dss->suggestProcurement($salesOrder, $result, auth()->user()?->name);

            $lines = collect($result['materials'])
                ->filter(fn ($m) => ($m['shortage'] ?? 0) > 0)
                ->map(fn ($m) => "{$m['material_name']}: needs {$m['required']}{$m['unit']}, ATP {$m['atp']}{$m['unit']} (short {$m['shortage']}{$m['unit']})")
                ->implode('; ');

            $reqs = collect($suggested)->map(fn ($s) => $s['req_number'])->implode(', ');

            return "Order {$result['jo_number']} BLOCKED: stock cannot sustain this job after {$result['committed_orders']} accepted order(s). {$lines}. Procurement suggested: {$reqs} (see SCM → Procurement Orders).";
        }

        return null;
    }

    /**
     * Push to Supply Chain Management (SCM).
     *
     * The route passes {order} which is the sales_order primary-key (id).
     * We update its status so SCM can see it via ScmSalesOrderController::index().
     *
     * DSS gate: blocked when live stock minus already-committed orders
     * cannot cover this job; shortfalls are auto-suggested to procurement.
     */
    public function pushToScm($order, StockSustainabilityService $dss)
    {
        try {
            $salesOrder = SalesOrder::findOrFail($order);

            // Guard: only push if still pending
            if ($salesOrder->status !== 'pending') {
                return redirect()->back()->withErrors([
                    'error' => 'This order has already been pushed and cannot be pushed again.',
                ]);
            }

            if ($blocker = $this->enforceSustainability($salesOrder, $dss)) {
                return redirect()->back()->withErrors(['error' => $blocker]);
            }

            $salesOrder->update([
                'status'    => 'pushed_to_scm',
                'pushed_to' => 'SCM',
            ]);

            return redirect()->back()->with('success', 'Order successfully pushed to SCM.');
        } catch (\Exception $e) {
            Log::error('Push SCM Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to push to SCM. Please try again.']);
        }
    }

    /**
     * Push to Order Management.
     *
     * Same DSS gate as SCM pushes — sustainability is checked before ANY
     * acceptance path.
     */
    public function pushToOrderMgmt($order, StockSustainabilityService $dss)
    {
        try {
            $salesOrder = SalesOrder::findOrFail($order);

            if ($salesOrder->status !== 'pending') {
                return redirect()->back()->withErrors([
                    'error' => 'This order has already been pushed and cannot be pushed again.',
                ]);
            }

            if ($blocker = $this->enforceSustainability($salesOrder, $dss)) {
                return redirect()->back()->withErrors(['error' => $blocker]);
            }

            $salesOrder->update([
                'status'    => 'pushed_to_ordermgmt',
                'pushed_to' => 'Order Mgmt',
            ]);

            return redirect()->back()->with('success', 'Order forwarded to Order Management.');
        } catch (\Exception $e) {
            Log::error('Push Order Mgmt Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to push to Order Management.']);
        }
    }

    /**
     * Manual Store (for staff overrides — uploads a PO attachment).
     */
    public function manualStore(Request $request)
    {
        $request->validate([
            'client_id'  => 'required|exists:clients,id',
            'attachment' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        $path = $request->file('attachment')->store('manual_pos', 'public');

        PurchaseOrder::create([
            'client_id'       => $request->client_id,
            'po_number'       => 'MPO-' . strtoupper(Str::random(8)),
            'status'          => 'approved',
            'subtotal'        => 0,
            'total_amount'    => 0,
            'attachment_path' => $path,
            'notes'           => 'Manually uploaded via ECO Push Center',
        ]);

        return redirect()->back()->with('success', 'Manual PO successfully uploaded and queued.');
    }

    /**
     * Manual Job Order store (staff override).
     * Creates a SalesOrder directly without going through the normal inquiry flow.
     */
    public function manualJobOrder(Request $request)
    {
        $request->validate([
            'client_id'    => 'required|exists:clients,id',
            'color'        => 'nullable|string|max:255',
            'yarn_type'    => 'nullable|string|max:255',
            'quantity'     => 'required|numeric|min:1',
            'total_amount' => 'required|numeric|min:0',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $joNumber = 'JO-' . strtoupper(Str::random(8));

        SalesOrder::create([
            'client_id'    => $request->client_id,
            'jo_number'    => $joNumber,
            'color'        => $request->color,
            'yarn_type'    => $request->yarn_type,
            'quantity'     => $request->quantity,
            'total_amount' => $request->total_amount,
            'status'       => 'pending',
            'notes'        => $request->notes ?? 'Manually created via ECO Push Center',
        ]);

        return redirect()->back()->with('success', "Manual Job Order {$joNumber} created successfully.");
    }
}