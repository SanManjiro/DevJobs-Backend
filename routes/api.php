<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Auth
Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);
        Route::middleware(['auth:sanctum', 'active'])->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('changePassword', [AuthController::class, 'changePassword']);
            Route::get('me',      [AuthController::class, 'me']);
        });
    });
});
