<?php

namespace App\Http\Requests\Api\Audit;

use App\Constants\Audits;
use App\Constants\Paginations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuditListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'auditable_type' => ['sometimes', 'nullable', 'string', Rule::in(Audits::types())],
            'auditable_id' => ['sometimes', 'nullable', 'uuid'],
            'user_id' => ['sometimes', 'nullable', 'uuid'],
            'event' => ['sometimes', 'nullable', 'string', Rule::in(Audits::events())],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['sometimes', 'nullable', 'string', 'in:created_at,-created_at'],
        ];
    }

    /**
     * @return array{
     *     auditable_type: ?string,
     *     auditable_id: ?string,
     *     user_id: ?string,
     *     event: ?string,
     *     date_from: ?string,
     *     date_to: ?string,
     *     sort: ?string
     * }
     */
    public function filters(): array
    {
        return [
            'auditable_type' => $this->validated('auditable_type'),
            'auditable_id' => $this->validated('auditable_id'),
            'user_id' => $this->validated('user_id'),
            'event' => $this->validated('event'),
            'date_from' => $this->validated('date_from'),
            'date_to' => $this->validated('date_to'),
            'sort' => $this->validated('sort') ?? '-created_at',
        ];
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', Paginations::DEFAULT_PER_PAGE);
    }
}
