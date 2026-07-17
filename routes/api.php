<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\JobListingController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {

    //Auth
    Route::prefix('auth')->group(function () {

        Route::post('register',        [AuthController::class, 'register']);
        Route::post('login',           [AuthController::class, 'login']);
        Route::post('forgot-password', [PasswordResetController::class, 'sendResetLink']);
        Route::post('reset-password',  [PasswordResetController::class, 'reset']);

        // OAuth — GitHub & Google. The provider redirects the browser to
        // {provider}/callback; the frontend then trades the one-time code.
        Route::get('{provider}/redirect',  [SocialAuthController::class, 'redirect'])
            ->whereIn('provider', ['github', 'google']);
        Route::get('{provider}/callback',  [SocialAuthController::class, 'callback'])
            ->whereIn('provider', ['github', 'google']);
        Route::post('exchange',            [SocialAuthController::class, 'exchange']);

        // 'active' blocks a deactivated account whose token was issued before
        // it was disabled.
        Route::middleware(['auth:sanctum', 'active'])->group(function () {
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

    //Skills (public)
    Route::get('skills', [SkillController::class, 'index']);

    // Job writes: authenticated, active, and company-only. The FormRequests
    // already enforce ownership; 'role:company' makes the intent explicit on
    // the route and refuses a developer before the request is even built.
    Route::middleware(['auth:sanctum', 'active', 'role:company'])->group(function () {
        Route::post('jobs',              [JobListingController::class, 'store']);
        Route::put('jobs/{job}',         [JobListingController::class, 'update']);
        Route::patch('jobs/{job}',       [JobListingController::class, 'update']);
        Route::delete('jobs/{job}',      [JobListingController::class, 'destroy']);
    });
});
