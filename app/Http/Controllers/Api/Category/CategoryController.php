<?php

namespace App\Http\Controllers\Api\Category;


use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Category\CategoryCreateRequest;
use App\Http\Requests\Api\Category\CategoryUpdateRequest;
use App\Http\Resources\Api\Category\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function __construct(
        public CategoryService $categoryService
    ) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryCreateRequest $request)
    {
        try {
            $res = $this->categoryService->store($request->validated());

            return respondWithData(
                data: new CategoryResource($res),
                message: 'Success create user data'
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
            $res = $this->categoryService->getCategory($publicId);

            return respondWithData(
                data: new CategoryResource($res),
                message: 'Category data retrieved successfully'
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
    public function update(CategoryUpdateRequest $request, string $publicId)
    {
        try {
            $res = $this->categoryService->update($request->validated(), $publicId);

            return respondWithData(
                data: new CategoryResource($res),
                message: 'User data updated successfully'
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
            $this->categoryService->destroy($publicId);

            return respondWithMessage(
                message: 'User data deleted successfully'
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
