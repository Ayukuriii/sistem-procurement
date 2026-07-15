<?php

namespace App\Services;

use App\Constants\Paginations;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SupplierService
{
    private const SORTABLE_COLUMNS = ['name', 'email', 'phone', 'created_at'];

    private const DEFAULT_SORT_COLUMN = 'created_at';

    private const DEFAULT_PER_PAGE = Paginations::DEFAULT_PER_PAGE;

    /**
     * @param  array{search?: ?string, sort?: ?string}  $filters
     */
    public function paginate(array $filters, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = Supplier::query();

        if (! empty($filters['search'])) {
            $this->applySearch($query, $filters['search']);
        }

        $this->applySort($query, $filters['sort'] ?? null);

        return $query->paginate($perPage);
    }

    public function paginateForSelect(?string $term, int $perPage = 20): LengthAwarePaginator
    {
        $query = Supplier::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($term !== null && $term !== '') {
            $this->applySearch($query, $term);
        }

        return $query->paginate($perPage);
    }

    private function applySearch($query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
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

    public function store(array $request): Supplier
    {
        return DB::transaction(function () use ($request) {
            // Check if email already exists
            if (Supplier::where('email', $request['email'])->exists()) {
                throw new \Exception('Email already exists');
            }

            // Check if phone already exists
            if (Supplier::where('phone', $request['phone'])->exists()) {
                throw new \Exception('Phone already exists');
            }

            $supplier = Supplier::create($request);

            return $supplier->fresh();
        });
    }

    public function getSupplier(string $publicId): Supplier
    {
        $supplier = Supplier::where('public_id', $publicId)
            ->first();

        if (! $supplier) {
            throw new \Exception('Supplier not found');
        }

        return $supplier;
    }

    public function update(array $request, string $publicId): Supplier
    {
        $supplier = Supplier::firstWhere('public_id', $publicId);

        if (! $supplier) {
            throw new \Exception('Supplier not found');
        }

        if (array_key_exists('is_active', $request)) {
            $supplier->is_active = (bool) $request['is_active'];
        }

        $supplier->update($request);

        return $supplier->fresh();
    }

    public function destroy(string $publicId): void
    {
        $supplier = Supplier::firstWhere('public_id', $publicId);

        if (! $supplier) {
            throw new \Exception('Supplier not found');
        }

        $supplier->delete();

    }
}
