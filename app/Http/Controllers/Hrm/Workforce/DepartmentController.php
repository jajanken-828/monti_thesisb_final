<?php

namespace App\Http\Controllers\Hrm\Workforce;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Hrm\HrmDepartment;
use App\Models\Hrm\HrmEmploymentType;
use App\Models\Hrm\HrmPosition;
use App\Models\Hrm\HrmRole;
use App\Traits\HasPagePermissions;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    use HasPagePermissions;

    public function index(Request $request)
    {
        $q = HrmDepartment::withCount(['positions as open_positions' => function ($qq) {
            $qq->where('status', 'active');
        }])->with('head');

        if ($s = $request->get('search')) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
        }
        if ($request->get('status') === 'archived') {
            $q->whereNotNull('archived_at');
        } else {
            $q->whereNull('archived_at');
        }

        return Inertia::render('Dashboard/HRM_NEW/Department', [
            'departmentsData' => $q->latest()->paginate(12)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
            'metrics' => [
                'active' => HrmDepartment::whereNull('archived_at')->count(),
                'archived' => HrmDepartment::whereNotNull('archived_at')->count(),
                'totalEmployees' => User::where('is_active', true)->count(),
                'totalOpenPositions' => HrmPosition::where('status', 'active')->sum('approved_headcount'),
                'totalProjects' => 0,
            ],
            'positions' => HrmPosition::where('status', 'active')->select('id', 'name')->get(),
            // Canonical module codes (HRM, CRM, …) drive the dropdown even
            // when no department row uses them yet; union with any custom
            // categories already stored so nothing disappears.
            'categories' => HrmRole::where('is_active', true)->orderBy('code')->pluck('code')
                ->merge(HrmDepartment::distinct()->pluck('category'))
                ->filter()->unique()->values()->map(fn ($c) => ['value' => $c])->all(),
            'permissions' => $this->getPagePermissionsForModule('HRM'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:30|unique:hrm_departments,code',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:60',
            'description' => 'nullable|string',
            'head_id' => 'nullable|exists:hrm_positions,id',
            'status' => 'nullable|in:active,inactive',
        ]);
        $data['created_by'] = $request->user()->id;
        HrmDepartment::create($data);

        return back()->with('success', 'Department created.');
    }

    public function update(Request $request, HrmDepartment $department)
    {
        $data = $request->validate([
            'code' => 'sometimes|string|max:30|unique:hrm_departments,code,' . $department->id,
            'name' => 'sometimes|string|max:255',
            'category' => 'nullable|string|max:60',
            'description' => 'nullable|string',
            'head_id' => 'nullable|exists:hrm_positions,id',
            'status' => 'nullable|in:active,inactive',
        ]);
        $department->update($data);

        return back()->with('success', 'Department updated.');
    }

    public function archive(HrmDepartment $department)
    {
        $department->update(['archived_at' => now(), 'status' => 'inactive']);

        return back()->with('success', 'Department archived.');
    }

    public function reactivate(HrmDepartment $department)
    {
        $department->update(['archived_at' => null, 'status' => 'active']);

        return back()->with('success', 'Department reactivated.');
    }

    public function export(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');

        return response()->streamDownload(function () use ($search, $status) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['code', 'name', 'category', 'status']);
            $q = HrmDepartment::query();
            if ($search) {
                $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            }
            if ($status === 'archived') {
                $q->whereNotNull('archived_at');
            } elseif ($status === 'active') {
                $q->whereNull('archived_at');
            }
            $q->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $d) {
                    fputcsv($out, [$d->code, $d->name, $d->category, $d->archived_at ? 'archived' : $d->status]);
                }
            });
            fclose($out);
        }, 'departments.csv');
    }
}
