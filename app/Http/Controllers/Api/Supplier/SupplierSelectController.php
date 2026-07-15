<?php

namespace App\Http\Controllers\Api\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Select2\Select2QueryRequest;
use App\Http\Responses\Select2Response;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;

class SupplierSelectController extends Controller
{
    public function __construct(
        private SupplierService $supplierService,
    ) {}

    public function select(Select2QueryRequest $request): JsonResponse
    {
        $paginator = $this->supplierService->paginateForSelect(
            term: $request->term(),
            perPage: $request->perPage(),
        );

        return Select2Response::fromPaginator(
            $paginator,
            fn ($supplier) => [
                'id' => (string) $supplier->public_id,
                'text' => (string) $supplier->name,
            ],
        );
    }
}
