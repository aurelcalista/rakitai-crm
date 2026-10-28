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
    return view('landing');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [CrmController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

// ──────────────────────────────────────────────────────────────────
// Tambah Kontak Cepat (Public)
// ──────────────────────────────────────────────────────────────────
Route::get('/presensi', [\App\Http\Controllers\QuickContactController::class, 'create'])->name('kontak-cepat');
Route::post('/presensi', [\App\Http\Controllers\QuickContactController::class, 'store'])->name('kontak-cepat.store');

Route::get('/presensi-corporate', [\App\Http\Controllers\QuickContactController::class, 'createCorporate'])->name('kontak-cepat.corporate');
Route::post('/presensi-corporate', [\App\Http\Controllers\QuickContactController::class, 'storeCorporate'])->name('kontak-cepat.store-corporate');
Route::get('/presensi-corporate/terima-kasih', [\App\Http\Controllers\QuickContactController::class, 'terimaKasihCorporate'])->name('kontak-cepat.terima-kasih-corporate');

Route::get('/presensi/terima-kasih', [\App\Http\Controllers\QuickContactController::class, 'terimaKasih'])->name('kontak-cepat.terima-kasih');

// ──────────────────────────────────────────────────────────────────
// PMB Online Payment & Midtrans Webhook (Public / CAMABA)
// ──────────────────────────────────────────────────────────────────
Route::prefix('pmb')->name('pmb.')->group(function () {
    // Portal PMB Publik
    Route::get('/portal', [\App\Http\Controllers\Pmb\CamabaPortalController::class, 'landing'])->name('portal');
    Route::get('/bantuan', [\App\Http\Controllers\Pmb\CamabaPortalController::class, 'bantuan'])->name('bantuan');

    // Autentikasi CAMABA (Tamu / Guest)
    Route::middleware('guest')->group(function () {
        Route::get('/login',    [\App\Http\Controllers\Pmb\CamabaAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login',   [\App\Http\Controllers\Pmb\CamabaAuthController::class, 'login'])->name('login.post');
        Route::get('/register', [\App\Http\Controllers\Pmb\CamabaAuthController::class, 'showRegisterForm'])->name('register');
        Route::post('/register',[\App\Http\Controllers\Pmb\CamabaAuthController::class, 'register'])->name('register.post');
    });

    // Logout CAMABA
    Route::post('/logout', [\App\Http\Controllers\Pmb\CamabaAuthController::class, 'logout'])->name('logout');

    // Webhook Notification Endpoint (Midtrans IPN)
    Route::post('/midtrans/notification', [\App\Http\Controllers\Pmb\MidtransWebhookController::class, 'handleNotification'])->name('midtrans.notification');

    // Halaman Pembayaran CAMABA & Status Transaksi
    Route::get('/bayar/{order_id}', [\App\Http\Controllers\Pmb\MidtransPaymentController::class, 'showPaymentPage'])->name('bayar');
    Route::get('/pembayaran/status/{order_id}', [\App\Http\Controllers\Pmb\MidtransPaymentController::class, 'status'])->name('pembayaran.status');
    Route::post('/checkout/{prospek}', [\App\Http\Controllers\Pmb\MidtransPaymentController::class, 'checkout'])->name('checkout');

    // Portal Akun CAMABA (Khusus Calon Mahasiswa yang Terautentikasi)
    Route::middleware(['auth', 'role:Camaba'])->prefix('akun')->name('akun.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Pmb\CamabaPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/status',    [\App\Http\Controllers\Pmb\CamabaPortalController::class, 'status'])->name('status');
        Route::get('/profil',    [\App\Http\Controllers\Pmb\CamabaPortalController::class, 'profile'])->name('profil');
        Route::put('/profil',    [\App\Http\Controllers\Pmb\CamabaPortalController::class, 'updateProfile'])->name('profil.update');
        Route::get('/form-pendaftaran',  [\App\Http\Controllers\Pmb\CamabaPortalController::class, 'profile'])->name('form-pendaftaran');
        Route::post('/form-pendaftaran', [\App\Http\Controllers\Pmb\CamabaPortalController::class, 'updateProfile'])->name('form-pendaftaran.update');
        Route::put('/form-pendaftaran',  [\App\Http\Controllers\Pmb\CamabaPortalController::class, 'updateProfile'])->name('form-pendaftaran.put');
        Route::get('/ujian',     [\App\Http\Controllers\Pmb\CamabaPortalController::class, 'ujian'])->name('ujian');
        Route::get('/bayar',     [\App\Http\Controllers\Pmb\CamabaPortalController::class, 'bayar'])->name('bayar');
        Route::get('/pembayaran', [\App\Http\Controllers\Pmb\CamabaPortalController::class, 'bayar'])->name('pembayaran');
        Route::get('/bantuan',   [\App\Http\Controllers\Pmb\CamabaPortalController::class, 'bantuan'])->name('bantuan');

        // Upload Dokumen Persyaratan CAMABA
        Route::get('/upload-dokumen',      [\App\Http\Controllers\Pmb\CamabaPortalController::class, 'uploadDokumen'])->name('upload-dokumen');
        Route::post('/upload-dokumen',     [\App\Http\Controllers\Pmb\CamabaPortalController::class, 'storeDokumen'])->name('upload-dokumen.store');
        Route::get('/dokumen/{type}/view', [\App\Http\Controllers\Pmb\CamabaPortalController::class, 'viewDokumen'])->name('dokumen.view');
        Route::delete('/dokumen/{type}',   [\App\Http\Controllers\Pmb\CamabaPortalController::class, 'deleteDokumen'])->name('dokumen.delete');
    });

    // Alias redirect for user/pembayaran and user/upload-dokumen
    Route::get('/user/pembayaran', function () {
        return redirect()->route('pmb.akun.bayar');
    });
    Route::get('/user/upload-dokumen', function () {
        return redirect()->route('pmb.akun.upload-dokumen');
    });
});

Route::get('/user/pembayaran', function () {
    return redirect()->route('pmb.akun.bayar');
});

Route::get('/user/upload-dokumen', function () {
    return redirect()->route('pmb.akun.upload-dokumen');
});

// ──────────────────────────────────────────────────────────────────
// Authenticated Routes (all roles)
// ──────────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    // Internal Calendar
    Route::get('/calendar', [\App\Http\Controllers\CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/api/calendar/events', [\App\Http\Controllers\CalendarController::class, 'fetchEvents'])->name('api.calendar.events');
    Route::post('/calendar/meetings', [\App\Http\Controllers\CalendarController::class, 'storeMeeting'])->name('calendar.meetings.store');
    Route::put('/calendar/meetings/{event}', [\App\Http\Controllers\CalendarController::class, 'updateMeeting'])->name('calendar.meetings.update');
    Route::delete('/calendar/meetings/{event}', [\App\Http\Controllers\CalendarController::class, 'destroyMeeting'])->name('calendar.meetings.destroy');
    Route::post('/calendar/events/{event}/takeover', [\App\Http\Controllers\CalendarController::class, 'takeoverEvent'])->name('calendar.events.takeover');
    Route::put('/calendar/events/{event}', [\App\Http\Controllers\CalendarController::class, 'updateEvent'])->name('calendar.events.update');
    Route::delete('/calendar/events/{event}', [\App\Http\Controllers\CalendarController::class, 'destroyEvent'])->name('calendar.events.destroy');
    Route::post('/calendar/events/{event}/assign-sales', [\App\Http\Controllers\CalendarController::class, 'assignSalesToSpv'])->name('calendar.events.assign-sales');

    // Dependent Dropdown Master Data Wilayah & Sekolah
    Route::get('/api/wilayah/{city}/kecamatan', [\App\Http\Controllers\WilayahController::class, 'getKecamatanByKota'])->name('api.wilayah.kecamatan');
    Route::get('/api/kecamatan/{district}/sekolah', [\App\Http\Controllers\WilayahController::class, 'getSekolahByKecamatan'])->name('api.kecamatan.sekolah');
    Route::get('/wilayah/{city}/kecamatan', [\App\Http\Controllers\WilayahController::class, 'getKecamatanByKota'])->name('wilayah.kecamatan');
    Route::get('/kecamatan/{district}/sekolah', [\App\Http\Controllers\WilayahController::class, 'getSekolahByKecamatan'])->name('kecamatan.sekolah');

    Route::get('/potensi-wilayah', [\App\Http\Controllers\PotensiWilayahController::class, 'index'])->name('potensi-wilayah.index');
    Route::get('/infografis',      [\App\Http\Controllers\InfografisController::class, 'index'])->name('infografis.index');

    // Unified role dashboard dispatcher
    Route::get('/dashboard', function () {
        $role = strtolower(auth()->user()->role ?? 'sales');
        if ($role === 'camaba') {
            return redirect()->route('pmb.akun.dashboard');
        }
        if (in_array($role, ['data analyst', 'data-analyst', 'analyst'])) {
            return redirect()->route('dashboard.data-analyst');
        }
        return redirect()->route('dashboard.' . $role);
    })->name('dashboard');

    // Role-specific dashboard entry points
    Route::prefix('dashboard')->group(function () {
        Route::middleware('role:Sales')->get('/sales',        [\App\Http\Controllers\Sales\DashboardController::class, 'index'])->name('dashboard.sales');
        Route::middleware('role:CS')->get('/cs',              [CrmController::class, 'dashboardCs'])->name('dashboard.cs');
        Route::middleware('role:SPV')->get('/spv',            [\App\Http\Controllers\Spv\DashboardController::class, 'index'])->name('dashboard.spv');
        Route::middleware('role:HM')->get('/hm',              [CrmController::class, 'dashboardHm'])->name('dashboard.hm');
        Route::middleware('role:Admin')->get('/admin',        [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('dashboard.admin');
        Route::middleware('role:EO')->get('/eo',              [\App\Http\Controllers\Eo\DashboardController::class, 'index'])->name('dashboard.eo');
        Route::middleware('role:Data Analyst')->get('/data-analyst', [\App\Http\Controllers\Analyst\DataAnalystController::class, 'index'])->name('dashboard.data-analyst');
        Route::middleware('role:Data Analyst')->get('/data analyst', [\App\Http\Controllers\Analyst\DataAnalystController::class, 'index'])->name('dashboard.data analyst');
    });

    // ──────────────────────────────────────────────────────────────
    // SALES Role Routes — prefix: /spv, name: sales.*
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
            Route::delete('/prospek/{prospek}',       [\App\Http\Controllers\Sales\ProspectController::class, 'destroy'])->name('prospek.destroy');

            // Follow-up management
            Route::get('/follow-up',  [\App\Http\Controllers\Sales\FollowUpController::class, 'index'])->name('follow-up.index');
            Route::post('/follow-up', [\App\Http\Controllers\Sales\FollowUpController::class, 'store'])->name('follow-up.store');

            // Field visit management
            Route::get('/kunjungan',                [\App\Http\Controllers\Sales\VisitController::class, 'index'])->name('kunjungan.index');
            Route::get('/kunjungan/create',         [\App\Http\Controllers\Sales\VisitController::class, 'create'])->name('kunjungan.create');
            Route::post('/kunjungan',               [\App\Http\Controllers\Sales\VisitController::class, 'store'])->name('kunjungan.store');
            Route::get('/kunjungan/{kunjungan}',    [\App\Http\Controllers\Sales\VisitController::class, 'show'])->name('kunjungan.show');
            Route::put('/kunjungan/{kunjungan}',    [\App\Http\Controllers\Sales\VisitController::class, 'update'])->name('kunjungan.update');
            Route::delete('/kunjungan/{kunjungan}', [\App\Http\Controllers\Sales\VisitController::class, 'destroy'])->name('kunjungan.destroy');

            // Events
            Route::get('/event', [\App\Http\Controllers\Sales\EventController::class, 'index'])->name('event.index');
            Route::post('/events/{event}/confirm', [\App\Http\Controllers\Sales\VisitController::class, 'confirmEvent'])->name('event.confirm');

            // Pipeline
            Route::get('/pipeline',                [\App\Http\Controllers\Sales\PipelineController::class, 'index'])->name('pipeline.index');
            Route::post('/pipeline/update-status', [\App\Http\Controllers\Sales\PipelineController::class, 'updateStatus'])->name('pipeline.updateStatus');

            // Riwayat Pembayaran
            Route::get('/pembayaran', [\App\Http\Controllers\Sales\PaymentHistoryController::class, 'index'])->name('pembayaran.index');

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
            Route::put('/kunjungan/{kunjungan}', [\App\Http\Controllers\Spv\VisitController::class, 'update'])->name('kunjungan.update');
            Route::delete('/kunjungan/{kunjungan}', [\App\Http\Controllers\Spv\VisitController::class, 'destroy'])->name('kunjungan.destroy');

            // Team Target & Performance
            Route::get('/target-performa', [\App\Http\Controllers\Spv\PerformanceController::class, 'index'])->name('performa.index');
            Route::post('/target-performa/alokasi', [\App\Http\Controllers\Spv\PerformanceController::class, 'alokasi'])->name('performa.alokasi');
            Route::post('/target-performa/kunci-defisit', [\App\Http\Controllers\Spv\PerformanceController::class, 'kunciDefisit'])->name('performa.kunciDefisit');

            // Team Recap Reports & Payments
            Route::get('/pembayaran', [\App\Http\Controllers\Sales\PaymentHistoryController::class, 'index'])->name('pembayaran.index');
            Route::get('/laporan', [\App\Http\Controllers\Spv\ReportController::class, 'index'])->name('laporan.index');

            // Team Structure & Directory
            Route::get('/tim', [\App\Http\Controllers\Spv\TeamController::class, 'index'])->name('tim.index');
            Route::post('/tim/spv', [\App\Http\Controllers\Spv\TeamController::class, 'storeSales'])->name('tim.sales.store');
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
        Route::post('/wilayah/{wilayah}/unassign-spv/{spv}', [\App\Http\Controllers\Hm\HmWilayahController::class, 'unassignSpv'])->name('wilayah.unassignSpv');
        Route::post('/wilayah/{wilayah}/target', [\App\Http\Controllers\Hm\HmWilayahController::class, 'setWilayahTarget'])->name('wilayah.setTarget');
        Route::post('/wilayah/spv', [\App\Http\Controllers\Hm\HmWilayahController::class, 'storeSpv'])->name('wilayah.storeSpv');

        // HM SPV Management
        Route::get('/spv', [\App\Http\Controllers\Hm\HmSpvController::class, 'index'])->name('spv.index');
        Route::post('/spv', [\App\Http\Controllers\Hm\HmSpvController::class, 'store'])->name('spv.store');
        Route::post('/spv/assign', [\App\Http\Controllers\Hm\HmSpvController::class, 'assignTerritory'])->name('spv.territory.assign');
        Route::patch('/spv/{user}/status', [\App\Http\Controllers\Hm\HmSpvController::class, 'updateStatus'])->name('spv.status.update');
        // HM CS Management
        Route::get('/cs', [\App\Http\Controllers\Hm\HmCsController::class, 'index'])->name('cs.index');
        Route::post('/cs', [\App\Http\Controllers\Hm\HmCsController::class, 'store'])->name('cs.store');
        Route::post('/cs/assign', [\App\Http\Controllers\Hm\HmCsController::class, 'assignTerritory'])->name('cs.territory.assign');
        Route::patch('/cs/{user}/status', [\App\Http\Controllers\Hm\HmCsController::class, 'updateStatus'])->name('cs.status.update');
        Route::delete('/cs/{user}/wilayah/{wilayah}', [\App\Http\Controllers\Hm\HmCsController::class, 'deactivateTerritory'])->name('cs.territory.deactivate');
    });


    // ──────────────────────────────────────────────────────────────
    // EO (Event Organizer) Role Routes
    // ──────────────────────────────────────────────────────────────
    Route::middleware('role:EO')
        ->prefix('eo')
        ->name('eo.')
        ->group(function () {
            Route::resource('events', \App\Http\Controllers\Eo\EventController::class)->except(['create', 'edit']);
            Route::resource('event-types', \App\Http\Controllers\Eo\EventTypeController::class)->except(['create', 'show', 'edit']);
        });

    // ──────────────────────────────────────────────────────────────
    // Data Analyst Role Routes — prefix: /analyst, name: analyst.*
    // Protected by role:Data Analyst,Admin middleware
    // Read-only cross-region, cross-team access + Excel export
    // ──────────────────────────────────────────────────────────────
    Route::middleware('role:Data Analyst,Admin')
        ->prefix('analyst')
        ->name('analyst.')
        ->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Analyst\DataAnalystController::class, 'index'])->name('dashboard');
            Route::get('/prospek',   [\App\Http\Controllers\Analyst\DataAnalystController::class, 'prospek'])->name('prospek.index');
            Route::get('/laporan',   [\App\Http\Controllers\CrmController::class, 'laporanIndex'])->name('laporan.index');
            Route::get('/export',    [\App\Http\Controllers\Analyst\DataAnalystController::class, 'exportExcel'])->name('export');
        });

    // ──────────────────────────────────────────────────────────────
    // Admin Panel Modules — prefix: /admin, name: admin.*
    // Protected by role:Admin middleware
    // ──────────────────────────────────────────────────────────────
    Route::middleware(['role:Admin'])->prefix('admin')->name('admin.')->group(function () {
        $except = ['except' => ['create', 'show', 'edit']];

        // Custom Actions
        Route::post('wilayah/{wilayah}/toggle-status',         [\App\Http\Controllers\Admin\AdminWilayahController::class, 'toggleStatus'])->name('wilayah.toggle-status');
        Route::post('sekolah/{sekolah}/toggle-status',         [\App\Http\Controllers\Admin\AdminSekolahController::class, 'toggleStatus'])->name('sekolah.toggle-status');
        Route::post('prodi/{prodi}/toggle-status',             [\App\Http\Controllers\Admin\AdminProdiController::class, 'toggleStatus'])->name('prodi.toggle-status');
        Route::post('perusahaan/{perusahaan}/toggle-status',   [\App\Http\Controllers\Admin\AdminPerusahaanController::class, 'toggleStatus'])->name('perusahaan.toggle-status');
        Route::post('master-data/{masterData}/toggle-status', [\App\Http\Controllers\Admin\AdminMasterDataController::class, 'toggleStatus'])->name('master-data.toggle-status');

        // Penugasan Wilayah HM (Admin Control)
        Route::get('hm-wilayah',                  [\App\Http\Controllers\Admin\AdminHmWilayahController::class, 'index'])->name('hm-wilayah.index');
        Route::post('hm-wilayah/assign',          [\App\Http\Controllers\Admin\AdminHmWilayahController::class, 'assign'])->name('hm-wilayah.assign');
        Route::post('hm-wilayah/{user}/unassign', [\App\Http\Controllers\Admin\AdminHmWilayahController::class, 'unassign'])->name('hm-wilayah.unassign');

        Route::resource('kunjungan',   \App\Http\Controllers\Admin\AdminKunjunganController::class, $except);
        Route::post('master-data/update-weights', [\App\Http\Controllers\Admin\AdminMasterDataController::class, 'updateWeights'])->name('master-data.update-weights');
        Route::resource('master-data', \App\Http\Controllers\Admin\AdminMasterDataController::class, $except)->parameters([
            'master-data' => 'master_data'
        ]);
        Route::resource('wilayah',     \App\Http\Controllers\Admin\AdminWilayahController::class, $except);
        Route::resource('sekolah',     \App\Http\Controllers\Admin\AdminSekolahController::class, $except);
        Route::resource('prodi',       \App\Http\Controllers\Admin\AdminProdiController::class, $except);
        Route::resource('perusahaan',  \App\Http\Controllers\Admin\AdminPerusahaanController::class, $except);
        Route::resource('events',      \App\Http\Controllers\Admin\AdminEventController::class)->except(['create', 'edit']);
        Route::resource('event-types', \App\Http\Controllers\Admin\AdminEventTypeController::class)->except(['create', 'show', 'edit']);

        Route::post('tahun-akademik',                          [\App\Http\Controllers\Admin\AdminMasterDataController::class, 'storeTahunAkademik'])->name('tahun-akademik.store');
        Route::post('tahun-akademik/{tahunAkademik}/activate', [\App\Http\Controllers\Admin\AdminMasterDataController::class, 'activateTahunAkademik'])->name('tahun-akademik.activate');
        Route::delete('tahun-akademik/{tahunAkademik}',        [\App\Http\Controllers\Admin\AdminMasterDataController::class, 'destroyTahunAkademik'])->name('tahun-akademik.destroy');

        // Master Rekening Bank (Pembayaran CRM)
        Route::post('bank-accounts/{bankAccount}/toggle-status', [\App\Http\Controllers\Admin\AdminBankAccountController::class, 'toggleStatus'])->name('bank-accounts.toggle-status');
        Route::resource('bank-accounts', \App\Http\Controllers\Admin\AdminBankAccountController::class)->except(['create', 'show', 'edit']);

        // Kelola Kalender Kerja
        Route::get('work-calendar', [\App\Http\Controllers\WorkCalendarController::class, 'index'])->name('work-calendar.index');
        Route::post('work-calendar/weekly', [\App\Http\Controllers\WorkCalendarController::class, 'updateWeekly'])->name('work-calendar.update-weekly');
        Route::post('work-calendar', [\App\Http\Controllers\WorkCalendarController::class, 'store'])->name('work-calendar.store');
        Route::put('work-calendar/{workCalendar}', [\App\Http\Controllers\WorkCalendarController::class, 'update'])->name('work-calendar.update');
        Route::delete('work-calendar/{workCalendar}', [\App\Http\Controllers\WorkCalendarController::class, 'destroy'])->name('work-calendar.destroy');


        Route::get('/settings',   [CrmController::class, 'adminSettings'])->name('settings.index');
    });

    // Admin & HM Shared Modules (User Management & Target)
    Route::middleware(['role:Admin,HM'])->prefix('admin')->name('admin.')->group(function () {
        $except = ['except' => ['create', 'show', 'edit']];
        Route::post('users/{user}/reset-password',             [\App\Http\Controllers\Admin\AdminUserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('users/{user}/toggle-status',              [\App\Http\Controllers\Admin\AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('users/{user}/approve',                    [\App\Http\Controllers\Admin\AdminUserController::class, 'approve'])->name('users.approve');
        Route::post('users/{user}/reject',                     [\App\Http\Controllers\Admin\AdminUserController::class, 'reject'])->name('users.reject');
        Route::resource('users', \App\Http\Controllers\Admin\AdminUserController::class, $except);
        Route::resource('target', \App\Http\Controllers\Admin\AdminTargetController::class, $except);
        Route::get('/audit-logs', [CrmController::class, 'adminAuditLogs'])->name('audit-logs.index');

        // Tahun Akademik Management (PRD 5.2)
        Route::get('tahun-akademik',                           [\App\Http\Controllers\Admin\TahunAkademikController::class, 'index'])->name('tahun-akademik.index');
        Route::post('tahun-akademik',                          [\App\Http\Controllers\Admin\TahunAkademikController::class, 'store'])->name('tahun-akademik.store');
        Route::post('tahun-akademik/{tahunAkademik}/activate', [\App\Http\Controllers\Admin\TahunAkademikController::class, 'activate'])->name('tahun-akademik.activate');
    });

    // ──────────────────────────────────────────────────────────────
    // Shared CRM Modules (CS, SPV, HM, Admin, Fallback, Sales, EO)
    // ──────────────────────────────────────────────────────────────
    Route::middleware(['role:Admin,HM,SPV,CS,Sales,EO,Data Analyst'])->group(function () {
        // Attendance Routes
        Route::middleware(['role:Sales,CS,EO,SPV'])->group(function () {
            Route::get('/absensi/input', [\App\Http\Controllers\AttendanceController::class, 'create'])->name('attendance.create');
            Route::post('/absensi/input', [\App\Http\Controllers\AttendanceController::class, 'store'])->name('attendance.store');
        });
        Route::middleware(['role:HM,SPV'])->group(function () {
            Route::get('/absensi/kelola', [\App\Http\Controllers\AttendanceController::class, 'index'])->name('attendance.index');
            Route::get('/absensi/kelola/{id}', [\App\Http\Controllers\AttendanceController::class, 'show'])->name('attendance.show');
        });
        Route::get('/prospek',         [CrmController::class, 'prospekIndex'])->name('prospek.index');
        Route::post('/prospek',        [CrmController::class, 'prospekStore'])->name('prospek.store');
        Route::post('/prospek/presensi-manual', [CrmController::class, 'prospekPresensiManual'])->name('prospek.presensi-manual');
        Route::get('/prospek/{id}',    [CrmController::class, 'prospekShow'])->name('prospek.show');
        Route::put('/prospek/{id}',    [CrmController::class, 'prospekUpdate'])->name('prospek.update');
        Route::delete('/prospek/{id}', [CrmController::class, 'prospekDestroy'])->name('prospek.destroy');
        Route::post('/prospek/{id}/takeover', [CrmController::class, 'prospekTakeover'])->name('prospek.takeover');
        Route::get('/prospek/{id}/takeover', fn($id) => redirect()->route('prospek.show', $id));
        Route::post('/prospek/{id}/transaksi', [CrmController::class, 'transaksiStore'])->name('prospek.transaksi');
        Route::get('/prospek/{id}/transaksi', fn($id) => redirect()->route('prospek.show', $id));
        Route::post('/prospek/{id}/realokasi', [CrmController::class, 'prospekRealokasi'])->name('prospek.realokasi');
        Route::get('/prospek/{id}/realokasi', fn($id) => redirect()->route('prospek.show', $id));

        Route::get('/kunjungan',         [CrmController::class, 'kunjunganIndex'])->name('kunjungan.index');
        Route::get('/kunjungan/create',  [\App\Http\Controllers\Sales\VisitController::class, 'create'])->name('kunjungan.create');
        Route::post('/kunjungan',        [\App\Http\Controllers\Sales\VisitController::class, 'store'])->name('kunjungan.store');
        Route::get('/kunjungan/{kunjungan}', [\App\Http\Controllers\Sales\VisitController::class, 'show'])->name('kunjungan.show');
        Route::put('/kunjungan/{kunjungan}', [CrmController::class, 'kunjunganUpdate'])->name('kunjungan.update');
        Route::delete('/kunjungan/{kunjungan}', [CrmController::class, 'kunjunganDestroy'])->name('kunjungan.destroy');

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

    // ──────────────────────────────────────────────────────────────
    // CS Verifikasi Pembayaran (CS, Admin, HM)
    // ──────────────────────────────────────────────────────────────
    Route::middleware(['role:CS,Admin,HM'])->prefix('cs')->name('cs.')->group(function () {
        Route::get('/verifikasi',                           [\App\Http\Controllers\Cs\VerifikasiController::class, 'index'])->name('verifikasi.index');
        Route::post('/verifikasi/{transaksi}/verify',       [\App\Http\Controllers\Cs\VerifikasiController::class, 'verify'])->name('verifikasi.verify');
        Route::post('/verifikasi/{transaksi}/reject',       [\App\Http\Controllers\Cs\VerifikasiController::class, 'reject'])->name('verifikasi.reject');
    });

    // ──────────────────────────────────────────────────────────────
    // PMB UCIC Integrated Modules (Admin, HM, CS)
    // ──────────────────────────────────────────────────────────────
    Route::middleware(['role:Admin,HM,CS'])->prefix('pmb')->name('pmb.')->group(function () {
        // PMB Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Pmb\PmbDashboardController::class, 'index'])->name('dashboard');

        // Data Pendaftar PMB
        Route::get('/pendaftar',                      [\App\Http\Controllers\Pmb\PmbPendaftarController::class, 'index'])->name('pendaftar.index');
        Route::get('/pendaftar/export',               [\App\Http\Controllers\Pmb\PmbPendaftarController::class, 'export'])->name('pendaftar.export');
        Route::get('/pendaftar/{id}',                 [\App\Http\Controllers\Pmb\PmbPendaftarController::class, 'show'])->name('pendaftar.show');
        Route::put('/pendaftar/{id}',                 [\App\Http\Controllers\Pmb\PmbPendaftarController::class, 'update'])->name('pendaftar.update');
        Route::delete('/pendaftar/{id}',              [\App\Http\Controllers\Pmb\PmbPendaftarController::class, 'destroy'])->name('pendaftar.destroy');
        Route::post('/pendaftar/{id}/reset-password', [\App\Http\Controllers\Pmb\PmbPendaftarController::class, 'resetPassword'])->name('pendaftar.reset-password');

        // Data Pembayaran PMB
        Route::get('/pembayaran',                     [\App\Http\Controllers\Pmb\PmbPembayaranController::class, 'index'])->name('pembayaran.index');
        Route::get('/pembayaran/export',              [\App\Http\Controllers\Pmb\PmbPembayaranController::class, 'export'])->name('pembayaran.export');
        Route::post('/pembayaran/{id}/verify',        [\App\Http\Controllers\Pmb\PmbPembayaranController::class, 'verify'])->name('pembayaran.verify');
        Route::post('/pembayaran/{id}/reject',        [\App\Http\Controllers\Pmb\PmbPembayaranController::class, 'reject'])->name('pembayaran.reject');

        // Data Admin PMB (HANYA untuk Admin & HM, CS DILARANG AKSES)
        Route::middleware(['role:Admin,HM'])->group(function () {
            Route::get('/admin',              [\App\Http\Controllers\Pmb\PmbAdminController::class, 'index'])->name('admin.index');
            Route::post('/admin',             [\App\Http\Controllers\Pmb\PmbAdminController::class, 'store'])->name('admin.store');
            Route::put('/admin/{id}',         [\App\Http\Controllers\Pmb\PmbAdminController::class, 'update'])->name('admin.update');
            Route::delete('/admin/{id}',      [\App\Http\Controllers\Pmb\PmbAdminController::class, 'destroy'])->name('admin.destroy');
        });
    });    // Account & Profile (all roles)
    Route::get('/profil',           [CrmController::class, 'profilIndex'])->name('profil.index');
    Route::post('/profil',          [CrmController::class, 'profilUpdate'])->name('profil.update');
    Route::post('/profil/password', [CrmController::class, 'profilPasswordUpdate'])->name('profil.password.update');
    Route::get('/pengaturan',       [CrmController::class, 'pengaturanIndex'])->name('pengaturan.index');
    Route::post('/pengaturan/terima-kasih', [CrmController::class, 'updateTerimaKasihSettings'])->name('pengaturan.update-terima-kasih');
    Route::post('/pengaturan/terima-kasih-corporate', [CrmController::class, 'updateTerimaKasihCorporateSettings'])->name('pengaturan.update-terima-kasih-corporate');

    // Unduh Spreadsheet (SPS) Data Routes (Semua Role sesuai kewenangan)
    Route::get('/export/prospek',    [\App\Http\Controllers\ExportController::class, 'exportProspek'])->name('export.prospek');
    Route::get('/export/kunjungan',  [\App\Http\Controllers\ExportController::class, 'exportKunjungan'])->name('export.kunjungan');
    Route::get('/export/verifikasi', [\App\Http\Controllers\ExportController::class, 'exportVerifikasi'])->name('export.verifikasi');
    Route::get('/export/pembayaran', [\App\Http\Controllers\ExportController::class, 'exportPembayaran'])->name('export.pembayaran');

    // Notifications (all roles)
    Route::get('/notifications/latest',         [NotificationController::class, 'getLatest'])->name('notifications.latest');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::post('/notifications/read-all',      [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::post('/notifications/{id}/read',     [NotificationController::class, 'markAsRead'])->name('notifications.markRead');
});

// ──────────────────────────────────────────────────────────────────
// Mobile Platform Form Lapangan (Flutter WebView Integration)
// ──────────────────────────────────────────────────────────────────
Route::prefix('mobile')->name('mobile.')->group(function () {
    Route::get('/prospek/create',   [\App\Http\Controllers\MobileFormController::class, 'prospekCreate'])->name('prospek.create');
    Route::post('/prospek',         [\App\Http\Controllers\MobileFormController::class, 'prospekStore'])->name('prospek.store');

    Route::get('/kunjungan/create', [\App\Http\Controllers\MobileFormController::class, 'kunjunganCreate'])->name('kunjungan.create');
    Route::post('/kunjungan',       [\App\Http\Controllers\MobileFormController::class, 'kunjunganStore'])->name('kunjungan.store');

    Route::get('/sukses',           [\App\Http\Controllers\MobileFormController::class, 'sukses'])->name('sukses');
});
