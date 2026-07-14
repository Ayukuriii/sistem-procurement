<?php

use App\Http\Controllers\Api\Document\DocumentController;
use Illuminate\Support\Facades\Route;

Route::prefix('documents')->middleware(['auth:sanctum', 'throttle:60'])->group(function () {
    Route::get('/{publicId}', [DocumentController::class, 'show']);
});
