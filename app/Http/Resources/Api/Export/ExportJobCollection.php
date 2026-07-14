<?php

namespace App\Http\Resources\Api\Export;

use App\Http\Resources\Api\ApiCollection;

class ExportJobCollection extends ApiCollection
{
    public $collects = ExportJobResource::class;
}
