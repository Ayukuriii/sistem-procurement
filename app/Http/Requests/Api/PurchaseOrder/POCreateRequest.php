<?php

namespace App\Http\Requests\Api\PurchaseOrder;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class POCreateRequest extends FormRequest
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
            'supplier_id' => ['required', 'uuid', 'exists:suppliers,public_id'],
            'order_date' => ['required', 'date', 'date_format:Y-m-d'],
            'is_urgent' => ['required', 'boolean'],

            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,public_id'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
        ];
    }
}
