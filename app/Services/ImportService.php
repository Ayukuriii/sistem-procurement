<?php

namespace App\Services;

use App\Services\ExportImport\ModuleHandlerRegistry;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ImportService
{
    public function __construct(
        protected ModuleHandlerRegistry $registry
    ) {}

    /**
     * @return array{module: string, imported_count: int, failed_count: int, failed_rows: list<array{row: int, errors: list<string>}>}
     */
    public function import(string $module, UploadedFile $file): array
    {
        $handler = $this->registry->importable($module);

        $sheets = Excel::toArray(new \stdClass, $file);
        $sheet = $sheets[0] ?? [];

        if ($sheet === []) {
            throw new UnprocessableEntityHttpException('File does not match the expected template for the chosen module');
        }

        $rawHeaders = array_map(
            fn ($h) => Str::lower(trim((string) $h)),
            $sheet[0] ?? []
        );

        $expected = $handler->expectedHeaders();
        $expectedLower = array_map(fn ($h) => Str::lower($h), $expected);

        if ($rawHeaders !== $expectedLower) {
            throw new UnprocessableEntityHttpException('File does not match the expected template for the chosen module');
        }

        $rows = [];
        for ($i = 1; $i < count($sheet); $i++) {
            $raw = $sheet[$i];
            if ($this->rowIsEmpty($raw)) {
                continue;
            }

            $assoc = [];
            foreach ($expected as $index => $header) {
                $assoc[$header] = $raw[$index] ?? null;
            }
            $assoc['_row'] = $i + 1; // Excel row number (1-based, header is row 1)
            $rows[] = $assoc;
        }

        $result = $handler->importRows($rows);

        return [
            'module' => $module,
            'imported_count' => $result['imported_count'],
            'failed_count' => $result['failed_count'],
            'failed_rows' => $result['failed_rows'],
        ];
    }

    /**
     * @return BinaryFileResponse|Response
     */
    public function downloadTemplate(string $module)
    {
        $handler = $this->registry->importable($module);

        return Excel::download(
            $handler->makeTemplateExporter(),
            "import-template-{$module}.xlsx"
        );
    }

    /**
     * @param  array<int, mixed>|null  $row
     */
    private function rowIsEmpty(?array $row): bool
    {
        if ($row === null) {
            return true;
        }

        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
