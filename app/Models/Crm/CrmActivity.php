<?php

namespace App\Models\Crm;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmActivity extends Model
{
    public const TYPES = ['call', 'meeting', 'note', 'task', 'email'];

    protected $fillable = [
        'client_id', 'opportunity_id', 'lead_id', 'type', 'subject',
        'body', 'due_at', 'done_at', 'owner_id',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'done_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(CrmOpportunity::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->done_at === null && $this->due_at !== null && $this->due_at->isPast();
    }
}
