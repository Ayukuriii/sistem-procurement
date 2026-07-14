<?php

namespace App\Http\Resources\Api\Audit;

use App\Http\Resources\Api\ApiCollection;

class AuditCollection extends ApiCollection
{
    public $collects = AuditResource::class;
}
