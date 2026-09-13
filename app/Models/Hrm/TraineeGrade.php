<?php

namespace App\Models\Hrm;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TraineeGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'skills_performance',
        'behaviour',
        'technicals',
        'safety_awareness',
        'productivity',
        'total_percentage',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
