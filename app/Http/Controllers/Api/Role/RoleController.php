<?php

namespace App\Http\Controllers\Api\Role;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Role\RoleCreateRequest;
use App\Http\Requests\Api\Role\RoleUpdateRequest;
use App\Http\Resources\Api\Role\RoleResource;
use App\Services\RoleService;
use Illuminate\Http\Response;

class RoleController extends Controller
{
    public function __construct(
        public RoleService $roleService
    ) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleCreateRequest $request)
    {
        try {
            $res = $this->roleService->store($request->validated());

            return respondWithData(
                data: new RoleResource($res),
                message: 'Success create role data'
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

    /**
     * Display the specified resource.
     */
    public function show(string $publicId)
    {
        try {
            $res = $this->roleService->getRole($publicId);

            return respondWithData(
                data: new RoleResource($res),
                message: 'Role data retrieved successfully'
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

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleUpdateRequest $request, string $publicId)
    {
        try {
            $res = $this->roleService->update($request->validated(), $publicId);

            return respondWithData(
                data: new RoleResource($res),
                message: 'Role data updated successfully'
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $publicId)
    {
        try {
            $this->roleService->destroy($publicId);

            return respondWithMessage(
                message: 'Role data deleted successfully'
            );
        } catch (\Exception $e) {
            $message = $e->getMessage();

            $statusCode = match (true) {
                str_contains($message, 'not found') => Response::HTTP_NOT_FOUND,
                str_contains($message, 'System role') => Response::HTTP_UNPROCESSABLE_ENTITY,
                str_contains($message, 'already') => Response::HTTP_CONFLICT,
                default => Response::HTTP_INTERNAL_SERVER_ERROR,
            };

            return respondError(
                error: $message,
                statusCode: $statusCode
            );
        }
    }
}
