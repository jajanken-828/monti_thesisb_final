<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        // NOTE: A previous revision skipped this gate entirely when
        // APP_ENV=local, which silently disabled module authorization on any
        // deployment running with that env value. The bypass has been removed:
        // authorization is now enforced in every environment. For local
        // development seeding, grant access via user_module_access rows instead.
        $user = Auth::user();

        if (! $user) {
            abort(403, 'Unauthorized');
        }

        // ── Admin has access to everything ──────────────────────────────────
        if (in_array($user->role, ['admin'])) {
            return $next($request);
        }

        // ── Secretary or Special Officer: check granted_modules ───────────────
        // With no grants saved yet, they fall back to their home (root)
        // module — mirrors User::canAccessModule(). Once the CEO assigns
        // explicit grants, only those modules are allowed.
        if (in_array($user->position, ['secretary', 'special_officer'])) {
            $granted = $user->moduleAccess->pluck('module')->toArray();
            if (empty($granted) && $user->role === $module) {
                return $next($request);
            }
            if (in_array($module, $granted)) {
                return $next($request);
            }
            abort(403, "You don't have access to the {$module} module.");
        }

        // ── Manufacturing Supervisor ──────────────────────────────────────────
        // Supervisors always have implicit access to the MAN module because
        // their entire role revolves around it. For any OTHER module they
        // must still have an explicit grant.
        if ($user->is_manufacturing_supervisor) {
            if ($module === 'MAN') {
                return $next($request);
            }

            // Non-MAN modules require an explicit grant
            $granted = $user->moduleAccess->pluck('module')->toArray();
            if (in_array($module, $granted)) {
                return $next($request);
            }

            abort(403, "You don't have access to the {$module} module.");
        }

        // ── Regular manager: role must match module name ──────────────────────
        if ($user->role === $module && $user->position === 'manager') {
            return $next($request);
        }

        // ── Staff: role must match module name ────────────────────────────────
        // e.g. a MAN staff member has role = 'MAN' and position = 'staff'
        if ($user->role === $module && $user->position === 'staff') {
            return $next($request);
        }

        abort(403, "You don't have access to the {$module} module.");
    }
}