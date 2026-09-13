<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProAccessController extends Controller
{
    /**
     * Display the access control page for Procurement module.
     */
    public function index()
    {
        // Fetch users with PRO module access or relevant permissions
        // Adjust model/table queries as needed for your application
        $users = \App\Models\Core\User::whereHas('modules', function ($query) {
            $query->where('module_name', 'PRO');
        })->with('employee')->get()->map(function ($user) {
            return [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'position'    => $user->employee?->position ?? 'N/A',
                'modules'     => $user->modules->pluck('module_name'),
                'permissions' => $user->pagePermissions->pluck('permission'),
            ];
        });

        // Get all available pages for PRO module (optional)
        $pages = [
            ['name' => 'Dashboard', 'key' => 'dashboard'],
            ['name' => 'Material Requests', 'key' => 'material_requests'],
            ['name' => 'Supplier Quotations', 'key' => 'supplier_quotations'],
            ['name' => 'Receipt', 'key' => 'receipt'],
            ['name' => 'Access Control', 'key' => 'access'],
        ];

        return Inertia::render('Dashboard/PRO/Access', [
            'users' => $users,
            'pages' => $pages,
        ]);
    }

    /**
     * Update user access permissions for Procurement module.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'user_id'     => 'required|exists:users,id',
            'permissions' => 'array',
            'permissions.*.page'      => 'string',
            'permissions.*.can_view'  => 'boolean',
            'permissions.*.can_edit'  => 'boolean',
        ]);

        // Implement your access update logic here.
        // Example: sync page permissions for the user in the PRO module.

        return back()->with('message', 'Procurement access updated successfully.');
    }
}