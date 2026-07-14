<?php

namespace App\Http\Resources\Api\Audit;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'user' => $this->when(
                $this->relationLoaded('user'),
                fn () => $this->user
                    ? [
                        'public_id' => $this->user->public_id,
                        'name' => $this->user->name,
                        'email' => $this->user->email,
                    ]
                    : null
            ),
            'event' => $this->event,
            'action' => $this->action ?? null,
            'description' => $this->description ?? null,
            'auditable_type' => $this->auditable_type_slug ?? null,
            'auditable_id' => $this->auditable_public_id ?? null,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'url' => $this->url,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at,
        ];
    }
}
