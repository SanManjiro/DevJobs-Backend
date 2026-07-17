<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\JobListingController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {

    //Auth
    Route::prefix('auth')->group(function () {

        Route::post('register',        [AuthController::class, 'register']);
        Route::post('login',           [AuthController::class, 'login']);
        Route::post('forgot-password', [PasswordResetController::class, 'sendResetLink']);
        Route::post('reset-password',  [PasswordResetController::class, 'reset']);


        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout',          [AuthController::class, 'logout']);
            Route::get('me',               [AuthController::class, 'me']);
            Route::post('change-password', [PasswordResetController::class, 'changePassword']);
        });
    });

    //Jobs (public)
    Route::get('jobs',       [JobListingController::class, 'index']);
    Route::get('jobs/{job}', [JobListingController::class, 'show']);

    //Companies (public)
    Route::get('companies',           [CompanyController::class, 'index']);
    Route::get('companies/{company}', [CompanyController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('jobs',              [JobListingController::class, 'store']);
        Route::put('jobs/{job}',         [JobListingController::class, 'update']);
        Route::patch('jobs/{job}',       [JobListingController::class, 'update']);
        Route::delete('jobs/{job}',      [JobListingController::class, 'destroy']);
    });
});
