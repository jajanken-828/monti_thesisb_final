<?php

namespace App\Http\Controllers\Hrm\Workforce;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Hrm\HrmDepartment;
use App\Models\Hrm\HrmPosition;
use App\Traits\HasPagePermissions;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PositionController extends Controller
{
    use HasPagePermissions;

    public function index(Request $request)
    {
        $q = HrmPosition::with(['department', 'reportsTo']);
        if ($s = $request->get('search')) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
        }
        if ($d = $request->get('department')) {
            $q->where('department_id', $d);
        }
        if ($request->get('status') === 'archived') {
            $q->whereNotNull('archived_at');
        } else {
            $q->whereNull('archived_at');
        }

        $positions = $q->latest()->paginate(12)->withQueryString();
        // Attach filled counts (employees holding each org position)
        $positions->getCollection()->transform(function ($p) {
            $p->filled_positions = User::where('hrm_position_id', $p->id)->count();
            $p->department_name = $p->department?->name;
            $p->reports_to_name = $p->reportsTo?->name;
            return $p;
        });

        return Inertia::render('Dashboard/HRM_NEW/Position', [
            'positionsData' => $positions,
            'filters' => $request->only(['search', 'department', 'status']),
            'filterOptions' => [
                'departments' => HrmDepartment::select('id', 'name')->get(),
                'managementLevels' => ['Executive', 'Managerial', 'Supervisory', 'Individual Contributor', 'Trainee'],
                'rankOptions' => range(1, 10),
            ],
            'metrics' => [
                'active' => HrmPosition::whereNull('archived_at')->count(),
                'archived' => HrmPosition::whereNotNull('archived_at')->count(),
                'totalApproved' => (int) HrmPosition::sum('approved_headcount'),
                'totalFilled' => (int) User::whereNotNull('hrm_position_id')->count(),
                'totalVacant' => max(0, (int) HrmPosition::sum('approved_headcount') - (int) User::whereNotNull('hrm_position_id')->count()),
            ],
            'departments' => HrmDepartment::select('id', 'name')->get(),
            'allPositions' => HrmPosition::select('id', 'name', 'department_id')->get(),
            'permissions' => $this->getPagePermissionsForModule('HRM'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:30|unique:hrm_positions,code',
            'name' => 'required|string|max:255',
            'department_id' => 'nullable|exists:hrm_departments,id',
            'reports_to_id' => 'nullable|exists:hrm_positions,id',
            'management_level' => 'nullable|string|max:60',
            'organization_level' => 'nullable|integer|min:1|max:5',
            'rank' => 'nullable|integer|min:1|max:10',
            'description' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'qualifications' => 'nullable|string',
            'required_skills' => 'nullable|string',
            'required_training' => 'nullable|string',
            'required_certifications' => 'nullable|string',
            'required_equipment' => 'nullable|string',
            'salary_min' => 'nullable|integer|min:0',
            'salary_max' => 'nullable|integer|min:0',
            'overtime_eligible' => 'nullable|boolean',
            'approved_headcount' => 'nullable|integer|min:1',
            'status' => 'nullable|in:active,inactive',
        ]);
        HrmPosition::create($data);

        return back()->with('success', 'Position created.');
    }

    public function update(Request $request, HrmPosition $position)
    {
        $data = $request->validate([
            'code' => 'sometimes|string|max:30|unique:hrm_positions,code,' . $position->id,
            'name' => 'sometimes|string|max:255',
            'department_id' => 'nullable|exists:hrm_departments,id',
            'reports_to_id' => 'nullable|exists:hrm_positions,id',
            'management_level' => 'nullable|string|max:60',
            'organization_level' => 'nullable|integer|min:1|max:5',
            'rank' => 'nullable|integer|min:1|max:10',
            'description' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'qualifications' => 'nullable|string',
            'required_skills' => 'nullable|string',
            'required_training' => 'nullable|string',
            'required_certifications' => 'nullable|string',
            'required_equipment' => 'nullable|string',
            'salary_min' => 'nullable|integer|min:0',
            'salary_max' => 'nullable|integer|min:0',
            'overtime_eligible' => 'nullable|boolean',
            'approved_headcount' => 'nullable|integer|min:1',
            'status' => 'nullable|in:active,inactive',
        ]);
        $position->update($data);

        return back()->with('success', 'Position updated.');
    }

    public function destroy(HrmPosition $position)
    {
        $position->delete();

        return back()->with('success', 'Position deleted.');
    }

    public function archive(HrmPosition $position)
    {
        $position->update(['archived_at' => now(), 'status' => 'inactive']);

        return back()->with('success', 'Position archived.');
    }

    public function reactivate(HrmPosition $position)
    {
        $position->update(['archived_at' => null, 'status' => 'active']);

        return back()->with('success', 'Position reactivated.');
    }

    public function employees(HrmPosition $position)
    {
        return response()->json([
            'employees' => User::where('hrm_position_id', $position->id)->select('id', 'name', 'email')->paginate(10),
            'total' => User::where('hrm_position_id', $position->id)->count(),
        ]);
    }
}
