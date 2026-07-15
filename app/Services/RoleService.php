<?php

namespace App\Services;

use App\Constants\Paginations;
use App\Constants\Roles;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RoleService
{
    private const SORTABLE_COLUMNS = ['name', 'created_at'];

    private const DEFAULT_SORT_COLUMN = 'created_at';

    private const DEFAULT_PER_PAGE = Paginations::DEFAULT_PER_PAGE;

    private const GUARD_NAME = 'web';

    /**
     * @param  array{search?: ?string, sort?: ?string}  $filters
     */
    public function paginate(array $filters, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = Role::query()->where('guard_name', self::GUARD_NAME);

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

    public function store(array $request): Role
    {
        return DB::transaction(function () use ($request) {
            return Role::create([
                'public_id' => (string) Str::uuid7(),
                'name' => $request['name'],
                'guard_name' => self::GUARD_NAME,
            ]);
        });
    }

    public function getRole(string $publicId): Role
    {
        $role = Role::query()
            ->where('guard_name', self::GUARD_NAME)
            ->where('public_id', $publicId)
            ->first();

        if (! $role) {
            throw new \Exception('Role not found');
        }

        return $role;
    }

    public function update(array $request, string $publicId): Role
    {
        $role = $this->getRole($publicId);

        $role->update([
            'name' => $request['name'],
        ]);

        return $role->fresh();
    }

    public function destroy(string $publicId): void
    {
        $role = $this->getRole($publicId);

        if (in_array($role->name, [Roles::ROLE_ADMIN, Roles::ROLE_STAFF], true)) {
            throw new \Exception('System role cannot be deleted');
        }

        if ($role->users()->exists()) {
            throw new \Exception('Role already assigned to users');
        }

        $role->delete();
    }
}
