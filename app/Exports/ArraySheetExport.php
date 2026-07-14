<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ArraySheetExport implements FromCollection, WithHeadings
{
    /**
     * @param  list<string>  $headings
     * @param  list<array<int|string, mixed>>  $rows
     */
    public function __construct(
        protected array $headings,
        protected array $rows = []
    ) {}

    public function headings(): array
    {
        return $this->headings;
    }

    public function collection(): Collection
    {
        return collect($this->rows)->map(function (array $row) {
            // Preserve heading order
            $ordered = [];
            foreach ($this->headings as $heading) {
                $ordered[] = $row[$heading] ?? $row[array_search($heading, $this->headings, true)] ?? '';
            }

            return $ordered;
        });
    }
}
