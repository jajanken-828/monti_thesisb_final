<?php

namespace App\Http\Controllers\Hrm\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Hrm\Applicant;
use App\Models\Hrm\ApplicantStatusHistory;
use App\Models\Hrm\HrmInterview;
use App\Models\Hrm\HrmInterviewEvaluation;
use App\Models\Hrm\HrmOnboarding;
use App\Traits\HasPagePermissions;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InterviewController extends Controller
{
    use HasPagePermissions;

    public function index()
    {
        return Inertia::render('Dashboard/HRM_NEW/Interviews', [
            'applicants' => Applicant::whereIn('status', ['Shortlisted', 'Interview'])->latest()->take(50)->get(),
            'metrics' => [
                'total' => Applicant::count(),
                'shortlisted' => Applicant::where('status', 'Shortlisted')->count(),
                'interview_scheduled' => HrmInterview::where('status', 'Scheduled')->count(),
                'interviewed' => HrmInterview::where('status', 'Completed')->count(),
            ],
            'permissions' => $this->getPagePermissionsForModule('HRM'),
        ]);
    }

    public function list()
    {
        return response()->json(HrmInterview::with('evaluation')->latest()->paginate(20));
    }

    public function interviewers()
    {
        return response()->json(User::whereIn('position', ['manager', 'staff'])
            ->select('id', 'name', 'email')->take(50)->get());
    }

    public function fetch()
    {
        return response()->json(HrmInterview::latest()->take(50)->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'application_id' => 'nullable|exists:applicants,id',
            'applicant_id' => 'nullable|exists:applicants,id',
            'candidate_name' => 'nullable|string',
            'position' => 'nullable|string',
            'type' => 'nullable|in:Initial,Final',
            'interviewer_id' => 'nullable|exists:users,id',
            'date' => 'nullable|date',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'method' => 'nullable|in:Online,Onsite',
            'location' => 'nullable|string',
            'meeting_link' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        $data['applicant_id'] ??= $data['application_id'] ?? null;
        $interview = HrmInterview::create($data + ['status' => 'Scheduled']);
        if ($interview->applicant_id) {
            Applicant::whereKey($interview->applicant_id)->update(['status' => 'Interview']);
            if ($app = Applicant::find($interview->applicant_id)) {
                \App\Support\NotifiesApplicant::push($app, 'interview', 'Interview Scheduled', 'Your ' . ($interview->type ?? '') . ' interview has been scheduled.', [
                    'position' => $interview->position,
                    'interview_type' => $interview->type,
                    'date' => optional($interview->date)?->toDateString(),
                    'start_time' => $interview->start_time,
                    'end_time' => $interview->end_time,
                    'location' => $interview->location,
                    'meeting_link' => $interview->meeting_link,
                    'notes' => $interview->notes,
                    'status' => 'Scheduled',
                ]);
            }
        }

        return response()->json($interview, 201);
    }

    public function reschedule(Request $request, HrmInterview $interview)
    {
        $interview->update($request->validate([
            'date' => 'sometimes|date', 'start_time' => 'sometimes|string',
            'end_time' => 'sometimes|string', 'reason' => 'nullable|string',
        ]));

        return response()->json($interview);
    }

    public function cancel(Request $request, HrmInterview $interview)
    {
        $interview->update(['status' => 'Cancelled', 'cancel_reason' => $request->get('reason')]);

        return response()->json(['ok' => true]);
    }

    public function noShow(HrmInterview $interview)
    {
        $interview->update(['status' => 'No Show']);

        return response()->json(['ok' => true]);
    }

    public function complete(Request $request, HrmInterview $interview)
    {
        $interview->update(['status' => 'Completed']);
        HrmInterviewEvaluation::updateOrCreate(['interview_id' => $interview->id], $request->only([
            'communication_skills', 'relevant_skills', 'technical_knowledge', 'problem_solving',
            'work_experience', 'adaptability', 'teamwork', 'professionalism',
            'strengths', 'concerns', 'interview_notes', 'result', 'recommendation',
        ]) + ['evaluated_by' => $request->user()->id]);

        return response()->json(['ok' => true]);
    }

    public function advanceToOnboarding(HrmInterview $interview)
    {
        $app = $interview->applicant;
        $onboarding = HrmOnboarding::create([
            'applicant_id' => $interview->applicant_id,
            'job_posting_id' => $interview->job_posting_id,
            'name' => $interview->candidate_name ?? $app?->first_name . ' ' . $app?->last_name,
            'email' => $app?->email,
            'position' => $interview->position,
            'status' => 'In Progress',
        ]);
        // Close the loop: interview done, applicant now in onboarding.
        $interview->update(['status' => 'Completed']);
        if ($app) {
            $from = $app->status;
            $app->update(['status' => 'Onboarding', 'archived' => false]);
            ApplicantStatusHistory::create([
                'applicant_id' => $app->id, 'from_status' => $from,
                'to_status' => 'Onboarding', 'changed_by' => auth()->id(),
                'reason' => 'Advanced to onboarding #' . $onboarding->id,
            ]);
        }

        return response()->json($onboarding, 201);
    }

    public function select(Request $request, HrmInterview $interview)
    {
        $interview->update(['status' => 'Completed']);
        if ($app = $interview->applicant) {
            $from = $app->status;
            $app->update(['status' => 'Shortlisted']);
            ApplicantStatusHistory::create([
                'applicant_id' => $app->id, 'from_status' => $from,
                'to_status' => 'Shortlisted', 'changed_by' => $request->user()->id,
                'reason' => 'Selected after interview #' . $interview->id,
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
