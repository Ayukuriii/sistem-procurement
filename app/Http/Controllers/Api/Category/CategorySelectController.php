<?php

namespace App\Http\Controllers\Api\Category;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Select2\Select2QueryRequest;
use App\Http\Responses\Select2Response;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

class CategorySelectController extends Controller
{
    public function __construct(
        private CategoryService $categoryService,
    ) {}

    public function select(Select2QueryRequest $request): JsonResponse
    {
        $paginator = $this->categoryService->paginateForSelect(
            term: $request->term(),
            perPage: $request->perPage(),
        );

        return Select2Response::fromPaginator(
            $paginator,
            fn ($category) => [
                'id' => (string) $category->public_id,
                'text' => (string) $category->name,
            ],
        );
    }
}
