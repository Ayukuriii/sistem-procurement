<?php

namespace App\Http\Controllers\Api\Role;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Select2\Select2QueryRequest;
use App\Http\Responses\Select2Response;
use App\Services\RoleSelectService;
use Illuminate\Http\JsonResponse;

class RoleSelectController extends Controller
{
    public function __construct(
        private RoleSelectService $roleSelectService,
    ) {}

    public function select(Select2QueryRequest $request): JsonResponse
    {
        $paginator = $this->roleSelectService->paginateForSelect(
            term: $request->term(),
            perPage: $request->perPage(),
        );

        return Select2Response::fromPaginator(
            $paginator,
            fn ($role) => [
                'id' => (string) $role->public_id,
                'text' => (string) $role->name,
            ],
        );
    }
}
