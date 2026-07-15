<?php

use App\Http\Controllers\Api\Product\ProductController;
use App\Http\Controllers\Api\Product\ProductListController;
use App\Http\Controllers\Api\Product\ProductSelectController;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->middleware(['auth:sanctum', 'throttle:60'])->group(function () {
    Route::get('/list', [ProductListController::class, 'list']);
    Route::get('/select', [ProductSelectController::class, 'select']);

    Route::post('/', [ProductController::class, 'store']);
    Route::get('/{publicId}', [ProductController::class, 'show']);
    Route::put('/{publicId}', [ProductController::class, 'update']);
    Route::delete('/{publicId}', [ProductController::class, 'destroy']);
});
