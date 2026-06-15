<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('products/search', [ProductController::class, 'search']);

Route::get('products/category/{categoryId}', [ProductController::class, 'filterByCategory']);

Route::get('products/stats/stock', [ProductController::class, 'stockStats']);

Route::apiResource('products', ProductController::class);