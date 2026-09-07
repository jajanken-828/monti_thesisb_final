<?php

namespace App\Models\ord;

use App\Models\core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdAccess extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'granted_by', 'can_access_ord'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function grantor()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}