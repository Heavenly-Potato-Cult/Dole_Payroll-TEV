<?php

use Illuminate\Support\Facades\Route;
use Modules\Allowances\Http\Controllers\AllowancesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('allowances', AllowancesController::class)->names('allowances');
});
