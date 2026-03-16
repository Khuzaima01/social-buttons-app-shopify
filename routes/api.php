<?php

use App\Http\Controllers\ShopifyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// 1. Install request from merchant
Route::get('/auth', [ShopifyController::class, 'redirectToShopify']);

// 2. OAuth callback from Shopify
Route::get('/auth/callback', [ShopifyController::class, 'handleCallback']);

// 3. React App entry point (iframe app)
Route::get('/app', function () {
    return view('app');
});
