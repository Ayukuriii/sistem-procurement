<?php

namespace App\Http\Resources\Api\Category;

use App\Http\Resources\Api\ApiCollection;

class CategoryCollection extends ApiCollection
{
    public $collects = CategoryResource::class;
}
