<?php

namespace App\Http\Controllers\Applicants\Portal;

use App\Http\Controllers\Controller;
use App\Models\Hrm\HrmInterview;
use App\Models\Hrm\HrmJobPosting;
use App\Support\NotifiesApplicant;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Official APPLICANTS portal home (Dashboard/APPLICANTS/Dashboard.vue).
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $applicant = $request->user('applicant');
        $apps = $applicant->jobApplications()->with('jobPosting')->latest()->get();
        $interviews = HrmInterview::where('applicant_id', $applicant->id)
            ->with('jobPosting')->latest()->get();

        $upcoming = $interviews->where('status', 'Scheduled')->take(5)->map(fn ($i) => [
            'id' => $i->id,
            'job_title' => $i->jobPosting?->title ?? $i->position ?? $applicant->position_applied,
            'interview_type' => $i->type,
            'date' => optional($i->date)?->toDateString(),
            'time' => trim(($i->start_time ?? '') . ($i->end_time ? ' - ' . $i->end_time : '')),
            'location' => $i->location ?? $i->method,
        ])->values()->all();

        $appliedIds = $apps->pluck('job_posting_id')->filter()->all();
        $recommended = HrmJobPosting::with('position')
            ->where('status', 'Published')->whereNull('archived_at')
            ->whereNotIn('id', $appliedIds ?: [0])
            ->latest()->take(6)->get()->map(function ($p) use ($applicant) {
                $sameDept = $p->department && $applicant->department && $p->department === $applicant->department;
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'department' => $p->department,
                    'location' => $p->work_location,
                    'match' => $sameDept ? 90 : 70,
                ];
            })->values()->all();

        return Inertia::render('Dashboard/APPLICANTS/Dashboard', [
            'stats' => [
                'applications_count' => $apps->count(),
                'interviews_count' => $interviews->count(),
                'pending_applications' => $apps->whereIn('status', ['Submitted', 'Screening', 'Shortlisted'])->count(),
            ],
            'recentApplications' => $apps->take(5)->map(fn ($a) => [
                'id' => $a->id,
                'job_title' => $a->jobPosting?->title ?? $applicant->position_applied,
                'status' => $a->status,
                'application_date' => optional($a->created_at)->toDateString(),
            ])->values()->all(),
            'upcomingInterviews' => $upcoming,
            'recommendedJobs' => $recommended,
        ]);
    }
}
