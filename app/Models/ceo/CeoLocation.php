<?php

namespace App\Models\ceo;

use Illuminate\Database\Eloquent\Model;

class CeoLocation extends Model {
    protected $fillable = ['user_id', 'latitude', 'longitude', 'range_radius', 'label', 'place_name'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}