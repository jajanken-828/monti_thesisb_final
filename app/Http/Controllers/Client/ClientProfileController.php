<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmLogoPartner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ClientProfileController extends Controller
{
    public function edit()
    {
        $client = Auth::guard('client')->user();
        $client->load('logo');
        return Inertia::render('Client/Profile', ['client' => $client]);
    }

    public function update(Request $request)
    {
        $client = Auth::guard('client')->user();

        $validated = $request->validate([
            'company_name'    => 'required|string|max:255',
            'business_type'   => 'required|string|max:255',
            'tin_number'      => 'nullable|string|max:50',
            'contact_person'  => 'required|string|max:255',
            'phone'           => 'required|string|max:20',
            'company_address' => 'required|string',
            'city'            => 'nullable|string|max:100',
            'province'        => 'nullable|string|max:100',
            'postal_code'     => 'nullable|string|max:20',
            'latitude'        => 'nullable|numeric|between:-90,90',
            'longitude'       => 'nullable|numeric|between:-180,180',
            'logo'            => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:5120',
        ]);

        // 'logo' is an uploaded file, not a clients column — keep it out of the mass update.
        $logoFile = $validated['logo'] ?? null;
        unset($validated['logo']);

        // Explicitly cast lat/lng to float so they are never stored as strings,
        // and preserve null when the client hasn't pinned a location yet.
        $validated['latitude']  = isset($validated['latitude'])
            ? (float) $validated['latitude']
            : null;

        $validated['longitude'] = isset($validated['longitude'])
            ? (float) $validated['longitude']
            : null;

        $client->update($validated);

        if ($logoFile) {
            // Replace any existing logo (file + record) with the new upload.
            $existing = CrmLogoPartner::where('client_id', $client->id)->get();
            foreach ($existing as $old) {
                if ($old->logo_path) {
                    Storage::disk('public')->delete($old->logo_path);
                }
                $old->delete();
            }

            $path = $logoFile->store('crm-logos', 'public');
            CrmLogoPartner::create([
                'client_id' => $client->id,
                'logo_path' => $path,
                'original_name' => $logoFile->getClientOriginalName(),
            ]);
        }

        return back()->with('success', 'Profile updated.');
    }

    public function destroyLogo()
    {
        $client = Auth::guard('client')->user();

        $logos = CrmLogoPartner::where('client_id', $client->id)->get();
        foreach ($logos as $logo) {
            if ($logo->logo_path) {
                Storage::disk('public')->delete($logo->logo_path);
            }
            $logo->delete();
        }

        return back()->with('success', 'Company logo removed.');
    }

    /**
     * Save (upload/replace) the company logo on its own,
     * without submitting the rest of the profile form.
     */
    public function storeLogo(Request $request)
    {
        $client = Auth::guard('client')->user();

        $validated = $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,webp,svg|max:5120',
        ]);

        $existing = CrmLogoPartner::where('client_id', $client->id)->get();
        foreach ($existing as $old) {
            if ($old->logo_path) {
                Storage::disk('public')->delete($old->logo_path);
            }
            $old->delete();
        }

        $path = $validated['logo']->store('crm-logos', 'public');
        $logo = CrmLogoPartner::create([
            'client_id' => $client->id,
            'logo_path' => $path,
            'original_name' => $validated['logo']->getClientOriginalName(),
        ]);

        return back()->with('success', 'Company logo saved.');
    }
}