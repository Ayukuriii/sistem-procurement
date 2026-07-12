<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\UserListRequest;
use App\Http\Resources\Api\User\UserCollection;
use App\Services\UserService;

class UserListController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * Get paginated data of all users
     */
    public function list(UserListRequest $request): UserCollection
    {
        $paginator = $this->userService->paginate(
            filters: $request->filters(),
            perPage: $request->perPage(),
        );

        return new UserCollection($paginator);
    }
}
