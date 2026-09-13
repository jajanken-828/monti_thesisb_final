<?php

namespace App\Http\Controllers\It;

use App\Http\Controllers\Controller;
use App\Models\Core\PagePermission;
use App\Models\Core\User;
use App\Models\Core\UserModuleAccess;
use App\Models\It\ItAccessLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Organization-wide access administration for the IT department.
 * Mirrors the CEO organization chart's capabilities (account state,
 * promotion, module + per-page view/edit grants) with every change
 * written to the it_access_logs audit trail.
 */
class ItAccessControlController extends Controller
{
    protected $moduleNames = [
        'HRM' => 'Human Resources',
        'CRM' => 'Customer Relations',
        'MAN' => 'Manufacturing',
        'LOG' => 'Logistics',
        'ECO' => 'E-Commerce',
        'ORD' => 'Order Management',
        'SCM' => 'Supply Chain',
        'WAR' => 'Warehouse',
        'INV' => 'Inventory',
        'PRO' => 'Procurement',
        'FIN' => 'Finance',
        'PROJ' => 'Projects',
        'IT' => 'IT & Systems',
        'WRF' => 'Workforce',
    ];

    protected $assignableRoles = [
        'HRM', 'CRM', 'MAN', 'LOG', 'ECO', 'ORD', 'SCM',
        'WAR', 'INV', 'PRO', 'FIN', 'PROJ', 'IT',
    ];

    protected $assignablePositions = ['manager', 'staff', 'secretary', 'special_officer'];

    /**
     * Canonical per-module page map: config file first, WRF appended
     * (it has no entry in config/module_pages.php).
     */
    protected function modulePages(): array
    {
        $pages = config('module_pages', []);
        $pages['wrf'] = [
            'dashboard' => 'Dashboard',
            'scheduler' => 'Scheduler',
            'leave' => 'Leave Management',
            'absent' => 'Absences',
        ];

        $ordered = [];
        foreach (array_keys($this->moduleNames) as $key) {
            $lookup = strtolower($key);
            if (isset($pages[$lookup])) {
                $ordered[$key] = $pages[$lookup];
            }
        }

        return $ordered;
    }

    public function index()
    {
        $users = User::with(['moduleAccess', 'pagePermissions'])
            ->where('role', '!=', 'CEO')
            ->orderBy('role')
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'employee_id' => $u->employee_id,
                'role' => $u->role,
                'position' => $u->position,
                'is_active' => (bool) $u->is_active,
                'suspended_until' => $u->suspended_until,
                'is_manufacturing_supervisor' => (bool) $u->is_manufacturing_supervisor,
            ]);

        $pagePerms = PagePermission::whereIn('user_id', $users->pluck('id'))->get();
        $userPagePerms = [];
        foreach ($pagePerms as $pp) {
            $userPagePerms[$pp->user_id][$pp->module][$pp->page] = strtolower($pp->permission_level ?? 'edit');
        }

        $userModules = UserModuleAccess::whereIn('user_id', $users->pluck('id'))
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('module')->values())
            ->toArray();

        return Inertia::render('Dashboard/IT/Manager/ItAccessControl', [
            'users' => $users,
            'modules' => collect($this->moduleNames)
                ->map(fn ($name, $key) => ['key' => $key, 'name' => $name])
                ->values(),
            'modulePages' => $this->modulePages(),
            'userPagePerms' => $userPagePerms,
            'userModules' => $userModules,
            'assignableRoles' => $this->assignableRoles,
            'assignablePositions' => $this->assignablePositions,
        ]);
    }

    /**
     * Enable / disable / suspend / restore an account.
     */
    public function setStatus(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'action' => 'required|in:enable,disable,suspend,restore',
            'suspended_until' => 'nullable|date|after:now',
        ]);

        $user = User::findOrFail($data['user_id']);
        if ($user->role === 'CEO') {
            return back()->withErrors(['error' => 'CEO accounts cannot be modified here.']);
        }
        if ($user->id === auth()->id() && in_array($data['action'], ['disable', 'suspend'], true)) {
            return back()->withErrors(['error' => 'You cannot disable or suspend your own account.']);
        }
        if ($data['action'] === 'suspend' && empty($data['suspended_until'])) {
            return back()->withErrors(['suspended_until' => 'A suspension end date is required.']);
        }

        $before = ['is_active' => (bool) $user->is_active, 'suspended_until' => $user->suspended_until];

        match ($data['action']) {
            'enable' => (function () use ($user) {
                $user->is_active = true;
                $user->suspended_until = null;
                $user->save();
            })(),
            'disable' => (function () use ($user) {
                $user->is_active = false;
                $user->suspended_until = null;
                $user->save();
            })(),
            'suspend' => (function () use ($user, $data) {
                $user->is_active = false;
                $user->suspended_until = $data['suspended_until'];
                $user->save();
            })(),
            'restore' => (function () use ($user) {
                $user->is_active = true;
                $user->suspended_until = null;
                $user->save();
            })(),
        };

        $labels = [
            'enable' => 'account.enabled',
            'disable' => 'account.disabled',
            'suspend' => 'account.suspended',
            'restore' => 'account.restored',
        ];

        $this->logAction($user->id, $labels[$data['action']], match ($data['action']) {
            'enable' => "Account enabled for {$user->name}.",
            'disable' => "Account disabled for {$user->name} (blocked from all logins).",
            'suspend' => "Account suspended for {$user->name} until {$user->suspended_until}.",
            'restore' => "Account restored for {$user->name}.",
        }, ['before' => $before, 'after' => ['is_active' => (bool) $user->is_active, 'suspended_until' => $user->suspended_until]]);

        return back()->with('success', 'Account status updated.');
    }

    /**
     * Promote / demote position (and optionally move home module).
     */
    public function updatePosition(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'position' => 'required|in:' . implode(',', $this->assignablePositions),
            'role' => 'nullable|in:' . implode(',', $this->assignableRoles),
        ]);

        $user = User::findOrFail($data['user_id']);
        if ($user->role === 'CEO') {
            return back()->withErrors(['error' => 'CEO accounts cannot be modified here.']);
        }

        if ($data['position'] === 'secretary') {
            $exists = User::where('position', 'secretary')
                ->where('is_manufacturing_supervisor', 0)
                ->where('id', '!=', $user->id)
                ->first();
            if ($exists) {
                return back()->withErrors(['error' => "A secretary already exists ({$exists->name}). Only one is allowed."]);
            }
        }

        if ($data['position'] === 'manager') {
            $roleForCheck = $data['role'] ?? $user->role;
            $exists = User::where('position', 'manager')
                ->where('role', $roleForCheck)
                ->where('id', '!=', $user->id)
                ->first();
            if ($exists) {
                return back()->withErrors(['error' => "The {$roleForCheck} module already has a manager ({$exists->name})."]);
            }
        }

        $before = ['position' => $user->position, 'role' => $user->role];

        DB::transaction(function () use ($user, $data) {
            if (! empty($data['role'])) {
                $user->role = $data['role'];
            }
            $user->position = $data['position'];
            $user->save();

            if ($data['position'] === 'staff') {
                $user->moduleAccess()->delete();
                $user->pagePermissions()->delete();
            } elseif ($data['position'] === 'manager') {
                $user->pagePermissions()->delete();
            }
        });

        $this->logAction($user->id, 'position.updated',
            "Position updated for {$user->name}: {$before['position']} → {$user->position}" .
            ($before['role'] !== $user->role ? " ({$before['role']} → {$user->role})" : '') . '.',
            ['before' => $before, 'after' => ['position' => $user->position, 'role' => $user->role]]
        );

        return back()->with('success', 'Position updated.');
    }

    /**
     * Sync module grants (user_module_access).
     */
    public function updateModules(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'modules' => 'array',
            'modules.*' => 'in:' . implode(',', array_keys($this->moduleNames)),
        ]);

        $user = User::findOrFail($data['user_id']);
        if ($user->role === 'CEO') {
            return back()->withErrors(['error' => 'CEO accounts cannot be modified here.']);
        }

        $before = $user->moduleAccess()->pluck('module')->toArray();
        $modules = array_values(array_unique($data['modules'] ?? []));

        DB::transaction(function () use ($user, $modules) {
            $user->moduleAccess()->delete();
            foreach ($modules as $module) {
                UserModuleAccess::create([
                    'user_id' => $user->id,
                    'module' => $module,
                    'permission_level' => 'edit',
                    'granted_by' => auth()->id(),
                ]);
            }
        });

        $this->logAction($user->id, 'modules.updated',
            'Module grants updated for ' . $user->name . ': [' . implode(', ', $modules) . '].',
            ['before' => $before, 'after' => $modules]
        );

        return back()->with('success', 'Module grants updated.');
    }

    /**
     * Sync per-page view/edit grants across every module tab.
     */
    public function updatePages(Request $request)
    {
        $map = $this->modulePages();
        $validPairs = [];
        foreach ($map as $module => $pages) {
            foreach (array_keys($pages) as $page) {
                $validPairs[] = $module . '.' . strtolower($page);
            }
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'grants' => 'array',
            'grants.*.module' => 'required|string',
            'grants.*.page' => 'required|string',
            'grants.*.level' => 'required|in:view,edit',
        ]);

        $user = User::findOrFail($data['user_id']);
        if ($user->role === 'CEO') {
            return back()->withErrors(['error' => 'CEO accounts cannot be modified here.']);
        }

        $before = PagePermission::where('user_id', $user->id)
            ->get()
            ->map(fn ($pp) => $pp->module . '.' . $pp->page . ':' . strtolower($pp->permission_level ?? 'edit'))
            ->toArray();

        $summary = [];
        DB::transaction(function () use ($user, $data, $validPairs, &$summary) {
            $user->pagePermissions()->delete();
            foreach ($data['grants'] ?? [] as $grant) {
                $module = strtoupper($grant['module']);
                $page = strtolower($grant['page']);
                if (! in_array($module . '.' . $page, $validPairs, true)) {
                    continue;
                }
                PagePermission::create([
                    'user_id' => $user->id,
                    'module' => $module,
                    'page' => $page,
                    'permission_level' => $grant['level'],
                ]);
                $summary[] = "{$module}.{$page}:{$grant['level']}";
            }
        });

        $this->logAction($user->id, 'pages.updated',
            'Page permissions updated for ' . $user->name . ' (' . count($summary) . ' grants).',
            ['before' => $before, 'after' => $summary]
        );

        return back()->with('success', 'Page permissions updated.');
    }

    /**
     * Audit trail of every change made in IT Access Control.
     */
    public function logs(Request $request)
    {
        $query = ItAccessLog::with(['actor:id,name', 'target:id,name'])
            ->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('details', 'like', "%{$search}%")
                    ->orWhereHas('target', fn ($t) => $t->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('actor', fn ($a) => $a->where('name', 'like', "%{$search}%"));
            });
        }

        return Inertia::render('Dashboard/IT/Manager/AccessLogs', [
            'logs' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['action', 'search']),
            'actions' => ItAccessLog::ACTIONS,
        ]);
    }

    protected function logAction(int $targetUserId, string $action, string $details, array $meta = []): void
    {
        ItAccessLog::create([
            'actor_id' => auth()->id(),
            'target_user_id' => $targetUserId,
            'action' => $action,
            'details' => $details,
            'meta' => $meta,
            'ip_address' => request()->ip(),
        ]);
    }
}
