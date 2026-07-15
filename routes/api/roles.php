<?php

use App\Constants\Roles;
use App\Http\Controllers\Api\Role\RoleController;
use App\Http\Controllers\Api\Role\RoleListController;
use App\Http\Controllers\Api\Role\RoleSelectController;
use Illuminate\Support\Facades\Route;

Route::prefix('roles')->middleware(['auth:sanctum', 'throttle:60'])->group(function () {
    Route::get('/select', [RoleSelectController::class, 'select']);
});

Route::prefix('roles')->middleware([
    'auth:sanctum',
    'role:'.Roles::ROLE_ADMIN,
    'throttle:60',
])->group(function () {
    Route::get('/list', [RoleListController::class, 'list']);

    Route::post('/', [RoleController::class, 'store']);
    Route::get('/{publicId}', [RoleController::class, 'show']);
    Route::put('/{publicId}', [RoleController::class, 'update']);
    Route::delete('/{publicId}', [RoleController::class, 'destroy']);
});
