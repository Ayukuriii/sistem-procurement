<?php

namespace App\Http\Resources;

use App\Http\Resources\Api\Product\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class POItemResource extends JsonResource
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
            'purchase_order_id' => $this->purchaseOrder->public_id,
            'product' => new ProductResource($this->product),
            'product_name_snapshot' => $this->product_name_snapshot,
            'unit_price_snapshot' => $this->unit_price_snapshot,
            'quantity' => $this->quantity,
            'subtotal' => $this->subtotal,
            'created_at' => $this->created_at,
        ];
    }
}
