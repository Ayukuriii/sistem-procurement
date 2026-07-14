<?php

namespace App\Services;

use App\Constants\Paginations;
use App\Constants\Statuses;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Class PurchaseOrderService
 */
class PurchaseOrderService
{
    private const SORTABLE_COLUMNS = ['po_number', 'order_date', 'created_at'];

    private const DEFAULT_SORT_COLUMN = 'created_at';

    private const DEFAULT_PER_PAGE = Paginations::DEFAULT_PER_PAGE;

    /**
     * @param  array{search?: ?string, sort?: ?string}  $filters
     */
    public function paginate(array $filters, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = PurchaseOrder::query()
            ->withCount('items')
            ->withSum('items as items_total', 'subtotal')
            ->withExists('document as document_exists');

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

    public function store(array $request): PurchaseOrder
    {
        $supplier = Supplier::firstWhere('public_id', $request['supplier_id']);
        if (! $supplier) {
            throw new \Exception('Supplier not found');
        }

        $userId = Auth::id();

        if (! $userId) {
            throw new \Exception('Unauthorized', 401);
        }

        // validate product items
        $productIds = collect($request['items'])->pluck('product_id')->toArray();
        $products = Product::whereIn('public_id', $productIds)->get()->keyBy('public_id');

        foreach ($request['items'] as $item) {
            if (! isset($products[$item['product_id']])) {
                throw new \Exception("Product with ID {$item['product_id']} not found");
            }
        }

        /**
         * use atomic lock for race condition safety
         * atomic lock max duration 10s
         */
        return Cache::lock('generate-po-number', 5)->block(10, function () use ($request, $supplier, $userId, $products) {
            return DB::transaction(function () use ($request, $supplier, $userId, $products) {
                $request['po_number'] = $this->generatePoNumber();

                $purchaseOrder = new PurchaseOrder($request);
                $purchaseOrder->supplier()->associate($supplier);
                $purchaseOrder->creator()->associate($userId);

                // for initial PO creation set the status value to 'draft'
                $purchaseOrder->status = Statuses::PO_DRAFT;

                $purchaseOrder->save();

                foreach ($request['items'] as $itemData) {
                    $product = $products[$itemData['product_id']];
                    $subtotal = $product->unit_price * $itemData['quantity'];

                    $purchaseOrder->items()->create([
                        'product_id' => $product->id,
                        'product_name_snapshot' => $product->name,
                        'unit_price_snapshot' => $product->unit_price,
                        'quantity' => $itemData['quantity'],
                        'subtotal' => $subtotal,
                    ]);
                }

                return $purchaseOrder->load('items.product');
            });
        });
    }

    private function generatePoNumber(): string
    {
        $startOfYear = now()->tz('asia/jakarta')->startOfYear()->utc();
        $endOfYear = now()->tz('asia/jakarta')->endOfYear()->utc();

        $currentYear = now()->tz('asia/jakarta')->format('Y');

        $poCount = PurchaseOrder::whereBetween('created_at', [$startOfYear, $endOfYear])
            ->withTrashed()
            ->count();

        $nextSequence = $poCount + 1;

        return 'PO-'.$currentYear.'-'.str_pad($nextSequence, 6, '0', STR_PAD_LEFT);
    }

    public function getPurchaseOrder(string $publicId): PurchaseOrder
    {
        $purchaseOrder = PurchaseOrder::where('public_id', $publicId)
            ->with([
                'supplier',
                'creator',
                'document',
            ])
            ->withCount('items')
            ->withSum('items as items_total', 'subtotal')
            ->first();

        if (! $purchaseOrder) {
            throw new \Exception('Purchase order not found');
        }

        return $purchaseOrder;
    }

    public function update(array $request, string $publicId): PurchaseOrder
    {
        $purchaseOrder = PurchaseOrder::firstWhere('public_id', $publicId);

        if (! $purchaseOrder) {
            throw new \Exception('Purchase order not found');
        }

        if ($purchaseOrder->status !== Statuses::PO_DRAFT) {
            throw new \Exception('Cannot update purchase order information. Status not draft');
        }

        $supplier = Supplier::firstWhere('public_id', $request['supplier_id']);

        if (! $supplier) {
            throw new \Exception('Supplier not found');
        }

        $purchaseOrder->supplier()->associate($supplier);

        // remove sensitive data from request payload
        unset($request['po_number'], $request['supplier_id']);

        $purchaseOrder->fill($request);
        $purchaseOrder->save();

        return $purchaseOrder->fresh();
    }

    public function destroy(string $publicId): void
    {
        $purchaseOrder = PurchaseOrder::firstWhere('public_id', $publicId);

        if (! $purchaseOrder) {
            throw new \Exception('Purchase order not found');
        }

        $purchaseOrder->delete();
    }

    /**
     * Change the status of a specific purchase order.
     *
     * @param  array{reason?: string}  $data
     *
     * @throws \Exception
     */
    public function changeStatus(array $data, string $publicId, string $targetStatus): PurchaseOrder
    {
        return DB::transaction(function () use ($publicId, $targetStatus) {
            $purchaseOrder = PurchaseOrder::firstWhere('public_id', $publicId);

            if (! $purchaseOrder) {
                throw new \Exception('Purchase order not found.');
            }

            $currentStatus = $purchaseOrder->status;

            // If the current status is already the target status, skip the transition logic
            if ($currentStatus === $targetStatus) {
                return $purchaseOrder;
            }

            // Validate state transition boundaries based on business logic
            switch ($targetStatus) {
                case Statuses::PO_SUBMITTED:
                    if ($currentStatus !== Statuses::PO_DRAFT) {
                        throw new \Exception('Only draft purchase orders can be submitted.');
                    }
                    if (! $purchaseOrder->document()->exists()) {
                        throw new \Exception('A PDF document must be uploaded before submitting this purchase order.');
                    }
                    break;

                case Statuses::PO_APPROVED:
                    if ($currentStatus !== Statuses::PO_SUBMITTED) {
                        throw new \Exception('Only submitted purchase orders can be approved.');
                    }
                    break;

                case Statuses::PO_RECEIVED:
                    if ($currentStatus !== Statuses::PO_APPROVED) {
                        throw new \Exception('Only approved purchase orders can be received.');
                    }
                    break;

                case Statuses::PO_CANCELLED:
                    if ($currentStatus === Statuses::PO_RECEIVED) {
                        throw new \Exception('Fully received purchase orders cannot be cancelled.');
                    }
                    break;

                default:
                    throw new \Exception('Invalid target status.');
            }

            // Apply updates
            $purchaseOrder->status = $targetStatus;

            $purchaseOrder->save();

            return $purchaseOrder;
        });
    }
}
