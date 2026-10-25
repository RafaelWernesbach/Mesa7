<?php

use App\Http\Controllers\PublicMenuController;
use App\Http\Controllers\RestaurantTableQrCodeController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::prefix('r/{restaurant:slug}')->name('public.menu.')->group(function () {
    Route::get('/', [PublicMenuController::class, 'show'])->name('show');
    Route::get('/cardapio/{uuid}', [PublicMenuController::class, 'showForTableUuid'])
        ->whereUuid('uuid')
        ->name('table');
    Route::get('/mesa/{code}', [PublicMenuController::class, 'showForTable'])
        ->where('code', '[A-Za-z0-9_-]+')
        ->name('table-code');
});

Route::middleware('auth')->get(
    '/admin/{restaurant:slug}/mesas/{table}/qr-code',
    [RestaurantTableQrCodeController::class, 'show'],
)->whereNumber('table')->name('admin.restaurant-tables.qr-code');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
