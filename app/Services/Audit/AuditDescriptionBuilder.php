<?php

namespace App\Services\Audit;

use App\Constants\Audits;
use App\Models\Audit;
use App\Models\Category;
use App\Models\Document;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AuditDescriptionBuilder
{
    /**
     * @var array<string, string>
     */
    private const FIELD_LABELS = [
        'name' => 'nama',
        'email' => 'email',
        'phone' => 'telepon',
        'address' => 'alamat',
        'is_active' => 'status aktif',
        'sku' => 'SKU',
        'unit_price' => 'harga satuan',
        'category_id' => 'kategori',
        'specifications' => 'spesifikasi',
        'metadata' => 'metadata',
        'supplier_id' => 'supplier',
        'creator_id' => 'pembuat',
        'po_number' => 'nomor PO',
        'order_date' => 'tanggal order',
        'status' => 'status',
        'is_urgent' => 'tanda urgent',
        'notes' => 'catatan',
        'product_id' => 'produk',
        'product_name_snapshot' => 'nama produk (snapshot)',
        'unit_price_snapshot' => 'harga satuan (snapshot)',
        'quantity' => 'kuantitas',
        'subtotal' => 'subtotal',
        'purchase_order_id' => 'purchase order',
        'documentable_type' => 'tipe dokumen induk',
        'documentable_id' => 'induk dokumen',
        'file_path' => 'path file',
    ];

    /**
     * @var array<string, string>
     */
    private const TYPE_LABELS = [
        Audits::TYPE_USER => 'user',
        Audits::TYPE_CATEGORY => 'kategori',
        Audits::TYPE_PRODUCT => 'produk',
        Audits::TYPE_SUPPLIER => 'supplier',
        Audits::TYPE_PURCHASE_ORDER => 'purchase order',
        Audits::TYPE_PURCHASE_ORDER_ITEM => 'item purchase order',
        Audits::TYPE_DOCUMENT => 'dokumen',
    ];

    public function build(Audit $audit): string
    {
        $type = Audits::classToType($audit->auditable_type) ?? $audit->auditable_type;
        $typeLabel = self::TYPE_LABELS[$type] ?? $type;
        $identifier = $this->resolveIdentifier($audit->auditable, $type, $audit);
        $oldValues = $this->normalizeValues($audit->old_values ?? []);
        $newValues = $this->normalizeValues($audit->new_values ?? []);

        return match ($audit->event) {
            Audits::EVENT_CREATED => $this->describeCreated($typeLabel, $identifier, $newValues, $type),
            Audits::EVENT_UPDATED => $this->describeUpdated($typeLabel, $identifier, $oldValues, $newValues, $type),
            Audits::EVENT_DELETED => $this->describeDeleted($typeLabel, $identifier, $oldValues, $type),
            Audits::EVENT_RESTORED => $this->describeRestored($typeLabel, $identifier),
            Audits::EVENT_STATUS_CHANGED => $this->describeStatusChanged($typeLabel, $identifier, $oldValues, $newValues),
            default => sprintf(
                'Melakukan aksi %s pada %s%s',
                $audit->event,
                $typeLabel,
                $identifier !== '' ? " {$identifier}" : ''
            ),
        };
    }

    /**
     * @param  array<string, mixed>  $newValues
     */
    private function describeCreated(string $typeLabel, string $identifier, array $newValues, string $type): string
    {
        if ($type === Audits::TYPE_DOCUMENT) {
            $fileName = data_get($newValues, 'metadata.file_name');
            $filePart = $fileName ? " bernama \"{$fileName}\"" : '';

            return sprintf(
                'Mengunggah dokumen PDF%s%s',
                $filePart,
                $identifier !== '' ? " untuk {$identifier}" : ''
            );
        }

        if ($type === Audits::TYPE_PURCHASE_ORDER_ITEM) {
            $name = $newValues['product_name_snapshot'] ?? null;
            $qty = $newValues['quantity'] ?? null;
            $price = $newValues['unit_price_snapshot'] ?? null;
            $parts = array_filter([
                $name ? "produk \"{$name}\"" : null,
                $qty !== null ? "kuantitas {$this->formatValue($qty)}" : null,
                $price !== null ? "harga satuan {$this->formatValue($price)}" : null,
            ]);

            return sprintf(
                'Menambahkan item purchase order%s%s',
                $identifier !== '' ? " {$identifier}" : '',
                $parts !== [] ? ': '.implode(', ', $parts) : ''
            );
        }

        $details = $this->formatFieldSummaries($newValues, $type);

        return sprintf(
            'Membuat %s%s%s',
            $typeLabel,
            $identifier !== '' ? " {$identifier}" : '',
            $details !== '' ? " dengan {$details}" : ''
        );
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function describeUpdated(string $typeLabel, string $identifier, array $oldValues, array $newValues, string $type): string
    {
        if ($type === Audits::TYPE_DOCUMENT) {
            $oldName = data_get($oldValues, 'metadata.file_name');
            $newName = data_get($newValues, 'metadata.file_name');

            if ($oldName || $newName) {
                return sprintf(
                    'Mengganti dokumen PDF%s: file dari %s menjadi %s',
                    $identifier !== '' ? " untuk {$identifier}" : '',
                    $this->formatValue($oldName),
                    $this->formatValue($newName)
                );
            }
        }

        $diffs = $this->buildDiffParts($oldValues, $newValues, $type);

        return sprintf(
            'Memperbarui %s%s%s',
            $typeLabel,
            $identifier !== '' ? " {$identifier}" : '',
            $diffs !== [] ? ': '.implode('; ', $diffs) : ''
        );
    }

    /**
     * @param  array<string, mixed>  $oldValues
     */
    private function describeDeleted(string $typeLabel, string $identifier, array $oldValues, string $type): string
    {
        if ($type === Audits::TYPE_DOCUMENT) {
            $fileName = data_get($oldValues, 'metadata.file_name');

            return sprintf(
                'Menghapus dokumen PDF%s%s',
                $fileName ? " \"{$fileName}\"" : '',
                $identifier !== '' ? " untuk {$identifier}" : ''
            );
        }

        if ($type === Audits::TYPE_PURCHASE_ORDER_ITEM) {
            $name = $oldValues['product_name_snapshot'] ?? null;

            return sprintf(
                'Menghapus item purchase order%s%s',
                $identifier !== '' ? " {$identifier}" : '',
                $name ? " (produk \"{$name}\")" : ''
            );
        }

        return sprintf(
            'Menghapus %s%s',
            $typeLabel,
            $identifier !== '' ? " {$identifier}" : ''
        );
    }

    private function describeRestored(string $typeLabel, string $identifier): string
    {
        return sprintf(
            'Memulihkan %s%s',
            $typeLabel,
            $identifier !== '' ? " {$identifier}" : ''
        );
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function describeStatusChanged(string $typeLabel, string $identifier, array $oldValues, array $newValues): string
    {
        return sprintf(
            'Mengubah status %s%s dari %s menjadi %s',
            $typeLabel,
            $identifier !== '' ? " {$identifier}" : '',
            $this->formatValue($oldValues['status'] ?? null),
            $this->formatValue($newValues['status'] ?? null)
        );
    }

    private function resolveIdentifier(?Model $auditable, string $type, Audit $audit): string
    {
        if ($auditable instanceof PurchaseOrder) {
            return $auditable->po_number ?? '';
        }

        if ($auditable instanceof Product) {
            $parts = array_filter([$auditable->sku, $auditable->name ? "\"{$auditable->name}\"" : null]);

            return implode(' ', $parts);
        }

        if ($auditable instanceof Category || $auditable instanceof Supplier) {
            return $auditable->name ? "\"{$auditable->name}\"" : '';
        }

        if ($auditable instanceof User) {
            $parts = array_filter([
                $auditable->name ? "\"{$auditable->name}\"" : null,
                $auditable->email ? "({$auditable->email})" : null,
            ]);

            return implode(' ', $parts);
        }

        if ($auditable instanceof PurchaseOrderItem) {
            $poNumber = $auditable->purchaseOrder?->po_number;
            $name = $auditable->product_name_snapshot;

            return trim(($poNumber ? "pada {$poNumber}" : '').($name ? " — \"{$name}\"" : ''));
        }

        if ($auditable instanceof Document) {
            $parent = $auditable->documentable;
            if ($parent instanceof PurchaseOrder) {
                return "purchase order {$parent->po_number}";
            }
        }

        // Fallback from audited values when auditable soft-deleted without relation load
        $new = $this->normalizeValues($audit->new_values ?? []);
        $old = $this->normalizeValues($audit->old_values ?? []);

        foreach (['po_number', 'sku', 'name', 'email', 'product_name_snapshot'] as $key) {
            $value = $new[$key] ?? $old[$key] ?? null;
            if ($value !== null && $value !== '') {
                return in_array($key, ['name', 'product_name_snapshot'], true)
                    ? "\"{$value}\""
                    : (string) $value;
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function normalizeValues(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function formatFieldSummaries(array $values, string $type): string
    {
        $parts = [];

        foreach ($values as $field => $value) {
            if (in_array($field, ['id', 'public_id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }

            $parts[] = sprintf(
                '%s %s',
                $this->fieldLabel($field),
                $this->formatResolvedValue($field, $value, $type)
            );
        }

        return implode(', ', $parts);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @return list<string>
     */
    private function buildDiffParts(array $oldValues, array $newValues, string $type): array
    {
        $keys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
        $parts = [];

        foreach ($keys as $field) {
            if (in_array($field, ['id', 'public_id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }

            $old = Arr::get($oldValues, $field);
            $new = Arr::get($newValues, $field);

            if ($this->valuesEqual($old, $new)) {
                continue;
            }

            $parts[] = sprintf(
                '%s dari %s menjadi %s',
                $this->fieldLabel($field),
                $this->formatResolvedValue($field, $old, $type),
                $this->formatResolvedValue($field, $new, $type)
            );
        }

        return $parts;
    }

    private function fieldLabel(string $field): string
    {
        return self::FIELD_LABELS[$field] ?? str_replace('_', ' ', $field);
    }

    private function formatResolvedValue(string $field, mixed $value, string $type): string
    {
        if ($field === 'category_id' && $value !== null) {
            $name = Category::query()->whereKey($value)->value('name');

            return $name ? "\"{$name}\"" : $this->formatValue($value);
        }

        if ($field === 'supplier_id' && $value !== null) {
            $name = Supplier::query()->whereKey($value)->value('name');

            return $name ? "\"{$name}\"" : $this->formatValue($value);
        }

        if ($field === 'product_id' && $value !== null) {
            $product = Product::query()->whereKey($value)->first(['sku', 'name']);
            if ($product) {
                return trim(($product->sku ?? '').' '.($product->name ? "\"{$product->name}\"" : ''));
            }
        }

        if ($field === 'creator_id' && $value !== null) {
            $user = User::query()->whereKey($value)->first(['name', 'email']);
            if ($user) {
                return trim(($user->name ? "\"{$user->name}\"" : '').($user->email ? " ({$user->email})" : ''));
            }
        }

        if ($field === 'is_active' || $field === 'is_urgent') {
            if ($value === null) {
                return '(kosong)';
            }

            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'ya' : 'tidak';
        }

        return $this->formatValue($value);
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '(kosong)';
        }

        if (is_bool($value)) {
            return $value ? 'ya' : 'tidak';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
        }

        if (is_string($value) && $value === '') {
            return '(kosong)';
        }

        return (string) $value;
    }

    private function valuesEqual(mixed $old, mixed $new): bool
    {
        if (is_array($old) || is_array($new)) {
            return json_encode($old) === json_encode($new);
        }

        return $old == $new;
    }
}
