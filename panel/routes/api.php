<?php

use App\Http\Controllers\Api\BrandApiController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['ok' => true]);

// Public brand API: read-only, called server-side by brand sites with their X-Api-Key.
Route::prefix('v1/{site:slug}')->middleware(['site.key', 'throttle:300,1'])->group(function () {
    Route::get('/settings', [BrandApiController::class, 'settings']);
    Route::get('/posts', [BrandApiController::class, 'posts']);
    Route::get('/posts/{slug}', [BrandApiController::class, 'post'])->where('slug', '[a-z0-9-]+');
    Route::get('/sitemap', [BrandApiController::class, 'sitemap']);
    Route::get('/locations', [BrandApiController::class, 'locations']);
    Route::get('/locations/{key}', [BrandApiController::class, 'location'])->where('key', 'l[0-9]+(s[0-9]+)?');
});
