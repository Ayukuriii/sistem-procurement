<?php

use App\Constants\Roles;
use App\Http\Controllers\Api\Export\ExportController;
use App\Http\Controllers\Api\Export\ExportModuleSelectController;
use Illuminate\Support\Facades\Route;

Route::prefix('exports')->middleware([
    'auth:sanctum',
    'role:'.Roles::ROLE_ADMIN,
    'throttle:60',
])->group(function () {
    Route::get('/modules/select', [ExportModuleSelectController::class, 'select']);
    Route::get('/', [ExportController::class, 'index']);
    Route::post('/', [ExportController::class, 'store']);
    Route::get('/{exportJobId}', [ExportController::class, 'show']);
});
