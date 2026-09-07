<?php

namespace App\Models\pro;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProAccess extends Pivot
{
    protected $table = 'pro_access';

    protected $fillable = ['user_id', 'can_access_procurement'];

    public $timestamps = true;
}
