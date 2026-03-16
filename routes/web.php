<?php

use App\Http\Controllers\ShopifyController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::middleware('shop.resolve')->group(function () {
    Route::get('/', [ShopifyController::class, 'index'])->name('home');

    Route::get('whatsapp-settings', function () {
        return 'Whatsapp Settings';
    });

    // Route::middleware(['auth', 'verified'])->group(function () {
    //     Route::inertia('dashboard', 'dashboard')->name('dashboard');
    // });
});

require __DIR__ . '/settings.php';
