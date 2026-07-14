<?php

namespace App\Models;

use App\Constants\Documents;
use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Document extends Model implements Auditable
{
    use AuditableTrait, HasPublicId, SoftDeletes;

    /**
     * @var list<string>
     */
    protected array $auditExclude = [
        'file_path',
    ];

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
     * Public URL for the stored file — derived from file_path, not persisted.
     */
    protected function fileUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->file_path
                ? Storage::disk(Documents::STORAGE_DISK)->url($this->file_path)
                : null,
        );
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
