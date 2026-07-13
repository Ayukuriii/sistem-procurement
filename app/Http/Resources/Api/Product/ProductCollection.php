<?php

namespace App\Http\Resources\Api\Product;

use App\Http\Resources\Api\ApiCollection;

class ProductCollection extends ApiCollection
{
    public $collects = ProductResource::class;
}
