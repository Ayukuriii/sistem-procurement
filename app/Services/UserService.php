<?php

namespace App\Services;

use App\Constants\Paginations;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserService
{
    private const SORTABLE_COLUMNS = ['name', 'email', 'created_at'];

    private const DEFAULT_SORT_COLUMN = 'created_at';

    private const DEFAULT_PER_PAGE = Paginations::DEFAULT_PER_PAGE;

    /**
     * @param  array{search?: ?string, sort?: ?string}  $filters
     */
    public function paginate(array $filters, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = User::query()->with('roles');

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
                ->orWhere('email', 'like', "%{$search}%");
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

    public function store(array $request): User
    {
        return DB::transaction(function () use ($request) {
            // Check if email already exists
            if (User::where('email', $request['email'])->exists()) {
                throw new \Exception('Email already registered');
            }

            // Check for role
            $role = Role::firstWhere('public_id', $request['role_id']);
            if (! $role || $role == null) {
                throw new \Exception('Role not found');
            }

            $user = User::create($request);
            $user->assignRole($role);

            return $user->fresh();
        });
    }

    public function getUser(string $publicId): User
    {
        $user = User::where('public_id', $publicId)
            ->first();

        if (! $user) {
            throw new \Exception('User not found');
        }

        return $user;
    }

    public function update(array $request, string $publicId): User
    {
        $user = User::firstWhere('public_id', $publicId);

        if (! $user) {
            throw new \Exception('User not found');
        }

        if (! empty($request['password'])) {
            $request['password'] = Hash::make($request['password']);
        } else {
            unset($request['password']);
        }

        if (array_key_exists('is_active', $request)) {
            $user->is_active = (bool) $request['is_active'];
        }

        $user->update($request);

        return $user->fresh();
    }

    public function delete(string $publicId): void
    {
        $user = User::firstWhere('public_id', $publicId);

        if (! $user) {
            throw new \Exception('User not found');
        }

        $user->delete();

    }

    public function updateRole(array $request, string $publicId): User
    {
        $user = User::firstWhere('public_id', $publicId);

        if (! $user) {
            throw new \Exception('User not found');
        }

        $role = Role::firstWhere('public_id', $request['role_id']);
        if (! $role) {
            throw new \Exception('Role not found');
        }

        $user->syncRoles($role);

        return $user->fresh();
    }
}
