<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [\App\Http\Controllers\Api\AuthController::class, 'user']);
        Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);

        // Prospek
        Route::get('/prospek', [\App\Http\Controllers\Api\ProspekController::class, 'index']);
        Route::post('/prospek', [\App\Http\Controllers\Api\ProspekController::class, 'store']);
        Route::get('/prospek/{prospek}', [\App\Http\Controllers\Api\ProspekController::class, 'show']);
        Route::patch('/prospek/{prospek}/status', [\App\Http\Controllers\Api\ProspekController::class, 'updateStatus']);

        // Kunjungan
        Route::get('/kunjungan', [\App\Http\Controllers\Api\VisitController::class, 'index']);
        Route::post('/kunjungan', [\App\Http\Controllers\Api\VisitController::class, 'store']);
        Route::get('/kunjungan/{kunjungan}', [\App\Http\Controllers\Api\VisitController::class, 'show']);
    });
});
