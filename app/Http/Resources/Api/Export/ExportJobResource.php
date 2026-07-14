<?php

namespace App\Http\Resources\Api\Export;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExportJobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'export_job_id' => $this->public_id,
            'module' => $this->module,
            'status' => $this->status,
            'requested_at' => optional($this->created_at)?->toIso8601String(),
            'completed_at' => optional($this->completed_at)?->toIso8601String(),
            'download_url' => $this->download_url,
            'failure_reason' => $this->failure_reason,
        ];
    }
}
