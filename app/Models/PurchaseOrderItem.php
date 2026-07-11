<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasPublicId;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'product_name_snapshot',
        'unit_price_snapshot',
        'quantity',
        'subtotal',
    ];

    public function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    /**
     * Get the PurchaseOrder that owns the PurchaseOrderItem
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'id');
    }

    /**
     * Get the Product that owns the PurchaseOrderItem
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
