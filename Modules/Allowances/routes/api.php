<?php

use Illuminate\Support\Facades\Route;
use Modules\Allowances\Http\Controllers\AllowancesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('allowances', AllowancesController::class)->names('allowances');
});
