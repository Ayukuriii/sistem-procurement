<?php

use App\Http\Controllers\Api\PurchaseOrder\PurchaseOrderController;
use App\Http\Controllers\Api\PurchaseOrder\PurchaseOrderListController;
use App\Http\Controllers\Api\PurchaseOrder\StatusManagementController;
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
});
