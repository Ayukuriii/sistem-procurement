<?php

namespace App\Services\ExportImport\Handlers;

use App\Constants\Exports;
use App\Exports\ArraySheetExport;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Services\ExportImport\Contracts\ExportableModuleHandler;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PurchaseOrdersModuleHandler implements ExportableModuleHandler
{
    public function module(): string
    {
        return Exports::MODULE_PURCHASE_ORDERS;
    }

    public function filename(): string
    {
        return 'purchase-orders-export-'.now()->format('Ymd-His').'.xlsx';
    }

    public function makeExporter(array $filters): FromCollection&WithHeadings
    {
        $query = PurchaseOrderItem::query()
            ->with([
                'purchaseOrder.supplier',
                'purchaseOrder.creator',
                'purchaseOrder.document',
                'product',
            ]);

        $query->whereHas('purchaseOrder', function ($q) use ($filters) {
            if (! empty($filters['status'])) {
                $q->where('status', $filters['status']);
            }

            if (! empty($filters['supplier_id'])) {
                $supplier = Supplier::firstWhere('public_id', $filters['supplier_id']);
                if ($supplier) {
                    $q->where('supplier_id', $supplier->id);
                } else {
                    $q->whereRaw('1 = 0');
                }
            }

            if (! empty($filters['search'])) {
                $search = (string) $filters['search'];
                $q->where('po_number', 'like', "{$search}%");
            }
        });

        $rows = $query->orderByDesc('id')->get()->map(function (PurchaseOrderItem $item) {
            $po = $item->purchaseOrder;
            $documentUrl = $po?->document?->file_url ?? '';

            return [
                'po_number' => $po?->po_number,
                'supplier_name' => $po?->supplier?->name,
                'status' => $po?->status,
                'order_date' => optional($po?->order_date)?->format('Y-m-d'),
                'is_urgent' => $po?->is_urgent ? '1' : '0',
                'creator_email' => $po?->creator?->email,
                'product_sku' => $item->product?->sku,
                'product_name_snapshot' => $item->product_name_snapshot,
                'unit_price_snapshot' => $item->unit_price_snapshot,
                'quantity' => $item->quantity,
                'subtotal' => $item->subtotal,
                'document_url' => $documentUrl,
                'created_at' => optional($po?->created_at)?->toIso8601String(),
            ];
        })->all();

        return new ArraySheetExport(
            [
                'po_number',
                'supplier_name',
                'status',
                'order_date',
                'is_urgent',
                'creator_email',
                'product_sku',
                'product_name_snapshot',
                'unit_price_snapshot',
                'quantity',
                'subtotal',
                'document_url',
                'created_at',
            ],
            $rows
        );
    }
}
