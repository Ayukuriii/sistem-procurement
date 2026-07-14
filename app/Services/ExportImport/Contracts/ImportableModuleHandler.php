<?php

namespace App\Services\ExportImport\Contracts;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

interface ImportableModuleHandler
{
    public function module(): string;

    /**
     * @return list<string>
     */
    public function expectedHeaders(): array;

    /**
     * @param  list<array<string, mixed>>  $rows  heading-keyed rows (1-based source row in meta)
     * @return array{imported_count: int, failed_count: int, failed_rows: list<array{row: int, errors: list<string>}>}
     */
    public function importRows(array $rows): array;

    public function makeTemplateExporter(): FromCollection&WithHeadings;
}
