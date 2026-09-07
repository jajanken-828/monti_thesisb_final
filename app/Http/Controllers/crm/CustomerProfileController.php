<?php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use App\Models\crm\Client;
use App\Models\crm\CrmClientAssignment;
use App\Models\crm\CrmMeeting;
use App\Models\crm\CrmFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Traits\HasPagePermissions;

class CustomerProfileController extends Controller
{
    use HasPagePermissions;

    /**
     * Display a list of clients (all for managers/CEO, assigned only for staff).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['CRM', 'CEO'])) {
            abort(403, 'Unauthorized access.');
        }

        if ($user->role === 'CRM' && $user->position === 'staff') {
            // Staff sees only assigned clients
            $assignments = CrmClientAssignment::with('client.logo')->where('staff_id', $user->id)->get();
            $clients = $assignments->pluck('client');
            if ($clients->isEmpty()) {
                return Inertia::render('Dashboard/CRM/CustomerProfilesList', [
                    'clients' => [],
                    'message' => 'No clients assigned to you.'
                ]);
            }
        } else {
            // Manager/CEO: optionally filter by assigned clients if ?assigned=1
            if ($request->query('assigned') == 1) {
                // Get clients that have at least one assignment (any staff)
                $assignedClientIds = CrmClientAssignment::distinct()->pluck('client_id');
                $clients = Client::with('logo')->whereIn('id', $assignedClientIds)
                    ->where('status', 'active')
                    ->get();
            } else {
                // Show all active clients
                $clients = Client::with('logo')->where('status', 'active')->get();
            }
        }

        $permissions = $this->getPagePermissionsForModule('CRM');

        return Inertia::render('Dashboard/CRM/CustomerProfilesList', [
            'clients' => $clients,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Show the detailed profile of a specific client.
     */
    public function show($id)
    {
        $user = Auth::user();
        $client = Client::with(['feedback.assignee', 'logo'])->findOrFail($id);

        // Restrict staff to only their assigned clients
        if ($user->role === 'CRM' && $user->position === 'staff') {
            $isAssigned = CrmClientAssignment::where('client_id', $client->id)
                                             ->where('staff_id', $user->id)
                                             ->exists();
            if (!$isAssigned) {
                abort(403, 'You are not assigned to this client.');
            }
        } elseif (!in_array($user->role, ['CRM', 'CEO'])) {
            abort(403, 'Unauthorized access.');
        }

        $meetings = CrmMeeting::where('client_id', $client->id)->latest()->get();
        $feedback = CrmFeedback::where('client_id', $client->id)->with('assignee')->latest()->get();

        $permissions = $this->getPagePermissionsForModule('CRM');

        return Inertia::render('Dashboard/CRM/CustomerProfile', [
            'client'   => $client,
            'meetings' => $meetings,
            'feedback' => $feedback,
            'permissions' => $permissions,
        ]);
    }
}