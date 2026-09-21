<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CrmController;
use Illuminate\Support\Facades\Route;

// Authentication & Landing
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [CrmController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    // Unified Role Dashboard entry
    Route::get('/dashboard', function () {
        $role = auth()->check() ? strtolower(auth()->user()->role) : session('user_role', 'sales');

        return redirect()->route('dashboard.' . $role);
    })->name('dashboard');

    // Role-specific Dashboards (Each dedicated to 1 role)
    Route::prefix('dashboard')->group(function () {
<<<<<<< Updated upstream
        Route::get('/sales', [CrmController::class, 'dashboardSales'])->name('dashboard.sales');
        Route::get('/cs', [CrmController::class, 'dashboardCs'])->name('dashboard.cs');
        Route::get('/spv', [CrmController::class, 'dashboardSpv'])->name('dashboard.spv');
        Route::get('/hm', [CrmController::class, 'dashboardHm'])->name('dashboard.hm');
        Route::get('/admin', [App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('dashboard.admin');
    });

    // Admin Panel Modules (Only for Admin)
=======
        // Sales dashboard now served by dedicated Sales\DashboardController
        Route::get('/sales', [\App\Http\Controllers\Sales\DashboardController::class, 'index'])->name('dashboard.sales');
        Route::get('/cs',    [CrmController::class, 'dashboardCs'])->name('dashboard.cs');
        Route::get('/spv',   [\App\Http\Controllers\Spv\DashboardController::class, 'index'])->name('dashboard.spv');
        Route::get('/hm',    [CrmController::class, 'dashboardHm'])->name('dashboard.hm');
        Route::get('/admin', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('dashboard.admin');
    });

    // ──────────────────────────────────────────────────────────────
    // SPV (Supervisor) Role Routes — prefix: /spv, name: spv.*
    // Protected by role:SPV middleware
    // ──────────────────────────────────────────────────────────────
    Route::middleware('role:SPV')
        ->prefix('spv')
        ->name('spv.')
        ->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Spv\DashboardController::class, 'index'])->name('dashboard');

            // Team Prospect management
            Route::get('/prospek',                    [\App\Http\Controllers\Spv\ProspectController::class, 'index'])->name('prospek.index');
            Route::get('/prospek/create',             [\App\Http\Controllers\Spv\ProspectController::class, 'create'])->name('prospek.create');
            Route::post('/prospek',                   [\App\Http\Controllers\Spv\ProspectController::class, 'store'])->name('prospek.store');
            Route::get('/prospek/{prospek}',          [\App\Http\Controllers\Spv\ProspectController::class, 'show'])->name('prospek.show');
            Route::put('/prospek/{prospek}',          [\App\Http\Controllers\Spv\ProspectController::class, 'update'])->name('prospek.update');
            Route::patch('/prospek/{prospek}/status', [\App\Http\Controllers\Spv\ProspectController::class, 'updateStatus'])->name('prospek.updateStatus');
            Route::post('/prospek/{prospek}/reassign',[\App\Http\Controllers\Spv\ProspectController::class, 'reassign'])->name('prospek.reassign');
            Route::delete('/prospek/{prospek}',       [\App\Http\Controllers\Spv\ProspectController::class, 'destroy'])->name('prospek.destroy');

            // Team Pipeline Kanban
            Route::get('/pipeline',                [\App\Http\Controllers\Spv\PipelineController::class, 'index'])->name('pipeline.index');
            Route::post('/pipeline/update-status', [\App\Http\Controllers\Spv\PipelineController::class, 'updateStatus'])->name('pipeline.updateStatus');

            // Team Visit Monitoring
            Route::get('/kunjungan',             [\App\Http\Controllers\Spv\VisitController::class, 'index'])->name('kunjungan.index');
            Route::get('/kunjungan/{kunjungan}', [\App\Http\Controllers\Spv\VisitController::class, 'show'])->name('kunjungan.show');

            // Team Target & Performance
            Route::get('/target-performa', [\App\Http\Controllers\Spv\PerformanceController::class, 'index'])->name('performa.index');

            // Team Recap Reports
            Route::get('/laporan', [\App\Http\Controllers\Spv\ReportController::class, 'index'])->name('laporan.index');

            // Team Structure & Directory
            Route::get('/tim', [\App\Http\Controllers\Spv\TeamController::class, 'index'])->name('tim.index');
        });

    // ──────────────────────────────────────────────────────────────
    // SALES Role Routes  — prefix: /sales, name: sales.*
    // Protected by role:Sales middleware
    // ──────────────────────────────────────────────────────────────
    Route::middleware('role:Sales')
        ->prefix('sales')
        ->name('sales.')
        ->group(function () {
            // Prospect management
            Route::get('/prospek',                    [\App\Http\Controllers\Sales\ProspectController::class, 'index'])->name('prospek.index');
            Route::get('/prospek/create',             [\App\Http\Controllers\Sales\ProspectController::class, 'create'])->name('prospek.create');
            Route::post('/prospek',                   [\App\Http\Controllers\Sales\ProspectController::class, 'store'])->name('prospek.store');
            Route::get('/prospek/{prospek}',          [\App\Http\Controllers\Sales\ProspectController::class, 'show'])->name('prospek.show');
            Route::put('/prospek/{prospek}',          [\App\Http\Controllers\Sales\ProspectController::class, 'update'])->name('prospek.update');
            Route::patch('/prospek/{prospek}/status', [\App\Http\Controllers\Sales\ProspectController::class, 'updateStatus'])->name('prospek.updateStatus');
            Route::patch('/prospek/{prospek}/lost',   [\App\Http\Controllers\Sales\ProspectController::class, 'markLost'])->name('prospek.markLost');
            Route::post('/prospek/{prospek}/takeover', [\App\Http\Controllers\Sales\ProspectController::class, 'takeover'])->name('prospek.takeover');

            // Follow-up management
            Route::get('/follow-up',  [\App\Http\Controllers\Sales\FollowUpController::class, 'index'])->name('follow-up.index');
            Route::post('/follow-up', [\App\Http\Controllers\Sales\FollowUpController::class, 'store'])->name('follow-up.store');

            // Field visit management
            Route::get('/kunjungan',             [\App\Http\Controllers\Sales\VisitController::class, 'index'])->name('kunjungan.index');
            Route::get('/kunjungan/create',      [\App\Http\Controllers\Sales\VisitController::class, 'create'])->name('kunjungan.create');
            Route::post('/kunjungan',            [\App\Http\Controllers\Sales\VisitController::class, 'store'])->name('kunjungan.store');
            Route::get('/kunjungan/{kunjungan}', [\App\Http\Controllers\Sales\VisitController::class, 'show'])->name('kunjungan.show');

            // Pipeline
            Route::get('/pipeline',                [\App\Http\Controllers\Sales\PipelineController::class, 'index'])->name('pipeline.index');
            Route::post('/pipeline/update-status', [\App\Http\Controllers\Sales\PipelineController::class, 'updateStatus'])->name('pipeline.updateStatus');

            // Performance & Report
            Route::get('/target-performa', [\App\Http\Controllers\Sales\PerformanceController::class, 'index'])->name('performa.index');
            Route::get('/laporan',         [\App\Http\Controllers\Sales\ReportController::class, 'index'])->name('laporan.index');
        });

    // ──────────────────────────────────────────────────────────────
    // Admin Panel Modules — prefix: /admin, name: admin.*
    // Protected by role:Admin middleware
    // ──────────────────────────────────────────────────────────────
>>>>>>> Stashed changes
    Route::middleware(['role:Admin'])->prefix('admin')->name('admin.')->group(function () {
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

<<<<<<< Updated upstream
    // CRM Core Modules
    Route::get('/prospek', [CrmController::class, 'prospekIndex'])->name('prospek.index');
    Route::get('/prospek/{id}', [CrmController::class, 'prospekShow'])->name('prospek.show');
=======
    // ──────────────────────────────────────────────────────────────
    // Shared CRM Modules (CS, SPV, HM, Admin)
    // Non-prefixed routes for non-Sales roles
    // ──────────────────────────────────────────────────────────────
    Route::get('/prospek',         [CrmController::class, 'prospekIndex'])->name('prospek.index');
    Route::post('/prospek',        [CrmController::class, 'prospekStore'])->name('prospek.store');
    Route::get('/prospek/{id}',    [CrmController::class, 'prospekShow'])->name('prospek.show');
    Route::put('/prospek/{id}',    [CrmController::class, 'prospekUpdate'])->name('prospek.update');
    Route::delete('/prospek/{id}', [CrmController::class, 'prospekDestroy'])->name('prospek.destroy');
    Route::post('/prospek/{id}/takeover', [CrmController::class, 'prospekTakeover'])->name('prospek.takeover');
    Route::post('/prospek/{id}/realokasi', [CrmController::class, 'prospekRealokasi'])->name('prospek.realokasi');
>>>>>>> Stashed changes

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
});
