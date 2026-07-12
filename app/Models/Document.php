<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasPublicId, SoftDeletes;

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'file_path',
        'metadata',
    ];

    public function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Get the parent documentable model (polymorphic)
     *
     * @return MorphTo<PurchaseOrder>
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }
}
