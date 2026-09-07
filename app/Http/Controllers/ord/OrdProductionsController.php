<?php

namespace App\Http\Controllers\ord;

use App\Http\Controllers\Controller;
use App\Models\man\ManufacturingOrder;
use Inertia\Inertia;

class OrdProductionsController extends Controller
{
    public function index()
    {
        $manufacturingOrders = ManufacturingOrder::with([
                'salesOrder.bomRecord.client',
                'purchaseOrder.client'
            ])
            ->where('status', '!=', 'completed')
            ->get()
            ->map(function ($mo) {
                $totalQty = $mo->total_quantity;
                $completedQty = $totalQty - $mo->remaining_quantity;
                $progress = $totalQty > 0 ? round(($completedQty / $totalQty) * 100) : 0;

                // Determine source order info
                $orderNumber = $mo->salesOrder
                    ? $mo->salesOrder->jo_number
                    : ($mo->purchaseOrder ? $mo->purchaseOrder->po_number : 'N/A');

                $clientName = $mo->salesOrder
                    ? ($mo->salesOrder->bomRecord->client->company_name ?? 'N/A')
                    : ($mo->purchaseOrder->client->company_name ?? 'N/A');

                $productName = $mo->salesOrder
                    ? ($mo->salesOrder->bomRecord->product->name ?? 'N/A')
                    : 'N/A';

                return [
                    'id'                => $mo->id,
                    'order_number'      => $orderNumber,
                    'client_name'       => $clientName,
                    'product_name'      => $productName,
                    'total_quantity'    => $totalQty,
                    'completed_quantity' => $completedQty,
                    'progress'          => $progress,
                    'status'            => $mo->status,
                    'notes'             => $mo->notes,
                    'created_at'        => $mo->created_at->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('Dashboard/ORD/Productions', [
            'productions' => $manufacturingOrders,
        ]);
    }
}