<?php

namespace App\Http\Controllers\Hrm\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Applicant;
use App\Models\Hrm\ApplicantStatusHistory;
use App\Models\Hrm\HrmDepartment;
use App\Models\Hrm\HrmOnboarding;
use App\Models\Hrm\HrmOnboardingActivity;
use App\Models\Hrm\HrmOnboardingItem;
use App\Models\Hrm\HrmOnboardingNote;
use App\Models\Hrm\HrmOnboardingTemplate;
use App\Models\Core\User;
use App\Traits\HasPagePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class StatusController extends Controller
{
    use HasPagePermissions;

    public function index(Request $request)
    {
        $q = HrmOnboarding::query();
        if ($s = $request->get('search')) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
        }
        if ($st = $request->get('status')) {
            $q->where('status', $st);
        }
        if ($d = $request->get('department')) {
            $q->where('department', $d);
        }

        $paginated = $q->latest()->paginate(15)->withQueryString();

        $onboardings = collect($paginated->items())->filter(fn ($o) => $o !== null)->map(function ($o) {
            $o->initials = collect(explode(' ', (string) ($o->name ?? '')))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->join('');
            $o->progress ??= 0;
            return $o;
        })->values()->all();

        return Inertia::render('Dashboard/HRM_NEW/Onboarding', [
            'onboardings' => $onboardings,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
            'stats' => [
                'all' => HrmOnboarding::count(),
                'In Progress' => HrmOnboarding::where('status', 'In Progress')->count(),
                'Pending Requirements' => HrmOnboarding::where('status', 'Pending Requirements')->count(),
                'Ready to Hire' => HrmOnboarding::where('status', 'Ready to Hire')->count(),
                'Completed' => HrmOnboarding::where('status', 'Completed')->count(),
            ],
            'filters' => $request->only(['search', 'status', 'department']),
            'departments' => HrmDepartment::select('id', 'name')->get(),
            'templateCount' => HrmOnboardingTemplate::count(),
            'permissions' => $this->getPagePermissionsForModule('HRM'),
        ]);
    }

    public function show(HrmOnboarding $onboarding)
    {
        $onboarding->load(['items', 'activities', 'notes']);
        $required = $onboarding->items->where('status', '!=', 'Completed')->count();

        return Inertia::render('Dashboard/HRM_NEW/OnboardingDetail', [
            'onboarding' => $onboarding,
            'sections' => [],
            'activities' => $onboarding->activities,
            'notes' => $onboarding->notes,
            'profile' => null,
            'canComplete' => $required === 0 && $onboarding->status !== 'Completed',
            'missing' => [],
            'readiness' => [
                'required_items_done' => $onboarding->items->where('status', 'Completed')->count(),
                'required_items_total' => $onboarding->items->count(),
                'required_activities_done' => $onboarding->activities->where('status', 'Completed')->count(),
                'required_activities_total' => $onboarding->activities->count(),
                'ready' => $required === 0,
                'completed' => $onboarding->status === 'Completed',
            ],
            'progress' => (int) $onboarding->progress,
            'alreadyHired' => User::where('email', $onboarding->email)->exists(),
            'templates' => HrmOnboardingTemplate::select('id', 'name', 'code')->get(),
        ]);
    }

    public function update(Request $request, HrmOnboarding $onboarding)
    {
        $onboarding->update($request->validate([
            'start_date' => 'nullable|date',
            'expected_start_date' => 'nullable|date',
            'expected_completion_date' => 'nullable|date',
            'assigned_manager' => 'nullable|string',
            'work_location' => 'nullable|string',
            'work_schedule' => 'nullable|string',
            'template_id' => 'nullable|exists:hrm_onboarding_templates,id',
        ]));

        return back()->with('success', 'Onboarding updated.');
    }

    public function itemStatus(Request $request, HrmOnboarding $onboarding, HrmOnboardingItem $item)
    {
        $item->update(['status' => $request->validate(['status' => 'required|string'])['status']]);
        $this->recalc($onboarding);

        return back()->with('success', 'Item updated.');
    }

    public function itemReview(Request $request, HrmOnboarding $onboarding, HrmOnboardingItem $item)
    {
        $data = $request->validate(['decision' => 'required|in:approve,reject', 'reason' => 'nullable|string']);
        $item->update([
            'status' => $data['decision'] === 'approve' ? 'Completed' : 'Blocked',
            'rejection_reason' => $data['reason'] ?? null,
        ]);
        $this->recalc($onboarding);

        return back()->with('success', 'Review saved.');
    }

    public function noteStore(Request $request, HrmOnboarding $onboarding)
    {
        HrmOnboardingNote::create([
            'onboarding_id' => $onboarding->id, 'user_id' => $request->user()->id,
            'content' => $request->validate(['content' => 'required|string'])['content'],
            'is_hr_private' => (bool) $request->get('is_hr_private'),
        ]);

        return back()->with('success', 'Note added.');
    }

    public function noteDestroy(HrmOnboarding $onboarding, HrmOnboardingNote $note)
    {
        $note->delete();

        return back()->with('success', 'Note deleted.');
    }

    public function activityStore(Request $request, HrmOnboarding $onboarding)
    {
        HrmOnboardingActivity::create($request->validate([
            'title' => 'required|string', 'type' => 'nullable|string', 'description' => 'nullable|string',
            'schedule_date' => 'nullable|date', 'start_time' => 'nullable|string', 'end_time' => 'nullable|string',
            'method' => 'nullable|string', 'location' => 'nullable|string', 'meeting_link' => 'nullable|string',
            'facilitator' => 'nullable|string', 'organizer' => 'nullable|string',
            'is_required' => 'nullable|boolean', 'applicant_visible' => 'nullable|boolean',
        ]) + ['onboarding_id' => $onboarding->id]);

        return back()->with('success', 'Activity added.');
    }

    public function activityUpdate(Request $request, HrmOnboarding $onboarding, HrmOnboardingActivity $activity)
    {
        $activity->update($request->only(['status', 'attendance_status', 'title', 'description', 'schedule_date', 'start_time', 'end_time', 'method', 'location']));
        $this->recalc($onboarding);

        return back()->with('success', 'Activity updated.');
    }

    public function activityDestroy(HrmOnboarding $onboarding, HrmOnboardingActivity $activity)
    {
        $activity->delete();

        return back()->with('success', 'Activity deleted.');
    }

    public function complete(HrmOnboarding $onboarding)
    {
        $onboarding->update(['status' => 'Completed', 'progress' => 100]);

        return back()->with('success', 'Onboarding completed.');
    }

    public function createEmployee(HrmOnboarding $onboarding)
    {
        // Map the onboarding department to a valid users.role; fall back to HRM.
        $role = strtoupper((string) ($onboarding->department ?? 'HRM'));
        $validRoles = ['HRM', 'SCM', 'FIN', 'MAN', 'INV', 'ORD', 'WAR', 'CRM', 'ECO', 'PRO', 'PROJ', 'IT', 'LOG'];
        if (! in_array($role, $validRoles, true)) {
            $dept = HrmDepartment::where('name', $onboarding->department)->first();
            $role = ($dept && in_array(strtoupper((string) $dept->category), $validRoles, true))
                ? strtoupper((string) $dept->category) : 'HRM';
        }
        $deptId = HrmDepartment::where('name', $onboarding->department)->value('id');

        $user = User::firstOrCreate(['email' => $onboarding->email], [
            'name' => $onboarding->name ?? 'New Employee',
            'password' => Hash::make('password'),
            'role' => $role, 'position' => 'staff', 'is_active' => true,
            'employee_id' => 'EMP-' . str_pad((string) (User::max('id') + 1), 4, '0', STR_PAD_LEFT),
            'department' => $onboarding->department,
            'hrm_department_id' => $deptId,
            'join_date' => $onboarding->start_date ?? now()->toDateString(),
        ]);

        // Close the loop on the applicant side.
        if ($onboarding->applicant_id && ($app = Applicant::find($onboarding->applicant_id))) {
            $app->update(['status' => 'Hired', 'archived' => false, 'hired_user_id' => $user->id]);
            ApplicantStatusHistory::create([
                'applicant_id' => $app->id, 'from_status' => $app->status,
                'to_status' => 'Hired', 'changed_by' => auth()->id(),
                'reason' => 'Employee account created: ' . $user->email,
            ]);
        }

        return back()->with('success', "Employee created: {$user->email}");
    }

    private function recalc(HrmOnboarding $onboarding): void
    {
        $total = max(1, $onboarding->items()->count());
        $done = $onboarding->items()->where('status', 'Completed')->count();
        $onboarding->update(['progress' => (int) round($done / $total * 100)]);
    }
}
