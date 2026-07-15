<?php

namespace App\Http\Resources\Api\Select2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Select2OptionResource extends JsonResource
{
    /**
     * @return array{id: string, text: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->resource['id'],
            'text' => (string) $this->resource['text'],
        ];
    }
}
