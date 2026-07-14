<?php

namespace App\Http\Requests\Api\Document;

use App\Constants\Documents;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DocumentCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:pdf',
                'mimetypes:'.Documents::ALLOWED_MIME,
                'max:'.Documents::MAX_FILE_SIZE_KB,
            ],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
