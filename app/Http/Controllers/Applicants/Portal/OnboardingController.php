<?php

namespace App\Http\Controllers\Applicants\Portal;

use App\Http\Controllers\Controller;
use App\Models\Hrm\HrmOnboarding;
use App\Models\Hrm\HrmOnboardingActivity;
use App\Models\Hrm\HrmOnboardingItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Official APPLICANTS onboarding tracker
 * (Dashboard/APPLICANTS/Onboarding/index.vue).
 * Candidates submit requirements and confirm attendance; HR verifies.
 */
class OnboardingController extends Controller
{
    public function index(Request $request)
    {
        $applicant = $request->user('applicant');
        $onboarding = HrmOnboarding::with(['items.templateItem', 'activities'])
            ->where('applicant_id', $applicant->id)
            ->orWhere(fn ($q) => $q->where('email', $applicant->email))
            ->latest()->first();

        if (! $onboarding) {
            return Inertia::render('Dashboard/APPLICANTS/Onboarding/index', [
                'active' => false,
                'onboarding' => null,
            ]);
        }

        $sections = [];
        foreach ($onboarding->items as $item) {
            $section = $item->templateItem?->category ?? 'General Requirements';
            $sections[$section][] = [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->templateItem?->description,
                'due_date' => optional($item->due_date)?->toDateString(),
                'is_required' => (bool) ($item->templateItem?->is_required ?? true),
                'input_type' => $this->inputType($item->templateItem?->input_type),
                'status' => $item->status,
                'is_task' => (bool) ($item->templateItem?->applicant_visible ?? true),
                'responsible_party' => $item->templateItem?->responsible_party,
                'latest_submission' => ($item->file_url || $item->value || $item->submitted_at) ? [
                    'file_url' => $item->file_url ? Storage::url($item->file_url) : null,
                    'original_name' => $item->original_name,
                    'value' => $item->value,
                    'submitted_at' => optional($item->submitted_at)?->toDateTimeString(),
                    'rejection_reason' => $item->rejection_reason,
                ] : null,
            ];
        }

        return Inertia::render('Dashboard/APPLICANTS/Onboarding/index', [
            'active' => true,
            'onboarding' => [
                'name' => $onboarding->name,
                'position' => $onboarding->position,
                'department' => $onboarding->department,
                'template' => $onboarding->template?->name,
                'start_date' => optional($onboarding->start_date)?->toDateString(),
                'expected_start_date' => optional($onboarding->expected_start_date)?->toDateString(),
                'work_location' => $onboarding->work_location,
                'work_schedule' => $onboarding->work_schedule,
                'assigned_manager' => $onboarding->assigned_manager,
                'status' => $onboarding->status,
                'progress' => (int) $onboarding->progress,
                'activities' => $onboarding->activities->map(fn ($a) => [
                    'id' => $a->id,
                    'title' => $a->title,
                    'type' => $a->type,
                    'status' => $a->status,
                    'schedule_date' => optional($a->schedule_date)?->toDateString(),
                    'start_time' => $a->start_time,
                    'end_time' => $a->end_time,
                    'method' => $a->method,
                    'location' => $a->location,
                    'meeting_link' => $a->meeting_link,
                    'facilitator' => $a->facilitator,
                    'organizer' => $a->organizer,
                    'is_required' => (bool) $a->is_required,
                    'attendance_status' => $a->attendance_status,
                ])->values()->all(),
                'sections' => $sections,
            ],
        ]);
    }

    public function submitItem(Request $request, HrmOnboardingItem $item)
    {
        $this->owned($request, $item);
        $data = $request->validate([
            'file' => 'nullable|file|max:10240',
            'value' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:1000',
        ]);
        if (! $request->hasFile('file') && empty($data['value'])) {
            return back()->withErrors(['file' => 'Attach a file or enter a value.']);
        }
        $update = ['submitted_at' => now(), 'status' => 'Submitted'];
        if ($request->hasFile('file')) {
            $update['file_url'] = $request->file('file')->store('onboarding/submissions', 'public');
            $update['original_name'] = $request->file('file')->getClientOriginalName();
        }
        if (! empty($data['value'])) {
            $update['value'] = $data['value'];
        }
        $item->update($update);

        return back()->with('success', 'Requirement submitted for HR verification.');
    }

    public function attendActivity(Request $request, HrmOnboardingActivity $activity)
    {
        $this->ownedActivity($request, $activity);
        $activity->update(['attendance_status' => 'Confirmed']);

        return back()->with('success', 'Attendance confirmed.');
    }

    private function inputType(?string $raw): string
    {
        return match (strtolower((string) $raw)) {
            'file' => 'Document Upload',
            'date' => 'Date',
            'checkbox' => 'Checkbox',
            'select' => 'Form',
            'number' => 'Number',
            default => 'Text',
        };
    }

    private function owned(Request $request, HrmOnboardingItem $item): void
    {
        $applicant = $request->user('applicant');
        $owner = $item->onboarding;
        abort_unless(
            $owner && ($owner->applicant_id === $applicant->id || $owner->email === $applicant->email),
            404
        );
    }

    private function ownedActivity(Request $request, HrmOnboardingActivity $activity): void
    {
        $applicant = $request->user('applicant');
        $owner = $activity->onboarding;
        abort_unless(
            $owner && ($owner->applicant_id === $applicant->id || $owner->email === $applicant->email),
            404
        );
    }
}
