<?php

namespace App\Http\Controllers\ord;

use App\Http\Controllers\Controller;
use App\Models\ord\PurchaseOrder;
use App\Models\ord\SalesOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class OrdOrdersController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        // Purchase Orders
        $purchaseOrders = PurchaseOrder::with('client')
            ->whereNotNull('delivery_date')
            ->whereYear('delivery_date', $year)
            ->whereMonth('delivery_date', $month)
            ->get()
            ->map(function ($po) {
                $deliveryDate = $po->delivery_date instanceof Carbon
                    ? $po->delivery_date
                    : Carbon::parse($po->delivery_date);

                $createdAt = $po->created_at instanceof Carbon
                    ? $po->created_at
                    : Carbon::parse($po->created_at);

                return [
                    'id'              => $po->id,
                    'type'            => 'PO',
                    'number'          => $po->po_number,
                    'client_name'     => $po->client->company_name ?? 'N/A',
                    'total'           => $po->total_amount,
                    'status'          => $po->status,
                    'date'            => $deliveryDate->format('Y-m-d'),
                    'created_at'      => $createdAt->format('Y-m-d H:i'),
                    'payment_status'  => $po->payment_status ?? 'unpaid',
                    'receipt_file'    => $po->receipt_file,
                ];
            });

        // Sales Orders
        $salesOrders = SalesOrder::with(['bomRecord.client', 'bomRecord.product'])
            ->whereNotNull('created_at')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get()
            ->map(function ($so) {
                $createdAt = $so->created_at instanceof Carbon
                    ? $so->created_at
                    : Carbon::parse($so->created_at);

                return [
                    'id'              => $so->id,
                    'type'            => 'SO',
                    'number'          => $so->jo_number,
                    'client_name'     => $so->bomRecord->client->company_name ?? 'N/A',
                    'product_name'    => $so->bomRecord->product->name ?? 'N/A',
                    'total'           => $so->total_amount,
                    'status'          => $so->status,
                    'date'            => $createdAt->format('Y-m-d'),
                    'created_at'      => $createdAt->format('Y-m-d H:i'),
                    'yarn_type'       => $so->yarn_type,
                    'color'           => $so->color,
                    'quantity'        => $so->quantity,
                    'payment_status'  => $so->payment_status ?? 'unpaid',
                    'receipt_file'    => $so->receipt_file,
                ];
            });

        $allOrders = $purchaseOrders->concat($salesOrders);

        return Inertia::render('Dashboard/ORD/Orders', [
            'orders'       => $allOrders,
            'currentYear'  => (int) $year,
            'currentMonth' => (int) $month,
        ]);
    }

    /**
     * Update payment status and upload receipt.
     */
    public function updatePayment(Request $request)
    {
        // Only CEO can update payment status
        $this->authorize('ceo-only');

        $request->validate([
            'order_id'       => 'required|integer',
            'type'           => 'required|in:PO,SO',
            'payment_status' => 'required|in:unpaid,paid',
            'receipt'        => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', // max 2MB
        ]);

        $model = $request->type === 'PO' ? PurchaseOrder::class : SalesOrder::class;
        $order = $model::findOrFail($request->order_id);

        // Handle file upload
        if ($request->hasFile('receipt')) {
            // Delete old receipt if exists
            if ($order->receipt_file && Storage::disk('public')->exists($order->receipt_file)) {
                Storage::disk('public')->delete($order->receipt_file);
            }
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
            $order->receipt_file = $receiptPath;
        }

        $order->payment_status = $request->payment_status;
        $order->save();

        return redirect()->back()->with('success', 'Payment status updated successfully.');
    }

    /**
     * Download receipt file (optional).
     */
    public function downloadReceipt($type, $id)
    {
        $this->authorize('ceo-only');

        $model = $type === 'PO' ? PurchaseOrder::class : SalesOrder::class;
        $order = $model::findOrFail($id);

        if (!$order->receipt_file || !Storage::disk('public')->exists($order->receipt_file)) {
            abort(404, 'Receipt not found.');
        }

        return Storage::disk('public')->download($order->receipt_file);
    }
}