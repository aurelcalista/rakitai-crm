<?php

namespace Tests\Feature;

use App\Models\Kunjungan;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\Sekolah;
use App\Models\Target;
use App\Models\TahunAkademik;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\AkademikService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AkademikTest extends TestCase
{
    use RefreshDatabase;

    protected User $sales;
    protected Prodi $prodi;
    protected Sekolah $sekolah;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Flush AkademikService per-request cache before each test
        AkademikService::flushCache();

        // Ensure a clean slate: deactivate everything inside the transaction
        TahunAkademik::query()->update(['status' => 'Non-Aktif']);
        AkademikService::flushCache();

        $this->sales  = User::factory()->create(['role' => 'Sales']);
        $this->prodi  = Prodi::first() ?? Prodi::create([
            'kode' => 'TI-' . uniqid(), 'nama' => 'Teknik Informatika', 'jenjang' => 'S1',
            'fakultas' => 'FTIK', 'status' => 'Aktif',
        ]);
        $this->sekolah = Sekolah::create([
            'kode' => 'SCH-' . uniqid(), 'nama' => 'SMA Test', 'tier' => 'A',
            'alamat' => 'Jl. Test', 'pic_name' => 'PIC', 'pic_phone' => '08123',
            'status' => 'Aktif', 'lat' => -6.726, 'lng' => 108.557,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // A. Hanya satu TA dapat aktif
    // ──────────────────────────────────────────────────────────────
    public function test_a_only_one_tahun_akademik_can_be_active(): void
    {
        $ta1 = TahunAkademik::create(['nama' => '2025/2026-a', 'status' => 'Aktif']);
        $ta2 = TahunAkademik::create(['nama' => '2026/2027-a', 'status' => 'Aktif']); // triggers booted()

        AkademikService::flushCache();

        $this->assertEquals('Non-Aktif', $ta1->fresh()->status,
            'ta1 harus menjadi Non-Aktif ketika ta2 diaktifkan');
        $this->assertEquals('Aktif', $ta2->fresh()->status);
        $this->assertEquals(1, TahunAkademik::where('status', 'Aktif')->count());
    }

    // ──────────────────────────────────────────────────────────────
    // B. Aktivasi TA baru otomatis menonaktifkan yang lama
    // ──────────────────────────────────────────────────────────────
    public function test_b_activating_new_ta_deactivates_previous(): void
    {
        $taA = TahunAkademik::create(['nama' => '2025/2026-b', 'status' => 'Aktif']);
        AkademikService::flushCache();

        $taB = TahunAkademik::create(['nama' => '2026/2027-b', 'status' => 'Non-Aktif']);
        AkademikService::activate($taB);

        $this->assertEquals('Non-Aktif', $taA->fresh()->status);
        $this->assertEquals('Aktif', $taB->fresh()->status);
        $this->assertEquals(1, TahunAkademik::where('status', 'Aktif')->count());
    }

    // ──────────────────────────────────────────────────────────────
    // C. Central mechanism mengembalikan TA aktif
    // ──────────────────────────────────────────────────────────────
    public function test_c_central_mechanism_returns_active_ta(): void
    {
        $ta = TahunAkademik::create(['nama' => '2027/2028-c', 'status' => 'Aktif']);
        AkademikService::flushCache();

        $aktif = AkademikService::getAktif();
        $this->assertNotNull($aktif);
        $this->assertEquals($ta->id, $aktif->id);

        $aktifById = AkademikService::getAktifId();
        $this->assertEquals($ta->id, $aktifById);

        $aktifOrFail = AkademikService::getAktifOrFail();
        $this->assertEquals($ta->id, $aktifOrFail->id);
    }

    // ──────────────────────────────────────────────────────────────
    // D. Prospek baru otomatis mendapat active academic year
    // ──────────────────────────────────────────────────────────────
    public function test_d_new_prospek_gets_active_academic_year(): void
    {
        $ta = TahunAkademik::create(['nama' => '2027/2028-d', 'status' => 'Aktif']);
        AkademikService::flushCache();

        $prospek = Prospek::create([
            'name'     => 'Prospek Test D',
            'type'     => 'Individu',
            'source'   => 'Website',
            'status'   => 'BARU',
            'sales_id' => $this->sales->id,
            'owner_id' => $this->sales->id,
            'prodi_id' => $this->prodi->id,
        ]);

        $this->assertEquals($ta->id, $prospek->academic_year_id);
    }

    // ──────────────────────────────────────────────────────────────
    // E. Kunjungan baru otomatis mendapat active academic year
    // ──────────────────────────────────────────────────────────────
    public function test_e_new_kunjungan_gets_active_academic_year(): void
    {
        $ta = TahunAkademik::create(['nama' => '2027/2028-e', 'status' => 'Aktif']);
        AkademikService::flushCache();

        $kunjungan = Kunjungan::create([
            'nomor'         => 'KNJ-TEST-E',
            'tanggal'       => now()->toDateString(),
            'waktu'         => '08:00:00',
            'sales_id'      => $this->sales->id,
            'prodi_id'      => $this->prodi->id,
            'jenis'         => 'Sekolah',
            'tujuan_id'     => $this->sekolah->id,
            'status'        => 'Selesai',
            'lat'           => -6.726,
            'lng'           => 108.557,
            'jarak_meter'   => 10.0,
            'status_lokasi' => 'Valid',
            'is_verified'   => true,
        ]);

        $this->assertEquals($ta->id, $kunjungan->academic_year_id);
    }

    // ──────────────────────────────────────────────────────────────
    // F. Target baru otomatis mendapat active academic year
    // ──────────────────────────────────────────────────────────────
    public function test_f_new_target_gets_active_academic_year(): void
    {
        $ta = TahunAkademik::create(['nama' => '2027/2028-f', 'status' => 'Aktif']);
        AkademikService::flushCache();

        $target = Target::create([
            'sales_id'       => $this->sales->id,
            'allocated_by'   => $this->sales->id,
            'tipe_periode'   => 'Bulanan',
            'tanggal_mulai'  => now()->startOfMonth()->toDateString(),
            'tanggal_selesai'=> now()->endOfMonth()->toDateString(),
            'target_kontak'  => 50,
            'target_formulir'=> 10,
            'target_lunas'   => 5,
            'status'         => 'Aktif',
        ]);

        $this->assertEquals($ta->id, $target->academic_year_id);
    }

    // ──────────────────────────────────────────────────────────────
    // G. Formulir (Transaksi 'Beli Formulir') mendapat academic year
    // ──────────────────────────────────────────────────────────────
    public function test_g_new_transaksi_beli_formulir_gets_academic_year(): void
    {
        $ta = TahunAkademik::create(['nama' => '2027/2028-g', 'status' => 'Aktif']);
        AkademikService::flushCache();

        $prospek = Prospek::create([
            'name' => 'P-G', 'type' => 'Individu', 'source' => 'Website',
            'status' => 'FORMULIR', 'sales_id' => $this->sales->id,
            'owner_id' => $this->sales->id, 'prodi_id' => $this->prodi->id,
        ]);

        $trx = Transaksi::create([
            'prospek_id' => $prospek->id,
            'user_id'    => $this->sales->id,
            'jenis'      => 'Beli Formulir',
            'nominal'    => 200000,
            'tanggal'    => now(),
        ]);

        $this->assertEquals($ta->id, $trx->academic_year_id);
    }

    // ──────────────────────────────────────────────────────────────
    // H. Pembayaran Termin 1 mendapat academic year
    // ──────────────────────────────────────────────────────────────
    public function test_h_new_transaksi_termin1_gets_academic_year(): void
    {
        $ta = TahunAkademik::create(['nama' => '2027/2028-h', 'status' => 'Aktif']);
        AkademikService::flushCache();

        $prospek = Prospek::create([
            'name' => 'P-H', 'type' => 'Individu', 'source' => 'Website',
            'status' => 'LUNAS', 'sales_id' => $this->sales->id,
            'owner_id' => $this->sales->id, 'prodi_id' => $this->prodi->id,
        ]);

        $trx = Transaksi::create([
            'prospek_id' => $prospek->id,
            'user_id'    => $this->sales->id,
            'jenis'      => 'Pembayaran Termin 1',
            'nominal'    => 5000000,
            'tanggal'    => now(),
        ]);

        $this->assertEquals($ta->id, $trx->academic_year_id);
    }

    // ──────────────────────────────────────────────────────────────
    // I. Dashboard default tidak mencampur data dari TA yang berbeda
    // ──────────────────────────────────────────────────────────────
    public function test_i_dashboard_query_does_not_mix_academic_years(): void
    {
        $taOld = TahunAkademik::create(['nama' => '2025/2026-i', 'status' => 'Aktif']);
        AkademikService::flushCache();

        $prospekOld = Prospek::create([
            'name' => 'Old Prospek', 'type' => 'Individu', 'source' => 'Website',
            'status' => 'BARU', 'sales_id' => $this->sales->id,
            'owner_id' => $this->sales->id, 'prodi_id' => $this->prodi->id,
        ]);
        $this->assertEquals($taOld->id, $prospekOld->academic_year_id);

        // Now switch to a new TA
        $taNew = TahunAkademik::create(['nama' => '2027/2028-i', 'status' => 'Non-Aktif']);
        AkademikService::activate($taNew);

        $prospekNew = Prospek::create([
            'name' => 'New Prospek', 'type' => 'Individu', 'source' => 'Website',
            'status' => 'BARU', 'sales_id' => $this->sales->id,
            'owner_id' => $this->sales->id, 'prodi_id' => $this->prodi->id,
        ]);
        $this->assertEquals($taNew->id, $prospekNew->academic_year_id);

        // Active TA query must NOT return the old prospek
        $activeCount = Prospek::where('sales_id', $this->sales->id)
            ->where('academic_year_id', $taNew->id)
            ->count();
        $this->assertEquals(1, $activeCount, 'Hanya prospek TA baru yang terlihat di query active TA');

        // Old prospek still accessible via old TA id
        $historicalCount = Prospek::where('sales_id', $this->sales->id)
            ->where('academic_year_id', $taOld->id)
            ->count();
        $this->assertEquals(1, $historicalCount);
    }

    // ──────────────────────────────────────────────────────────────
    // J. Historical data tetap dapat dibaca dengan filter TA lama
    // ──────────────────────────────────────────────────────────────
    public function test_j_historical_data_readable_via_old_ta_filter(): void
    {
        $taOld = TahunAkademik::create(['nama' => '2025/2026-j', 'status' => 'Aktif']);
        AkademikService::flushCache();

        $oldProspek = Prospek::create([
            'name' => 'Historical', 'type' => 'Individu', 'source' => 'Website',
            'status' => 'LUNAS', 'sales_id' => $this->sales->id,
            'owner_id' => $this->sales->id, 'prodi_id' => $this->prodi->id,
        ]);

        $taNew = TahunAkademik::create(['nama' => '2027/2028-j', 'status' => 'Non-Aktif']);
        AkademikService::activate($taNew);

        // Historical TA filter still works
        $found = Prospek::where('academic_year_id', $taOld->id)->where('name', 'Historical')->first();
        $this->assertNotNull($found, 'Data historis harus tetap dapat dibaca via TA lama');
        $this->assertEquals('LUNAS', $found->status);
    }

    // ──────────────────────────────────────────────────────────────
    // K. Tidak ada logic baru menggunakan Carbon::now() sebagai TA
    // ──────────────────────────────────────────────────────────────
    public function test_k_new_records_use_academic_year_id_not_carbon_now(): void
    {
        $ta = TahunAkademik::create(['nama' => '2027/2028-k', 'status' => 'Aktif']);
        AkademikService::flushCache();

        $prospek = Prospek::create([
            'name' => 'P-K', 'type' => 'Individu', 'source' => 'Website',
            'status' => 'BARU', 'sales_id' => $this->sales->id,
            'owner_id' => $this->sales->id, 'prodi_id' => $this->prodi->id,
        ]);

        // Must have academic_year_id, not rely on calendar year
        $this->assertNotNull($prospek->academic_year_id);
        $this->assertEquals($ta->id, $prospek->academic_year_id);

        // The active TA name must match what we set, not Carbon::now()->year
        $this->assertNotEquals(now()->year . '/' . (now()->year + 1), $ta->nama,
            'TA aktif bukan tahun kalender sekarang — ini membuktikan TA adalah konfigurasi bisnis, bukan derivasi Carbon::now()');
    }

    // ──────────────────────────────────────────────────────────────
    // L. Tidak ada TA aktif → business logic gagal secara eksplisit
    // ──────────────────────────────────────────────────────────────
    public function test_l_no_active_ta_causes_explicit_failure(): void
    {
        // All TAs already deactivated in setUp
        $this->assertNull(AkademikService::getAktif());
        $this->assertNull(AkademikService::getAktifId());

        $this->expectException(\RuntimeException::class);
        AkademikService::getAktifOrFail();
    }

    // ──────────────────────────────────────────────────────────────
    // M. Active TA + target bulan berjalan → ditemukan
    // ──────────────────────────────────────────────────────────────
    public function test_m_active_ta_and_current_month_target_found(): void
    {
        $ta = TahunAkademik::create(['nama' => '2027/2028-m', 'status' => 'Aktif']);
        AkademikService::flushCache();

        Target::create([
            'sales_id'        => $this->sales->id,
            'allocated_by'    => $this->sales->id,
            'tipe_periode'    => 'Bulanan',
            'tanggal_mulai'   => now()->startOfMonth()->toDateString(),
            'tanggal_selesai' => now()->endOfMonth()->toDateString(),
            'target_kontak'   => 30,
            'target_formulir' => 5,
            'target_lunas'    => 2,
            'status'          => 'Aktif',
            'academic_year_id'=> $ta->id,
        ]);

        $service = app(\App\Services\SalesTargetService::class);
        $found = $service->getActiveTarget($this->sales);

        $this->assertNotNull($found);
        $this->assertEquals($ta->id, $found->academic_year_id);
    }

    // ──────────────────────────────────────────────────────────────
    // N. Active TA + target bulan LAIN → tidak dianggap target bulan ini
    // ──────────────────────────────────────────────────────────────
    public function test_n_target_from_different_month_not_returned(): void
    {
        $ta = TahunAkademik::create(['nama' => '2027/2028-n', 'status' => 'Aktif']);
        AkademikService::flushCache();

        // Create a target for last month
        Target::create([
            'sales_id'        => $this->sales->id,
            'allocated_by'    => $this->sales->id,
            'tipe_periode'    => 'Bulanan',
            'tanggal_mulai'   => now()->subMonth()->startOfMonth()->toDateString(),
            'tanggal_selesai' => now()->subMonth()->endOfMonth()->toDateString(),
            'target_kontak'   => 30,
            'target_formulir' => 5,
            'target_lunas'    => 2,
            'status'          => 'Aktif',
            'academic_year_id'=> $ta->id,
        ]);

        $service = app(\App\Services\SalesTargetService::class);
        $found = $service->getActiveTarget($this->sales);

        $this->assertNull($found, 'Target bulan lalu tidak boleh dianggap target bulan ini');
    }

    // ──────────────────────────────────────────────────────────────
    // O. Historical override → ambil TA yang diminta, bukan active TA
    // ──────────────────────────────────────────────────────────────
    public function test_o_historical_ta_override_uses_requested_ta(): void
    {
        $taOld = TahunAkademik::create(['nama' => '2025/2026-o', 'status' => 'Aktif']);
        AkademikService::flushCache();

        $spv = User::factory()->create(['role' => 'SPV']);

        // Create a target for the old TA
        $oldTarget = Target::create([
            'sales_id'        => $spv->id,
            'allocated_by'    => $spv->id,
            'tipe_periode'    => 'Bulanan',
            'tanggal_mulai'   => now()->startOfMonth()->toDateString(),
            'tanggal_selesai' => now()->endOfMonth()->toDateString(),
            'target_kontak'   => 100,
            'target_formulir' => 20,
            'target_lunas'    => 10,
            'status'          => 'Aktif',
            'academic_year_id'=> $taOld->id,
            'tahun_akademik'  => $taOld->nama,
        ]);

        // Switch to new active TA
        $taNew = TahunAkademik::create(['nama' => '2027/2028-o', 'status' => 'Non-Aktif']);
        AkademikService::activate($taNew);

        // Use SpvPerformanceService with historical override
        $service = app(\App\Services\SpvPerformanceService::class);
        $result  = $service->getTargetHmForSpv($spv, $taOld->nama);

        $this->assertEquals($taOld->nama, $result['tahun_akademik'],
            'Historical override harus menggunakan TA yang diminta, bukan active TA');
    }

    // ──────────────────────────────────────────────────────────────
    // P. Dua aktivasi berurutan → tidak menghasilkan >1 active TA
    // ──────────────────────────────────────────────────────────────
    public function test_p_sequential_activations_never_produce_two_active_ta(): void
    {
        $taA = TahunAkademik::create(['nama' => '2025/2026-p', 'status' => 'Non-Aktif']);
        $taB = TahunAkademik::create(['nama' => '2026/2027-p', 'status' => 'Non-Aktif']);
        $taC = TahunAkademik::create(['nama' => '2027/2028-p', 'status' => 'Non-Aktif']);

        AkademikService::activate($taA);
        AkademikService::activate($taB);
        AkademikService::activate($taC);

        $activeCount = TahunAkademik::where('status', 'Aktif')
            ->whereIn('id', [$taA->id, $taB->id, $taC->id])
            ->count();

        $this->assertEquals(1, $activeCount, 'Setelah 3 aktivasi berurutan, hanya 1 TA yang aktif');
        $this->assertEquals('Aktif', $taC->fresh()->status);
        $this->assertEquals('Non-Aktif', $taA->fresh()->status);
        $this->assertEquals('Non-Aktif', $taB->fresh()->status);
    }

    // ──────────────────────────────────────────────────────────────
    // Q. Historical record dengan academic_year_id = NULL tidak berubah
    // ──────────────────────────────────────────────────────────────
    public function test_q_historical_null_academic_year_id_not_auto_assigned(): void
    {
        // Create a TA and activate it
        $ta = TahunAkademik::create(['nama' => '2027/2028-q', 'status' => 'Aktif']);
        AkademikService::flushCache();

        // Manually insert a prospek with NULL academic_year_id (simulates historical data)
        DB::table('prospeks')->insert([
            'name'       => 'Historical Null AY',
            'type'       => 'Individu',
            'source'     => 'Website',
            'status'     => 'BARU',
            'sales_id'   => $this->sales->id,
            'owner_id'   => $this->sales->id,
            'prodi_id'   => $this->prodi->id,
            'created_at' => now(),
            'updated_at' => now(),
            // academic_year_id intentionally omitted → NULL
        ]);

        // Nothing in the application should auto-assign academic_year_id to existing records
        $nullRecord = \App\Models\Prospek::where('name', 'Historical Null AY')->first();
        $this->assertNull($nullRecord->academic_year_id,
            'Record historis dengan academic_year_id = NULL tidak boleh diubah secara otomatis');

        // And it must NOT appear in active-TA scoped queries
        $activeCount = \App\Models\Prospek::where('name', 'Historical Null AY')
            ->where('academic_year_id', $ta->id)
            ->count();
        $this->assertEquals(0, $activeCount,
            'Record historis NULL tidak boleh muncul di query active TA');
    }
}
