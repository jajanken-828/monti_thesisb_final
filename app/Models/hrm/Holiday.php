<?php

namespace App\Models\hrm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'holiday_date',
        'holiday_name',
        'holiday_type',
        'premium_rate',
        'status', // New field
    ];

    protected $attributes = [
        'status' => 'approved',
    ];
}
