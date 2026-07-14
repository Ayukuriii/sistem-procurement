<?php

namespace App\Constants;

class Exports
{
    public const MODULE_USERS = 'users';

    public const MODULE_SUPPLIERS = 'suppliers';

    public const MODULE_PRODUCT_CATEGORIES = 'product_categories';

    public const MODULE_PRODUCTS = 'products';

    public const MODULE_PURCHASE_ORDERS = 'purchase_orders';

    public const EXPORTABLE = [
        self::MODULE_USERS,
        self::MODULE_SUPPLIERS,
        self::MODULE_PRODUCT_CATEGORIES,
        self::MODULE_PRODUCTS,
        self::MODULE_PURCHASE_ORDERS,
    ];

    public const IMPORTABLE = [
        self::MODULE_USERS,
        self::MODULE_SUPPLIERS,
        self::MODULE_PRODUCT_CATEGORIES,
        self::MODULE_PRODUCTS,
    ];

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STORAGE_DISK = 'public';

    public const STORAGE_DIR = 'exports';

    public const DEFAULT_USER_PASSWORD = 'password';

    public const MAX_IMPORT_FILE_SIZE_KB = 5120;
}
