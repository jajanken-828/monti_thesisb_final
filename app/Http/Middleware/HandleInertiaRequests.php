<?php

namespace App\Http\Middleware;

use App\Models\core\PagePermission;
use App\Models\work\WorkforcePermission;
use App\Models\crm\CrmPagePermission;
use App\Models\core\UserModuleAccess;
use App\Models\crm\CrmClientAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        $userData = null;
        $pagePermissionsList = [];
        $assignedClientIds = [];

        if ($user) {
            // 1. Fetch explicit permissions from DB
            $explicitPermissions = PagePermission::where('user_id', $user->id)
                ->get(['module', 'page', 'permission_level'])
                ->toArray();

            // 2. Auto‑grant full access for module managers/staff on their own module
            $augmentedPermissions = $this->augmentPermissionsForModuleUser($user, $explicitPermissions);

            // ---- View/edit levels have been removed ----
            // Any page a user has been granted (explicitly or via the module
            // auto-grant above) is now full access — view AND edit. We keep
            // the 'permission_level' key in the payload (set to 'edit') only
            // for frontend backward compatibility; it no longer represents a
            // real restriction.
            $pagePermissionsList = array_map(function ($perm) {
                $perm['permission_level'] = 'edit';
                return $perm;
            }, $augmentedPermissions);

            // 3. Group by module (for backward compatibility with frontend consumers)
            $permissionsGrouped = collect($pagePermissionsList)
                ->groupBy('module')
                ->map(fn ($perms) => $perms->pluck('page'));

            // Fetch workforce permissions
            $workforcePermissions = WorkforcePermission::where('user_id', $user->id)->get();

            // Fetch CRM page permissions
            $crmPagePermissions = [];
            if (in_array($user->role, ['CRM', 'CEO'])) {
                $crmPagePermissions = CrmPagePermission::where('user_id', $user->id)->pluck('page')->toArray();
            }

            // Fetch granted modules for secretary/general manager
            $grantedModules = UserModuleAccess::where('user_id', $user->id)->pluck('module')->toArray();

            // Fetch assigned client IDs for CRM staff
            if ($user->role === 'CRM' && $user->position === 'staff') {
                $assignedClientIds = CrmClientAssignment::where('staff_id', $user->id)
                    ->pluck('client_id')
                    ->toArray();
            }

            // Merge user attributes with permissions
            $userData = array_merge($user->toArray(), [
                'permissions'            => $permissionsGrouped,
                'workforce_permissions'  => $workforcePermissions,
                'crmPagePermissions'     => $crmPagePermissions,
                'granted_modules'        => $grantedModules,
                'page_permissions'       => $pagePermissionsList, // all pages here are full access
            ]);
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user'                => $userData,
                'page_permissions'    => $pagePermissionsList,
                'assigned_client_ids' => $assignedClientIds,
                'client'              => $this->getGuardUser('client'),
                'supplier'            => $this->getGuardUser('supplier'),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
            ],
        ];
    }

    protected function getGuardUser(string $guard)
    {
        try {
            return Auth::guard($guard)->user();
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Augment the permission list so that managers/staff of a module get
     * full access on all pages of that module, unless an explicit
     * permission record already exists for that page.
     */
    protected function augmentPermissionsForModuleUser($user, array $explicitPermissions): array
    {
        $module = strtoupper($user->role);
        $position = $user->position;

        if (!in_array($position, ['manager', 'staff'])) {
            return $explicitPermissions;
        }

        $modulePages = $this->getModulePages($module);

        $result = $explicitPermissions;

        foreach ($modulePages as $page) {
            $exists = collect($result)->firstWhere(function ($perm) use ($module, $page) {
                return $perm['module'] === $module && $perm['page'] === $page;
            });

            if (!$exists) {
                $result[] = [
                    'module'           => $module,
                    'page'             => $page,
                    'permission_level' => 'edit', // full access
                ];
            }
        }

        return $result;
    }

    /**
     * Get all page names for a given module.
     */
    protected function getModulePages(string $module): array
    {
        $map = [
            'HRM' => ['dashboard', 'employee', 'application', 'interview', 'trainee', 'onboarding', 'access', 'payroll', 'analytics'],
            'CRM' => ['dashboard', 'leads', 'interviews', 'trainees', 'approvals', 'customer_profiles', 'investigation', 'access'],
            'SCM' => ['sales-orders', 'procurement-orders', 'vendors', 'access'],
            'FIN' => ['dashboard'],
            'MAN' => ['dashboard', 'production', 'rejected', 'inventory', 'access'],
            'INV' => ['dashboard', 'materials', 'products', 'bom', 'checker', 'access'],
            'ORD' => ['orders', 'productions', 'delivery', 'access'],
            'WAR' => ['index', 'receiving', 'monitor', 'packages', 'rejects', 'access'],
            'ECO' => ['dashboard', 'store', 'inquiries', 'credit', 'push', 'access'],
            'PRO' => ['dashboard'],
            'PROJ' => ['dashboard'],
            'IT' => ['dashboard'],
            'LOG' => ['dashboard', 'fleet', 'drivers', 'load', 'dispatch', 'routes', 'tracking', 'access'],
        ];

        return $map[$module] ?? [];
    }
}