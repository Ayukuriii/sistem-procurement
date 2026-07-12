<?php

namespace App\Http\Requests\Api\Supplier;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierUpdateRequest extends FormRequest
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
        $publicId = $this->route('publicId');

        return [
            'name' => ['sometimes',  'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')->ignore($publicId, 'public_id'),
            ],
            'phone' => [
                'sometimes',
                'string', 'max:20',
                Rule::unique('suppliers', 'phone')->ignore($publicId, 'public_id'),
            ],
            'is_active' => ['sometimes',  'boolean'],
            'metadata' => ['nullable', 'json'],
        ];
    }
}
