<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RatingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/products', [RatingController::class, 'index']);
        Route::post('/products/{productId}/rating', [RatingController::class, 'store']);
        Route::put('/products/{productId}/rating', [RatingController::class, 'update']);
        Route::delete('/products/{productId}/rating', [RatingController::class, 'destroy']);
    });
});
