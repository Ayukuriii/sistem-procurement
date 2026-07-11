<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'public_id',
        'category_id',
        'sku',
        'name',
        'unit_price',
        'is_active',
        'specifications',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'specifications' => 'array',
        ];
    }

    /**
     * Get the Category that owns the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
}
