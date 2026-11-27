<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SystemHealthController;

Route::middleware([
    'auth:sanctum',
    'admin',
    'throttle:10,1',
])
    ->prefix('admin/system')
    ->name('admin.system.')
    ->group(function () {

        Route::get(
            '/health',
            [SystemHealthController::class, 'index']
        )->name('health');

    });