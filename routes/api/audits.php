<?php

use App\Http\Controllers\Api\Audit\AuditListController;
use Illuminate\Support\Facades\Route;

Route::prefix('audits')->middleware(['auth:sanctum', 'throttle:60'])->group(function () {
    Route::get('/', [AuditListController::class, 'list']);
});
