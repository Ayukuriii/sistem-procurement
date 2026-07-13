<?php

namespace App\Services;

use App\Constants\Paginations;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    private const SORTABLE_COLUMNS = ['category', 'sku', 'name', 'unit_price', 'is_active', 'created_at'];

    private const DEFAULT_SORT_COLUMN = 'created_at';

    private const DEFAULT_PER_PAGE = Paginations::DEFAULT_PER_PAGE;

    /**
     * @param  array{search?: ?string, sort?: ?string}  $filters
     */
    public function paginate(array $filters, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = Product::query();

        if (! empty($filters['search'])) {
            $this->applySearch($query, $filters['search']);
        }

        $this->applySort($query, $filters['sort'] ?? null);

        return $query->paginate($perPage);
    }

    private function applySearch($query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%");

            if (is_numeric($search)) {
                $q->orWhere('unit_price', '>=', $search);
            }

            $q->orWhereExists(function ($subQuery) use ($search) {
                $subQuery->select(DB::raw(1))
                    ->from('categories')
                    ->whereColumn('categories.id', 'products.category_id')
                    ->where('categories.name', 'like', "%{$search}%");
            });
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

        if ($column === 'category') {
            $query->orderBy(
                Category::select('name')
                    ->whereColumn('categories.id', 'products.category_id')
                    ->take(1),
                $direction
            );

            return;
        }

        $query->orderBy($column, $direction);
    }

    public function store(array $request): Product
    {
        return DB::transaction(function () use ($request) {
            // 1. get category
            $category = Category::firstWhere('public_id', $request['category_id']);
            if (! $category) {
                throw new \Exception('Category not found');
            }

            // 2. add category into request payload
            $request['category_id'] = $category->id;

            // 3. create product
            $product = Product::create($request);

            return $product->fresh();
        });
    }

    public function getProduct(string $publicId): Product
    {
        $product = Product::where('public_id', $publicId)
            ->first();

        if (! $product) {
            throw new \Exception('Product not found');
        }

        return $product;
    }

    public function update(array $request, string $publicId): Product
    {
        $product = Product::firstWhere('public_id', $publicId);

        if (! $product) {
            throw new \Exception('Product not found');
        }

        $category = Category::firstWhere('public_id', $request['category_id']);

        if (! $category){
            throw new \Exception('Category not found');
        }

        // if the category unchanged, then remove 'category_id' from the payload
        if($product->category_id == $category->id){
            unset($request['category_id']);
        } else {
            // if the category not match, then replace with it's id
            $request['category_id'] = $category->id;
        }

        $product->update($request);

        return $product->fresh();
    }

    public function destroy(string $publicId): void
    {
        $product = Product::firstWhere('public_id', $publicId);

        if (! $product) {
            throw new \Exception('Product not found');
        }

        $product->delete();

    }
}
