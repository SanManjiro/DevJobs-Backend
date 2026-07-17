<?php

use App\Http\Controllers\Admin\JobController as AdminJobController;
use App\Http\Controllers\Admin\OverviewController as AdminOverviewController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\Company\ApplicationController as CompanyApplicationController;
use App\Http\Controllers\Company\JobController as CompanyJobController;
use App\Http\Controllers\Company\ProfileController as CompanyProfileController;
use App\Http\Controllers\Developer\ApplicationController as DeveloperApplicationController;
use App\Http\Controllers\Developer\ProfileController as DeveloperProfileController;
use App\Http\Controllers\Developer\SavedJobController;
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

    // Espace entreprise : lecture de ses propres offres (brouillons compris),
    // candidatures reçues et profil. L'écriture des offres reste sur /v1/jobs.
    Route::middleware(['auth:sanctum', 'active', 'role:company'])
        ->prefix('company')
        ->group(function () {
            Route::get('jobs',                    [CompanyJobController::class, 'index']);
            Route::get('jobs/{job}',              [CompanyJobController::class, 'show']);
            Route::get('jobs/{job}/applications', [CompanyApplicationController::class, 'index']);
            Route::patch('applications/{application}/status', [CompanyApplicationController::class, 'updateStatus']);
            Route::get('profile',                 [CompanyProfileController::class, 'show']);
            Route::put('profile',                 [CompanyProfileController::class, 'update']);
        });

    // Espace développeur : profil, candidatures, favoris. 'role:developer'
    // refuse une entreprise ou un admin avant même d'atteindre le controller.
    Route::middleware(['auth:sanctum', 'active', 'role:developer'])
        ->prefix('developer')
        ->group(function () {
            // Profil
            Route::get('profile',                   [DeveloperProfileController::class, 'show']);
            Route::put('profile',                   [DeveloperProfileController::class, 'update']);
            Route::post('profile/skills',           [DeveloperProfileController::class, 'addSkills']);
            Route::delete('profile/skills/{skill}', [DeveloperProfileController::class, 'removeSkill']);

            // Candidatures
            Route::get('applications',                  [DeveloperApplicationController::class, 'index']);
            Route::post('jobs/{job}/apply',             [DeveloperApplicationController::class, 'store']);
            Route::delete('applications/{application}', [DeveloperApplicationController::class, 'destroy']);

            // Favoris — la route d'ids passe avant {job}/save pour ne pas être
            // capturée comme un paramètre.
            Route::get('saved-jobs',        [SavedJobController::class, 'index']);
            Route::get('saved-jobs/ids',    [SavedJobController::class, 'ids']);
            Route::post('jobs/{job}/save',  [SavedJobController::class, 'store']);
            Route::delete('jobs/{job}/save', [SavedJobController::class, 'destroy']);
        });

    // Espace admin : modération des comptes et des offres.
    Route::middleware(['auth:sanctum', 'active', 'role:admin'])
        ->prefix('admin')
        ->group(function () {
            Route::get('overview',              [AdminOverviewController::class, 'index']);
            Route::get('users',                 [AdminUserController::class, 'index']);
            Route::patch('users/{user}/toggle', [AdminUserController::class, 'toggle']);
            Route::delete('users/{user}',       [AdminUserController::class, 'destroy']);
            Route::get('jobs',                  [AdminJobController::class, 'index']);
            Route::delete('jobs/{job}',         [AdminJobController::class, 'destroy']);
        });
});
