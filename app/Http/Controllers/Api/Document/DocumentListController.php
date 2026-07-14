<?php

namespace App\Http\Controllers\Api\Document;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Document\DocumentResource;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DocumentListController extends Controller
{
    public function __construct(
        public DocumentService $documentService
    ) {}

    /**
     * List documents attached to a purchase order (0 or 1 item).
     */
    public function list(string $publicId): JsonResponse
    {
        try {
            $documents = $this->documentService->listByPurchaseOrder($publicId);

            return respondWithData(
                data: DocumentResource::collection($documents),
                message: 'Documents retrieved successfully'
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
