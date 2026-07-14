<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Product extends Model implements Auditable
{
    use AuditableTrait, HasPublicId, SoftDeletes;

    protected $fillable = [
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
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
}
