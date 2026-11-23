<?php

use App\Http\Controllers\PublicMenuController;
use App\Http\Controllers\RestaurantTableQrCodeController;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login')->name('home');

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
    Route::get('dashboard', function (Request $request) {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $restaurant = $user
            ->restaurants()
            ->wherePivotIn('role', ['owner', 'manager'])
            ->first();

        abort_unless($restaurant instanceof Restaurant, 403);

        return redirect()->route('filament.admin.resources.products.index', [
            'tenant' => $restaurant->slug,
        ]);
    })->name('dashboard');
});

require __DIR__.'/settings.php';
