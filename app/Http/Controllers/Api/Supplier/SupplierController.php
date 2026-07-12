<?php

namespace App\Http\Controllers\Api\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Supplier\SupplierCreateRequest;
use App\Http\Requests\Api\Supplier\SupplierUpdateRequest;
use App\Http\Resources\Api\Supplier\SupplierResource;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SupplierController extends Controller
{
    public function __construct(
        public SupplierService $supplierService
    ) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(SupplierCreateRequest $request): JsonResponse
    {
        try {
            $res = $this->supplierService->store($request->validated());

            return respondWithData(
                data: new SupplierResource($res),
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

    /**
     * Display the specified resource.
     */
    public function show(string $publicId): JsonResponse
    {
        try {
            $res = $this->supplierService->getSupplier($publicId);

            return respondWithData(
                data: new SupplierResource($res),
                message: 'Supplier data retrieved successfully'
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
    public function update(SupplierUpdateRequest $request, string $publicId): JsonResponse
    {
        try {
            $res = $this->supplierService->update($request->validated(), $publicId);

            return respondWithData(
                data: new SupplierResource($res),
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $publicId): JsonResponse
    {
        try {
            $this->supplierService->destroy($publicId);

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
}
