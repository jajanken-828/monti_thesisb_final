<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware alias: man.role
 *
 * Controls granular access to manufacturing STAFF sub-pages.
 * Quality Checker (checker_quality) is NO LONGER here — it was moved to
 * manager-level and is now protected by the `can.access.man.manager` middleware.
 *
 * Access rules (in priority order):
 *  1. CEO / secretary / general_manager → always allowed
 *  2. MAN manager (role=MAN, position=manager) → allowed on all staff pages (oversight)
 *  3. Manufacturing supervisor (is_manufacturing_supervisor=true) → allowed on all pages
 *     whose role slug belongs to their assigned supervisor_department
 *  4. Regular staff → allowed only if their manufacturing_role exactly matches the required $role
 */
class CheckManufacturingRoleAccess
{
    /**
     * Maps each supervisor_department value to the manufacturing_role slugs it covers.
     *
     * Note: checker_quality has been moved to manager level (can.access.man.manager)
     * and is no longer in this map.
     *
     * Keep this in sync with $manufacturingRoleLabels in CeoAccessController.
     */
    protected array $departmentRoles = [
        'knitting' => [
            'knitting_yarn',
        ],
        'dyeing' => [
            'dyeing_color',
            'dyeing_fabric_softener',
            'dyeing_squeezer',
            'dyeing_ironing',
            'dyeing_packaging',
        ],
        'maintenance' => [
            'maintenance_checker',
        ],
    ];

    /**
     * Handle an incoming request.
     *
     * @param  string  $role  The manufacturing_role slug required for this route
     *                        e.g. 'dyeing_color', 'dyeing_ironing', 'knitting_yarn', etc.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        // ── 1. Elevated roles always have full access ─────────────────────────
        if (
            $user->role === 'CEO'
            || in_array($user->position, ['secretary', 'general_manager'])
        ) {
            return $next($request);
        }

        // ── 2. Manufacturing manager has oversight access to all staff pages ──
        if ($user->role === 'MAN' && $user->position === 'manager') {
            return $next($request);
        }

        // ── 3. Manufacturing supervisor ───────────────────────────────────────
        // A supervisor of a given department can access every staff-role page
        // that belongs to that department.
        if ($user->is_manufacturing_supervisor) {
            $dept         = $user->supervisor_department; // e.g. 'dyeing'
            $allowedRoles = $this->departmentRoles[$dept] ?? [];

            if (in_array($role, $allowedRoles, true)) {
                return $next($request);
            }

            abort(403, "You are not authorized to access the {$role} section. "
                . "Your supervised department is '{$dept}'.");
        }

        // ── 4. Regular staff — exact role match ───────────────────────────────
        if ($user->manufacturing_role === $role) {
            return $next($request);
        }

        abort(403, "You are not authorized to access {$role}.");
    }
}