<?php

namespace App\Models\Sec;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;

class SecretaryMemo extends Model
{
    protected $fillable = [
        'ref_no', 'title', 'body', 'audience', 'priority',
        'status', 'published_at', 'created_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
