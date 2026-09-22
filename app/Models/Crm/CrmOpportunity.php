<?php

namespace App\Models\Crm;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmOpportunity extends Model
{
    public const STAGES = [
        'qualification' => 10,
        'sampling' => 25,
        'quotation' => 50,
        'negotiation' => 75,
        'won' => 100,
        'lost' => 0,
    ];

    public const TERMINAL = ['won', 'lost'];

    protected $fillable = [
        'client_id', 'lead_id', 'title', 'stage', 'value',
        'probability', 'expected_close', 'owner_id', 'lost_reason',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expected_close' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(CrmOpportunityHistory::class, 'opportunity_id')->latest();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class, 'opportunity_id')->latest();
    }

    public function getWeightedAttribute(): float
    {
        return round(((float) $this->value) * ((int) $this->probability) / 100, 2);
    }

    public static function allowedMoves(string $from): array
    {
        if (in_array($from, self::TERMINAL, true)) {
            return [];
        }
        $order = ['qualification', 'sampling', 'quotation', 'negotiation'];

        return array_merge(
            array_values(array_diff($order, [$from])),
            self::TERMINAL
        );
    }
}
