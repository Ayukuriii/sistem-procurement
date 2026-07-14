<?php

namespace App\Services;

use App\Constants\Paginations;
use App\Constants\Statuses;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Class POItemService
 */
class POItemService
{
    private const SORTABLE_COLUMNS = ['product_name_snapshot', 'unit_price_snapshot', 'created_at'];

    private const DEFAULT_SORT_COLUMN = 'created_at';

    private const DEFAULT_PER_PAGE = Paginations::DEFAULT_PER_PAGE;

    /**
     * @param  array{search?: ?string, sort?: ?string}  $filters
     */
    public function paginate(array $filters, string $publicId, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = PurchaseOrderItem::query()
            ->with(['product'])
            ->withWhereHas('purchaseOrder', function ($q) use ($publicId) {
                $q->where('public_id', $publicId);
            });

        if (! empty($filters['search'])) {
            $this->applySearch($query, $filters['search']);
        }

        $this->applySort($query, $filters['sort'] ?? null);

        return $query->paginate($perPage);
    }

    private function applySearch($query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('po_number', 'like', "{$search}%");
        });
    }

    private function applySort($query, ?string $sort): void
    {
        $sort ??= '-'.self::DEFAULT_SORT_COLUMN;

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        if (! in_array($column, self::SORTABLE_COLUMNS, true)) {
            $column = self::DEFAULT_SORT_COLUMN;
            $direction = 'desc';
        }

        $query->orderBy($column, $direction);
    }

    public function store(array $request, string $publicId): PurchaseOrderItem
    {
        $purchaseOrder = PurchaseOrder::firstWhere('public_id', $publicId);

        if (! $purchaseOrder) {
            throw new \Exception('Purchase order not found!');
        }

        if ($purchaseOrder->status !== Statuses::PO_DRAFT) {
            throw new \Exception('Cannot add items. Purchase order is already '.$purchaseOrder->status, 422);
        }

        // get product data
        $product = Product::firstWhere('public_id', $request['product_id']);

        if (! $product) {
            throw new \Exception('Product not found!');
        }

        return DB::transaction(function () use ($request, $purchaseOrder, $product) {
            $subtotal = $this->calculateSubtotal($product->unit_price, $request['quantity']);

            $POItem = $purchaseOrder->items()->create([
                'product_id' => $product->id,
                'product_name_snapshot' => $product->name,
                'unit_price_snapshot' => $product->unit_price,
                'quantity' => $request['quantity'],
                'subtotal' => $subtotal,
            ]);

            return $POItem;
        });
    }

    private function calculateSubtotal(int $unitPrice, int $quantity)
    {
        return $unitPrice * $quantity;
    }

    public function getPoItem(string $publicId, string $itemPublicId): PurchaseOrderItem
    {
        $purchaseOrder = PurchaseOrder::firstWhere('public_id', $publicId);

        if (! $purchaseOrder) {
            throw new \Exception('Purchase order not found!', 404);
        }

        $POItem = PurchaseOrderItem::where('public_id', $itemPublicId)
            ->where('purchase_order_id', $purchaseOrder->id)
            ->with([
                'product',
                'purchaseOrder',
            ])
            ->first();

        if (! $POItem) {
            throw new \Exception('Purchase order item not found!', 404);
        }

        return $POItem;
    }

    public function update(array $request, string $publicId, string $itemPublicId): PurchaseOrderItem
    {
        $POItem = $this->getPoItem($publicId, $itemPublicId);

        $purchaseOrder = $POItem->purchaseOrder;
        if ($purchaseOrder->status !== Statuses::PO_DRAFT) {
            throw new \Exception('Cannot update item. Purchase order is already '.$purchaseOrder->status, 422);
        }

        return DB::transaction(function () use ($request, $POItem) {
            $newSubtotal = $this->calculateSubtotal($POItem->unit_price_snapshot, $request['quantity']);

            $POItem->update([
                'quantity' => $request['quantity'],
                'subtotal' => $newSubtotal,
            ]);

            return $POItem;
        });
    }

    public function destroy(string $publicId, string $itemPublicId): void
    {
        $POItem = $this->getPoItem($publicId, $itemPublicId);

        $POItem->delete();
    }
}
