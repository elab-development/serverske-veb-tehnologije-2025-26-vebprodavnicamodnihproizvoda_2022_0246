<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::get('products/search', [ProductController::class, 'search']);
Route::get('products/category/{categoryId}', [ProductController::class, 'filterByCategory']);
Route::get('products/stats/stock', [ProductController::class, 'stockStats']);

Route::apiResource('products', ProductController::class)
    ->only(['index', 'show']);
    
Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource('orders', OrderController::class);

    Route::get('/reports/orders', [OrderController::class, 'ordersReport']);

    Route::get('/users/{id}/orders', [OrderController::class, 'userOrders']);

    Route::get('/orders/{id}/items', [OrderController::class, 'orderItems']);

    Route::post('logout', [AuthController::class, 'logout']);

    Route::middleware('admin')->group(function () {
        Route::apiResource('products', ProductController::class)
            ->only(['store', 'update', 'destroy']);
    });

});