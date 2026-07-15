<?php

namespace App\Http\Controllers\Api\Role;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Role\RoleListRequest;
use App\Http\Resources\Api\Role\RoleCollection;
use App\Services\RoleService;

class RoleListController extends Controller
{
    public function __construct(
        public RoleService $roleService
    ) {}

    public function list(RoleListRequest $request): RoleCollection
    {
        $paginator = $this->roleService->paginate(
            filters: $request->filters(),
            perPage: $request->perPage(),
        );

        return new RoleCollection($paginator);
    }
}
