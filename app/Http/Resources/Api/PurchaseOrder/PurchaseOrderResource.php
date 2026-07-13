<?php

namespace App\Http\Resources\Api\PurchaseOrder;

use App\Http\Resources\Api\Supplier\SupplierResource;
use App\Http\Resources\Api\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
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
            'po_number' => $this->po_number,
            'supplier' => new SupplierResource($this->supplier),
            'creator' => new UserResource($this->creator),
            'order_date' => $this->order_date,
            'status' => $this->status,
            'is_urgent' => $this->is_urgent,
            'notes' => $this->notes,
            'item_count' => $this->item_count,
            'items_total' => $this->items_total,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
