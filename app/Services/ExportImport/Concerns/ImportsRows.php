<?php

namespace App\Services\ExportImport\Concerns;

use Illuminate\Support\Facades\Validator;

trait ImportsRows
{
    /**
     * @param  list<string>  $errors
     * @param  list<array{row: int, errors: list<string>}>  $failedRows
     */
    protected function pushFailure(array &$failedRows, int $rowNumber, array $errors): void
    {
        $failedRows[] = [
            'row' => $rowNumber,
            'errors' => array_values($errors),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $rules
     * @return list<string>
     */
    protected function validateRow(array $data, array $rules): array
    {
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        return [];
    }

    /**
     * Normalize Excel boolean-ish values.
     */
    protected function toBool(mixed $value, bool $default = true): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'y'], true);
    }
}
