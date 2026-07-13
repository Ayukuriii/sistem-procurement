<?php

namespace App\Services;

use App\Constants\Paginations;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    private const SORTABLE_COLUMNS = ['name', 'created_at'];

    private const DEFAULT_SORT_COLUMN = 'created_at';

    private const DEFAULT_PER_PAGE = Paginations::DEFAULT_PER_PAGE;

    /**
     * @param  array{search?: ?string, sort?: ?string}  $filters
     */
    public function paginate(array $filters, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = Category::query();

        if (! empty($filters['search'])) {
            $this->applySearch($query, $filters['search']);
        }

        $this->applySort($query, $filters['sort'] ?? null);

        return $query->paginate($perPage);
    }

    private function applySearch($query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        });
    }

    private function applySort($query, ?string $sort): void
    {
        $sort ??= '-'.self::DEFAULT_SORT_COLUMN;

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        if (! in_array($column, self::SORTABLE_COLUMNS, true)) {
            $column = self::DEFAULT_SORT_COLUMN;
            $direction = 'desc';
        }

        $query->orderBy($column, $direction);
    }

    public function store(array $request): Category
    {
        return DB::transaction(function () use ($request) {
            $request['name'] = strtolower($request['name']);
            $category = Category::create($request);

            return $category->fresh();
        });
    }

    public function getCategory(string $publicId): Category
    {
        $category = Category::where('public_id', $publicId)
            ->first();

        if (! $category) {
            throw new \Exception('Category not found');
        }

        return $category;
    }

    public function update(array $request, string $publicId): Category
    {
        $category = Category::firstWhere('public_id', $publicId);

        if (! $category) {
            throw new \Exception('Category not found');
        }

        if (isset($request['name'])) {
            $request['name'] = strtolower($request['name']);
        }

        $category->update($request);

        return $category->fresh();
    }

    public function destroy(string $publicId): void
    {
        $category = Category::firstWhere('public_id', $publicId);

        if (! $category) {
            throw new \Exception('Category not found');
        }

        $category->delete();
    }
}
