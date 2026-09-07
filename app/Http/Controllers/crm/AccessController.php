<?php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use App\Models\core\User;
use App\Models\core\PagePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Traits\HasPagePermissions;

class AccessController extends Controller
{
    use HasPagePermissions;

    public function index()
    {
        $user = Auth::user();

        // Allow CRM managers OR CEO
        if (!in_array($user->role, ['CRM', 'CEO']) || ($user->role === 'CRM' && $user->position !== 'manager')) {
            abort(403, 'Only CRM managers or CEO can manage access.');
        }

        $module = 'CRM';

        // Get all CRM users (both managers and staff) – CEO excluded from list
        $users = User::where('role', 'CRM')
            ->with('pagePermissions')
            ->get()
            ->map(fn ($u) => [
                'id'          => $u->id,
                'name'        => $u->name,
                'role'        => $u->role,
                'position'    => $u->position,
                'permissions' => $u->pagePermissions
                    ->where('module', $module)
                    ->map(fn ($perm) => [
                        'page' => $perm->page,
                        'permission_level' => $perm->permission_level ?? 'edit',
                    ])
                    ->values(),
            ]);

        // ✅ Use 'customer_profiles' (matches the sidebar and database)
        $pages = ['dashboard', 'leads', 'interviews', 'trainees', 'approvals', 'customer_profiles', 'investigation', 'access'];

        $currentUserPermissions = $this->getPagePermissionsForModule($module);

        return Inertia::render('Dashboard/CRM/Access', [
            'users' => $users,
            'pages' => $pages,
            'permissions' => $currentUserPermissions,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        // Same permission check for update
        if (!in_array($user->role, ['CRM', 'CEO']) || ($user->role === 'CRM' && $user->position !== 'manager')) {
            abort(403, 'Only CRM managers or CEO can manage access.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'pages'   => 'required|array',
            'pages.*.page'       => 'required|string',
            'pages.*.permission' => 'required|in:view,edit',
        ]);

        $module = 'CRM';

        // Ensure the target user is a CRM user
        $targetUser = User::findOrFail($request->user_id);
        if ($targetUser->role !== 'CRM') {
            return back()->with('error', 'Can only assign permissions to CRM users.');
        }

        // ✅ FIX: Always ensure 'dashboard' is present (fallback to prevent total lockout)
        $submittedPages = $request->pages;
        $hasDashboard = collect($submittedPages)->contains(fn($p) => $p['page'] === 'dashboard');
        if (!$hasDashboard) {
            $submittedPages[] = ['page' => 'dashboard', 'permission' => 'view'];
        }

        DB::transaction(function () use ($request, $targetUser, $module, $submittedPages) {
            PagePermission::where('user_id', $request->user_id)
                ->where('module', $module)
                ->delete();

            foreach ($submittedPages as $pageData) {
                $pagePerm = new PagePermission();
                $pagePerm->user_id = $targetUser->id;
                $pagePerm->module = $module;
                $pagePerm->page = $pageData['page'];
                $pagePerm->permission_level = $pageData['permission'];
                $pagePerm->save();
            }
        });

        return back()->with('message', 'Permissions updated successfully.');
    }
}