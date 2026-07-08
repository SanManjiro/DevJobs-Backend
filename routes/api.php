<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\JobListingController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Version 1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |----------------------------------------------------------------------
    | Auth — routes publiques
    |----------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {

        Route::post('register',        [AuthController::class, 'register']);
        Route::post('login',           [AuthController::class, 'login']);
        Route::post('forgot-password', [PasswordResetController::class, 'sendResetLink']);
        Route::post('reset-password',  [PasswordResetController::class, 'reset']);

        /*
        |------------------------------------------------------------------
        | Auth — routes protégées (token Sanctum requis)
        |------------------------------------------------------------------
        */
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout',          [AuthController::class, 'logout']);
            Route::get('me',               [AuthController::class, 'me']);
            Route::post('change-password', [PasswordResetController::class, 'changePassword']);
        });
    });

    /*
    |----------------------------------------------------------------------
    | Job Listings — lecture publique, écriture protégée
    |----------------------------------------------------------------------
    */
    Route::get('jobs',       [JobListingController::class, 'index']);
    Route::get('jobs/{job}', [JobListingController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('jobs',              [JobListingController::class, 'store']);
        Route::put('jobs/{job}',         [JobListingController::class, 'update']);
        Route::patch('jobs/{job}',       [JobListingController::class, 'update']);
        Route::delete('jobs/{job}',      [JobListingController::class, 'destroy']);
    });
});
