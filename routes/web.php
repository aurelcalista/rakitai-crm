<?php

use App\Http\Controllers\CrmController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// Authentication & Landing
Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/login', [CrmController::class, 'login'])->name('login');

// Unified Role Dashboard entry
Route::get('/dashboard', function () {
    $role = session('user_role', 'sales');

    return redirect()->route('dashboard.' . $role);
})->name('dashboard');

// Role-specific Dashboards (Each dedicated to 1 role)
Route::prefix('dashboard')->group(function () {
    Route::get('/sales', [CrmController::class, 'dashboardSales'])->name('dashboard.sales');
    Route::get('/cs', [CrmController::class, 'dashboardCs'])->name('dashboard.cs');
    Route::get('/spv', [CrmController::class, 'dashboardSpv'])->name('dashboard.spv');
    Route::get('/hm', [CrmController::class, 'dashboardHm'])->name('dashboard.hm');
    Route::get('/admin', [CrmController::class, 'dashboardAdmin'])->name('dashboard.admin');
});

// Admin Panel Modules (Only for Admin)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [CrmController::class, 'adminUsers'])->name('users.index');
    Route::get('/master-data', [CrmController::class, 'adminMasterData'])->name('master-data.index');
    Route::get('/audit-logs', [CrmController::class, 'adminAuditLogs'])->name('audit-logs.index');
    Route::get('/settings', [CrmController::class, 'adminSettings'])->name('settings.index');
});

// CRM Core Modules
Route::get('/prospek', [CrmController::class, 'prospekIndex'])->name('prospek.index');
Route::post('/prospek', [CrmController::class, 'prospekStore'])->name('prospek.store');
Route::get('/prospek/{id}', [CrmController::class, 'prospekShow'])->name('prospek.show');
Route::put('/prospek/{id}', [CrmController::class, 'prospekUpdate'])->name('prospek.update');
Route::delete('/prospek/{id}', [CrmController::class, 'prospekDestroy'])->name('prospek.destroy');

Route::get('/kunjungan', [CrmController::class, 'kunjunganIndex'])->name('kunjungan.index');
Route::post('/kunjungan', [CrmController::class, 'kunjunganStore'])->name('kunjungan.store');

Route::get('/follow-up', [CrmController::class, 'followUpIndex'])->name('follow-up.index');
Route::post('/follow-up', [CrmController::class, 'followUpStore'])->name('follow-up.store');

Route::get('/pipeline', [CrmController::class, 'pipelineIndex'])->name('pipeline.index');
Route::post('/pipeline/update-status', [CrmController::class, 'pipelineUpdateStatus'])->name('pipeline.update-status');

// Performance & Reports
Route::get('/target-performa', [CrmController::class, 'performaIndex'])->name('performa.index');
Route::get('/laporan', [CrmController::class, 'laporanIndex'])->name('laporan.index');

// Management (Head Marketing)
Route::middleware([])->group(function () {
    Route::get('/wilayah', [\App\Http\Controllers\WilayahController::class, 'index'])->name('wilayah.index');
    Route::post('/wilayah', [\App\Http\Controllers\WilayahController::class, 'store'])->name('wilayah.store');
    Route::put('/wilayah/{id}', [\App\Http\Controllers\WilayahController::class, 'update'])->name('wilayah.update');
    Route::delete('/wilayah/{id}', [\App\Http\Controllers\WilayahController::class, 'destroy'])->name('wilayah.destroy');
    Route::patch('/wilayah/{id}/toggle', [\App\Http\Controllers\WilayahController::class, 'toggleStatus'])->name('wilayah.toggle');

    Route::get('/tim', [\App\Http\Controllers\TimController::class, 'index'])->name('tim.index');
    Route::post('/tim', [\App\Http\Controllers\TimController::class, 'store'])->name('tim.store');
});

// Account & Profile
Route::get('/profil', [CrmController::class, 'profilIndex'])->name('profil.index');
Route::get('/pengaturan', [CrmController::class, 'pengaturanIndex'])->name('pengaturan.index');

// Notifications
Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
