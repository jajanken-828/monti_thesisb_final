<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\Client;
use App\Models\Crm\CrmClientAssignment;
use App\Models\Crm\CrmFeedback;
use App\Models\Core\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Traits\HasPagePermissions;

class InvestigationController extends Controller
{
    use HasPagePermissions;

    public function index()
    {
        $user = Auth::user();
        
        // The middleware already checks page permission, so we don't need role checks here,
        // but we keep the business logic for staff vs manager view.
        if (!in_array($user->role, ['CRM'])) {
            abort(403, 'Unauthorized access.');
        }

        // Fetch CRM staff for assignment dropdown
        $staff = User::where('role', 'CRM')
                     ->where('position', 'staff')
                     ->get(['id', 'name', 'email']);

        if ($user->position === 'staff') {
            // Get client IDs assigned to this staff member
            $clientIds = CrmClientAssignment::where('staff_id', $user->id)->pluck('client_id');
            
            if ($clientIds->isEmpty()) {
                return Inertia::render('Dashboard/CRM/Investigation', [
                    'clients' => [],
                    'staff'   => $staff,
                    'message' => 'No clients assigned to you.'
                ]);
            }
            
            // Fetch clients with feedback and assignee eagerly loaded
            $clients = Client::whereIn('id', $clientIds)
                             ->with(['logo', 'feedback' => function($q) {
                                 $q->latest()->with('assignee');
                             }])
                             ->get();
        } else {
            // Manager/CEO sees all active clients
            $clients = Client::where('status', 'active')
                             ->with(['logo', 'feedback' => function($q) {
                                 $q->latest()->with('assignee');
                             }])
                             ->get();
        }

        $permissions = $this->getPagePermissionsForModule('CRM');

        return Inertia::render('Dashboard/CRM/Investigation', [
            'clients' => $clients,
            'staff'   => $staff,
            'permissions' => $permissions,
        ]);
    }

    public function assignStaff(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'staff_id'  => 'required|exists:users,id',
        ]);

        CrmClientAssignment::updateOrCreate(
            ['client_id' => $validated['client_id'], 'staff_id' => $validated['staff_id']],
            ['assigned_by' => Auth::id()]
        );

        return back()->with('message', 'Staff assigned to client.');
    }

    public function storeFeedback(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'type'      => 'required|in:feedback,complaint',
            'subject'   => 'required|string|max:255',
            'message'   => 'required|string',
        ]);

        $feedback = CrmFeedback::create($validated + ['status' => 'open']);

        // Auto-assign to the staff who is assigned to this client
        $assignment = CrmClientAssignment::where('client_id', $validated['client_id'])->first();
        if ($assignment) {
            $feedback->assigned_to = $assignment->staff_id;
            $feedback->save();
        }

        return back()->with('message', 'Feedback recorded.');
    }

    public function updateFeedbackStatus(Request $request, $id)
    {
        $feedback = CrmFeedback::findOrFail($id);
        $feedback->update([
            'status'           => $request->status,
            'resolution_notes' => $request->resolution_notes,
            'resolved_at'      => $request->status === 'resolved' ? now() : null,
        ]);
        return back()->with('message', 'Feedback status updated.');
    }
}