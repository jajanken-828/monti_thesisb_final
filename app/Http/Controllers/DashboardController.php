<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Logistics\Driver;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = strtoupper($user->role);
        $position = strtolower($user->position);

        // ---- Redirect drivers and conductors to their portals ----
        if ($role === 'LOG' && $position === 'staff') {
            $isDriver = DB::table('drivers')->where('user_id', $user->id)->exists();
            $isConductor = DB::table('conductors')->where('user_id', $user->id)->exists();

            if (!$isDriver && !$isConductor && property_exists($user, 'log_role')) {
                if ($user->log_role === 'driver') $isDriver = true;
                if ($user->log_role === 'conductor') $isConductor = true;
            }

            // Auto-create driver record if user has log_role = 'driver' but no driver record
            if (!$isDriver && !$isConductor && property_exists($user, 'log_role') && $user->log_role === 'driver') {
                Driver::create([
                    'user_id' => $user->id,
                    'license_number' => 'DRV-' . strtoupper(uniqid()),
                    'is_available' => true,
                ]);
                $isDriver = true;
            }

            if ($isDriver) {
                return redirect()->route('logistics.driver.portal');
            }
            if ($isConductor) {
                return redirect()->route('logistics.conductor.portal');
            }
        }

        // ---- Trainee redirection ----
        if ($position === 'trainee') {
            return Inertia::render('Dashboard/TRAINEE/index', [
                'user' => $user,
                'stats' => [
                    'progress' => 45,
                    'assigned_modules' => 5,
                    'days_remaining' => 12,
                ],
            ]);
        }

        // ---- Secretary redirect (own executive workspace) ----
        if ($position === 'secretary') {
            return redirect()->route('secretary.dashboard');
        }

        // ---- Vice President redirect (own execution workspace) ----
        if ($user->position === 'vice_president') {
            return redirect()->route('vp.operations');
        }

        // ---- CEO redirect ----
        if ($role === 'CEO') {
            return redirect()->route('ceo.dashboard');
        }

        // ---- Role & position to route mapping ----
        // Define the route name for each combination of role and position.
        // All route names must exist in web.php.
        $routeMap = [
            'HRM' => [
                'manager' => 'hrm.dashboard',
                'staff'   => 'hrm.dashboard',
                // secretaries/general managers are handled below
            ],
            'CRM' => [
                'manager' => 'crm.dashboard',
                'staff'   => 'crm.dashboard',
            ],
            'SCM' => [
                'manager' => 'scm.sales-orders',   // No separate dashboard, use sales orders
                'staff'   => 'scm.sales-orders',
            ],
            'FIN' => [
                'manager' => 'fin.manager.dashboard',
                'staff'   => 'fin.employee.dashboard',
            ],
            'MAN' => [
                'manager' => 'man.manager.dashboard',
                'staff'   => 'man.employee.dashboard', // this exists as a redirect to role-specific
            ],
            'INV' => [
                'manager' => 'inv.dashboard',
                'staff'   => 'inv.dashboard', // staff share the same dashboard
            ],
            'ORD' => [
                'manager' => 'ord.orders',
                'staff'   => 'ord.orders',
            ],
            'WAR' => [
                'manager' => 'warehouse.index',
                'staff'   => 'warehouse.index',
            ],
            'ECO' => [
                'manager' => 'eco.dashboard',
                'staff'   => 'eco.dashboard',
            ],
            'PRO' => [
                'manager' => 'pro.manager.dashboard',
                'staff'   => 'pro.manager.dashboard', // staff may not have a separate dashboard; adjust if needed
            ],
            'PROJ' => [
                'manager' => 'proj.manager.dashboard',
                'staff'   => 'proj.employee.dashboard',
            ],
            'IT' => [
                'manager' => 'it.dashboard',
                'staff'   => 'it.dashboard',
            ],
            'LOG' => [
                'manager' => 'logistics.dashboard',
                'staff'   => 'logistics.dashboard', // staff (non-driver/conductor) go to logistics dashboard
            ],
        ];

        // Check for secretary or special_officer positions – they might have granted modules,
        // but we'll redirect to the dashboard of their primary role if set, else to the generic dashboard.
        if (in_array($position, ['secretary', 'special_officer'])) {
            // If the user has a module access grant, we could redirect to that module's main page.
            // For simplicity, redirect to the root dashboard (or to a default).
            // But we can also use the role mapping if they have a primary role.
            if (isset($routeMap[$role])) {
                $default = $routeMap[$role]['manager'] ?? $routeMap[$role]['staff'] ?? null;
                if ($default) {
                    return redirect()->route($default);
                }
            }
            // Fallback for secretaries/GMs: go to CEO dashboard? Or generic dashboard view.
            return redirect()->route('dashboard'); // This route may not exist; better to use a fallback view.
        }

        // Normal manager/staff redirection
        if (isset($routeMap[$role]) && isset($routeMap[$role][$position])) {
            return redirect()->route($routeMap[$role][$position]);
        }

        // ---- Fallback if no mapping found ----
        // Render a generic Inertia dashboard (if you have one) or redirect to home.
        return Inertia::render('Dashboard', [
            'stats' => [
                'total_tasks' => 0,
                'pending_tasks' => 0,
                'completed_tasks' => 0,
            ],
            'user' => $user,
        ]);
    }
}