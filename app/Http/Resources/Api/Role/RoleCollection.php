<?php

namespace App\Http\Resources\Api\Role;

use App\Http\Resources\Api\ApiCollection;

class RoleCollection extends ApiCollection
{
    public $collects = RoleResource::class;
}
