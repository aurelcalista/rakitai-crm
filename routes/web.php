<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// ──────────────────────────────────────────────────────────────────
// Authentication & Landing
// ──────────────────────────────────────────────────────────────────
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

// ──────────────────────────────────────────────────────────────────
// Authenticated Routes (all roles)
// ──────────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    // Internal Calendar
    Route::get('/calendar', [\App\Http\Controllers\CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/api/calendar/events', [\App\Http\Controllers\CalendarController::class, 'fetchEvents'])->name('api.calendar.events');

    // Unified role dashboard dispatcher
    Route::get('/dashboard', function () {
        $role = strtolower(auth()->user()->role ?? 'sales');
        return redirect()->route('dashboard.' . $role);
    })->name('dashboard');

    // Role-specific dashboard entry points
    Route::prefix('dashboard')->group(function () {
        Route::get('/sales', [\App\Http\Controllers\Sales\DashboardController::class, 'index'])->name('dashboard.sales');
        Route::get('/cs',    [CrmController::class, 'dashboardCs'])->name('dashboard.cs');
        Route::get('/spv',   [\App\Http\Controllers\Spv\DashboardController::class, 'index'])->name('dashboard.spv');
        Route::get('/hm',    [CrmController::class, 'dashboardHm'])->name('dashboard.hm');
        Route::get('/admin', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('dashboard.admin');
        Route::get('/eo',    [\App\Http\Controllers\Eo\DashboardController::class, 'index'])->name('dashboard.eo');
    });

    // ──────────────────────────────────────────────────────────────
    // SALES Role Routes — prefix: /sales, name: sales.*
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
            Route::get('/kunjungan',                [\App\Http\Controllers\Sales\VisitController::class, 'index'])->name('kunjungan.index');
            Route::get('/kunjungan/create',         [\App\Http\Controllers\Sales\VisitController::class, 'create'])->name('kunjungan.create');
            Route::post('/kunjungan',               [\App\Http\Controllers\Sales\VisitController::class, 'store'])->name('kunjungan.store');
            Route::get('/kunjungan/{kunjungan}',    [\App\Http\Controllers\Sales\VisitController::class, 'show'])->name('kunjungan.show');
            Route::delete('/kunjungan/{kunjungan}', [\App\Http\Controllers\Sales\VisitController::class, 'destroy'])->name('kunjungan.destroy');

            // Events
            Route::get('/event', [\App\Http\Controllers\Sales\EventController::class, 'index'])->name('event.index');
            Route::post('/events/{event}/confirm', [\App\Http\Controllers\Sales\VisitController::class, 'confirmEvent'])->name('event.confirm');

            // Pipeline
            Route::get('/pipeline',                [\App\Http\Controllers\Sales\PipelineController::class, 'index'])->name('pipeline.index');
            Route::post('/pipeline/update-status', [\App\Http\Controllers\Sales\PipelineController::class, 'updateStatus'])->name('pipeline.updateStatus');

            // Performance & Report
            Route::get('/target-performa', [\App\Http\Controllers\Sales\PerformanceController::class, 'index'])->name('performa.index');
            Route::get('/laporan',         [\App\Http\Controllers\Sales\ReportController::class, 'index'])->name('laporan.index');
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
            Route::post('/target-performa/alokasi', [\App\Http\Controllers\Spv\PerformanceController::class, 'alokasi'])->name('performa.alokasi');
            Route::post('/target-performa/kunci-defisit', [\App\Http\Controllers\Spv\PerformanceController::class, 'kunciDefisit'])->name('performa.kunciDefisit');

            // Team Recap Reports
            Route::get('/laporan', [\App\Http\Controllers\Spv\ReportController::class, 'index'])->name('laporan.index');

            // Team Structure & Directory
            Route::get('/tim', [\App\Http\Controllers\Spv\TeamController::class, 'index'])->name('tim.index');
            Route::post('/tim/assign', [\App\Http\Controllers\Spv\TeamController::class, 'assignMember'])->name('tim.assign');
            Route::post('/tim/territory', [\App\Http\Controllers\Spv\TeamController::class, 'assignTeamTerritory'])->name('tim.territory.assign');
            Route::patch('/tim/{user}/wilayah', [\App\Http\Controllers\Spv\TeamController::class, 'assignWilayah'])->name('tim.wilayah.assign');
            Route::delete('/tim/{user}/wilayah/{wilayah}', [\App\Http\Controllers\Spv\TeamController::class, 'deactivateTerritory'])->name('tim.wilayah.deactivate');

            // SPV Event Assignments
            Route::get('/events', [\App\Http\Controllers\Spv\EventAssignmentController::class, 'index'])->name('events.index');
            Route::post('/events/{id}/assign', [\App\Http\Controllers\Spv\EventAssignmentController::class, 'assignSales'])->name('events.assign');

            // SPV Specialized Actions (P0)
            Route::post('/prospek/{prospek}/closing',       [\App\Http\Controllers\Spv\ProspectController::class, 'closing'])->name('prospek.closing');
            Route::post('/kunjungan/{kunjungan}/verifikasi', [\App\Http\Controllers\Spv\VisitController::class, 'verifikasi'])->name('kunjungan.verifikasi');
        });

    // ──────────────────────────────────────────────────────────────
    // HM (Head Manager) Role Routes
    // ──────────────────────────────────────────────────────────────
    Route::middleware(['role:Admin,HM'])->prefix('hm')->name('hm.')->group(function () {
        Route::get('/wilayah', [\App\Http\Controllers\Hm\HmWilayahController::class, 'index'])->name('wilayah.index');
        Route::post('/wilayah/{wilayah}/assign-spv', [\App\Http\Controllers\Hm\HmWilayahController::class, 'assignSpv'])->name('wilayah.assignSpv');
        Route::post('/wilayah/{wilayah}/target', [\App\Http\Controllers\Hm\HmWilayahController::class, 'setWilayahTarget'])->name('wilayah.setTarget');
    });

    // ──────────────────────────────────────────────────────────────
    // EO (Event Organizer) Role Routes
    // ──────────────────────────────────────────────────────────────
    Route::middleware('role:EO')
        ->prefix('eo')
        ->name('eo.')
        ->group(function () {
            Route::resource('events', \App\Http\Controllers\Eo\EventController::class)->except(['create', 'show', 'edit']);
            Route::resource('event-types', \App\Http\Controllers\Eo\EventTypeController::class)->except(['create', 'show', 'edit']);
        });

    // ──────────────────────────────────────────────────────────────
    // Admin Panel Modules — prefix: /admin, name: admin.*
    // Protected by role:Admin middleware
    // ──────────────────────────────────────────────────────────────
    Route::middleware(['role:Admin'])->prefix('admin')->name('admin.')->group(function () {
        $except = ['except' => ['create', 'show', 'edit']];

        // Custom Actions
        Route::post('users/{user}/reset-password',             [\App\Http\Controllers\Admin\AdminUserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('users/{user}/toggle-status',              [\App\Http\Controllers\Admin\AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('wilayah/{wilayah}/toggle-status',         [\App\Http\Controllers\Admin\AdminWilayahController::class, 'toggleStatus'])->name('wilayah.toggle-status');
        Route::post('sekolah/{sekolah}/toggle-status',         [\App\Http\Controllers\Admin\AdminSekolahController::class, 'toggleStatus'])->name('sekolah.toggle-status');
        Route::post('prodi/{prodi}/toggle-status',             [\App\Http\Controllers\Admin\AdminProdiController::class, 'toggleStatus'])->name('prodi.toggle-status');
        Route::post('perusahaan/{perusahaan}/toggle-status',   [\App\Http\Controllers\Admin\AdminPerusahaanController::class, 'toggleStatus'])->name('perusahaan.toggle-status');
        Route::post('master-data/{master_data}/toggle-status', [\App\Http\Controllers\Admin\AdminMasterDataController::class, 'toggleStatus'])->name('master-data.toggle-status');

        // Penugasan Wilayah HM (Admin Control)
        Route::get('hm-wilayah',                  [\App\Http\Controllers\Admin\AdminHmWilayahController::class, 'index'])->name('hm-wilayah.index');
        Route::post('hm-wilayah/assign',          [\App\Http\Controllers\Admin\AdminHmWilayahController::class, 'assign'])->name('hm-wilayah.assign');
        Route::post('hm-wilayah/{user}/unassign', [\App\Http\Controllers\Admin\AdminHmWilayahController::class, 'unassign'])->name('hm-wilayah.unassign');

        Route::resource('users',       \App\Http\Controllers\Admin\AdminUserController::class, $except);
        Route::resource('kunjungan',   \App\Http\Controllers\Admin\AdminKunjunganController::class, $except);
        Route::resource('master-data', \App\Http\Controllers\Admin\AdminMasterDataController::class, $except);
        Route::resource('wilayah',     \App\Http\Controllers\Admin\AdminWilayahController::class, $except);
        Route::resource('sekolah',     \App\Http\Controllers\Admin\AdminSekolahController::class, $except);
        Route::resource('prodi',       \App\Http\Controllers\Admin\AdminProdiController::class, $except);
        Route::resource('perusahaan',  \App\Http\Controllers\Admin\AdminPerusahaanController::class, $except);

        Route::get('/settings',   [CrmController::class, 'adminSettings'])->name('settings.index');
    });

    // Admin & HM Shared Modules
    Route::middleware(['role:Admin,HM'])->prefix('admin')->name('admin.')->group(function () {
        $except = ['except' => ['create', 'show', 'edit']];
        Route::resource('target', \App\Http\Controllers\Admin\AdminTargetController::class, $except);
        Route::get('/audit-logs', [CrmController::class, 'adminAuditLogs'])->name('audit-logs.index');
    });

    // ──────────────────────────────────────────────────────────────
    // Shared CRM Modules (CS, SPV, HM, Admin, Fallback)
    // ──────────────────────────────────────────────────────────────
    Route::middleware(['role:Admin,HM,SPV,CS,Sales'])->group(function () {
        Route::get('/prospek',         [CrmController::class, 'prospekIndex'])->name('prospek.index');
        Route::post('/prospek',        [CrmController::class, 'prospekStore'])->name('prospek.store');
        Route::get('/prospek/{id}',    [CrmController::class, 'prospekShow'])->name('prospek.show');
        Route::put('/prospek/{id}',    [CrmController::class, 'prospekUpdate'])->name('prospek.update');
        Route::delete('/prospek/{id}', [CrmController::class, 'prospekDestroy'])->name('prospek.destroy');
        Route::post('/prospek/{id}/takeover', [CrmController::class, 'prospekTakeover'])->name('prospek.takeover');
        Route::post('/prospek/{id}/transaksi', [CrmController::class, 'transaksiStore'])->name('prospek.transaksi');
        Route::post('/prospek/{id}/realokasi', [CrmController::class, 'prospekRealokasi'])->name('prospek.realokasi');

        Route::get('/kunjungan',  [CrmController::class, 'kunjunganIndex'])->name('kunjungan.index');
        Route::post('/kunjungan', [CrmController::class, 'kunjunganStore'])->name('kunjungan.store');

        Route::get('/follow-up',  [CrmController::class, 'followUpIndex'])->name('follow-up.index');
        Route::post('/follow-up', [CrmController::class, 'followUpStore'])->name('follow-up.store');

        Route::get('/pipeline',                [CrmController::class, 'pipelineIndex'])->name('pipeline.index');
        Route::post('/pipeline/update-status', [CrmController::class, 'pipelineUpdateStatus'])->name('pipeline.update-status');

        // NOTE: Master Wilayah management is role-separated:
        //   Admin → admin.wilayah.* (AdminWilayahController — full CRUD)
        //   HM    → hm.wilayah.*   (HmWilayahController   — assign SPV + set target)
        // The old shared WilayahController is no longer exposed here.

        // Performance & Reports
        Route::get('/target-performa', [CrmController::class, 'performaIndex'])->name('performa.index');
        Route::get('/laporan',         [CrmController::class, 'laporanIndex'])->name('laporan.index');
    });

    // Account & Profile (all roles)
    Route::get('/profil',           [CrmController::class, 'profilIndex'])->name('profil.index');
    Route::post('/profil',          [CrmController::class, 'profilUpdate'])->name('profil.update');
    Route::post('/profil/password', [CrmController::class, 'profilPasswordUpdate'])->name('profil.password.update');
    Route::get('/pengaturan',       [CrmController::class, 'pengaturanIndex'])->name('pengaturan.index');

    // Notifications (all roles)
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::post('/notifications/read-all',      [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::post('/notifications/{id}/read',     [NotificationController::class, 'markAsRead'])->name('notifications.markRead');
});
