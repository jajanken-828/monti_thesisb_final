<?php

namespace App\Http\Controllers\ceo;

use App\Http\Controllers\Controller;
use App\Models\crm\Client;
use App\Models\crm\CrmClientAssignment;
use App\Models\core\PagePermission;
use App\Models\core\User;
use App\Models\core\UserModuleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;


class CeoAccessController extends Controller
{
    /**
     * Core modules — each has its own manager + staff hierarchy.
     * CRM added as a core module alongside HRM, MAN, LOG.
     */
    protected $coreModules = ['HRM', 'CRM', 'MAN', 'LOG'];

    /**
     * Feature modules — accessible only via elevated assignment by CEO.
     */
    protected $featureModules = ['ECO', 'ORD', 'SCM', 'WAR', 'INV', 'PRO', 'WRF'];

    /**
     * Pages available per module for staff-level granular access control.
     */
    protected $modulePages = [
        'HRM' => [
            'dashboard' => 'Dashboard',
            'application' => 'Applications',
            'interview' => 'Interviews',
            'trainee' => 'Trainees',
            'employee' => 'Employees',
            'payroll' => 'Payroll',
            'analytics' => 'Analytics',
            'onboarding' => 'Onboarding',
        ],
        'MAN' => [
            'dashboard' => 'Dashboard',
            'knitting' => 'Knitting',
            'dyeing' => 'Dyeing',
            'maintenance' => 'Maintenance',
            'checker' => 'Quality Checker',
        ],
        'LOG' => [
            'dashboard'  => 'Dashboard',
            'load'       => 'Load Packages',
            'dispatch'   => 'Dispatch Center',
            'fleet'      => 'Fleet Management',
            'drivers'    => 'Drivers & Conductors',
            'routes'     => 'Routes',
            'proof'      => 'Proof of Delivery',
            'report'     => 'Conductor Reports',
            'access'     => 'Access Control',
            'interview'  => 'Interviews',
            'trainee'    => 'Trainees',
        ],
        'CRM' => [
            'dashboard' => 'Dashboard',
            'leads' => 'Leads',
            'customer_profiles' => 'Customers',
            'interviews' => 'Interviews',
            'trainees' => 'Trainees',
            'approvals' => 'Approvals',
            'investigation' => 'Investigation',
            'access' => 'Access Control',
        ],
        'ECO' => [
            'store' => 'Store',
            'inquiry' => 'Inquiries',
            'push' => 'Push Notifications',
            'credit' => 'Credit',
            'supplier' => 'Suppliers',
        ],
        'ORD' => [
            'orders' => 'Orders',
            'productions' => 'Productions',
            'delivery' => 'Delivery',
        ],
        'SCM' => [
            'procurement' => 'Procurement',
            'sales' => 'Sales Orders',
            'vendor' => 'Vendors',
        ],
        'WAR' => [
            'warehouse' => 'Warehouse',
            'receiving' => 'Receiving',
            'packages' => 'Packages',
            'monitor' => 'Monitor',
            'reject' => 'Rejects',
        ],
        'INV' => [
            'materials' => 'Materials',
            'products' => 'Products',
            'bom' => 'Bill of Materials',
            'checker' => 'Checker',
        ],
        'PRO' => [
            'procurement' => 'Procurement',
            'quotations' => 'Quotations',
            'receipt' => 'Receipt',
        ],
        'WRF' => [
            'schedule' => 'Schedule',
            'leave' => 'Leave Management',
            'absent' => 'Absences',
        ],
        'FIN' => [
            'overview' => 'Overview',
            'payables' => 'Payables',
            'budget' => 'Budget',
        ],
        'PROJ' => [
            'projects' => 'Projects',
            'tasks' => 'Tasks',
        ],
        'IT' => [
            'systems' => 'Systems',
            'users' => 'Users',
        ],
    ];

    /**
     * Human-readable labels for manufacturing roles.
     */
    protected $manufacturingRoleLabels = [
        'knitting_yarn' => 'Knitting Yarn Staff',
        'dyeing_color' => 'Dyeing Color Staff',
        'dyeing_fabric_softener' => 'Dyeing Fabric Softener Staff',
        'dyeing_squeezer' => 'Dyeing Squeezer Staff',
        'dyeing_ironing' => 'Dyeing Ironing Staff',
        'dyeing_forming' => 'Dyeing Forming Staff',
        'dyeing_packaging' => 'Dyeing Packaging Staff',
        'maintenance_checker' => 'Maintenance Checker Staff',
    ];

    // -----------------------------------------------------------------------
    // Internal Helpers
    // -----------------------------------------------------------------------

    protected function getRootModuleForUser(User $user): ?string
    {
        if ($user->is_manufacturing_supervisor) {
            return 'MAN';
        }
        if (! $user->role) {
            return null;
        }
        $roleUpper = strtoupper($user->role);
        foreach ($this->coreModules as $core) {
            if (str_contains($roleUpper, $core)) {
                return $core;
            }
        }

        return null;
    }

    protected function getAssignableModulesForUser(User $user): array
    {
        $root = $this->getRootModuleForUser($user);
        if ($root && in_array($root, $this->coreModules)) {
            return array_merge([$root], $this->featureModules);
        }

        return $this->featureModules;
    }

    /**
     * Generate the human-readable "smart label" for a user.
     */
    protected function getSmartLabel(User $user): string
    {
        if ($user->is_manufacturing_supervisor) {
            if ($user->supervisor_department) {
                return ucfirst($user->supervisor_department).' Department Supervisor';
            }

            return 'Supervisor — No Department Assigned';
        }

        if ($user->position === 'manager') {
            return $this->getModuleName($user->role).' Manager';
        }
        if ($user->position === 'general_manager') {
            return $this->getModuleName($user->role).' General Manager';
        }
        if ($user->position === 'secretary') {
            return $this->getModuleName($user->role).' Secretary';
        }

        switch ($user->role) {
            case 'HRM':
                return 'Office Staff';
            case 'CRM':
                return 'Representative';
            case 'LOG':
                $isDriver = DB::table('drivers')->where('user_id', $user->id)->exists();
                $isConductor = DB::table('conductors')->where('user_id', $user->id)->exists();
                if ($isDriver) {
                    return 'Driver';
                }
                if ($isConductor) {
                    return 'Conductor';
                }
                if (array_key_exists('log_role', $user->getAttributes())) {
                    if ($user->log_role === 'driver') {
                        return 'Driver';
                    }
                    if ($user->log_role === 'conductor') {
                        return 'Conductor';
                    }
                }

                return 'Unassigned Logistics Staff';
            case 'MAN':
                if ($user->manufacturing_role) {
                    return $this->manufacturingRoleLabels[$user->manufacturing_role]
                        ?? ucwords(str_replace('_', ' ', $user->manufacturing_role));
                }

                return 'No Assigned Department Yet';
            default:
                return $this->getModuleName($user->role).' Staff';
        }
    }

    /**
     * Detect whether a LOG staff is a driver or conductor.
     */
    protected function resolveLogRole(User $user): ?string
    {
        if ($user->role !== 'LOG') {
            return null;
        }
        if (DB::table('drivers')->where('user_id', $user->id)->exists()) {
            return 'driver';
        }
        if (DB::table('conductors')->where('user_id', $user->id)->exists()) {
            return 'conductor';
        }
        if (array_key_exists('log_role', $user->getAttributes())) {
            return $user->log_role;
        }

        return null;
    }

    /**
     * Serialize a single user into the shape expected by the Vue component.
     */
    protected function formatUser(User $user): array
    {
        $moduleAccessRecords = $user->moduleAccess ?? collect();

        $grantedModules = $moduleAccessRecords->map(fn ($ma) => [
            'module' => $ma->module,
            'permission_level' => $ma->permission_level ?? 'edit',
        ])->toArray();
        $grantedModuleKeys = array_column($grantedModules, 'module');

        $isElevated = $user->is_manufacturing_supervisor
            || in_array($user->position, ['secretary', 'general_manager']);
        $rootModule = $this->getRootModuleForUser($user);

        if ($isElevated && $rootModule && ! in_array($rootModule, $grantedModuleKeys)) {
            $grantedModules[] = ['module' => $rootModule, 'permission_level' => 'edit'];
            $grantedModuleKeys[] = $rootModule;
        }

        $pagePermRecords = $user->pagePermissions ?? collect();
        $pagePermissions = $pagePermRecords->map(fn ($pp) => [
            'module' => $pp->module,
            'page' => $pp->page,
            'permission_level' => $pp->permission_level ?? 'edit',
        ])->toArray();

        $displayPosition = $user->position;
        if ($user->is_manufacturing_supervisor && $user->position === 'staff') {
            $displayPosition = 'manufacturing_supervisor';
        }

        $photoUrl = $user->profile_photo_path
            ? asset('storage/'.$user->profile_photo_path)
            : null;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'employee_id' => $user->employee_id,
            'role' => $user->role,
            'position' => $user->position,
            'display_position' => $displayPosition,
            'is_manufacturing_supervisor' => (bool) $user->is_manufacturing_supervisor,
            'supervisor_department' => $user->supervisor_department,
            'manufacturing_role' => $user->manufacturing_role,
            'log_role' => $this->resolveLogRole($user),
            'granted_modules' => $grantedModules,
            'root_module' => $rootModule,
            'assignable_modules' => $isElevated ? $this->getAssignableModulesForUser($user) : [],
            'page_permissions' => $pagePermissions,
            'smart_label' => $this->getSmartLabel($user),
            'profile_photo' => $photoUrl,
            'is_active' => (bool) $user->is_active,
            'join_date' => $user->join_date,
            'department' => $user->department,
        ];
    }

    private function getModuleName(string $key): string
    {
        $names = [
            'HRM' => 'Human Resource',
            'MAN' => 'Manufacturing',
            'LOG' => 'Logistics',
            'CRM' => 'Customer Relationship',
            'ECO' => 'E-Commerce',
            'ORD' => 'Order Management',
            'SCM' => 'Supply Chain',
            'WAR' => 'Warehouse',
            'INV' => 'Inventory',
            'PRO' => 'Procurement',
            'WRF' => 'Workforce Management',
            'FIN' => 'Finance',
            'PROJ' => 'Project',
            'IT' => 'IT & Systems',
            'CEO' => 'CEO',
        ];

        return $names[$key] ?? $key;
    }

    // -----------------------------------------------------------------------
    // Profile Photo Synchronisation
    // -----------------------------------------------------------------------

    /**
     * Synchronise profile photo between applicant and user (bidirectional).
     * - If applicant has an image and user has no profile_photo_path, copy to user.
     * - If user has a profile_photo_path and applicant has no image, copy to applicant.
     *
     * @param  object|null  $applicant  Row from applicants table
     */
    protected function syncProfilePhoto($applicant, User $user): void
    {
        if (! $applicant) {
            return;
        }

        $applicantImage = $applicant->image ?? null;
        $userPhoto = $user->profile_photo_path;

        // If applicant has image but user does not, copy to user
        if ($applicantImage && ! $userPhoto) {
            $user->profile_photo_path = $applicantImage;
            $user->save();
        }
        // If user has photo but applicant does not, copy to applicant
        elseif ($userPhoto && ! $applicantImage) {
            DB::table('applicants')
                ->where('id', $applicant->id)
                ->update(['image' => $userPhoto]);
        }
    }

    // -----------------------------------------------------------------------
    // Default Staff Page Permissions
    // -----------------------------------------------------------------------

    /**
     * Assign default page permissions for a staff member based on their module.
     * Currently, gives 'dashboard' with 'view' permission for core modules.
     * You can extend this for other modules as needed.
     */
    protected function assignDefaultStaffPagePermissions(User $user): void
    {
        // Only assign if user is a staff member (position === 'staff')
        if ($user->position !== 'staff') {
            return;
        }

        $module = $user->role;
        // Only assign for core modules that have defined pages
        if (! in_array($module, $this->coreModules)) {
            return;
        }

        // Check if the user already has any page permissions for this module
        $existing = PagePermission::where('user_id', $user->id)
            ->where('module', $module)
            ->exists();

        if (! $existing) {
            // Grant default: dashboard with view permission
            $pagePerm = new PagePermission;
            $pagePerm->user_id = $user->id;
            $pagePerm->module = $module;
            $pagePerm->page = 'dashboard';
            $pagePerm->permission_level = 'view';
            $pagePerm->save();
        }
    }

    // -----------------------------------------------------------------------
    // Public Actions
    // -----------------------------------------------------------------------

    /**
     * Main index — returns the full org structure to the Access.vue component.
     */
    public function index()
    {
        $ceo = User::where('role', 'CEO')->first();

        $allUsers = User::with(['moduleAccess', 'pagePermissions'])
            ->where('role', '!=', 'CEO')
            ->whereIn('position', ['manager', 'staff', 'secretary', 'general_manager'])
            ->orderBy('name')
            ->get();

        $managers = $allUsers
            ->filter(fn ($u) => $u->position === 'manager' && ! $u->is_manufacturing_supervisor)
            ->map(fn ($u) => $this->formatUser($u))
            ->values();

        $secretary = $allUsers
            ->filter(fn ($u) => $u->position === 'secretary' && ! $u->is_manufacturing_supervisor)
            ->map(fn ($u) => $this->formatUser($u))
            ->first();

        $generalManagers = $allUsers
            ->filter(fn ($u) => $u->position === 'general_manager' && ! $u->is_manufacturing_supervisor)
            ->map(fn ($u) => $this->formatUser($u))
            ->values();

        $supervisors = $allUsers
            ->filter(fn ($u) => (bool) $u->is_manufacturing_supervisor)
            ->map(fn ($u) => $this->formatUser($u))
            ->values();

        $staff = $allUsers
            ->filter(fn ($u) => $u->position === 'staff' && ! $u->is_manufacturing_supervisor)
            ->map(fn ($u) => $this->formatUser($u))
            ->values();

        $allModules = collect(array_merge($this->coreModules, $this->featureModules))
            ->map(fn ($key) => ['key' => $key, 'name' => $this->getModuleName($key)])
            ->values()
            ->toArray();

        $modulePages = [];
        foreach ($this->modulePages as $module => $pages) {
            $modulePages[$module] = collect($pages)
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
                ->values()
                ->toArray();
        }

        $manufacturingRoles = collect($this->manufacturingRoleLabels)
            ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
            ->values()
            ->toArray();

        // Check if a secretary already exists (for the frontend secretary-limit guard)
        $secretaryExists = User::where('position', 'secretary')
            ->where('role', '!=', 'CEO')
            ->where('is_manufacturing_supervisor', 0)
            ->exists();

        return Inertia::render('Dashboard/CEO/Access', [
            'ceo' => $ceo ? [
                'name' => $ceo->name,
                'email' => $ceo->email,
                'profile_photo' => $ceo->profile_photo_path ? asset('storage/'.$ceo->profile_photo_path) : null,
            ] : null,
            'secretary' => $secretary,
            'generalManagers' => $generalManagers,
            'managers' => $managers,
            'supervisors' => $supervisors,
            'staff' => $staff,
            'allModules' => $allModules,
            'modulePages' => $modulePages,
            'manufacturingRoles' => $manufacturingRoles,
            'secretaryExists' => $secretaryExists,
        ]);
    }

    /**
     * Promote / demote an employee's position.
     * Enforces: only ONE secretary allowed across the whole organisation.
     * Enforces: only ONE manager per module.
     * Additionally, assigns default page permissions when user becomes staff.
     */
    public function updatePosition(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'position' => 'required|in:manager,secretary,general_manager,staff',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->role === 'CEO') {
            return back()->withErrors(['error' => 'Cannot change CEO position.']);
        }
        if ($user->is_manufacturing_supervisor) {
            return back()->withErrors(['error' => 'Manufacturing supervisors cannot be promoted/demoted via this action.']);
        }

        // ── Secretary uniqueness guard ──────────────────────────────────────
        if ($request->position === 'secretary') {
            $existingSecretary = User::where('position', 'secretary')
                ->where('is_manufacturing_supervisor', 0)
                ->where('id', '!=', $user->id)
                ->first();

            if ($existingSecretary) {
                return back()->withErrors([
                    'error' => 'A secretary already exists ('.$existingSecretary->name.'). Monti Textile only allows one secretary. Please demote the current secretary first.',
                ]);
            }
        }

        // ── One manager per module guard (only when promoting to manager) ────
        if ($request->position === 'manager') {
            $existingManager = User::where('position', 'manager')
                ->where('role', $user->role)
                ->where('id', '!=', $user->id)
                ->first();
            if ($existingManager) {
                return back()->withErrors([
                    'error' => "The {$user->role} module already has a manager ({$existingManager->name}). Only one manager is allowed per module. Please demote the existing manager first.",
                ]);
            }
        }

        $oldPosition = $user->position;
        $user->position = $request->position;
        $user->save();

        // Handle module access cleanup/promotion
        if ($request->position === 'staff') {
            // Demoting from manager (or any higher role) to staff: remove all module access
            $user->moduleAccess()->delete();
            // Also remove any existing page permissions (they will be reassigned as default)
            $user->pagePermissions()->delete();
            // Assign default page permissions for the staff member's module
            $this->assignDefaultStaffPagePermissions($user);
        } elseif ($request->position === 'manager') {
            // Demoting from secretary/GM to manager, or promoting from staff to manager?
            if (in_array($oldPosition, ['secretary', 'general_manager'])) {
                // Demotion: remove all module access (they will only get their core module if needed)
                $user->moduleAccess()->delete();
            } elseif ($oldPosition === 'staff') {
                // Promotion from staff to manager: remove page permissions (managers don't need page restrictions)
                $user->pagePermissions()->delete();
            }
            // If promotion from staff, we do not delete module access (they may have none anyway)
        }

        // For promotions to secretary or general manager, ensure they have their root module access
        if (in_array($request->position, ['secretary', 'general_manager'])) {
            $rootModule = $this->getRootModuleForUser($user);
            if ($rootModule) {
                $exists = UserModuleAccess::where('user_id', $user->id)
                    ->where('module', $rootModule)
                    ->exists();
                if (! $exists) {
                    $moduleAccess = new UserModuleAccess;
                    $moduleAccess->user_id = $user->id;
                    $moduleAccess->module = $rootModule;
                    $moduleAccess->permission_level = 'edit';
                    $moduleAccess->granted_by = auth()->id();
                    $moduleAccess->save();
                }
            }
        }

        return back()->with('success', 'Position updated successfully.');
    }

    /**
     * Update which modules are assigned to an elevated user (GM / Secretary / Supervisor).
     */
    public function updateModules(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'modules' => 'array',
            'modules.*' => 'string',
            'permissions' => 'array',
            'permissions.*' => 'in:view,edit',
        ]);

        $user = User::findOrFail($request->user_id);

        $isEligible = in_array($user->position, ['secretary', 'general_manager'])
            || $user->is_manufacturing_supervisor;

        if (! $isEligible) {
            return back()->withErrors(['error' => 'Module access is only configurable for Secretaries, General Managers, or Manufacturing Supervisors.']);
        }

        $rootModule = $this->getRootModuleForUser($user);
        $allowedModules = $this->getAssignableModulesForUser($user);
        $modules = $request->modules ?? [];
        $permissions = $request->permissions ?? [];

        if ($rootModule && ! in_array($rootModule, $modules)) {
            $modules[] = $rootModule;
        }

        $modules = array_unique(array_intersect($modules, $allowedModules));

        $user->moduleAccess()->delete();
        foreach ($modules as $module) {
            // Direct assignment to bypass mass assignment protection
            $moduleAccess = new UserModuleAccess;
            $moduleAccess->user_id = $user->id;
            $moduleAccess->module = $module;
            $moduleAccess->permission_level = ($module === $rootModule) ? 'edit' : ($permissions[$module] ?? 'edit');
            $moduleAccess->granted_by = auth()->id();
            $moduleAccess->save();
        }

        return back()->with('success', 'Module permissions updated.');
    }

    /**
     * Assign specific page-level access to a staff member.
     */
    public function updateStaffPages(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'pages' => 'array',
            'pages.*.page' => 'required|string',
            'pages.*.permission' => 'required|in:view,edit',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->position !== 'staff') {
            return back()->withErrors(['error' => 'Page-level access can only be assigned to staff members.']);
        }

        $allowedPages = array_keys($this->modulePages[$user->role] ?? []);

        $user->pagePermissions()->delete();

        foreach ($request->pages ?? [] as $pageData) {
            if (! in_array($pageData['page'], $allowedPages)) {
                continue;
            }

            // Direct assignment to bypass mass assignment protection
            $pagePerm = new PagePermission;
            $pagePerm->user_id = $user->id;
            $pagePerm->module = $user->role;
            $pagePerm->page = $pageData['page'];
            $pagePerm->permission_level = $pageData['permission'] ?? 'edit';
            $pagePerm->save();
        }

        // Ensure at least dashboard is present (fallback)
        $hasDashboard = $user->pagePermissions()->where('page', 'dashboard')->exists();
        if (! $hasDashboard && in_array($user->role, $this->coreModules)) {
            $pagePerm = new PagePermission;
            $pagePerm->user_id = $user->id;
            $pagePerm->module = $user->role;
            $pagePerm->page = 'dashboard';
            $pagePerm->permission_level = 'view';
            $pagePerm->save();
        }

        return back()->with('success', 'Page permissions updated.');
    }

    /**
     * Assign a contextual role to a staff member or supervisor.
     */
    public function assignStaffRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'manufacturing_role' => 'nullable|in:knitting_yarn,dyeing_color,dyeing_fabric_softener,dyeing_squeezer,dyeing_ironing,dyeing_forming,dyeing_packaging,maintenance_checker',
            'log_role' => 'nullable|in:driver,conductor',
            'supervisor_department' => 'nullable|in:knitting,dyeing,maintenance',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->role === 'MAN' && $request->has('manufacturing_role')) {
            $user->manufacturing_role = $request->manufacturing_role;
            $user->save();

            if ($user->is_manufacturing_supervisor && $request->manufacturing_role) {
                DB::table('manufacturing_supervisor_roles')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'manufacturing_role' => $request->manufacturing_role,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        if ($user->is_manufacturing_supervisor && $request->has('supervisor_department')) {
            $user->supervisor_department = $request->supervisor_department;
            $user->save();
        }

        if ($user->role === 'LOG' && $request->has('log_role')) {
            if (array_key_exists('log_role', $user->getAttributes())) {
                $user->log_role = $request->log_role;
                $user->save();
            }
            if ($request->log_role === 'driver') {
                DB::table('conductors')->where('user_id', $user->id)->delete();
                // Generate a unique license number to avoid duplicate key violations
                $licenseNumber = 'DRV-' . strtoupper(uniqid());
                DB::table('drivers')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'is_available' => 1,
                        'license_number' => $licenseNumber,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            } elseif ($request->log_role === 'conductor') {
                DB::table('drivers')->where('user_id', $user->id)->delete();
                DB::table('conductors')->updateOrInsert(
                    ['user_id' => $user->id],
                    ['is_available' => 1, 'created_at' => now(), 'updated_at' => now()]
                );
            } elseif ($request->log_role === null) {
                DB::table('drivers')->where('user_id', $user->id)->delete();
                DB::table('conductors')->where('user_id', $user->id)->delete();
            }
        }

        return back()->with('success', 'Role assigned successfully.');
    }

    /**
     * Update a user's profile photo and sync to the corresponding applicant.
     * This endpoint can be called from the CEO panel (or any admin) to keep photos in sync.
     */
    public function updateProfilePhoto(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'photo' => 'required|image|max:2048', // 2MB max
        ]);

        $user = User::findOrFail($request->user_id);
        $path = $request->file('photo')->store('profile-photos', 'public');
        $user->profile_photo_path = $path;
        $user->save();

        // Sync to applicant table if an applicant exists with the same email
        DB::table('applicants')
            ->where('email', $user->email)
            ->update(['image' => $path]);

        return back()->with('success', 'Profile photo updated and synchronised.');
    }

    /**
     * Return full personal/application-form information for a specific employee.
     * Links users → applicants via matching email address.
     */
    public function getEmployeePersonalInfo(int $id)
    {
        $user = User::findOrFail($id);

        // Attempt to find the matching applicant record by email
        $applicant = DB::table('applicants')
            ->where('email', $user->email)
            ->first();

        // Synchronise profile photo between both tables (bidirectional)
        if ($applicant) {
            $this->syncProfilePhoto($applicant, $user);
            // Refresh user to get the latest profile_photo_path after sync
            $user->refresh();
        }

        // Unified profile photo: applicant image first, then user's profile photo
        $profilePhotoUrl = null;
        if ($applicant && $applicant->image) {
            $profilePhotoUrl = asset('storage/'.$applicant->image);
        } elseif ($user->profile_photo_path) {
            $profilePhotoUrl = asset('storage/'.$user->profile_photo_path);
        }

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'employee_id' => $user->employee_id,
            'role' => $user->role,
            'position' => $user->position,
            'join_date' => $user->join_date,
            'department' => $user->department,
            'is_active' => $user->is_active,
            'created_at' => $user->created_at,
            'profile_photo' => $profilePhotoUrl,
        ];

        $applicantData = null;
        if ($applicant) {
            // Decode JSON columns
            $children = $applicant->children ? json_decode($applicant->children, true) : null;
            $employmentRecords = $applicant->employment_records ? json_decode($applicant->employment_records, true) : null;
            $relatedEmployees = $applicant->related_employees ? json_decode($applicant->related_employees, true) : null;

            // Asset URLs for uploaded ID files
            $sssFileUrl = $applicant->sss_file ? asset('storage/'.$applicant->sss_file) : null;
            $philhealthFileUrl = $applicant->philhealth_file ? asset('storage/'.$applicant->philhealth_file) : null;
            $pagibigFileUrl = $applicant->pagibig_file ? asset('storage/'.$applicant->pagibig_file) : null;

            $applicantData = [
                // ── Personal ────────────────────────────────────────────────
                'first_name' => $applicant->first_name,
                'middle_name' => $applicant->middle_name,
                'last_name' => $applicant->last_name,
                'date_of_birth' => $applicant->date_of_birth,
                'place_of_birth' => $applicant->place_of_birth,
                'citizenship' => $applicant->citizenship,
                'weight' => $applicant->weight,
                'height' => $applicant->height,
                'civil_status' => $applicant->civil_status,
                'sex' => $applicant->sex,
                'age' => $applicant->age,
                'religion' => $applicant->religion,
                'contact_number' => $applicant->contact_number,
                'phone_number' => $applicant->phone_number,
                'image' => $applicant->image ? asset('storage/'.$applicant->image) : null,

                // ── Address ─────────────────────────────────────────────────
                'street_address' => $applicant->street_address,
                'street_address_line2' => $applicant->street_address_line2,
                'city' => $applicant->city,
                'state_province' => $applicant->state_province,
                'postal_zip_code' => $applicant->postal_zip_code,

                // ── Government IDs (with file URLs) ─────────────────────────
                'sss_number' => $applicant->sss_number,
                'sss_file_url' => $sssFileUrl,
                'philhealth_number' => $applicant->philhealth_number,
                'philhealth_file_url' => $philhealthFileUrl,
                'pagibig_number' => $applicant->pagibig_number,
                'pagibig_file_url' => $pagibigFileUrl,

                // ── Family ──────────────────────────────────────────────────
                'spouse_name' => $applicant->spouse_name,
                'spouse_occupation' => $applicant->spouse_occupation,
                'spouse_address' => $applicant->spouse_address,
                'number_of_children' => $applicant->number_of_children,
                'children' => $children,
                'mother_name' => $applicant->mother_name,
                'mother_address' => $applicant->mother_address,
                'father_name' => $applicant->father_name,
                'father_address' => $applicant->father_address,

                // ── Emergency Contact ────────────────────────────────────────
                'emergency_name' => $applicant->emergency_name,
                'emergency_relationship' => $applicant->emergency_relationship,
                'emergency_phone' => $applicant->emergency_phone,
                'emergency_address' => $applicant->emergency_address,

                // ── Education ───────────────────────────────────────────────
                'elementary_school' => $applicant->elementary_school,
                'elementary_year' => $applicant->elementary_year,
                'high_school' => $applicant->high_school,
                'high_year' => $applicant->high_year,
                'college' => $applicant->college,
                'college_year' => $applicant->college_year,
                'vocational' => $applicant->vocational,
                'vocational_year' => $applicant->vocational_year,

                // ── Employment History ───────────────────────────────────────
                'has_employment_record' => (bool) $applicant->has_employment_record,
                'employment_records' => $employmentRecords,
                'previous_employment_company' => $applicant->previous_employment_company,
                'previous_employment_when' => $applicant->previous_employment_when,
                'previous_employment_position' => $applicant->previous_employment_position,
                'previous_employment_department' => $applicant->previous_employment_department,

                // ── Skills & Other ───────────────────────────────────────────
                'languages' => $applicant->languages,
                'special_skills' => $applicant->special_skills,
                'machine_operation' => $applicant->machine_operation,
                'position_applied' => $applicant->position_applied,
                'notice_period' => $applicant->notice_period,

                // ── References / Relatives ───────────────────────────────────
                'referred_by' => $applicant->referred_by,
                'referred_by_address' => $applicant->referred_by_address,
                'related_employees' => $relatedEmployees,

                // ── Application meta ─────────────────────────────────────────
                'status' => $applicant->status,
                'assigned_module' => $applicant->assigned_module,
                'application_date' => $applicant->created_at,
            ];
        }

        return response()->json([
            'user' => $userData,
            'applicant' => $applicantData,
        ]);
    }

    // -----------------------------------------------------------------------
    // Client assignment for CRM staff
    // -----------------------------------------------------------------------

    /**
     * Get all active clients and the ones already assigned to a specific CRM staff member.
     */
    public function getClientAssignments(int $staffId)
    {
        $staff = User::findOrFail($staffId);

        if ($staff->role !== 'CRM' || $staff->position !== 'staff') {
            return response()->json(['error' => 'User is not a CRM staff member.'], 403);
        }

        // Get all active clients (status 'active')
        $clients = Client::where('status', 'active')
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'contact_person', 'email']);

        // Get already assigned client IDs
        $assignedIds = CrmClientAssignment::where('staff_id', $staffId)
            ->pluck('client_id')
            ->toArray();

        return response()->json([
            'clients' => $clients,
            'assigned_client_ids' => $assignedIds,
        ]);
    }

    /**
     * Update client assignments for a CRM staff member.
     * Receives an array of client_ids to assign (full sync).
     */
    public function updateClientAssignments(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:users,id',
            'client_ids' => 'array',
            'client_ids.*' => 'exists:clients,id',
        ]);

        $staff = User::findOrFail($request->staff_id);

        if ($staff->role !== 'CRM' || $staff->position !== 'staff') {
            return back()->withErrors(['error' => 'User is not a CRM staff member.']);
        }

        $clientIds = $request->client_ids ?? [];

        // Sync assignments: remove old ones not in the new list, add new ones
        $currentAssignments = CrmClientAssignment::where('staff_id', $staff->id)->pluck('client_id')->toArray();

        // Remove assignments that are no longer in $clientIds
        $toRemove = array_diff($currentAssignments, $clientIds);
        if (! empty($toRemove)) {
            CrmClientAssignment::where('staff_id', $staff->id)
                ->whereIn('client_id', $toRemove)
                ->delete();
        }

        // Add new assignments
        $toAdd = array_diff($clientIds, $currentAssignments);
        foreach ($toAdd as $clientId) {
            CrmClientAssignment::create([
                'client_id' => $clientId,
                'staff_id' => $staff->id,
                'assigned_by' => auth()->id(),
            ]);
        }

        return back()->with('success', 'Client assignments updated successfully.');
    }
}