<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Product\ProductCreateRequest;
use App\Http\Requests\Api\Product\ProductUpdateRequest;
use App\Http\Resources\Api\Product\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(
        public ProductService $productService
    ) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductCreateRequest $request)
    {
        try {
            $res = $this->productService->store($request->validated());

            return respondWithData(
                data: new ProductResource($res),
                message: 'Success create product data'
            );
        } catch (\Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'already')
                ? Response::HTTP_CONFLICT
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            return respondError(
                error: $e->getMessage(),
                statusCode: $statusCode
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $publicId)
    {
        try {
            $res = $this->productService->getProduct($publicId);

            return respondWithData(
                data: new ProductResource($res),
                message: 'Product data retrieved successfully'
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

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductUpdateRequest $request, string $publicId)
    {
        try {
            $res = $this->productService->update($request->validated(), $publicId);

            return respondWithData(
                data: new ProductResource($res),
                message: 'Product data updated successfully'
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $publicId)
    {
        try {
            $this->productService->destroy($publicId);

            return respondWithMessage(
                message: 'Product data deleted successfully'
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
