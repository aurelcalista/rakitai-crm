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
        Route::post('/presensi/sekolah', [\App\Http\Controllers\Api\PresensiController::class, 'storeSchool']);
        Route::post('/presensi/korporasi', [\App\Http\Controllers\Api\PresensiController::class, 'storeCorporate']);
        Route::get('/prospek/{prospek}', [\App\Http\Controllers\Api\ProspekController::class, 'show']);
        Route::patch('/prospek/{prospek}/status', [\App\Http\Controllers\Api\ProspekController::class, 'updateStatus']);

        // Kunjungan
        Route::get('/kunjungan', [\App\Http\Controllers\Api\VisitController::class, 'index']);
        Route::post('/kunjungan', [\App\Http\Controllers\Api\VisitController::class, 'store']);
        Route::get('/kunjungan/{kunjungan}', [\App\Http\Controllers\Api\VisitController::class, 'show']);

        // Mobile WebView Signed URL
        Route::get('/mobile/signed-url', function (Request $request) {
            $form = $request->query('form', 'prospek');
            $routeName = $form === 'kunjungan' ? 'mobile.kunjungan.create' : 'mobile.prospek.create';
            $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                $routeName,
                now()->addMinutes(60),
                ['signed_user_id' => $request->user()->id]
            );
            return response()->json(['signed_url' => $url]);
        });
    });
});
