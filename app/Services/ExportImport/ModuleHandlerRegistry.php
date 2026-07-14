<?php

namespace App\Services\ExportImport;

use App\Constants\Exports;
use App\Services\ExportImport\Contracts\ExportableModuleHandler;
use App\Services\ExportImport\Contracts\ImportableModuleHandler;
use App\Services\ExportImport\Handlers\ProductCategoriesModuleHandler;
use App\Services\ExportImport\Handlers\ProductsModuleHandler;
use App\Services\ExportImport\Handlers\PurchaseOrdersModuleHandler;
use App\Services\ExportImport\Handlers\SuppliersModuleHandler;
use App\Services\ExportImport\Handlers\UsersModuleHandler;
use InvalidArgumentException;

class ModuleHandlerRegistry
{
    /** @var array<string, ExportableModuleHandler> */
    private array $exportHandlers;

    /** @var array<string, ImportableModuleHandler> */
    private array $importHandlers;

    public function __construct(
        UsersModuleHandler $users,
        SuppliersModuleHandler $suppliers,
        ProductCategoriesModuleHandler $categories,
        ProductsModuleHandler $products,
        PurchaseOrdersModuleHandler $purchaseOrders,
    ) {
        $this->exportHandlers = [
            Exports::MODULE_USERS => $users,
            Exports::MODULE_SUPPLIERS => $suppliers,
            Exports::MODULE_PRODUCT_CATEGORIES => $categories,
            Exports::MODULE_PRODUCTS => $products,
            Exports::MODULE_PURCHASE_ORDERS => $purchaseOrders,
        ];

        $this->importHandlers = [
            Exports::MODULE_USERS => $users,
            Exports::MODULE_SUPPLIERS => $suppliers,
            Exports::MODULE_PRODUCT_CATEGORIES => $categories,
            Exports::MODULE_PRODUCTS => $products,
        ];
    }

    public function exportable(string $module): ExportableModuleHandler
    {
        if (! isset($this->exportHandlers[$module])) {
            throw new InvalidArgumentException("Unknown or unsupported export module: {$module}");
        }

        return $this->exportHandlers[$module];
    }

    public function importable(string $module): ImportableModuleHandler
    {
        if (! isset($this->importHandlers[$module])) {
            throw new InvalidArgumentException("Unknown or unsupported import module: {$module}");
        }

        return $this->importHandlers[$module];
    }
}
