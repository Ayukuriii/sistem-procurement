<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\UserCreateRequest;
use App\Http\Requests\Api\User\UserUpdateRequest;
use App\Http\Requests\Api\User\UserUpdateRoleRequest;
use App\Http\Resources\Api\User\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function __construct(
        public UserService $userService
    ) {}

    public function store(UserCreateRequest $request): JsonResponse
    {
        try {
            $res = $this->userService->store($request->validated());

            return respondWithData(
                data: new UserResource($res),
                message: 'Success create user data'
            );
        } catch (\Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'already')
                ? Response::HTTP_CONFLICT
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            return respondError(
                error: $e->getMessage(),
                statusCode: $statusCode
            );
        }
    }

    public function show(string $publicId): JsonResponse
    {
        try {
            $res = $this->userService->getUser($publicId);

            return respondWithData(
                data: new UserResource($res),
                message: 'User data retrieved successfully'
            );
        } catch (\Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'not found')
            ? Response::HTTP_NOT_FOUND
            : Response::HTTP_INTERNAL_SERVER_ERROR;

            return respondError(
                error: $e->getMessage(),
                statusCode: $statusCode
            );
        }
    }

    public function update(UserUpdateRequest $request, string $publicId): JsonResponse
    {
        try {
            $res = $this->userService->update($request->validated(), $publicId);

            return respondWithData(
                data: new UserResource($res),
                message: 'User data updated successfully'
            );
        } catch (\Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'not found')
                ? Response::HTTP_NOT_FOUND
                    : Response::HTTP_INTERNAL_SERVER_ERROR;

            return respondError(
                error: $e->getMessage(),
                statusCode: $statusCode
            );
        }
    }

    public function delete(string $publicId): JsonResponse
    {
        try {
            $this->userService->delete($publicId);

            return respondWithMessage(
                message: 'User data deleted successfully'
            );
        } catch (\Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'not found')
                ? Response::HTTP_NOT_FOUND
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            return respondError(
                error: $e->getMessage(),
                statusCode: $statusCode
            );
        }
    }

    public function syncRole(UserUpdateRoleRequest $request, string $publicId): JsonResponse
    {
        try {
            $res = $this->userService->updateRole($request->validated(), $publicId);

            return respondWithData(
                data: new UserResource($res),
                message: 'User role data updated successfully'
            );
        } catch (\Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'not found')
                ? Response::HTTP_NOT_FOUND
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            return respondError(
                error: $e->getMessage(),
                statusCode: $statusCode
            );
        }
    }
}
