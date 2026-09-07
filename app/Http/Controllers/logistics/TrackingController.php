<?php

namespace App\Http\Controllers\logistics;

use App\Http\Controllers\Controller;
use App\Models\ceo\CeoLocation;
use App\Models\logistics\Delivery;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrackingController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');

        $query = Delivery::with([
            'truck',
            'driver.user',
            'conductor1.user',
            'conductor2.user',
            'route.client',
            'packages.product',
        ]);

        if ($filter === 'scheduled') {
            $query->where('status', 'pending');
        } elseif ($filter === 'active') {
            $query->whereIn('status', ['dispatched', 'in_transit']);
        } elseif ($filter === 'delivered') {
            $query->where('status', 'delivered');
        }

        $deliveries = $query->latest('scheduled_departure')->get();

        return Inertia::render('Dashboard/Logistics/Tracking', [
            'deliveries'    => $deliveries,
            'filter'        => $filter,
            'montiLocation' => CeoLocation::latest()->first(),
        ]);
    }
}