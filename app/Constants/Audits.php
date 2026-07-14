<?php

namespace App\Constants;

use App\Models\Category;
use App\Models\Document;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;

class Audits
{
    public const EVENT_CREATED = 'created';

    public const EVENT_UPDATED = 'updated';

    public const EVENT_DELETED = 'deleted';

    public const EVENT_RESTORED = 'restored';

    public const EVENT_STATUS_CHANGED = 'status_changed';

    public const TYPE_USER = 'user';

    public const TYPE_CATEGORY = 'category';

    public const TYPE_PRODUCT = 'product';

    public const TYPE_SUPPLIER = 'supplier';

    public const TYPE_PURCHASE_ORDER = 'purchase_order';

    public const TYPE_PURCHASE_ORDER_ITEM = 'purchase_order_item';

    public const TYPE_DOCUMENT = 'document';

    /**
     * @return array<string, class-string>
     */
    public static function typeMap(): array
    {
        return [
            self::TYPE_USER => User::class,
            self::TYPE_CATEGORY => Category::class,
            self::TYPE_PRODUCT => Product::class,
            self::TYPE_SUPPLIER => Supplier::class,
            self::TYPE_PURCHASE_ORDER => PurchaseOrder::class,
            self::TYPE_PURCHASE_ORDER_ITEM => PurchaseOrderItem::class,
            self::TYPE_DOCUMENT => Document::class,
        ];
    }

    public static function classToType(?string $class): ?string
    {
        if ($class === null) {
            return null;
        }

        $flipped = array_flip(self::typeMap());

        return $flipped[$class] ?? $class;
    }

    public static function typeToClass(string $type): ?string
    {
        return self::typeMap()[$type] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public static function actionLabels(): array
    {
        return [
            self::EVENT_CREATED => 'Dibuat',
            self::EVENT_UPDATED => 'Diperbarui',
            self::EVENT_DELETED => 'Dihapus',
            self::EVENT_RESTORED => 'Dipulihkan',
            self::EVENT_STATUS_CHANGED => 'Status diubah',
        ];
    }

    public static function actionLabel(string $event): string
    {
        return self::actionLabels()[$event] ?? ucfirst(str_replace('_', ' ', $event));
    }

    /**
     * @return list<string>
     */
    public static function events(): array
    {
        return [
            self::EVENT_CREATED,
            self::EVENT_UPDATED,
            self::EVENT_DELETED,
            self::EVENT_RESTORED,
            self::EVENT_STATUS_CHANGED,
        ];
    }

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return array_keys(self::typeMap());
    }
}
