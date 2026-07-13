<?php

namespace App\Http\Controllers\Api\PurchaseOrder;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PurchaseOrder\POListRequest;
use App\Http\Resources\Api\PurchaseOrder\PurchaseOrderCollection;
use App\Services\PurchaseOrderService;

class PurchaseOrderListController extends Controller
{
    public function __construct(
        public PurchaseOrderService $POService
    ) {}

    public function list(POListRequest $request): PurchaseOrderCollection
    {
        $paginator = $this->POService->paginate(
            filters: $request->filters(),
            perPage: $request->perPage(),
        );

        return new PurchaseOrderCollection($paginator);
    }
}
