<?php

namespace App\Http\Controllers\Api\Export;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Export\ExportListRequest;
use App\Http\Requests\Api\Export\ExportStoreRequest;
use App\Http\Resources\Api\Export\ExportJobCollection;
use App\Http\Resources\Api\Export\ExportJobResource;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExportController extends Controller
{
    public function __construct(
        protected ExportService $exportService
    ) {}

    public function index(ExportListRequest $request): ExportJobCollection|JsonResponse
    {
        try {
            $paginator = $this->exportService->paginateForUser(
                user: $request->user(),
                filters: $request->filters(),
                perPage: $request->perPage(),
            );

            return new ExportJobCollection($paginator);
        } catch (\Exception $e) {
            return respondError(error: $e->getMessage(), statusCode: 400);
        }
    }

    public function store(ExportStoreRequest $request): JsonResponse
    {
        try {
            $job = $this->exportService->queue(
                user: $request->user(),
                module: $request->validated('module'),
                filters: $request->filters(),
            );

            return response()->json([
                'data' => (new ExportJobResource($job))->resolve(),
                'message' => "Your export is being prepared. We'll email you a download link when it's ready.",
            ], Response::HTTP_ACCEPTED);
        } catch (\InvalidArgumentException $e) {
            return respondError(error: $e->getMessage(), statusCode: 422);
        } catch (\Exception $e) {
            return respondError(error: $e->getMessage(), statusCode: 400);
        }
    }

    public function show(string $exportJobId): JsonResponse
    {
        try {
            $job = $this->exportService->findForUser(
                user: request()->user(),
                publicId: $exportJobId,
            );

            return respondWithData(data: new ExportJobResource($job));
        } catch (NotFoundHttpException $e) {
            return respondError(error: $e->getMessage(), statusCode: 404);
        } catch (HttpExceptionInterface $e) {
            return respondError(error: $e->getMessage(), statusCode: $e->getStatusCode());
        } catch (\Exception $e) {
            return respondError(error: $e->getMessage(), statusCode: 400);
        }
    }
}
