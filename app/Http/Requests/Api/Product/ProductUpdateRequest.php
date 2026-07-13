<?php

namespace App\Http\Requests\Api\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
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
            'category_id' => ['sometimes', 'uuid'],
            'sku' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($publicId, 'public_id')
                ],
            'name' => ['sometimes', 'string', 'max:255'],
            'unit_price' => ['sometimes', 'numeric'],
            'is_active' => ['sometimes', 'boolean']
        ];
    }
}
