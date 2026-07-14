<?php

namespace App\Http\Controllers\Api\PurchaseOrderItem;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PurchaseOrder\POItemCreateRequest;
use App\Http\Requests\Api\PurchaseOrder\POItemUpdateRequest;
use App\Http\Resources\POItemResource;
use App\Services\POItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class POItemController extends Controller
{
    public function __construct(
        public POItemService $poItemService
    ) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(POItemCreateRequest $request, string $publicId): JsonResponse
    {
        try {
            $res = $this->poItemService->store(
                request: $request->validated(),
                publicId: $publicId
            );

            return respondWithData(
                data: new POItemResource($res),
                message: 'Success create purchase order item data'
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
    public function show(string $publicId, string $itemPublicId): JsonResponse
    {
        try {
            $res = $this->poItemService->getPoItem(
                publicId: $publicId,
                itemPublicId: $itemPublicId,
            );

            return respondWithData(
                data: new POItemResource($res),
                message: 'Purchase order item data retrieved successfully'
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
    public function update(POItemUpdateRequest $request, string $publicId, string $itemPublicId): JsonResponse
    {
        try {
            $res = $this->poItemService->update(
                request: $request->validated(),
                publicId: $publicId,
                itemPublicId: $itemPublicId
            );

            return respondWithData(
                data: new POItemResource($res),
                message: 'Purchase order item data updated successfully'
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
    public function destroy(string $publicId, string $itemPublicId): JsonResponse
    {
        try {
            $this->poItemService->destroy(
                publicId: $publicId,
                itemPublicId: $itemPublicId
            );

            return respondWithMessage(
                message: 'Purchase order item data deleted successfully'
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
