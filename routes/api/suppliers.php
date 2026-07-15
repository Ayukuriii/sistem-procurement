<?php

use App\Http\Controllers\Api\Supplier\SupplierController;
use App\Http\Controllers\Api\Supplier\SupplierListController;
use App\Http\Controllers\Api\Supplier\SupplierSelectController;
use Illuminate\Support\Facades\Route;

Route::prefix('suppliers')->middleware(['auth:sanctum', 'throttle:60'])->group(function () {
    Route::get('/list', [SupplierListController::class, 'list']);
    Route::get('/select', [SupplierSelectController::class, 'select']);

    Route::post('/', [SupplierController::class, 'store']);
    Route::get('/{publicId}', [SupplierController::class, 'show']);
    Route::put('/{publicId}', [SupplierController::class, 'update']);
    Route::delete('/{publicId}', [SupplierController::class, 'destroy']);
});
