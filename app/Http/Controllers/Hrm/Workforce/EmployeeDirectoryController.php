<?php

namespace App\Http\Controllers\Hrm\Workforce;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Hrm\Applicant;
use App\Models\Hrm\AttendanceLog;
use App\Models\Hrm\HrmCertification;
use App\Models\Hrm\HrmDepartment;
use App\Models\Hrm\HrmEmploymentType;
use App\Models\Hrm\HrmLeaveBalance;
use App\Models\Hrm\HrmLeaveType;
use App\Models\Hrm\HrmPosition;
use App\Models\Hrm\HrmTrainingEnrollment;
use App\Models\Hrm\Payroll;
use App\Traits\HasPagePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Employee Master Records — whole-company directory (all roles).
 * Every user row becomes a full master record; org/contact chapters are
 * editable here, role/position elevation stays on Hrm\EmployeeController.
 */
class EmployeeDirectoryController extends Controller
{
    use HasPagePermissions;

    public function index(Request $request)
    {
        $q = User::with(['hrmDepartment', 'hrmOrgPosition.reportsTo', 'hrmEmploymentType', 'hrmRole']);

        if ($s = $request->get('search')) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('employee_id', 'like', "%{$s}%"));
        }
        if ($d = $request->get('department')) {
            $q->where(fn ($w) => $w->where('hrm_department_id', $d)->orWhere('role', $d)->orWhere('department', $d));
        }
        if ($r = $request->get('role')) {
            $q->where('role', $r);
        }
        if ($request->get('status') === 'inactive') {
            $q->where('is_active', false);
        } elseif ($request->get('status') === 'active') {
            $q->where('is_active', true);
        }

        $paginated = $q->orderBy('name')->paginate(20)->withQueryString();
        $employees = collect($paginated->items())->filter(fn ($u) => $u !== null)
            ->map(fn ($u) => $this->record($u))->values()->all();

        $deptCounts = User::selectRaw('role, count(*) as c')->groupBy('role')->pluck('c', 'role');

        return Inertia::render('Dashboard/HRM_NEW/Employee', [
            'employees' => $employees,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
            'stats' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'inactive' => User::where('is_active', false)->count(),
                'byDepartment' => $deptCounts,
            ],
            'filters' => $request->only(['search', 'department', 'role', 'status']),
            'departments' => HrmDepartment::whereNull('archived_at')->select('id', 'code', 'name')->get(),
            'positions' => HrmPosition::whereNull('archived_at')->select('id', 'name', 'department_id')->get(),
            'employmentTypes' => HrmEmploymentType::where('is_active', true)->select('id', 'name')->get(),
            'roles' => HrmDepartment::whereNull('archived_at')->pluck('category')->filter()->unique()->values()->all(),
            'permissions' => $this->getPagePermissionsForModule('HRM'),
        ]);
    }

    public function update(Request $request, User $employee)
    {
        $data = $request->validate([
            'first_name' => 'nullable|string|max:255', 'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255', 'suffix' => 'nullable|string|max:30',
            'nickname' => 'nullable|string|max:60', 'preferred_name' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:20', 'date_of_birth' => 'nullable|date',
            'civil_status' => 'nullable|string|max:20', 'nationality' => 'nullable|string|max:60',
            'personal_email' => 'nullable|email|max:255', 'mobile_number' => 'nullable|string|max:40',
            'telephone' => 'nullable|string|max:40',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_number' => 'nullable|string|max:40',
            'current_address' => 'nullable|string', 'permanent_address' => 'nullable|string',
            'country' => 'nullable|string|max:60', 'province' => 'nullable|string|max:60',
            'city' => 'nullable|string|max:60', 'postal_code' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255', 'branch' => 'nullable|string|max:255',
            'business_unit' => 'nullable|string|max:255', 'department' => 'nullable|string|max:255',
            'cost_center' => 'nullable|string|max:60', 'work_location' => 'nullable|string|max:255',
            'department_head' => 'nullable|string|max:255', 'immediate_supervisor' => 'nullable|string|max:255',
            'payroll_group' => 'nullable|string|max:60', 'salary_grade' => 'nullable|string|max:30',
            'basic_salary' => 'nullable|numeric|min:0', 'bank_account' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:60', 'sss_id' => 'nullable|string|max:60',
            'work_schedule' => 'nullable|string|max:255', 'shift' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:20', 'biometrics_id' => 'nullable|string|max:60',
            'join_date' => 'nullable|date', 'probation_end_date' => 'nullable|date',
            'regularization_date' => 'nullable|date', 'resignation_date' => 'nullable|date',
            'last_working_day' => 'nullable|date',
            'hrm_department_id' => 'nullable|exists:hrm_departments,id',
            'hrm_position_id' => 'nullable|exists:hrm_positions,id',
            'hrm_employment_type_id' => 'nullable|exists:hrm_employment_types,id',
            'education' => 'nullable|string', 'certifications' => 'nullable|string',
            'skills' => 'nullable|string', 'languages' => 'nullable|string|max:255',
            'trainings' => 'nullable|string',
        ]);

        $employee->update($data);
        // Keep the display name in sync with name parts.
        if (array_intersect_key($data, array_flip(['first_name', 'middle_name', 'last_name']))) {
            $employee->update(['name' => trim(($employee->first_name ?? '') . ' ' . ($employee->middle_name ? $employee->middle_name . ' ' : '') . ($employee->last_name ?? '')) ?: $employee->name]);
        }

        return back()->with('success', 'Master record updated.');
    }

    public function export()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['employee_id', 'name', 'email', 'role', 'position', 'department', 'status', 'join_date']);
            User::orderBy('name')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $u) {
                    fputcsv($out, [
                        $u->employee_id ?: ('EMP-' . $u->id), $u->name, $u->email,
                        $u->role, $u->position,
                        $u->hrmDepartment?->name ?? $u->department ?? $u->role,
                        $u->is_active ? 'Active' : 'Inactive', $u->join_date,
                    ]);
                }
            });
            fclose($out);
        }, 'employee-master.csv');
    }

    private function record(User $u): array
    {
        $deptName = $u->hrmDepartment?->name ?? $u->department ?? $u->role;
        $posName = $u->hrmOrgPosition?->name ?? ucfirst((string) $u->position);
        $typeName = $u->hrmEmploymentType?->name ?? 'Regular';
        $year = (int) now()->year;

        $balances = HrmLeaveBalance::with('leaveType')->where('user_id', $u->id)->where('year', $year)->get();
        if ($balances->isEmpty()) {
            $balances = HrmLeaveType::where('is_active', true)->get()->map(fn ($t) => (object) [
                'leaveType' => $t, 'entitled' => $t->default_days, 'used' => 0,
            ]);
        }
        $bal = fn ($code) => collect($balances)->first(
            fn ($b) => strtolower($b->leaveType?->code ?? '') === $code
        );

        $monthStart = now()->startOfMonth()->toDateString();
        $att = AttendanceLog::where('user_id', $u->id)->where('date', '>=', $monthStart)->get();
        $payroll = Payroll::where('employee_id', $u->id)->latest()->first();
        $applicant = Applicant::where('email', $u->email)->with('documents')->first();
        $certs = HrmCertification::where('user_id', $u->id)->pluck('name')->all();
        $enrolled = HrmTrainingEnrollment::with('training')->where('user_id', $u->id)->get()
            ->map(fn ($e) => $e->training?->name)->filter()->values()->all();

        return [
            'id' => $u->id,
            'employeeId' => $u->employee_id ?: ('EMP-' . str_pad((string) $u->id, 4, '0', STR_PAD_LEFT)),
            'firstName' => $u->first_name ?? strtok((string) $u->name, ' '),
            'middleName' => $u->middle_name ?? '',
            'lastName' => $u->last_name ?? '',
            'suffix' => $u->suffix ?? '',
            'nickname' => $u->nickname ?? '',
            'preferredName' => $u->preferred_name ?? $u->name,
            'gender' => $u->gender ?? '',
            'dateOfBirth' => $u->date_of_birth,
            'civilStatus' => $u->civil_status ?? '',
            'nationality' => $u->nationality ?? 'Filipino',
            'profilePicture' => $u->profile_photo_path ? Storage::url($u->profile_photo_path) : '',
            'personalEmail' => $u->personal_email ?? '',
            'companyEmail' => $u->email,
            'mobileNumber' => $u->mobile_number ?? '',
            'telephone' => $u->telephone ?? '',
            'emergencyContact' => $u->emergency_contact_name ?? '',
            'emergencyContactNumber' => $u->emergency_contact_number ?? '',
            'currentAddress' => $u->current_address ?? '',
            'permanentAddress' => $u->permanent_address ?? '',
            'country' => $u->country ?? 'Philippines',
            'provinceState' => $u->province ?? '',
            'city' => $u->city ?? '',
            'postalCode' => $u->postal_code ?? '',
            'company' => $u->company ?? 'MontiTextile',
            'branch' => $u->branch ?? '',
            'businessUnit' => $u->business_unit ?? '',
            'department' => $deptName,
            'departmentId' => $u->hrm_department_id,
            'position' => $posName,
            'orgPositionId' => $u->hrm_position_id,
            'employmentType' => $typeName,
            'employmentTypeId' => $u->hrm_employment_type_id,
            'employeeStatus' => $u->is_active ? 'Active' : 'Inactive',
            'role' => $u->role,
            'positionRank' => $u->position,
            'dateHired' => $u->join_date,
            'probationEndDate' => $u->probation_end_date,
            'regularizationDate' => $u->regularization_date,
            'resignationDate' => $u->resignation_date,
            'lastWorkingDay' => $u->last_working_day,
            'reportsTo' => $u->hrmOrgPosition?->reportsTo?->name ?? $u->immediate_supervisor ?? $u->department_head ?? '',
            'immediateSupervisor' => $u->immediate_supervisor ?? '',
            'departmentHead' => $u->department_head ?? '',
            'costCenter' => $u->cost_center ?? '',
            'workLocation' => $u->work_location ?? '',
            'payrollGroup' => $u->payroll_group ?? '',
            'salaryGrade' => $u->salary_grade ?? '',
            'basicSalary' => $u->basic_salary !== null ? '₱' . number_format((float) $u->basic_salary, 2) : ($payroll?->base_salary ? '₱' . number_format((float) $payroll->base_salary, 2) : ''),
            'basicSalaryRaw' => $u->basic_salary,
            'bankAccount' => $u->bank_account ?? '',
            'taxID' => $u->tax_id ?? '',
            'ssn' => $u->sss_id ?? '',
            'workSchedule' => $u->work_schedule ?? '',
            'shift' => $u->shift ?? '',
            'timeZone' => $u->timezone ?? 'GMT+8',
            'biometricsID' => $u->biometrics_id ?? '',
            'vacationLeaveBalance' => (string) max(0, (int) ($bal('vacation')?->entitled ?? 12) - (int) ($bal('vacation')?->used ?? 0)),
            'sickLeaveBalance' => (string) max(0, (int) ($bal('sick')?->entitled ?? 15) - (int) ($bal('sick')?->used ?? 0)),
            'otherLeaveBalance' => '5',
            'attendanceMonth' => [
                'present' => $att->whereIn('status', ['On-Time', 'Present'])->count(),
                'late' => $att->where('status', 'Late')->count(),
                'absent' => $att->where('status', 'Absent')->count(),
            ],
            'education' => $u->education ?? '',
            'certifications' => $u->certifications ?? implode(', ', $certs),
            'skills' => $u->skills ?? '',
            'languages' => $u->languages ?? '',
            'trainings' => $u->trainings ?? implode(', ', $enrolled),
            'documents' => ($applicant?->documents ?? collect())->map(fn ($d) => [
                'name' => $d->original_name ?: basename($d->file_path),
                'type' => $d->type, 'size' => '', 'url' => Storage::url($d->file_path),
            ])->values()->all(),
            'auditTrail' => $u->auditLogs()->latest()->take(5)->get()->map(fn ($a) => [
                'date' => optional($a->created_at)->format('F d, Y'),
                'title' => ucfirst((string) $a->action) . ' — ' . ($a->target_name ?? $u->name),
                'desc' => $a->reason ?? '',
            ])->values()->all(),
        ];
    }
}
