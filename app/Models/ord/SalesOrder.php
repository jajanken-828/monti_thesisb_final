<?php

namespace App\Models\ord;

use App\Models\crm\Client;
use App\Models\ord\PurchaseOrder;
use App\Models\man\BomRecord;
use App\Models\man\ManufacturingOrder;
use App\Models\inv\Product;


use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'client_id',
        'jo_number',
        'control_number',
        'color',
        'quantity',
        'unit_price',
        'total_amount',
        'yarn_type',
        'design',
        'recipe_id',
        'status',
        'pushed_to', 
        'inv_check_sufficient', 
         'knitting_done_at', 'knitting_done_by',        // ← Added: stores 'SCM' or 'Order Mgmt'
    ];

    /**
     * Get the purchase order that owns the sales order.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the client that owns the sales order.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the recipe (BOM record) associated with the sales order.
     */
    public function recipe()
    {
        return $this->belongsTo(BomRecord::class, 'recipe_id');
    }
    public function manufacturingOrder()
{
    return $this->hasOne(ManufacturingOrder::class, 'sales_order_id');
}
 public function bomRecord()
    {
        return $this->belongsTo(BomRecord::class, 'recipe_id');
    }
}