<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Select2\Select2QueryRequest;
use App\Http\Responses\Select2Response;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductSelectController extends Controller
{
    public function __construct(
        private ProductService $productService,
    ) {}

    public function select(Select2QueryRequest $request): JsonResponse
    {
        $paginator = $this->productService->paginateForSelect(
            term: $request->term(),
            perPage: $request->perPage(),
        );

        return Select2Response::fromPaginator(
            $paginator,
            fn ($product) => [
                'id' => (string) $product->public_id,
                'text' => "{$product->sku} — {$product->name}",
            ],
        );
    }
}
