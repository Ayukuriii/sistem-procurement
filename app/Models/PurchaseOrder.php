<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'public_id',
        'po_number',
        'supplier_id',
        'creator_id',
        'order_date',
        'status',
        'is_urgent',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_urgent' => 'boolean',
            'notes' => 'array',
            'order_date' => 'datetime:Y-m-d H:i:s'
        ];
    }

    /**
     * Get the Supplier that owns the PurchaseOrderController
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    /**
     * Get the User that owns the PurchaseOrder
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    /**
     * Get all of the Items for the PurchaseOrder
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id', 'id');
    }
}
