<?php

use App\Http\Controllers\Api\Audit\AuditListController;
use App\Http\Controllers\Api\Audit\AuditSelectController;
use Illuminate\Support\Facades\Route;

Route::prefix('audits')->middleware(['auth:sanctum', 'throttle:60'])->group(function () {
    Route::get('/types/select', [AuditSelectController::class, 'types']);
    Route::get('/events/select', [AuditSelectController::class, 'events']);
    Route::get('/', [AuditListController::class, 'list']);
});
