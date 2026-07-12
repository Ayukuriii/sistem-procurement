<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Category\CategoryListRequest;
use App\Http\Resources\Api\Category\CategoryCollection;
use App\Services\CategoryService;

class CategoryListController extends Controller
{
    public function __construct(
        public CategoryService $userService
    ) {}

    public function list(CategoryListRequest $request): CategoryCollection
    {
        $paginator = $this->userService->paginate(
            filters: $request->filters(),
            perPage: $request->perPage(),
        );

        return new CategoryCollection($paginator);
    }
}
