<?php

namespace App\Http\Requests\Api\Import;

use App\Constants\Exports;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportStoreRequest extends FormRequest
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
            'module' => ['required', 'string', Rule::in(Exports::IMPORTABLE)],
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:'.Exports::MAX_IMPORT_FILE_SIZE_KB,
            ],
        ];
    }
}
