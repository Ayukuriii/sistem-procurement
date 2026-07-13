<?php

namespace App\Http\Resources\Api\PurchaseOrder;

use App\Http\Resources\Api\ApiCollection;

class PurchaseOrderCollection extends ApiCollection
{
    public $collects = PurchaseOrderResource::class;
}
