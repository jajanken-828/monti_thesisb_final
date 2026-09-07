<?php

namespace App\Models\man;

use App\Models\ord\PurchaseOrder;
use App\Models\ord\SalesOrder;
use App\Models\core\User;
use Illuminate\Database\Eloquent\Model;

class ManufacturingOrder extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'sales_order_id',
        'total_quantity',
        'remaining_quantity',
        'status',
        'notes',
    ];

    protected $casts = [
        'total_quantity' => 'integer',
        'remaining_quantity' => 'integer',
        'status' => 'string',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function fabrics()
    {
        return $this->hasMany(Fabric::class);
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }
}
