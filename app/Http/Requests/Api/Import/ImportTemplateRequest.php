<?php

namespace App\Http\Requests\Api\Import;

use App\Constants\Exports;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'module' => $this->query('module', $this->input('module')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'module' => ['required', 'string', Rule::in(Exports::IMPORTABLE)],
        ];
    }
}
