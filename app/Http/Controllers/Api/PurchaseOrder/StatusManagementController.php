<?php

namespace App\Http\Controllers\Api\PurchaseOrder;

use App\Constants\Statuses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PurchaseOrder\StatusManagementRequest;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class StatusManagementController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $purchaseOrderService
    ) {}

    public function submit(StatusManagementRequest $request, string $publicId): JsonResponse
    {
        return $this->executeStatusChange(
            $request,
            $publicId,
            Statuses::PO_SUBMITTED,
            'Purchase order submitted successfully.'
        );
    }

    public function approve(StatusManagementRequest $request, string $publicId): JsonResponse
    {
        return $this->executeStatusChange(
            $request,
            $publicId,
            Statuses::PO_APPROVED,
            'Purchase order approved successfully.'
        );
    }

    public function receive(StatusManagementRequest $request, string $publicId): JsonResponse
    {
        return $this->executeStatusChange(
            $request,
            $publicId,
            Statuses::PO_RECEIVED,
            'Purchase order items received successfully.'
        );
    }

    public function cancel(StatusManagementRequest $request, string $publicId): JsonResponse
    {
        return $this->executeStatusChange(
            $request,
            $publicId,
            Statuses::PO_CANCELLED,
            'Purchase order cancelled successfully.'
        );
    }

    /**
     * Centralized helper to process state changes and handle standard responses.
     */
    private function executeStatusChange(
        StatusManagementRequest $request,
        string $publicId,
        string $status,
        string $successMessage
    ): JsonResponse {
        try {
            $this->purchaseOrderService->changeStatus(
                data: $request->validated(),
                publicId: $publicId,
                targetStatus: $status
            );

            return respondWithMessage(message: $successMessage);

        } catch (\Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'not found')
                ? Response::HTTP_NOT_FOUND
                : Response::HTTP_UNPROCESSABLE_ENTITY;

            return respondError(
                error: $e->getMessage(),
                statusCode: $statusCode
            );
        }
    }
}
