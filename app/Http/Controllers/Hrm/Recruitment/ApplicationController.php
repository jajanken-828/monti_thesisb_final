<?php

namespace App\Http\Controllers\Hrm\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Applicant;
use App\Models\Hrm\HrmApplicationScreening;
use App\Traits\HasPagePermissions;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    use HasPagePermissions;

    public function index(Request $request)
    {
        $q = Applicant::with('jobPosting');
        if ($s = $request->get('search')) {
            $q->where(fn ($w) => $w->where('first_name', 'like', "%{$s}%")
                ->orWhere('last_name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
        }
        if ($st = $request->get('status')) {
            $q->where('status', $st);
        }

        $paginated = $q->latest()->paginate(15)->withQueryString();
        // Attach the linked HRM_NEW job posting so the position filter works.
        $applications = collect($paginated->items())->filter(fn ($a) => $a !== null)->map(function ($a) {
            $arr = $a->toArray();
            $arr['job_posting'] = $a->jobPosting ? ['id' => $a->jobPosting->id, 'title' => $a->jobPosting->title] : null;
            return $arr;
        })->values()->all();

        return Inertia::render('Dashboard/HRM_NEW/Applications', [
            'applications' => $applications,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
            'metrics' => [
                'total' => Applicant::count(),
                'pending' => Applicant::whereIn('status', ['pending', 'Submitted'])->count(),
                'screening' => Applicant::where('status', 'Screening')->count(),
                'interview' => Applicant::where('status', 'Interview')->count(),
            ],
            'filters' => $request->only(['search', 'status', 'position']),
            'filterOptions' => ['statuses' => ['Submitted', 'Screening', 'Shortlisted', 'Interview', 'Offered', 'Hired', 'Rejected']],
            'permissions' => $this->getPagePermissionsForModule('HRM'),
        ]);
    }

    public function profile(Applicant $application)
    {
        // Rich payload shared with the APPLICANTS module (HRM-connected).
        return response()->json(app(\App\Services\Applicants\ApplicantProfileService::class)->for($application));
    }

    public function startScreening(Applicant $application)
    {
        $application->update(['status' => 'Screening']);

        return response()->json(['ok' => true, 'status' => 'Screening']);
    }

    public function screening(Request $request, Applicant $application)
    {
        $data = $request->validate([
            'resume_reviewed' => 'nullable|boolean',
            'education_verified' => 'nullable|boolean',
            'work_experience_reviewed' => 'nullable|boolean',
            'skills_reviewed' => 'nullable|boolean',
            'qualifications_met' => 'nullable|boolean',
            'applicant_info_reviewed' => 'nullable|boolean',
            'result' => 'required|in:passed,rejected',
            'notes' => 'nullable|string',
            'job_posting_id' => 'nullable|exists:hrm_job_postings,id',
        ]);
        HrmApplicationScreening::create($data + [
            'applicant_id' => $application->id, 'screened_by' => $request->user()->id,
        ]);
        $application->update(['status' => $data['result'] === 'passed' ? 'Shortlisted' : 'Rejected']);

        return response()->json(['ok' => true]);
    }

    public function reject(Request $request, Applicant $application)
    {
        $data = $request->validate(['reason' => 'required|string', 'feedback' => 'nullable|string']);
        $application->update(['status' => 'Rejected', 'rejection_reason' => $data['reason']]);

        return response()->json(['ok' => true]);
    }

    public function export()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'name', 'email', 'status']);
            Applicant::chunk(500, function ($rows) use ($out) {
                foreach ($rows as $a) {
                    fputcsv($out, [$a->id, $a->first_name . ' ' . $a->last_name, $a->email, $a->status]);
                }
            });
            fclose($out);
        }, 'applications.csv');
    }
}
