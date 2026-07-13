<?php

namespace App\Http\Controllers\Api\PurchaseOrder;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PurchaseOrder\POCreateRequest;
use App\Http\Requests\Api\PurchaseOrder\POUpdateRequest;
use App\Http\Resources\Api\PurchaseOrder\PurchaseOrderResource;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PurchaseOrderController extends Controller
{
    public function __construct(
        public PurchaseOrderService $poService
    ) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(POCreateRequest $request): JsonResponse
    {
        try {
            $res = $this->poService->store($request->validated());

            return respondWithData(
                data: new PurchaseOrderResource($res),
                message: 'Success create purchase order data'
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
            $res = $this->poService->getPurchaseOrder($publicId);

            return respondWithData(
                data: new PurchaseOrderResource($res),
                message: 'Purchase order data retrieved successfully'
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
    public function update(POUpdateRequest $request, string $publicId): JsonResponse
    {
        info($request);
        try {
            $res = $this->poService->update($request->validated(), $publicId);

            return respondWithData(
                data: new PurchaseOrderResource($res),
                message: 'Purchase order data updated successfully'
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
            $this->poService->destroy($publicId);

            return respondWithMessage(
                message: 'Purchase order data deleted successfully'
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
