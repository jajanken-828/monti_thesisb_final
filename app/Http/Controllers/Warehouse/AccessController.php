<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccessController extends Controller
{
    public function index()
    {
        $users = User::whereIn('position', ['secretary', 'special_officer', 'manager', 'supervisor'])->get();
        $warehouses = \App\Models\Warehouse::all();
        $userWarehouseAccess = [];

        foreach ($users as $user) {
            $userWarehouseAccess[$user->id] = [
                'can_access' => $user->hasWarehouseAccess(),
                'warehouse_ids' => $user->assignedWarehouses()->pluck('warehouses.id')->toArray(),
            ];
        }

        return Inertia::render('Dashboard/Warehouse/Access', [
            'users' => $users,
            'warehouses' => $warehouses,
            'userWarehouseAccess' => $userWarehouseAccess,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'grant' => 'boolean',
            'warehouse_ids' => 'array',
            'warehouse_ids.*' => 'exists:warehouses,id',
        ]);

        $user = User::findOrFail($data['user_id']);

        if ($data['grant']) {
            $user->warehouseAccess()->sync($data['warehouse_ids']);
        } else {
            $user->warehouseAccess()->detach();
        }

        return redirect()->back()->with('success', 'Access updated successfully.');
    }
}