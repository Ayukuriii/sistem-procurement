<?php

namespace App\Services\ExportImport\Handlers;

use App\Constants\Exports;
use App\Exports\ArraySheetExport;
use App\Models\Category;
use App\Services\CategoryService;
use App\Services\ExportImport\Concerns\ImportsRows;
use App\Services\ExportImport\Contracts\ExportableModuleHandler;
use App\Services\ExportImport\Contracts\ImportableModuleHandler;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductCategoriesModuleHandler implements ExportableModuleHandler, ImportableModuleHandler
{
    use ImportsRows;

    public function __construct(
        protected CategoryService $categoryService
    ) {}

    public function module(): string
    {
        return Exports::MODULE_PRODUCT_CATEGORIES;
    }

    public function filename(): string
    {
        return 'product-categories-export-'.now()->format('Ymd-His').'.xlsx';
    }

    public function expectedHeaders(): array
    {
        return ['name'];
    }

    public function makeExporter(array $filters): FromCollection&WithHeadings
    {
        $query = Category::query();

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        $rows = $query->orderByDesc('created_at')->get()->map(fn (Category $category) => [
            'public_id' => $category->public_id,
            'name' => $category->name,
            'created_at' => optional($category->created_at)?->toIso8601String(),
        ])->all();

        return new ArraySheetExport(
            ['public_id', 'name', 'created_at'],
            $rows
        );
    }

    public function makeTemplateExporter(): FromCollection&WithHeadings
    {
        return new ArraySheetExport(
            $this->expectedHeaders(),
            [['name' => 'elektronik']]
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
            ];

            $errors = $this->validateRow($data, [
                'name' => ['required', 'string', 'max:255'],
            ]);

            if ($errors !== []) {
                $this->pushFailure($failedRows, $rowNumber, $errors);

                continue;
            }

            try {
                $this->categoryService->store($data);
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
