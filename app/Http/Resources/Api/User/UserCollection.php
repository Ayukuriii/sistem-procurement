<?php

namespace App\Http\Resources\Api\User;

use App\Http\Resources\Api\ApiCollection;

class UserCollection extends ApiCollection
{
    public $collects = UserResource::class;
}
