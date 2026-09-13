<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\Client;
use App\Models\Crm\CrmLead;
use App\Models\Crm\CrmFeedback;
use App\Models\Crm\CrmMeeting;
use App\Models\Crm\CrmClientAssignment;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Traits\HasPagePermissions;

class CrmDashboardController extends Controller
{
    use HasPagePermissions;

    public function index()
    {
        $user = Auth::user();

        // Allow only CRM users (any position) and CEO
        if (!in_array($user->role, ['CRM'])) {
            abort(403, 'Unauthorized access.');
        }

        $stats = [
            'total_clients' => Client::where('status', 'active')->count(),
            'pending_clients' => Client::where('status', 'pending')->count(),
            'total_leads' => CrmLead::count(),
            'open_feedback' => CrmFeedback::where('status', 'open')->count(),
        ];

        // If user is staff and has assigned clients for investigation, show only those
        if ($user->position === 'staff') {
            $assignedClientIds = CrmClientAssignment::where('staff_id', $user->id)->pluck('client_id');
            $recentFeedback = CrmFeedback::whereIn('client_id', $assignedClientIds)->latest()->take(5)->get();
        } else {
            $recentFeedback = CrmFeedback::latest()->take(5)->get();
        }

        $permissions = $this->getPagePermissionsForModule('CRM');

        return Inertia::render('Dashboard/CRM/CRMDashboard', [
            'stats' => $stats,
            'recentFeedback' => $recentFeedback,
            'permissions' => $permissions,
        ]);
    }
}