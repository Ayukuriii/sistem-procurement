<?php

namespace App\Http\Controllers\Api\Import;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Import\ImportStoreRequest;
use App\Http\Requests\Api\Import\ImportTemplateRequest;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ImportController extends Controller
{
    public function __construct(
        protected ImportService $importService
    ) {}

    public function store(ImportStoreRequest $request): JsonResponse
    {
        try {
            $result = $this->importService->import(
                module: $request->validated('module'),
                file: $request->file('file'),
            );

            return respondWithData(data: $result);
        } catch (UnprocessableEntityHttpException $e) {
            return respondError(error: $e->getMessage(), statusCode: 422);
        } catch (\InvalidArgumentException $e) {
            return respondError(error: $e->getMessage(), statusCode: 422);
        } catch (HttpExceptionInterface $e) {
            return respondError(error: $e->getMessage(), statusCode: $e->getStatusCode());
        } catch (\Exception $e) {
            return respondError(error: $e->getMessage(), statusCode: 400);
        }
    }

    public function template(ImportTemplateRequest $request)
    {
        try {
            return $this->importService->downloadTemplate(
                module: $request->validated('module'),
            );
        } catch (\InvalidArgumentException $e) {
            return respondError(error: $e->getMessage(), statusCode: 422);
        } catch (\Exception $e) {
            return respondError(error: $e->getMessage(), statusCode: 400);
        }
    }
}
