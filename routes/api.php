<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::get('products/search', [ProductController::class, 'search']);
Route::get('products/category/{categoryId}', [ProductController::class, 'filterByCategory']);
Route::get('products/stats/stock', [ProductController::class, 'stockStats']);

Route::get('products', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show']);


Route::middleware('auth:sanctum')->group(function () {
    
    // Ruta za odjavu
    Route::post('logout', [AuthController::class, 'logout']);
    
    Route::post('products', [ProductController::class, 'store']);     
    Route::put('products/{id}', [ProductController::class, 'update']);  
    Route::delete('products/{id}', [ProductController::class, 'destroy']); 
});