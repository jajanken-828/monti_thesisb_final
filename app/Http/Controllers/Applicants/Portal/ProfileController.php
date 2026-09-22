<?php

namespace App\Http\Controllers\Applicants\Portal;

use App\Http\Controllers\Controller;
use App\Models\Hrm\HrmInterview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

/**
 * Official APPLICANTS profile + account
 * (Dashboard/APPLICANTS/Profile/index.vue, Account/index.vue).
 */
class ProfileController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Dashboard/APPLICANTS/Profile/index', [
            'applicant' => $this->shape($request->user('applicant')),
        ]);
    }

    public function update(Request $request)
    {
        $applicant = $request->user('applicant');
        $data = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'suffix' => 'nullable|string|max:30',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|string|max:20',
            'civil_status' => 'nullable|string|max:20',
            'email' => 'sometimes|email|max:255|unique:applicants,email,' . $applicant->id,
            'phone' => 'nullable|string|max:40',
            'street' => 'nullable|string|max:500',
            'barangay' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'highest_education' => 'nullable|string|max:255',
            'school' => 'nullable|string|max:255',
            'course' => 'nullable|string|max:255',
            'graduation_year' => 'nullable|string|max:10',
            'skills' => 'nullable|array',
            'skills.*' => 'nullable|string|max:100',
            'work_experience' => 'nullable|array',
            'elementary_school' => 'nullable|string|max:255',
            'elementary_year' => 'nullable|string|max:10',
            'high_school' => 'nullable|string|max:255',
            'high_year' => 'nullable|string|max:10',
            'college' => 'nullable|string|max:255',
            'college_year' => 'nullable|string|max:10',
            'vocational' => 'nullable|string|max:255',
            'vocational_year' => 'nullable|string|max:10',
            'special_skills' => 'nullable|string|max:2000',
            'emergency_contact.name' => 'nullable|string|max:255',
            'emergency_contact.relationship' => 'nullable|string|max:60',
            'emergency_contact.phone' => 'nullable|string|max:40',
            'emergency_contact.address' => 'nullable|string|max:500',
            'profile_photo' => 'nullable|image|max:4096',
            'resumes' => 'nullable|array',
            'resumes.*' => 'file|mimes:pdf,doc,docx|max:5120',
            'resume_to_delete' => 'nullable|array',
            'resume_to_delete.*' => 'string|max:500',
        ]);

        $update = [
            'first_name' => $data['first_name'] ?? $applicant->first_name,
            'middle_name' => $data['middle_name'] ?? $applicant->middle_name,
            'last_name' => $data['last_name'] ?? $applicant->last_name,
            'suffix' => $data['suffix'] ?? $applicant->suffix,
            'date_of_birth' => $data['birth_date'] ?? $applicant->date_of_birth,
            'sex' => $data['gender'] ?? $applicant->sex,
            'civil_status' => $data['civil_status'] ?? $applicant->civil_status,
            'email' => $data['email'] ?? $applicant->email,
            'phone_number' => $data['phone'] ?? $applicant->phone_number,
            'street_address' => $data['street'] ?? $applicant->street_address,
            'barangay' => $data['barangay'] ?? $applicant->barangay,
            'city' => $data['city'] ?? $applicant->city,
            'state_province' => $data['province'] ?? $applicant->state_province,
            'postal_zip_code' => $data['zip_code'] ?? $applicant->postal_zip_code,
            'highest_education' => $data['highest_education'] ?? $applicant->highest_education,
            'school' => $data['school'] ?? $applicant->school,
            'course' => $data['course'] ?? $applicant->course,
            'graduation_year' => $data['graduation_year'] ?? $applicant->graduation_year,
            'elementary_school' => $data['elementary_school'] ?? $applicant->elementary_school,
            'elementary_year' => $data['elementary_year'] ?? $applicant->elementary_year,
            'high_school' => $data['high_school'] ?? $applicant->high_school,
            'high_year' => $data['high_year'] ?? $applicant->high_year,
            'college' => $data['college'] ?? $applicant->college,
            'college_year' => $data['college_year'] ?? $applicant->college_year,
            'vocational' => $data['vocational'] ?? $applicant->vocational,
            'vocational_year' => $data['vocational_year'] ?? $applicant->vocational_year,
            'special_skills' => array_key_exists('skills', $data)
                ? implode(', ', array_filter($data['skills'] ?? []))
                : ($data['special_skills'] ?? $applicant->special_skills),
            'emergency_name' => $data['emergency_contact']['name'] ?? $applicant->emergency_name,
            'emergency_relationship' => $data['emergency_contact']['relationship'] ?? $applicant->emergency_relationship,
            'emergency_phone' => $data['emergency_contact']['phone'] ?? $applicant->emergency_phone,
            'emergency_address' => $data['emergency_contact']['address'] ?? $applicant->emergency_address,
        ];
        if (array_key_exists('work_experience', $data)) {
            $update['employment_records'] = $data['work_experience'];
        }
        if ($request->hasFile('profile_photo')) {
            $update['profile_photo_path'] = $request->file('profile_photo')->store('applicants/photos', 'public');
        }
        $applicant->update($update);

        foreach ($request->file('resumes', []) as $file) {
            $applicant->documents()->create([
                'type' => 'resume',
                'file_path' => $file->store('applicants/documents', 'public'),
                'original_name' => $file->getClientOriginalName(),
            ]);
        }
        foreach ($data['resume_to_delete'] ?? [] as $path) {
            $applicant->documents()->where('original_name', $path)->orWhere('file_path', $path)->delete();
        }

        return back()->with('success', 'Profile saved.');
    }

    public function account(Request $request)
    {
        $applicant = $request->user('applicant');

        return Inertia::render('Dashboard/APPLICANTS/Account/index', [
            'applicant' => [
                'id' => $applicant->id,
                'email' => $applicant->email,
                'first_name' => $applicant->first_name,
                'last_name' => $applicant->last_name,
                'email_verified_at' => optional($applicant->email_verified_at)?->toISOString(),
                'created_at' => optional($applicant->created_at)->toISOString(),
                'updated_at' => optional($applicant->updated_at)->toISOString(),
            ],
            'stats' => [
                'applications_count' => $applicant->jobApplications()->count(),
                'interviews_count' => HrmInterview::where('applicant_id', $applicant->id)->count(),
            ],
        ]);
    }

    public function password(Request $request)
    {
        $applicant = $request->user('applicant');
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        if (! $applicant->password || ! Hash::check($data['current_password'], $applicant->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }
        $applicant->update(['password' => $data['password']]);

        return back()->with('success', 'Password updated.');
    }

    public function verify(Request $request)
    {
        $request->user('applicant')->update(['email_verified_at' => now()]);

        return back()->with('success', 'Email verified.');
    }

    public function deactivate(Request $request)
    {
        $applicant = $request->user('applicant');
        $applicant->update(['archived' => true, 'status' => 'Deactivated']);
        Auth::guard('applicant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('applicant.login')->with('message', 'Account deactivated.');
    }

    private function shape($a): array
    {
        $resumes = $a->documents()->where('type', 'resume')->pluck('original_name')->filter()->values()->all();

        return [
            'id' => $a->id,
            'applicant_account_id' => $a->id,
            'first_name' => $a->first_name,
            'middle_name' => $a->middle_name,
            'last_name' => $a->last_name,
            'suffix' => $a->suffix,
            'birth_date' => optional($a->date_of_birth)?->toDateString(),
            'gender' => $a->sex,
            'civil_status' => $a->civil_status,
            'email' => $a->email,
            'phone' => $a->phone_number,
            'street' => $a->street_address,
            'barangay' => $a->barangay,
            'city' => $a->city,
            'province' => $a->state_province,
            'zip_code' => $a->postal_zip_code,
            'highest_education' => $a->highest_education,
            'school' => $a->school ?? $a->college,
            'course' => $a->course,
            'graduation_year' => $a->graduation_year ?? $a->college_year,
            'skills' => array_filter(array_map('trim', explode(',', (string) ($a->special_skills ?? '')))),
            'work_experience' => $a->employment_records ?? [],
            'resumes' => $resumes,
            'resume_path' => $a->documents()->where('type', 'resume')->value('file_path'),
            'profile_photo_path' => $a->profile_photo_path,
            'elementary_school' => $a->elementary_school,
            'elementary_year' => $a->elementary_year,
            'high_school' => $a->high_school,
            'high_year' => $a->high_year,
            'college' => $a->college,
            'college_year' => $a->college_year,
            'vocational' => $a->vocational,
            'vocational_year' => $a->vocational_year,
            'special_skills' => $a->special_skills,
            'emergency_contact' => [
                'name' => $a->emergency_name,
                'relationship' => $a->emergency_relationship,
                'phone' => $a->emergency_phone,
                'address' => $a->emergency_address,
            ],
        ];
    }
}
