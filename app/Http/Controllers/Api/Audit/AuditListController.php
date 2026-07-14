<?php

namespace App\Http\Controllers\Api\Audit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Audit\AuditListRequest;
use App\Http\Resources\Api\Audit\AuditCollection;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuditListController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function list(AuditListRequest $request): AuditCollection|JsonResponse
    {
        try {
            $paginator = $this->auditService->paginate(
                filters: $request->filters(),
                perPage: $request->perPage(),
            );

            return new AuditCollection($paginator);
        } catch (AccessDeniedHttpException $e) {
            return respondError(error: $e->getMessage(), statusCode: 403);
        } catch (NotFoundHttpException $e) {
            return respondError(error: $e->getMessage(), statusCode: 404);
        } catch (HttpExceptionInterface $e) {
            return respondError(error: $e->getMessage(), statusCode: $e->getStatusCode());
        } catch (\Exception $e) {
            return respondError(error: $e->getMessage(), statusCode: 400);
        }
    }
}
