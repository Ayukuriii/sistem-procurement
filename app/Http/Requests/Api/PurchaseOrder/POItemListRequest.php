<?php

namespace App\Http\Requests\Api\PurchaseOrder;

use App\Constants\Paginations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class POItemListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in([
                    'product_name_snapshot',
                    '-product_name_snapshot',
                    'unit_price_snapshot',
                    '-unit_price_snapshot',
                    'created_at',
                    '-created_at',
                ]),
            ],
        ];
    }

    /**
     * Filters consumed by UserService::paginate(), decoupled from the
     * raw request so the service never depends on the HTTP layer.
     */
    public function filters(): array
    {
        return [
            'search' => $this->validated('search'),
            'sort' => $this->validated('sort') ?? '-created_at',
        ];
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', Paginations::DEFAULT_PER_PAGE);
    }
}
