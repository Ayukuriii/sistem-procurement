<?php

namespace App\Http\Controllers\Api\PurchaseOrderItem;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PurchaseOrder\POItemListRequest;
use App\Http\Resources\POItemCollection;
use App\Models\PurchaseOrder;
use App\Services\POItemService;

class POItemListController extends Controller
{
    public function __construct(
        public POItemService $poItemService
    ) {}

    public function list(POItemListRequest $request, string $publicId): POItemCollection
    {
        $poExists = PurchaseOrder::where('public_id', $publicId)->exists();
        if (! $poExists) {
            abort(404, 'Purchase Order not found');
        }

        $paginator = $this->poItemService->paginate(
            filters: $request->filters(),
            publicId: $publicId,
            perPage: $request->perPage(),
        );

        return new POItemCollection($paginator);
    }
}
