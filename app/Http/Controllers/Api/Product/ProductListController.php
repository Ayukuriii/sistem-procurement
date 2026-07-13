<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Product\ProductListRequest;
use App\Http\Resources\Api\Product\ProductCollection;
use App\Services\ProductService;

class ProductListController extends Controller
{
    public function __construct(
        public ProductService $productService
    ) {}

    public function list(ProductListRequest $request): ProductCollection
    {
        $paginator = $this->productService->paginate(
            filters: $request->filters(),
            perPage: $request->perPage(),
        );

        return new ProductCollection($paginator);
    }
}
