<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CrmController;
use Illuminate\Support\Facades\Route;

// Authentication & Landing
Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/login', [CrmController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

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
    Route::get('/admin', [App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('dashboard.admin');
});

// Admin Panel Modules (Only for Admin)
Route::middleware(['auth', 'role:Admin'])->prefix('admin')->name('admin.')->group(function () {
    $except = ['except' => ['create', 'show', 'edit']];

    // Custom Actions
    Route::post('users/{user}/reset-password', [App\Http\Controllers\Admin\AdminUserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('users/{user}/toggle-status', [App\Http\Controllers\Admin\AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('wilayah/{wilayah}/toggle-status', [App\Http\Controllers\Admin\AdminWilayahController::class, 'toggleStatus'])->name('wilayah.toggle-status');
    Route::post('sekolah/{sekolah}/toggle-status', [App\Http\Controllers\Admin\AdminSekolahController::class, 'toggleStatus'])->name('sekolah.toggle-status');
    Route::post('prodi/{prodi}/toggle-status', [App\Http\Controllers\Admin\AdminProdiController::class, 'toggleStatus'])->name('prodi.toggle-status');
    Route::post('perusahaan/{perusahaan}/toggle-status', [App\Http\Controllers\Admin\AdminPerusahaanController::class, 'toggleStatus'])->name('perusahaan.toggle-status');
    Route::post('master-data/{master_data}/toggle-status', [App\Http\Controllers\Admin\AdminMasterDataController::class, 'toggleStatus'])->name('master-data.toggle-status');

    Route::resource('users',       App\Http\Controllers\Admin\AdminUserController::class, $except);
    Route::resource('kunjungan',   App\Http\Controllers\Admin\AdminKunjunganController::class, $except);
    Route::resource('target',      App\Http\Controllers\Admin\AdminTargetController::class, $except);
    Route::resource('master-data', App\Http\Controllers\Admin\AdminMasterDataController::class, $except);
    Route::resource('wilayah',     App\Http\Controllers\Admin\AdminWilayahController::class, $except);
    Route::resource('sekolah',     App\Http\Controllers\Admin\AdminSekolahController::class, $except);
    Route::resource('prodi',       App\Http\Controllers\Admin\AdminProdiController::class, $except);
    Route::resource('perusahaan',  App\Http\Controllers\Admin\AdminPerusahaanController::class, $except);
    
    Route::get('/audit-logs',   [CrmController::class, 'adminAuditLogs'])->name('audit-logs.index');
    Route::get('/settings',     [CrmController::class, 'adminSettings'])->name('settings.index');
});

// CRM Core Modules
Route::get('/prospek', [CrmController::class, 'prospekIndex'])->name('prospek.index');
Route::get('/prospek/{id}', [CrmController::class, 'prospekShow'])->name('prospek.show');

Route::get('/kunjungan', [CrmController::class, 'kunjunganIndex'])->name('kunjungan.index');
Route::get('/follow-up', [CrmController::class, 'followUpIndex'])->name('follow-up.index');
Route::get('/pipeline', [CrmController::class, 'pipelineIndex'])->name('pipeline.index');

// Performance & Reports
Route::get('/target-performa', [CrmController::class, 'performaIndex'])->name('performa.index');
Route::get('/laporan', [CrmController::class, 'laporanIndex'])->name('laporan.index');

// Account & Profile
Route::get('/profil', [CrmController::class, 'profilIndex'])->name('profil.index');
Route::post('/profil', [CrmController::class, 'profilUpdate'])->name('profil.update');
Route::post('/profil/password', [CrmController::class, 'profilPasswordUpdate'])->name('profil.password.update');
Route::get('/pengaturan', [CrmController::class, 'pengaturanIndex'])->name('pengaturan.index');

