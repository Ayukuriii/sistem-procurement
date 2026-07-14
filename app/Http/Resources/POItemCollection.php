<?php

namespace App\Http\Resources;

use App\Http\Resources\Api\ApiCollection;

class POItemCollection extends ApiCollection
{
    public $collects = POItemResource::class;
}
