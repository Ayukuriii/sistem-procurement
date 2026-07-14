<?php

namespace App\Services\ExportImport\Handlers;

use App\Constants\Exports;
use App\Exports\ArraySheetExport;
use App\Models\Supplier;
use App\Services\ExportImport\Concerns\ImportsRows;
use App\Services\ExportImport\Contracts\ExportableModuleHandler;
use App\Services\ExportImport\Contracts\ImportableModuleHandler;
use App\Services\SupplierService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SuppliersModuleHandler implements ExportableModuleHandler, ImportableModuleHandler
{
    use ImportsRows;

    public function __construct(
        protected SupplierService $supplierService
    ) {}

    public function module(): string
    {
        return Exports::MODULE_SUPPLIERS;
    }

    public function filename(): string
    {
        return 'suppliers-export-'.now()->format('Ymd-His').'.xlsx';
    }

    public function expectedHeaders(): array
    {
        return ['name', 'email', 'phone', 'address', 'is_active'];
    }

    public function makeExporter(array $filters): FromCollection&WithHeadings
    {
        $query = Supplier::query();

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $rows = $query->orderByDesc('created_at')->get()->map(fn (Supplier $supplier) => [
            'public_id' => $supplier->public_id,
            'name' => $supplier->name,
            'email' => $supplier->email,
            'phone' => $supplier->phone,
            'address' => $supplier->address,
            'is_active' => $supplier->is_active ? '1' : '0',
            'created_at' => optional($supplier->created_at)?->toIso8601String(),
        ])->all();

        return new ArraySheetExport(
            ['public_id', 'name', 'email', 'phone', 'address', 'is_active', 'created_at'],
            $rows
        );
    }

    public function makeTemplateExporter(): FromCollection&WithHeadings
    {
        return new ArraySheetExport(
            $this->expectedHeaders(),
            [[
                'name' => 'PT Contoh Supplier',
                'email' => 'supplier@example.com',
                'phone' => '08123456789',
                'address' => 'Jl. Contoh No. 1',
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
                'phone' => isset($row['phone']) ? trim((string) $row['phone']) : null,
                'address' => isset($row['address']) ? trim((string) $row['address']) : null,
                'is_active' => $this->toBool($row['is_active'] ?? true),
            ];

            $errors = $this->validateRow($data, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:suppliers,email'],
                'phone' => ['required', 'string', 'max:20', 'unique:suppliers,phone'],
                'address' => ['required', 'string', 'max:1000'],
                'is_active' => ['boolean'],
            ]);

            if ($errors !== []) {
                $this->pushFailure($failedRows, $rowNumber, $errors);

                continue;
            }

            try {
                $this->supplierService->store($data);
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
