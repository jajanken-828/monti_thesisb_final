<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Eco\Inquiry;
use App\Models\Ord\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClientDashboardController extends Controller
{
    public function index()
    {
        $client = Auth::guard('client')->user();
        $totalOrders = PurchaseOrder::where('client_id', $client->id)->count();
        $pendingInquiries = Inquiry::where('client_id', $client->id)->where('status', 'open')->count();
        $pendingQuotations = \App\Models\Client\ClientQuotation::where('client_id', $client->id)
            ->where('status', 'sent')->count();

        return Inertia::render('Client/Dashboard', [
            'stats' => [
                'totalOrders' => $totalOrders,
                'pendingInquiries' => $pendingInquiries,
                'pendingQuotations' => $pendingQuotations,
            ],
        ]);
    }
}
