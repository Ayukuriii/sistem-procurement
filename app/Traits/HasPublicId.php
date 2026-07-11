<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasPublicId
{
    protected static function bootHasPublicId(): void
    {
        static::creating(function ($model) {
            // Ensures that public_id column will always populated with UUIDv7 before being saved
            if (empty($model->public_id)) {
                $model->public_id = (string) Str::uuid7();
            }
        });
    }
}
