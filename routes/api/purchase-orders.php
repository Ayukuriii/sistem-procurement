<?php

use App\Http\Controllers\Api\Document\DocumentController;
use App\Http\Controllers\Api\Document\DocumentListController;
use App\Http\Controllers\Api\PurchaseOrder\PurchaseOrderController;
use App\Http\Controllers\Api\PurchaseOrder\PurchaseOrderListController;
use App\Http\Controllers\Api\PurchaseOrder\StatusManagementController;
use App\Http\Controllers\Api\PurchaseOrderItem\POItemController;
use App\Http\Controllers\Api\PurchaseOrderItem\POItemListController;
use Illuminate\Support\Facades\Route;

Route::prefix('purchase-orders')->middleware(['auth:sanctum', 'throttle:60'])->group(function () {
    Route::get('/list', [PurchaseOrderListController::class, 'list']);

    Route::post('/', [PurchaseOrderController::class, 'store']);
    Route::get('/{publicId}', [PurchaseOrderController::class, 'show']);
    Route::put('/{publicId}', [PurchaseOrderController::class, 'update']);
    Route::delete('/{publicId}', [PurchaseOrderController::class, 'destroy']);

    Route::post('/{publicId}/submit', [StatusManagementController::class, 'submit']);
    Route::post('/{publicId}/approve', [StatusManagementController::class, 'approve']);
    Route::post('/{publicId}/receive', [StatusManagementController::class, 'receive']);
    Route::post('/{publicId}/cancel', [StatusManagementController::class, 'cancel']);

    /**
     * Purchase Order Items
     */
    Route::get('/{publicId}/items', [POItemListController::class, 'list']);
    Route::post('/{publicId}/items', [POItemController::class, 'store']);
    Route::get('/{publicId}/items/{itemPublicId}', [POItemController::class, 'show']);
    Route::put('/{publicId}/items/{itemPublicId}', [POItemController::class, 'update']);
    Route::delete('/{publicId}/items/{itemPublicId}', [POItemController::class, 'destroy']);

    /**
     * Purchase Order Documents
     */
    Route::get('/{publicId}/documents', [DocumentListController::class, 'list']);
    Route::post('/{publicId}/documents', [DocumentController::class, 'store']);
});
