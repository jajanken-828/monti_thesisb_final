<?php

namespace App\Http\Controllers\ord;

use App\Http\Controllers\Controller;
use App\Models\logistics\Delivery;
use Inertia\Inertia;

class OrdDeliveryController extends Controller
{
    public function index()
    {
        $deliveries = Delivery::with([
                'driver.user',
                'truck',
                'route.client',
                'packages.product',
                'conductor1.user',
                'conductor2.user'
            ])
            ->whereIn('status', ['dispatched', 'in_transit', 'delivered'])
            ->latest('scheduled_departure')
            ->get()
            ->map(function ($delivery) {
                return [
                    'id'                  => $delivery->id,
                    'delivery_number'     => $delivery->delivery_number,
                    'status'              => $delivery->status,
                    'scheduled_departure' => $delivery->scheduled_departure,
                    'actual_departure'    => $delivery->actual_departure,
                    'arrival_time'        => $delivery->arrival_time,
                    'driver_name'         => $delivery->driver?->user?->name,
                    'truck_number'        => $delivery->truck?->truck_number,
                    'route_name'          => $delivery->route?->name,
                    'origin'              => $delivery->route?->origin,
                    'destination'         => $delivery->route?->destination,
                    'client_name'         => $delivery->route?->client?->company_name,
                    'package_count'       => $delivery->packages->count(),
                    'packages'            => $delivery->packages->map(fn($pkg) => [
                        'package_number' => $pkg->package_number,
                        'product_name'   => $pkg->product?->name,
                        'quantity'       => $pkg->quantity,
                    ]),
                ];
            });

        return Inertia::render('Dashboard/ORD/Delivery', [
            'deliveries' => $deliveries,
        ]);
    }
}