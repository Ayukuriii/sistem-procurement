<?php

namespace App\Http\Requests\Api\Export;

use App\Constants\Exports;
use App\Constants\Paginations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'module' => ['sometimes', 'nullable', 'string', Rule::in(Exports::EXPORTABLE)],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array{module?: ?string}
     */
    public function filters(): array
    {
        return [
            'module' => $this->query('module'),
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->query('per_page') ?? Paginations::DEFAULT_PER_PAGE);
    }
}
