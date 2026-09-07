<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPagePermission
{
    /**
     * Handle an incoming request.
     *
     * NOTE: The old "view" vs "edit" permission-level distinction has been
     * removed. Any user who has been granted access to a page (or who
     * qualifies via one of the role-based shortcuts below) now gets full
     * access to that page — they can view AND make changes.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $page  The page identifier (e.g., 'dashboard', 'employees')
     * @param  string|null  $requiredLevel  Deprecated / unused — kept only so existing
     *                                      route definitions that still pass a second
     *                                      middleware parameter (e.g. 'edit') don't break.
     */
    public function handle(Request $request, Closure $next, string $page, ?string $requiredLevel = null): Response
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        // CEO bypasses all checks
        if ($user->role === 'CEO') {
            return $next($request);
        }

        // Determine the module from the route name or URI
        $module = $this->detectModule($request);

        // Module-native managers and staff automatically have full access
        if ($module && strtoupper($user->role) === $module) {
            if (in_array($user->position, ['manager', 'staff'])) {
                return $next($request);
            }
        }

        // Secretaries and General Managers have full access to their root module
        if (in_array($user->position, ['secretary', 'general_manager'])) {
            $rootModule = $this->getRootModuleForUser($user);
            if ($module && $rootModule && strtoupper($module) === $rootModule) {
                return $next($request);
            }
        }

        // Fallback: HRM staff can always view the dashboard (legacy)
        if ($page === 'dashboard' && $user->role === 'HRM' && $user->position === 'staff') {
            return $next($request);
        }

        if (!$module) {
            abort(403, 'Could not determine module for permission check.');
        }

        // Check whether the user has ANY permission record for this page.
        // Any granted permission (regardless of the old level field) now
        // means full access — view and edit.
        if (method_exists($user, 'pagePermissions')) {
            $hasPermission = $user->pagePermissions()
                ->where('module', $module)
                ->where('page', $page)
                ->exists();
        } else {
            $hasPermission = false;
        }

        if ($hasPermission) {
            return $next($request);
        }

        abort(403, "You do not have permission to access the '{$page}' page in the {$module} module.");
    }

    /**
     * Detect the module name from the request URI or route name.
     *
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    protected function detectModule(Request $request): ?string
    {
        $path = $request->path();
        $routeName = $request->route() ? $request->route()->getName() : '';

        // 1. Try route name (first segment before dot)
        if ($routeName) {
            $parts = explode('.', $routeName);
            $firstPart = strtoupper($parts[0] ?? '');
            if ($this->isValidModule($firstPart)) {
                return $firstPart;
            }
        }

        // 2. Check for /dashboard/{module} pattern
        if (preg_match('#^dashboard/(hrm|crm|man|log|eco|ord|scm|war|inv|pro|wrf|fin|proj|it|ceo)#i', $path, $matches)) {
            return strtoupper($matches[1]);
        }

        // 3. Check for direct module prefix (e.g., /hrm/...)
        if (preg_match('#^(hrm|crm|man|log|eco|ord|scm|war|inv|pro|wrf|fin|proj|it|ceo)/#i', $path, $matches)) {
            return strtoupper($matches[1]);
        }

        // 4. Special case for CEO access routes
        if (str_starts_with($path, 'dashboard/ceo')) {
            return 'CEO';
        }

        return null;
    }

    /**
     * Check if a string is a valid module identifier.
     *
     * @param string $module
     * @return bool
     */
    protected function isValidModule(string $module): bool
    {
        $validModules = [
            'HRM', 'CRM', 'MAN', 'LOG', 'ECO', 'ORD', 'SCM',
            'WAR', 'INV', 'PRO', 'WRF', 'FIN', 'PROJ', 'IT', 'CEO'
        ];
        return in_array($module, $validModules);
    }

    /**
     * Get the root module for a secretary or general manager.
     * Mirrors the logic in CeoAccessController.
     *
     * @param \App\Models\User $user
     * @return string|null
     */
    protected function getRootModuleForUser($user): ?string
    {
        $coreModules = ['HRM', 'CRM', 'MAN', 'LOG'];

        if ($user->is_manufacturing_supervisor) {
            return 'MAN';
        }

        $roleUpper = strtoupper($user->role);
        foreach ($coreModules as $core) {
            if (str_contains($roleUpper, $core)) {
                return $core;
            }
        }

        return null;
    }
}