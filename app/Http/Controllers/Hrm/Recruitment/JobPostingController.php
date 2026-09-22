<?php

namespace App\Http\Controllers\Hrm\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Hrm\Applicant;
use App\Models\Hrm\ApplicantJobApplication;
use App\Models\Hrm\HrmDepartment;
use App\Models\Hrm\HrmEmploymentType;
use App\Models\Hrm\HrmJobPosting;
use App\Models\Hrm\HrmPosition;
use App\Traits\HasPagePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class JobPostingController extends Controller
{
    use HasPagePermissions;

    public function index(Request $request)
    {
        $q = HrmJobPosting::with(['position', 'employmentType']);
        if ($s = $request->get('search')) {
            $q->where(fn ($w) => $w->where('title', 'like', "%{$s}%")->orWhere('posting_id', 'like', "%{$s}%"));
        }
        if ($st = $request->get('status')) {
            $q->where('status', $st);
        }
        if ($d = $request->get('department')) {
            $q->where('department', $d);
        }
        // NOTE: query params arrive as strings, and the string 'false'
        // is truthy in PHP — so parse explicitly instead of truthiness.
        if (filter_var($request->get('showArchived'), FILTER_VALIDATE_BOOLEAN)) {
            $q->whereNotNull('archived_at');
        } else {
            $q->whereNull('archived_at');
        }

        $postings = $q->latest()->paginate(12)->withQueryString();
        // Flatten relations for the table (position/type names, live
        // applicant counts, formatted publish date).
        $postings->setCollection($postings->getCollection()->map(function ($p) {
            $positionName = $p->position?->name;
            $typeName = $p->employmentType?->name;
            $total = ApplicantJobApplication::where('job_posting_id', $p->id)->count()
                + Applicant::where('job_posting_id', $p->id)->count();
            $published = optional($p->published_at)?->toDateString();
            // Drop the loaded relation objects so the flattened strings win serialization.
            unset($p->position, $p->employmentType);
            $p->setAttribute('position', $positionName);
            $p->setAttribute('employment_type', $typeName);
            $p->setAttribute('total_applications', $total);
            $p->setAttribute('published_date', $published);

            return $p;
        }));

        return Inertia::render('Dashboard/HRM_NEW/JobPosting', [
            'postingsData' => $postings,
            'filters' => $request->only(['search', 'status', 'department', 'showArchived']),
            'filterOptions' => [
                'departments' => HrmDepartment::select('id', 'name')->get(),
                'statuses' => ['Draft', 'Published', 'Closed', 'Archived'],
                'employmentTypes' => HrmEmploymentType::select('id', 'name')->get(),
            ],
            'metrics' => [
                'active' => HrmJobPosting::where('status', 'Published')->count(),
                'drafts' => HrmJobPosting::where('status', 'Draft')->count(),
                'closed' => HrmJobPosting::where('status', 'Closed')->count(),
                'archived' => HrmJobPosting::whereNotNull('archived_at')->count(),
                'vacancies' => (int) HrmJobPosting::sum('vacancies'),
                'total_applicants' => ApplicantJobApplication::count() + Applicant::whereNotNull('job_posting_id')->count(),
                'hired' => Applicant::where('status', 'Hired')->count(),
                'urgent' => HrmJobPosting::where('hiring_priority', 'Urgent')->count(),
            ],
            // Full position records so the posting modal can auto-fill
            // department / reports-to / salary / vacancies straight from
            // the Positions page (same shaping as the Positions directory).
            'positions' => HrmPosition::with(['department', 'reportsTo'])
                ->whereNull('archived_at')->orderBy('name')->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'department_id' => $p->department_id,
                    'department_name' => $p->department?->name,
                    'reports_to_id' => $p->reports_to_id,
                    'reports_to_name' => $p->reportsTo?->name,
                    'management_level' => $p->management_level,
                    'rank' => $p->rank,
                    'salary_min' => $p->salary_min,
                    'salary_max' => $p->salary_max,
                    'approved_headcount' => $p->approved_headcount,
                    'filled_positions' => User::where('hrm_position_id', $p->id)->count(),
                ])->values()->all(),
            'highRankPositionsByDepartment' => HrmPosition::where('rank', '<=', 5)->get()->groupBy('department_id'),
            'workLocation' => 'Km 22 Emilio Aguinaldo Hwy, Anabu 1B, Imus, 4103 Cavite',
            'employmentTypes' => HrmEmploymentType::select('id', 'name')->get(),
            'permissions' => $this->getPagePermissionsForModule('HRM'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'position_id' => 'required|exists:hrm_positions,id',
            'employment_type_id' => 'nullable|exists:hrm_employment_types,id',
            'vacancies' => 'required|integer|min:1',
            'hiring_priority' => 'nullable|in:Normal,High,Urgent',
            'work_mode' => 'nullable|in:On-site,Remote,Hybrid',
            'work_schedule' => 'nullable|string',
            'salary_visibility' => 'nullable|string',
            'recruitment_notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'require_initial_interview' => 'nullable|boolean',
            'require_final_interview' => 'nullable|boolean',
            'closing_date' => 'nullable|date',
            'expected_start_date' => 'nullable|date',
            'salary_min' => 'nullable|integer',
            'salary_max' => 'nullable|integer',
            'department' => 'nullable|string',
            'reports_to' => 'nullable|string',
            'recruiter' => 'nullable|string',
            'hiring_manager' => 'nullable|string',
            'work_location' => 'nullable|string',
        ]);
        $data['posting_id'] = 'POST-' . strtoupper(Str::random(8));
        $data['status'] = 'Draft';
        HrmJobPosting::create($data);

        return back()->with('success', 'Job posting created.');
    }

    public function update(Request $request, HrmJobPosting $jobPosting)
    {
        $jobPosting->update($request->validate([
            'title' => 'sometimes|string|max:255',
            'position_id' => 'sometimes|exists:hrm_positions,id',
            'employment_type_id' => 'nullable|exists:hrm_employment_types,id',
            'vacancies' => 'sometimes|integer|min:1',
            'hiring_priority' => 'nullable|in:Normal,High,Urgent',
            'work_mode' => 'nullable|in:On-site,Remote,Hybrid',
            'work_schedule' => 'nullable|string',
            'salary_visibility' => 'nullable|string',
            'recruitment_notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'require_initial_interview' => 'nullable|boolean',
            'require_final_interview' => 'nullable|boolean',
            'closing_date' => 'nullable|date',
            'expected_start_date' => 'nullable|date',
            'salary_min' => 'nullable|integer',
            'salary_max' => 'nullable|integer',
            'department' => 'nullable|string',
            'reports_to' => 'nullable|string',
            'recruiter' => 'nullable|string',
            'hiring_manager' => 'nullable|string',
            'work_location' => 'nullable|string',
        ]));

        return back()->with('success', 'Job posting updated.');
    }

    public function destroy(HrmJobPosting $jobPosting)
    {
        $jobPosting->delete();

        return back()->with('success', 'Job posting deleted.');
    }

    private function setStatus(HrmJobPosting $p, string $status)
    {
        $p->update(['status' => $status] + ($status === 'Published' ? ['published_at' => now()] : []));

        return back()->with('success', "Posting {$status}.");
    }

    public function publish(HrmJobPosting $jobPosting) { return $this->setStatus($jobPosting, 'Published'); }
    public function close(HrmJobPosting $jobPosting) { return $this->setStatus($jobPosting, 'Closed'); }
    public function reopen(HrmJobPosting $jobPosting) { return $this->setStatus($jobPosting, 'Published'); }

    public function duplicate(HrmJobPosting $jobPosting)
    {
        $copy = $jobPosting->replicate();
        $copy->posting_id = 'POST-' . strtoupper(Str::random(8));
        $copy->status = 'Draft';
        $copy->published_at = null;
        $copy->save();

        return back()->with('success', 'Posting duplicated.');
    }

    public function archive(HrmJobPosting $jobPosting)
    {
        $jobPosting->update(['archived_at' => now(), 'status' => 'Archived']);

        return back()->with('success', 'Posting archived.');
    }

    public function reactivate(HrmJobPosting $jobPosting)
    {
        $jobPosting->update(['archived_at' => null, 'status' => 'Draft']);

        return back()->with('success', 'Posting reactivated.');
    }

    public function export()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['posting_id', 'title', 'status', 'vacancies']);
            HrmJobPosting::chunk(500, fn ($rows) => array_map(
                fn ($p) => fputcsv($out, [$p->posting_id, $p->title, $p->status, $p->vacancies]), $rows->all()
            ));
            fclose($out);
        }, 'job-postings.csv');
    }
}
