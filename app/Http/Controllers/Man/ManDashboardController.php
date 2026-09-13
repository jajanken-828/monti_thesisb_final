<?php

namespace App\Http\Controllers\Man;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ManDashboardController extends Controller
{
    public function staffDashboard()
    {
        $user = auth()->user();

        // Manufacturing supervisor → department overview dashboard
        // (no manufacturing manager; each supervisor sees their own department)
        if ($user->is_manufacturing_supervisor) {
            return Redirect::route('man.manager.dashboard');
        }

        // Normal staff (single role)
        $role = $user->manufacturing_role;

        $routes = [
            'knitting_yarn' => 'man.staff.knitting-yarn.dashboard',
            'knitting_mechanic' => 'man.staff.knitting-mechanic.dashboard',
            'dyeing_color' => 'man.staff.dyeing-color.dashboard',
            'dyeing_fabric_softener' => 'man.staff.dyeing-fabric-softener.dashboard',
            'dyeing_squeezer' => 'man.staff.dyeing-squeezer.dashboard',
            'dyeing_ironing' => 'man.staff.dyeing-ironing.dashboard',
            'dyeing_packaging' => 'man.staff.dyeing-packaging.dashboard',
            'dyeing_lab_chemist' => 'man.staff.dyeing-lab-chemist.dashboard',
            'maintenance_checker' => 'man.staff.maintenance-checker.dashboard',
            'boiler_operator' => 'man.staff.boiler-operator.dashboard',
            'pollution_control_operator' => 'man.staff.pollution-control.dashboard',
            'safety_officer' => 'man.staff.safety-officer.dashboard',
            'checker_quality' => 'man.staff.checker-quality.dashboard',
        ];

        if (isset($routes[$role])) {
            return Redirect::route($routes[$role]);
        }

        return Inertia::render('Dashboard/MAN/Employee/Index');
    }
}