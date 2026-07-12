<?php

namespace App\Http\Controllers\Api\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Supplier\SupplierListRequest;
use App\Http\Resources\Api\Supplier\SupplierCollection;
use App\Services\SupplierService;

class SupplierListController extends Controller
{
    public function __construct(
        public SupplierService $userService
    ) {}

    public function list(SupplierListRequest $request): SupplierCollection
    {
        $paginator = $this->userService->paginate(
            filters: $request->filters(),
            perPage: $request->perPage(),
        );

        return new SupplierCollection($paginator);
    }
}
