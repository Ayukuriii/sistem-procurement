<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CategoryListController;
use Illuminate\Support\Facades\Route;

Route::prefix('categories')->middleware('throttle:60')->group(function () {
    Route::get('/list', [CategoryListController::class, 'list']);

    Route::post('/', [CategoryController::class, 'store']);
    Route::get('/{publicId}', [CategoryController::class, 'show']);
    Route::put('/{publicId}', [CategoryController::class, 'update']);
    Route::delete('/{publicId}', [CategoryController::class, 'destroy']);
});
