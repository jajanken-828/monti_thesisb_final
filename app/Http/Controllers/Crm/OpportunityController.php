<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\Client;
use App\Models\Crm\CrmLead;
use App\Models\Crm\CrmOpportunity;
use App\Models\Crm\CrmOpportunityHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OpportunityController extends Controller
{
    /**
     * Deal pipeline board (kanban grouped by stage + weighted forecast).
     */
    public function index()
    {
        $opps = CrmOpportunity::with(['client.logo', 'lead', 'owner:id,name'])
            ->latest()
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'title' => $o->title,
                'stage' => $o->stage,
                'value' => (float) $o->value,
                'probability' => (int) $o->probability,
                'weighted' => $o->weighted,
                'expected_close' => $o->expected_close,
                'owner' => $o->owner?->name,
                'client' => $o->client?->only('id', 'company_name'),
                'lead' => $o->lead?->only('id', 'company_name'),
                'allowed' => CrmOpportunity::allowedMoves($o->stage),
                'created_at' => $o->created_at,
            ]);

        $byStage = [];
        foreach (CrmOpportunity::STAGES as $stage => $prob) {
            $set = $opps->where('stage', $stage);
            $byStage[$stage] = [
                'probability' => $prob,
                'count' => $set->count(),
                'value' => round($set->sum('value'), 2),
                'weighted' => round($set->sum('weighted'), 2),
            ];
        }

        $clients = Client::where('status', 'active')->orderBy('company_name')->get(['id', 'company_name']);
        $openLeads = CrmLead::whereNotIn('status', ['Converted', 'Archived', 'Lost', 'Closed-Won'])
            ->orderBy('company_name')->get(['id', 'company_name']);

        return Inertia::render('Dashboard/CRM/Opportunities', [
            'opportunities' => $opps->values(),
            'byStage' => $byStage,
            'forecast' => round($opps->reject(fn ($o) => in_array($o['stage'], ['won', 'lost']))->sum('weighted'), 2),
            'clients' => $clients,
            'openLeads' => $openLeads,
        ]);
    }

    public function show(CrmOpportunity $opportunity)
    {
        $opportunity->load(['client.contacts', 'lead', 'owner:id,name', 'histories.changer:id,name', 'activities.owner']);

        return Inertia::render('Dashboard/CRM/OpportunityShow', [
            'opportunity' => array_merge($opportunity->toArray(), [
                'weighted' => $opportunity->weighted,
                'allowed' => CrmOpportunity::allowedMoves($opportunity->stage),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'lead_id' => 'nullable|exists:crm_leads,id',
            'title' => 'required|string|max:255',
            'value' => 'required|numeric|min:0',
            'expected_close' => 'nullable|date',
        ]);
        abort_if(empty($data['client_id']) && empty($data['lead_id']), 422, 'Link the deal to an account or a lead.');

        $opp = CrmOpportunity::create([
            ...$data,
            'stage' => 'qualification',
            'probability' => CrmOpportunity::STAGES['qualification'],
            'owner_id' => Auth::id(),
        ]);
        CrmOpportunityHistory::create([
            'opportunity_id' => $opp->id,
            'from_stage' => null,
            'to_stage' => 'qualification',
            'changed_by' => Auth::id(),
            'notes' => 'Deal opened.',
        ]);

        return back()->with('message', 'Opportunity opened in Qualification.');
    }

    public function move(Request $request, CrmOpportunity $opportunity)
    {
        $data = $request->validate([
            'stage' => 'required|string',
            'lost_reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        if (! in_array($data['stage'], CrmOpportunity::allowedMoves($opportunity->stage), true)) {
            return back()->withErrors(['error' => "Cannot move deal from {$opportunity->stage} to {$data['stage']}."]);
        }
        if ($data['stage'] === 'lost' && empty($data['lost_reason'])) {
            return back()->withErrors(['error' => 'A lost reason is required to close a deal as lost.']);
        }

        $from = $opportunity->stage;
        $opportunity->update([
            'stage' => $data['stage'],
            'probability' => CrmOpportunity::STAGES[$data['stage']],
            'lost_reason' => $data['stage'] === 'lost' ? $data['lost_reason'] : null,
        ]);
        CrmOpportunityHistory::create([
            'opportunity_id' => $opportunity->id,
            'from_stage' => $from,
            'to_stage' => $data['stage'],
            'changed_by' => Auth::id(),
            'notes' => $data['notes'] ?? null,
        ]);

        // Closing as won against a prospect auto-spins an account pipeline entry.
        if ($data['stage'] === 'won' && $opportunity->lead_id && ! $opportunity->client_id) {
            $lead = $opportunity->lead;
            if ($lead && $lead->status !== 'Converted') {
                $lead->update(['status' => 'Closed-Won']);
            }
        }

        return back()->with('message', "Deal moved to {$data['stage']}.");
    }
}
