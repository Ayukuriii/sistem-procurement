<?php

use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Api\User\UserListController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->middleware(['auth:sanctum', 'throttle:60'])->group(function () {
    Route::get('/list', [UserListController::class, 'list']);

    Route::post('/', [UserController::class, 'store']);
    Route::get('/{publicId}', [UserController::class, 'show']);
    Route::put('/{publicId}', [UserController::class, 'update']);
    Route::delete('/{publicId}', [UserController::class, 'delete']);

    Route::put('/{publicId}/roles', [UserController::class, 'syncRole']);
});
