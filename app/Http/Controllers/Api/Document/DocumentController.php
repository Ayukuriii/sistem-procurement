<?php

namespace App\Http\Controllers\Api\Document;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Document\DocumentCreateRequest;
use App\Http\Resources\Api\Document\DocumentResource;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DocumentController extends Controller
{
    public function __construct(
        public DocumentService $documentService
    ) {}

    /**
     * Upload or replace the PDF document for a draft purchase order.
     */
    public function store(DocumentCreateRequest $request, string $publicId): JsonResponse
    {
        try {
            $document = $this->documentService->store(
                file: $request->file('file'),
                metadata: $request->validated('metadata'),
                poPublicId: $publicId
            );

            return response()->json(
                [
                    'message' => 'Document uploaded successfully',
                    'data' => new DocumentResource($document),
                ],
                Response::HTTP_CREATED,
                [],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
            );
        } catch (\Exception $e) {
            $statusCode = match (true) {
                str_contains($e->getMessage(), 'not found') => Response::HTTP_NOT_FOUND,
                str_contains($e->getMessage(), 'already') => Response::HTTP_UNPROCESSABLE_ENTITY,
                default => Response::HTTP_INTERNAL_SERVER_ERROR,
            };

            return respondError(
                error: $e->getMessage(),
                statusCode: $statusCode
            );
        }
    }

    /**
     * Display the specified document.
     */
    public function show(string $publicId): JsonResponse
    {
        try {
            $document = $this->documentService->show($publicId);

            return respondWithData(
                data: new DocumentResource($document),
                message: 'Document retrieved successfully'
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
