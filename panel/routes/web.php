<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LocationPageController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy']);
    Route::get('/', HomeController::class);

    // Own account
    Route::get('/profile', [ProfileController::class, 'edit']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'password']);

    // Admin only: brands, website connection, users
    Route::middleware('can:admin')->group(function () {
        Route::get('/sites', [SiteController::class, 'index']);
        Route::get('/sites/new', [SiteController::class, 'create']);
        Route::post('/sites', [SiteController::class, 'store']);

        Route::prefix('/s/{site:slug}')->group(function () {
            Route::get('/brand', [SiteController::class, 'edit']);
            Route::put('/brand', [SiteController::class, 'update']);
            Route::delete('/brand', [SiteController::class, 'destroy']);
            Route::post('/rotate-key', [SiteController::class, 'rotateKey']);
            Route::post('/rotate-secret', [SiteController::class, 'rotateSecret']);
            Route::post('/test-connection', [SiteController::class, 'testConnection']);
        });

        // For hosting without a terminal (storage link, migrations, caches).
        Route::get('/maintenance', [MaintenanceController::class, 'index']);
        Route::get('/maintenance/{task}', [MaintenanceController::class, 'run']);

        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/new', [UserController::class, 'create']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'edit']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });

    // Brand content: admins, and editors assigned to the brand
    Route::prefix('/s/{site:slug}')->middleware('can:access-site,site')->scopeBindings()->group(function () {
        Route::get('/posts', [PostController::class, 'index']);
        Route::get('/posts/new', [PostController::class, 'create']);
        Route::post('/posts', [PostController::class, 'store']);
        Route::get('/posts/{post}', [PostController::class, 'edit']);
        Route::put('/posts/{post}', [PostController::class, 'update']);
        Route::delete('/posts/{post}', [PostController::class, 'destroy']);
        Route::post('/posts/{post}/duplicate', [PostController::class, 'duplicate']);

        Route::get('/authors', [AuthorController::class, 'index']);
        Route::post('/authors', [AuthorController::class, 'store']);
        Route::put('/authors/{author}', [AuthorController::class, 'update']);
        Route::delete('/authors/{author}', [AuthorController::class, 'destroy']);

        Route::get('/services', [ServiceController::class, 'index']);
        Route::get('/services/new', [ServiceController::class, 'create']);
        Route::post('/services', [ServiceController::class, 'store']);
        Route::post('/services/reorder', [ServiceController::class, 'reorder']);
        Route::get('/services/{service}', [ServiceController::class, 'edit']);
        Route::put('/services/{service}', [ServiceController::class, 'update']);
        Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

        Route::get('/locations', [LocationController::class, 'index']);
        Route::get('/locations/new', [LocationController::class, 'create']);
        Route::post('/locations', [LocationController::class, 'store']);
        Route::post('/locations/reorder', [LocationController::class, 'reorder']);
        Route::get('/locations/defaults', [LocationController::class, 'defaults']);
        Route::put('/locations/defaults', [LocationController::class, 'updateDefaults']);
        Route::get('/locations/{location}', [LocationController::class, 'edit']);
        Route::put('/locations/{location}', [LocationController::class, 'update']);
        Route::delete('/locations/{location}', [LocationController::class, 'destroy']);
        // One location's page for one service ({serviceId} is looked up within the brand).
        Route::get('/locations/{location}/services/{serviceId}', [LocationPageController::class, 'edit'])->whereNumber('serviceId');
        Route::put('/locations/{location}/services/{serviceId}', [LocationPageController::class, 'update'])->whereNumber('serviceId');
        Route::delete('/locations/{location}/services/{serviceId}', [LocationPageController::class, 'destroy'])->whereNumber('serviceId');

        Route::get('/pages', [PageController::class, 'index']);
        Route::post('/pages', [PageController::class, 'store']);
        Route::post('/pages/discover', [PageController::class, 'discover']);
        Route::put('/pages/{page}', [PageController::class, 'update']);
        Route::delete('/pages/{page}', [PageController::class, 'destroy']);

        Route::get('/settings', [SettingController::class, 'edit']);
        Route::put('/settings', [SettingController::class, 'update']);

        Route::post('/uploads', [UploadController::class, 'store']);
    });
});
