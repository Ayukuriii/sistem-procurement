<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'public_id',
        'name',
        'email',
        'phone',
        'address',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Get all of the PurchaseOrders for the Supplier
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(purchaseOrder::class, 'supplier_id', 'id');
    }
}
