<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasPublicId, SoftDeletes;

    protected $fillable = [
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
            'order_date' => 'datetime:Y-m-d H:i:s',
        ];
    }

    /**
     * Get documents attached to this purchase order (legacy MorphMany).
     *
     * @return MorphMany<Document>
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Exactly one PDF document per purchase order.
     *
     * @return MorphOne<Document>
     */
    public function document(): MorphOne
    {
        return $this->morphOne(Document::class, 'documentable');
    }

    /**
     * Get the Supplier that owns the PurchaseOrderController
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    /**
     * Get the User that owns the PurchaseOrder
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    /**
     * Get all of the Items for the PurchaseOrder
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id', 'id');
    }
}
