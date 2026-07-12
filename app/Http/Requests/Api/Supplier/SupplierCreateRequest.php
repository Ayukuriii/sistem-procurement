<?php

namespace App\Http\Requests\Api\Supplier;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SupplierCreateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required',  'email', 'max:255', 'unique:suppliers,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:suppliers,email'],
            'address' => ['required',  'string', 'max:1000'],
            'metadata' => ['nullable', 'json'],
        ];
    }
}
