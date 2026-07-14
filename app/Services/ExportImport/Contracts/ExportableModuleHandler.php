<?php

namespace App\Services\ExportImport\Contracts;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

interface ExportableModuleHandler
{
    public function module(): string;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function makeExporter(array $filters): FromCollection&WithHeadings;

    public function filename(): string;
}
