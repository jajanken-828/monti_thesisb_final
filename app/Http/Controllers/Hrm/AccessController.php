<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Core\PagePermission;
use App\Models\Core\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Traits\HasPagePermissions;

class AccessController extends Controller
{
    use HasPagePermissions;

    /**
     * Display the access control page for a given module.
     * Shows all staff members of that module and allows the current user
     * to assign page-level permissions (view/edit) to them.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        // 1. DYNAMIC MODULE DETECTION
        // Extracts the prefix from the route name (e.g., 'scm.access.index' -> 'SCM')
        // This ensures the CEO can view and edit staff permissions for ANY department they visit.
        $routeName = $request->route()->getName();
        $modulePrefix = explode('.', $routeName)[0];
        $module = strtoupper($modulePrefix);

        // 2. Fetch staff members for this module with their current page permissions
        $staff = User::where('role', $module)
            ->where('position', 'staff')
            ->with('pagePermissions')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                // Get permissions for this module only, including permission levels
                'permissions' => $u->pagePermissions
                    ->where('module', $module)
                    ->map(fn ($perm) => [
                        'page' => $perm->page,
                        'permission_level' => $perm->permission_level ?? 'edit',
                    ])
                    ->values(),
            ]);

        // 3. Get the list of pages available for this module
        $pages = $this->getPagesForModule($module);

        // 4. Get the current user's own permissions for this module
        $currentUserPermissions = $this->getPagePermissionsForModule($module);

        // 5. Render the component with all necessary data
        return Inertia::render('Dashboard/HRM/Access', [
            'staff' => $staff,
            'pages' => $pages,
            'currentModule' => $module,
            'permissions' => $currentUserPermissions,
        ]);
    }

    /**
     * Update page permissions for a specific staff member.
     * Each page can be assigned a permission level: 'view' or 'edit'.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'pages' => 'required|array',
            'pages.*.page' => 'required|string',
            'pages.*.permission' => 'required|in:view,edit',
        ]);

        // 2. DYNAMIC MODULE DETECTION (For Update)
        $routeName = $request->route()->getName();
        $modulePrefix = explode('.', $routeName)[0];
        $module = strtoupper($modulePrefix);

        // Remove existing permissions for this specific module and user
        PagePermission::where('user_id', $request->user_id)
            ->where('module', $module)
            ->delete();

        // Add new permissions with their respective levels
        foreach ($request->pages as $pageData) {
            PagePermission::create([
                'user_id' => $request->user_id,
                'module' => $module,
                'page' => $pageData['page'],
                'permission_level' => $pageData['permission'],
            ]);
        }

        return back()->with('message', 'Permissions updated successfully.');
    }

    /**
     * Get the list of page keys available for a given module.
     * This defines the granular access points within each module.
     *
     * @param  string  $module
     * @return array
     */
    private function getPagesForModule($module)
    {
        // 3. COMPLETE MENU REGISTRY
        // Defines the exact menu IDs for all modules so the UI can build the checkboxes
        $pages = [
            'HRM' => ['dashboard', 'employee', 'application', 'interview', 'trainee', 'onboarding', 'reject', 'payroll', 'analytics', 'access'],
            'SCM' => ['dashboard', 'operations', 'sales', 'vendor', 'payments', 'assignment', 'close', 'interview', 'trainee', 'access'],
            'MAN' => ['dashboard', 'production', 'reject', 'interview', 'trainee', 'access'],
            'INV' => ['dashboard', 'inventory', 'planning', 'material', 'product', 'interview', 'trainee', 'access'],
            'PRO' => ['dashboard', 'quotation', 'request', 'receipt', 'interview', 'trainee', 'access'],
            'CRM' => ['dashboard', 'lead', 'profile', 'approval', 'oversight', 'strategy', 'interview', 'trainee', 'access'],
            'ECO' => ['dashboard', 'store', 'orders', 'quotations', 'credit', 'book', 'interview', 'trainee', 'access'],
            'FIN' => ['dashboard', 'interview', 'trainee', 'access'],
            'PROJ' => ['dashboard', 'interview', 'trainee', 'access'],
            'IT' => ['dashboard', 'interview', 'trainee', 'access'],
            'WAR' => ['dashboard', 'interview', 'trainee', 'access'],
            'ORD' => ['dashboard', 'interview', 'trainee', 'access'],
        ];

        // Fallback to basic dashboard and access if module is not explicitly detailed above
        return $pages[$module] ?? ['dashboard', 'interview', 'trainee', 'access'];
    }
}