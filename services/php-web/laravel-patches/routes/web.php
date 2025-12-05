<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IssController;
use App\Http\Controllers\AstroController;
use App\Http\Controllers\CmsController;

Route::get('/', function () { return redirect()->route('dashboard'); });

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data'); // ajax filter/sort

Route::get('/iss', [IssController::class, 'index'])->name('iss.index');
Route::get('/astro', [AstroController::class, 'index'])->name('astro.index');

Route::get('/cms/page/{slug}', [CmsController::class, 'show'])->name('cms.page');