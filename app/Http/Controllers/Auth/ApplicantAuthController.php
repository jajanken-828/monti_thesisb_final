<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Applicant;
use App\Models\Hrm\ApplicantStatusHistory;
use App\Models\Hrm\HrmJobPosting;
use App\Support\NotifiesApplicant;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Applicant portal auth (mirrors ClientAuthController).
 * Registration creates a portal-enabled applicant row; legacy rows
 * filed via the public /apply form can claim the same email by
 * registering (password is set, profile completed).
 */
class ApplicantAuthController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/ApplicantRegister', [
            'jobPostings' => HrmJobPosting::where('status', 'Published')
                ->whereNull('archived_at')->select('id', 'title')->latest()->take(100)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255',
            'phone_number' => 'required|string|max:40',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'position_applied' => 'nullable|string|max:255',
            'job_posting_id' => 'nullable|exists:hrm_job_postings,id',
            'city' => 'required|string|max:255',
            'state_province' => 'required|string|max:255',
            'street_address' => 'required|string|max:500',
            'postal_zip_code' => 'required|string|max:20',
        ]);

        $existing = Applicant::where('email', $request->email)->first();

        if ($existing) {
            // Claim flow: same email filed via public /apply — set password, fill gaps.
            if ($existing->password) {
                throw ValidationException::withMessages([
                    'email' => 'This email already has an applicant account. Please log in instead.',
                ]);
            }
            $posting = $request->job_posting_id ? HrmJobPosting::find($request->job_posting_id) : null;
            $existing->update([
                'first_name' => $existing->first_name ?: $request->first_name,
                'last_name' => $existing->last_name ?: $request->last_name,
                'phone_number' => $existing->phone_number ?: $request->phone_number,
                'password' => $request->password, // hashed via cast
                'position_applied' => $existing->position_applied ?: ($request->position_applied ?? $posting?->title ?? 'General Application'),
                'job_posting_id' => $existing->job_posting_id ?? $request->job_posting_id,
            ]);
            $applicant = $existing->fresh();
            NotifiesApplicant::welcome($applicant);
        } else {
            $posting = $request->job_posting_id ? HrmJobPosting::find($request->job_posting_id) : null;
            $applicant = Applicant::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'password' => $request->password, // hashed via cast
                'street_address' => $request->street_address,
                'city' => $request->city,
                'state_province' => $request->state_province,
                'postal_zip_code' => $request->postal_zip_code,
                'position_applied' => $request->position_applied ?? $posting?->title ?? 'General Application',
                'job_posting_id' => $request->job_posting_id,
                'status' => 'Submitted',
                'archived' => false,
            ]);
            ApplicantStatusHistory::create([
                'applicant_id' => $applicant->id, 'from_status' => null,
                'to_status' => 'Submitted', 'changed_by' => null,
                'reason' => 'Self-registered via applicant portal',
            ]);
            NotifiesApplicant::welcome($applicant);
        }

        Auth::guard('applicant')->login($applicant);
        $request->session()->regenerate();

        return $this->portalRedirect($request);
    }

    public function showLogin()
    {
        return Inertia::render('Auth/ApplicantLogin');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $this->ensureIsNotRateLimited($request);

        $applicant = Applicant::where('email', $credentials['email'])->first();

        // Generic message on every failure (no email/password enumeration).
        if ($applicant && $applicant->password && Hash::check($credentials['password'], $applicant->password)) {
            // Deactivated and rejected (archived) accounts stay locked out.
            if ((bool) $applicant->archived) {
                RateLimiter::hit($this->throttleKey($request));

                return back()->withErrors(['email' => 'Invalid credentials.']);
            }

            RateLimiter::clear($this->throttleKey($request));

            Auth::guard('applicant')->login($applicant, $request->boolean('remember'));
            $request->session()->regenerate();

            return $this->portalRedirect($request);
        }

        RateLimiter::hit($this->throttleKey($request));

        $hint = $applicant && ! $applicant->password
            ? 'No portal password set for this email yet — please register to claim your application.'
            : 'Invalid credentials.';

        return back()->withErrors(['email' => $hint]);
    }

    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->string('email')).'|'.$request->ip().'|applicant-login');
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('applicant')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('applicant.login');
    }

    /**
     * Multi-guard safe landing: only honor a stored intended URL when it
     * points inside the applicant portal. A stale intended URL from an
     * employee/client bounce would otherwise drop a fresh applicant
     * session at the wrong (employee) login screen.
     */
    protected function portalRedirect(Request $request)
    {
        $intended = $request->session()->pull('url.intended', route('applicant.dashboard'));
        $path = parse_url((string) $intended, PHP_URL_PATH) ?: '/';

        if (str_starts_with($path, '/applicant')) {
            return redirect()->to($intended);
        }

        return redirect()->route('applicant.dashboard');
    }
}
