<?php

namespace App\Http\Requests\Api\Select2;

use Illuminate\Foundation\Http\FormRequest;

class Select2QueryRequest extends FormRequest
{
    private const DEFAULT_PER_PAGE = 20;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function term(): ?string
    {
        $term = $this->validated('q') ?? $this->validated('search');

        if ($term === null || $term === '') {
            return null;
        }

        return $term;
    }

    public function page(): int
    {
        return (int) ($this->validated('page') ?? 1);
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? self::DEFAULT_PER_PAGE);
    }
}
