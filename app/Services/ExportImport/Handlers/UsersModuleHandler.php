<?php

namespace App\Services\ExportImport\Handlers;

use App\Constants\Exports;
use App\Constants\Roles;
use App\Exports\ArraySheetExport;
use App\Models\User;
use App\Services\ExportImport\Concerns\ImportsRows;
use App\Services\ExportImport\Contracts\ExportableModuleHandler;
use App\Services\ExportImport\Contracts\ImportableModuleHandler;
use App\Services\UserService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Spatie\Permission\Models\Role;

class UsersModuleHandler implements ExportableModuleHandler, ImportableModuleHandler
{
    use ImportsRows;

    public function __construct(
        protected UserService $userService
    ) {}

    public function module(): string
    {
        return Exports::MODULE_USERS;
    }

    public function filename(): string
    {
        return 'users-export-'.now()->format('Ymd-His').'.xlsx';
    }

    public function expectedHeaders(): array
    {
        return ['name', 'email', 'role', 'is_active'];
    }

    public function makeExporter(array $filters): FromCollection&WithHeadings
    {
        $query = User::query()->with('roles');

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['role'])) {
            $roleName = (string) $filters['role'];
            $query->whereHas('roles', fn ($q) => $q->where('name', $roleName));
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $rows = $query->orderByDesc('created_at')->get()->map(fn (User $user) => [
            'public_id' => $user->public_id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active ? '1' : '0',
            'role' => $user->roles->first()?->name ?? '',
            'created_at' => optional($user->created_at)?->toIso8601String(),
        ])->all();

        return new ArraySheetExport(
            ['public_id', 'name', 'email', 'is_active', 'role', 'created_at'],
            $rows
        );
    }

    public function makeTemplateExporter(): FromCollection&WithHeadings
    {
        return new ArraySheetExport(
            $this->expectedHeaders(),
            [[
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'role' => Roles::ROLE_STAFF,
                'is_active' => '1',
            ]]
        );
    }

    public function importRows(array $rows): array
    {
        $imported = 0;
        $failedRows = [];

        foreach ($rows as $row) {
            $rowNumber = (int) ($row['_row'] ?? 0);
            $data = [
                'name' => isset($row['name']) ? trim((string) $row['name']) : null,
                'email' => isset($row['email']) ? trim((string) $row['email']) : null,
                'role' => isset($row['role']) ? trim((string) $row['role']) : null,
                'is_active' => $this->toBool($row['is_active'] ?? true),
            ];

            $errors = $this->validateRow($data, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'role' => ['required', 'string', 'in:'.Roles::ROLE_ADMIN.','.Roles::ROLE_STAFF],
                'is_active' => ['boolean'],
            ]);

            if ($errors !== []) {
                $this->pushFailure($failedRows, $rowNumber, $errors);

                continue;
            }

            $role = Role::query()
                ->where('name', $data['role'])
                ->where('guard_name', 'web')
                ->first();

            if (! $role) {
                $this->pushFailure($failedRows, $rowNumber, ["Role [{$data['role']}] tidak ditemukan."]);

                continue;
            }

            try {
                $user = $this->userService->store([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Exports::DEFAULT_USER_PASSWORD,
                    'role_id' => $role->public_id,
                ]);

                if ($user->is_active !== $data['is_active']) {
                    $user->is_active = $data['is_active'];
                    $user->save();
                }

                $imported++;
            } catch (\Throwable $e) {
                $this->pushFailure($failedRows, $rowNumber, [$e->getMessage()]);
            }
        }

        return [
            'imported_count' => $imported,
            'failed_count' => count($failedRows),
            'failed_rows' => $failedRows,
        ];
    }
}
