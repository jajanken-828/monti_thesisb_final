<?php

namespace App\Models\work;

use App\Models\core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkforcePermission extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'module', 'department', 'access_level'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
