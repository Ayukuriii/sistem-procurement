<?php

use App\Constants\Roles;
use App\Http\Controllers\Api\Import\ImportController;
use App\Http\Controllers\Api\Import\ImportModuleSelectController;
use Illuminate\Support\Facades\Route;

Route::prefix('imports')->middleware([
    'auth:sanctum',
    'role:'.Roles::ROLE_ADMIN,
    'throttle:60',
])->group(function () {
    Route::get('/modules/select', [ImportModuleSelectController::class, 'select']);
    Route::get('/template', [ImportController::class, 'template']);
    Route::post('/', [ImportController::class, 'store']);
});
