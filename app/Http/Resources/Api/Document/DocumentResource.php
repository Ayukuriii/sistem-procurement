<?php

namespace App\Http\Resources\Api\Document;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'metadata' => array_merge($this->metadata ?? [], [
                'file_url' => $this->file_url,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
