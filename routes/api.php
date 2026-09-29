<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:inventory-api')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:inventory-auth');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:inventory-auth');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::apiResource('products', ProductController::class);
    });
});
