<?php

namespace App\Models\man;
use App\Models\man\Machine;
use App\Models\man\Fabric;
use App\Models\man\Dyejobchemical;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DyeJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'fabric_id',
        'machine_id',
        'dye_type',       // primary dye material name (from inventory)
        'chemical_no',    // primary dye lot control_number (nullable, from inventory)
        'remarks',
        'operator_id',
        'shift',
        'code',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function fabric()
    {
        return $this->belongsTo(Fabric::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * All individual chemical / dye-lot entries consumed in this dye job.
     */
    public function chemicals()
    {
        return $this->hasMany(DyeJobChemical::class);
    }
}