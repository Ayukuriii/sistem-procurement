<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

class RoleSelectService
{
    private const DEFAULT_PER_PAGE = 20;

    public function paginateForSelect(?string $term, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name');

        if ($term !== null && $term !== '') {
            $query->where('name', 'like', "%{$term}%");
        }

        return $query->paginate($perPage);
    }
}
