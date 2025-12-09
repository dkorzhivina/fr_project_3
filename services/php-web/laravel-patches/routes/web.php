<?php

use App\Http\Controllers\AstroController;
use App\Http\Controllers\CmsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IssController;
use App\Http\Controllers\OsdrController;
use App\Http\Controllers\ProxyController;
use App\Http\Controllers\TelemetryController;

Route::get('/', fn () => redirect()->route('dashboard'));

// Панели
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data'); // ajax filter/sort (оставляем для совместимости)
Route::get('/osdr', [OsdrController::class, 'index'])->name('osdr.index');
Route::get('/iss', [IssController::class, 'index'])->name('iss.index');
Route::get('/cms/page/{slug}', [CmsController::class, 'show'])->name('cms.page');

// Прокси к rust_iss
Route::get('/api/iss/last', [ProxyController::class, 'last']);
Route::get('/api/iss/trend', [ProxyController::class, 'trend']);

// JWST и AstronomyAPI
Route::get('/api/jwst/feed', [DashboardController::class, 'jwstFeed']);
Route::get('/api/astro/events', [AstroController::class, 'events']);

// Телеметрия
Route::get('/telemetry', [TelemetryController::class, 'index'])->name('telemetry.index');
Route::get('/api/telemetry', [TelemetryController::class, 'list'])->name('api.telemetry.list');