<?php

namespace App\Services\ExportImport\Handlers;

use App\Constants\Exports;
use App\Exports\ArraySheetExport;
use App\Models\Category;
use App\Models\Product;
use App\Services\ExportImport\Concerns\ImportsRows;
use App\Services\ExportImport\Contracts\ExportableModuleHandler;
use App\Services\ExportImport\Contracts\ImportableModuleHandler;
use App\Services\ProductService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsModuleHandler implements ExportableModuleHandler, ImportableModuleHandler
{
    use ImportsRows;

    public function __construct(
        protected ProductService $productService
    ) {}

    public function module(): string
    {
        return Exports::MODULE_PRODUCTS;
    }

    public function filename(): string
    {
        return 'products-export-'.now()->format('Ymd-His').'.xlsx';
    }

    public function expectedHeaders(): array
    {
        return ['category_id', 'sku', 'name', 'unit_price', 'is_active'];
    }

    public function makeExporter(array $filters): FromCollection&WithHeadings
    {
        $query = Product::query()->with('category');

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['category_id'])) {
            $category = Category::firstWhere('public_id', $filters['category_id']);
            if ($category) {
                $query->where('category_id', $category->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $rows = $query->orderByDesc('created_at')->get()->map(fn (Product $product) => [
            'public_id' => $product->public_id,
            'category_id' => $product->category?->public_id,
            'category_name' => $product->category?->name,
            'sku' => $product->sku,
            'name' => $product->name,
            'unit_price' => $product->unit_price,
            'is_active' => $product->is_active ? '1' : '0',
            'created_at' => optional($product->created_at)?->toIso8601String(),
        ])->all();

        return new ArraySheetExport(
            ['public_id', 'category_id', 'category_name', 'sku', 'name', 'unit_price', 'is_active', 'created_at'],
            $rows
        );
    }

    public function makeTemplateExporter(): FromCollection&WithHeadings
    {
        return new ArraySheetExport(
            $this->expectedHeaders(),
            [[
                'category_id' => '00000000-0000-0000-0000-000000000000',
                'sku' => 'SKU-001',
                'name' => 'Contoh Produk',
                'unit_price' => 10000,
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
                'category_id' => isset($row['category_id']) ? trim((string) $row['category_id']) : null,
                'sku' => isset($row['sku']) ? trim((string) $row['sku']) : null,
                'name' => isset($row['name']) ? trim((string) $row['name']) : null,
                'unit_price' => $row['unit_price'] ?? null,
                'is_active' => $this->toBool($row['is_active'] ?? true),
            ];

            $errors = $this->validateRow($data, [
                'category_id' => ['required', 'uuid', 'exists:categories,public_id'],
                'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
                'name' => ['required', 'string', 'max:255'],
                'unit_price' => ['required', 'numeric'],
                'is_active' => ['boolean'],
            ]);

            if ($errors !== []) {
                $this->pushFailure($failedRows, $rowNumber, $errors);

                continue;
            }

            try {
                $this->productService->store($data);
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
