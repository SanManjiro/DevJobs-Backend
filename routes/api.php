<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

// Auth
Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('register', [RegisterController::class, 'register']);
        Route::post('login',    [LoginController::class, 'login']);
        Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
        Route::post('/reset-password', [PasswordResetController::class, 'reset']);
        Route::middleware(['auth:sanctum', 'active'])->group(function () {
            Route::post('logout', [LoginController::class, 'logout']);
            Route::post('changePassword', [PasswordResetController::class, 'changePassword']);
            Route::get('me',      [LoginController::class, 'me']);
        });
    });
});
