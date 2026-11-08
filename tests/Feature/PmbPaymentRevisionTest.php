<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Prospek;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\MidtransService;
use App\Services\PaymentNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PmbPaymentRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $csUser;
    protected User $hmUser;
    protected User $spvUser;
    protected User $salesUser;
    protected string $serverKey = 'SB-Mid-server-TESTKEY123';
    protected string $clientKey = 'SB-Mid-client-TESTKEY123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'Admin', 'status' => 'Aktif']);
        $this->csUser    = User::factory()->create(['role' => 'CS', 'status' => 'Aktif']);
        $this->hmUser    = User::factory()->create(['role' => 'HM', 'status' => 'Aktif']);
        $this->spvUser   = User::factory()->create(['role' => 'SPV', 'status' => 'Aktif']);
        $this->salesUser = User::factory()->create(['role' => 'Sales', 'status' => 'Aktif']);

        config([
            'services.midtrans.server_key'            => $this->serverKey,
            'services.midtrans.client_key'            => $this->clientKey,
            'services.midtrans.is_production'         => false,
            'services.midtrans.biaya_pendaftaran'     => 300000,
            'services.midtrans.biaya_dp'              => 500000,
            'services.midtrans.total_tagihan_standar' => 5000000,
        ]);

        Storage::fake('local');
        Cache::flush();
    }

    protected function generateSignature(string $orderId, string $statusCode, string $grossAmount): string
    {
        return hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
    }

    /**
     * Skenario 1: Membayar DP pertama kali -> Transaksi berhasil dibuat.
     */
    public function test_01_membayar_dp_pertama_kali_berhasil_dibuat(): void
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Ahmad Fajar',
            'email'    => 'ahmad@gmail.com',
            'whatsapp' => '081234567891',
            'status'   => 'BARU',
        ]);

        $response = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_DP,
        ]);

        $response->assertOk()
            ->assertJson([
                'success'  => true,
                'kategori' => Transaksi::KATEGORI_DP,
                'nominal'  => 500000,
            ]);

        $this->assertDatabaseHas('transaksis', [
            'prospek_id'     => $prospek->id,
            'kategori'       => Transaksi::KATEGORI_DP,
            'nominal'        => 500000,
            'payment_status' => Transaksi::STATUS_PENDING,
        ]);
    }

    /**
     * Skenario 2: Membayar DP kedua kali setelah lunas -> Ditolak.
     */
    public function test_02_membayar_dp_kedua_kali_setelah_lunas_ditolak(): void
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Budi Lunas DP',
            'whatsapp' => '081234567892',
            'status'   => 'FORMULIR',
        ]);

        // Simulasikan DP sudah sah diverifikasi (LUNAS)
        Transaksi::create([
            'order_id'          => 'PMB-UCIC-' . $prospek->id . '-DP-PAID',
            'prospek_id'        => $prospek->id,
            'kategori'          => Transaksi::KATEGORI_DP,
            'jenis'             => 'DP/Konversi',
            'nominal'           => 500000,
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'tanggal'           => now(),
        ]);

        $response = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_DP,
            'fresh'    => true,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
        $this->assertStringContainsString('lunas', strtolower($response->json('message')));
    }

    /**
     * Skenario 3: Membayar pendaftaran pertama kali -> Transaksi berhasil dibuat.
     */
    public function test_03_membayar_pendaftaran_pertama_kali_berhasil_dibuat(): void
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Citra Pendaftaran',
            'whatsapp' => '081234567893',
            'status'   => 'BARU',
        ]);

        $response = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PENDAFTARAN,
        ]);

        $response->assertOk()
            ->assertJson([
                'success'  => true,
                'kategori' => Transaksi::KATEGORI_PENDAFTARAN,
                'nominal'  => 300000,
            ]);

        $this->assertDatabaseHas('transaksis', [
            'prospek_id'     => $prospek->id,
            'kategori'       => Transaksi::KATEGORI_PENDAFTARAN,
            'nominal'        => 300000,
            'payment_status' => Transaksi::STATUS_PENDING,
        ]);
    }

    /**
     * Skenario 4: Membayar pendaftaran kedua kali setelah lunas -> Ditolak.
     */
    public function test_04_membayar_pendaftaran_kedua_kali_setelah_lunas_ditolak(): void
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Dedi Lunas Daftar',
            'whatsapp' => '081234567894',
            'status'   => 'FORMULIR',
        ]);

        // Simulasikan pendaftaran sudah lunas
        Transaksi::create([
            'order_id'          => 'PMB-UCIC-' . $prospek->id . '-REG-PAID',
            'prospek_id'        => $prospek->id,
            'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
            'jenis'             => 'Beli Formulir',
            'nominal'           => 300000,
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'tanggal'           => now(),
        ]);

        $response = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PENDAFTARAN,
            'fresh'    => true,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
        $this->assertStringContainsString('lunas', strtolower($response->json('message')));
    }

    /**
     * Skenario 5: Membayar pelunasan pertama kali -> Transaksi berhasil dibuat.
     */
    public function test_05_membayar_pelunasan_pertama_kali_berhasil_dibuat(): void
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Eko Pelunasan',
            'whatsapp' => '081234567895',
            'status'   => 'FORMULIR',
        ]);

        // Pendaftaran 300.000 sudah dibayar
        Transaksi::create([
            'order_id'          => 'PMB-UCIC-' . $prospek->id . '-REG',
            'prospek_id'        => $prospek->id,
            'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
            'jenis'             => 'Beli Formulir',
            'nominal'           => 300000,
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'tanggal'           => now(),
        ]);

        // Total tagihan = 5.000.000, terverifikasi = 300.000, sisa = 4.700.000
        $response = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PELUNASAN,
        ]);

        $response->assertOk()
            ->assertJson([
                'success'  => true,
                'kategori' => Transaksi::KATEGORI_PELUNASAN,
                'nominal'  => 4700000,
            ]);

        $this->assertDatabaseHas('transaksis', [
            'prospek_id'     => $prospek->id,
            'kategori'       => Transaksi::KATEGORI_PELUNASAN,
            'nominal'        => 4700000,
            'payment_status' => Transaksi::STATUS_PENDING,
        ]);
    }

    /**
     * Skenario 6: Membayar pelunasan kedua kali setelah lunas -> Ditolak.
     */
    public function test_06_membayar_pelunasan_kedua_kali_setelah_lunas_ditolak(): void
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Fulan Lunas Kuliah',
            'whatsapp' => '081234567896',
            'status'   => 'LUNAS',
        ]);

        Transaksi::create([
            'order_id'          => 'PMB-UCIC-' . $prospek->id . '-PELUNASAN-PAID',
            'prospek_id'        => $prospek->id,
            'kategori'          => Transaksi::KATEGORI_PELUNASAN,
            'jenis'             => 'Pelunasan',
            'nominal'           => 4700000,
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'tanggal'           => now(),
        ]);

        $response = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PELUNASAN,
            'fresh'    => true,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Skenario 7: Membuat transaksi kedua saat transaksi pertama pending -> Ditolak.
     */
    public function test_07_membuat_transaksi_kedua_saat_transaksi_pertama_pending_ditolak(): void
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Gita Pending Test',
            'whatsapp' => '081234567897',
            'status'   => 'BARU',
        ]);

        // Transaksi DP pertama berstatus pending
        Transaksi::create([
            'order_id'          => 'PMB-UCIC-' . $prospek->id . '-DP-PENDING-1',
            'prospek_id'        => $prospek->id,
            'kategori'          => Transaksi::KATEGORI_DP,
            'nominal'           => 500000,
            'payment_status'    => Transaksi::STATUS_PENDING,
            'tanggal'           => now(),
        ]);

        // Mencoba memaksa membuat transaksi baru kedua saat yang pertama masih pending
        $response = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_DP,
            'fresh'    => true,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
        $this->assertStringContainsString('menunggu pembayaran', strtolower($response->json('message')));
    }

    /**
     * Skenario 8: Transaksi gagal atau expired -> Bisa mencoba kembali.
     */
    public function test_08_transaksi_gagal_atau_expired_bisa_mencoba_kembali(): void
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Hadi Retry User',
            'whatsapp' => '081234567898',
            'status'   => 'BARU',
        ]);

        // Transaksi sebelumnya ditolak / expired
        Transaksi::create([
            'order_id'          => 'PMB-UCIC-' . $prospek->id . '-DP-FAILED',
            'prospek_id'        => $prospek->id,
            'kategori'          => Transaksi::KATEGORI_DP,
            'nominal'           => 500000,
            'payment_status'    => Transaksi::STATUS_REJECTED,
            'rejection_reason'  => 'Waktu pembayaran kadaluarsa (Expired)',
            'tanggal'           => now()->subHours(2),
        ]);

        // Coba kembali
        $response = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_DP,
            'fresh'    => true,
        ]);

        $response->assertOk()
            ->assertJson([
                'success'  => true,
                'kategori' => Transaksi::KATEGORI_DP,
            ]);

        $newOrder = $response->json('order_id');
        $this->assertNotEquals('PMB-UCIC-' . $prospek->id . '-DP-FAILED', $newOrder);
        $this->assertDatabaseHas('transaksis', [
            'order_id'       => $newOrder,
            'payment_status' => Transaksi::STATUS_PENDING,
        ]);
    }

    /**
     * Skenario 9: Lima camaba melakukan pembayaran -> Lima notifikasi tercatat untuk penerima yang berhak.
     */
    public function test_09_lima_camaba_melakukan_pembayaran_lima_notifikasi_tercatat_untuk_seluruh_role(): void
    {
        // 5 Camaba berbeda melakukan pembayaran
        for ($i = 1; $i <= 5; $i++) {
            $camaba = User::factory()->create(['role' => 'Camaba', 'name' => "Camaba {$i}"]);
            $prospek = Prospek::create([
                'user_id'       => $camaba->id,
                'name'          => "Camaba {$i}",
                'no_registrasi' => "REG-2026-00{$i}",
                'whatsapp'      => "0812000000{$i}",
                'status'        => 'BARU',
            ]);

            Transaksi::create([
                'order_id'          => "PMB-UCIC-5CAMABA-00{$i}",
                'prospek_id'        => $prospek->id,
                'user_id'           => $camaba->id,
                'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
                'nominal'           => 300000,
                'payment_status'    => Transaksi::STATUS_VERIFIED,
                'metode_pembayaran' => 'MIDTRANS',
                'tanggal'           => now(),
            ]);
        }

        // Pastikan masing-masing role berwenang menerima 5 notifikasi
        $adminNotifs = $this->adminUser->notifications()->get();
        $csNotifs    = $this->csUser->notifications()->get();
        $hmNotifs    = $this->hmUser->notifications()->get();
        $spvNotifs   = $this->spvUser->notifications()->get();

        $this->assertGreaterThanOrEqual(5, $adminNotifs->count(), 'Admin harus menerima 5 notifikasi');
        $this->assertGreaterThanOrEqual(5, $csNotifs->count(), 'CS harus menerima 5 notifikasi');
        $this->assertGreaterThanOrEqual(5, $hmNotifs->count(), 'HM harus menerima 5 notifikasi');
        $this->assertGreaterThanOrEqual(5, $spvNotifs->count(), 'SPV harus menerima 5 notifikasi');

        // Pastikan membaca notifikasi di role Admin tidak menghilangkan notifikasi milik CS/HM
        $adminFirstNotif = $adminNotifs->first();
        $adminFirstNotif->markAsRead();

        $this->assertTrue($adminFirstNotif->fresh()->read());
        // Notifikasi CS tetap belum dibaca (unread)
        $csUnreadCount = $this->csUser->unreadNotifications()->count();
        $this->assertGreaterThanOrEqual(5, $csUnreadCount);
    }

    /**
     * Skenario 10: Satu webhook dikirim berulang -> Tidak membuat notifikasi atau pemrosesan ganda.
     */
    public function test_10_satu_webhook_dikirim_berulang_tidak_membuat_notifikasi_ganda(): void
    {
        $prospek = Prospek::create([
            'name'     => 'Idempotent Webhook User',
            'whatsapp' => '081299900010',
            'status'   => 'BARU',
        ]);

        $trx = Transaksi::create([
            'order_id'          => 'PMB-UCIC-IDEMPOTENT-TRX',
            'prospek_id'        => $prospek->id,
            'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
            'nominal'           => 300000,
            'payment_status'    => Transaksi::STATUS_PENDING,
            'metode_pembayaran' => 'MIDTRANS',
            'tanggal'           => now(),
        ]);

        $signature = $this->generateSignature($trx->order_id, '200', '300000');
        $payload = [
            'order_id'           => $trx->order_id,
            'status_code'        => '200',
            'gross_amount'       => '300000',
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
            'payment_type'       => 'qris',
        ];

        // Webhook pertama
        $res1 = $this->postJson(route('pmb.midtrans.notification'), $payload);
        $res1->assertOk()->assertJson(['status' => 'success']);

        $adminNotifCountAfterFirst = $this->adminUser->notifications()->count();

        // Webhook kedua (berulang)
        $res2 = $this->postJson(route('pmb.midtrans.notification'), $payload);
        $res2->assertOk()->assertJson(['data' => ['status' => 'already_verified']]);

        $adminNotifCountAfterSecond = $this->adminUser->notifications()->count();
        $this->assertEquals($adminNotifCountAfterFirst, $adminNotifCountAfterSecond, 'Notifikasi tidak boleh bertambah pada webhook duplikat.');
    }

    /**
     * Skenario 11: Upload format tidak valid -> Ditolak.
     */
    public function test_11_upload_format_tidak_valid_ditolak(): void
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Invalid Format User',
            'whatsapp' => '081299900011',
            'status'   => 'BARU',
        ]);

        $invalidFile = UploadedFile::fake()->create('dokumen.exe', 100, 'application/x-msdownload');

        $response = $this->actingAs($camaba)->post(route('pmb.akun.upload-dokumen.store'), [
            'dokumen_type' => 'ijazah',
            'dokumen_file' => $invalidFile,
        ]);

        $response->assertSessionHasErrors('dokumen_file');
    }

    /**
     * Skenario 12: Upload file melebihi batas ukuran -> Ditolak.
     */
    public function test_12_upload_file_melebihi_batas_ukuran_ditolak(): void
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Over Size User',
            'whatsapp' => '081299900012',
            'status'   => 'BARU',
        ]);

        // File 3MB (melebihi batas max 2048 KB)
        $largeFile = UploadedFile::fake()->create('ktp_besar.pdf', 3072, 'application/pdf');

        $response = $this->actingAs($camaba)->post(route('pmb.akun.upload-dokumen.store'), [
            'dokumen_type' => 'ktp',
            'dokumen_file' => $largeFile,
        ]);

        $response->assertSessionHasErrors('dokumen_file');
    }

    /**
     * Skenario 13: Upload file valid -> Berhasil disimpan.
     */
    public function test_13_upload_file_valid_berhasil_disimpan(): void
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Valid Upload User',
            'whatsapp' => '081299900013',
            'status'   => 'BARU',
        ]);

        $validFile = UploadedFile::fake()->create('ktp_asli.pdf', 500, 'application/pdf');

        $response = $this->actingAs($camaba)->post(route('pmb.akun.upload-dokumen.store'), [
            'dokumen_type' => 'ktp',
            'dokumen_file' => $validFile,
        ]);

        $response->assertRedirect(route('pmb.akun.upload-dokumen'))
            ->assertSessionHas('success');

        $freshProspek = $prospek->fresh();
        $this->assertNotEmpty($freshProspek->berkas_data['ktp']['file_path']);
        Storage::disk('local')->assertExists($freshProspek->berkas_data['ktp']['file_path']);

        // PENTING: Upload berkas TIDAK otomatis membuat pembayaran lunas
        $this->assertFalse(
            $freshProspek->transaksis()->where('payment_status', Transaksi::STATUS_VERIFIED)->exists(),
            'Upload dokumen tidak boleh menandai pembayaran sebagai lunas secara otomatis'
        );
    }

    /**
     * Skenario 14: Admin mengubah nomor rekening -> Informasi terbaru tampil di halaman camaba.
     */
    public function test_14_admin_mengubah_nomor_rekening_tampil_terbaru_di_halaman_camaba(): void
    {
        $bank = BankAccount::create([
            'bank_name'      => 'Bank BCA',
            'account_number' => '123-456-7890',
            'account_name'   => 'Yayasan UCIC',
            'is_active'      => true,
        ]);

        // Admin mengubah nomor rekening
        $this->actingAs($this->adminUser)->put(route('admin.bank-accounts.update', $bank->id), [
            'bank_name'      => 'Bank BCA',
            'account_number' => '999-888-7777',
            'account_name'   => 'Universitas Catur Insan Cendekia',
            'is_active'      => '1',
        ]);

        $this->assertEquals('999-888-7777', $bank->fresh()->account_number);

        // Akses halaman pembayaran oleh camaba
        $camaba = User::factory()->create(['role' => 'Camaba']);
        Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Camaba Bayar Bank',
            'whatsapp' => '081299900014',
            'status'   => 'BARU',
        ]);

        $response = $this->actingAs($camaba)->get(route('pmb.akun.bayar'));
        $response->assertOk();
        $response->assertSee('999-888-7777');
    }

    /**
     * Skenario 15: Role selain Admin mengubah rekening -> Ditolak.
     */
    public function test_15_role_selain_admin_mengubah_rekening_ditolak(): void
    {
        $bank = BankAccount::create([
            'bank_name'      => 'Bank Mandiri',
            'account_number' => '130-00-112233-4',
            'account_name'   => 'UCIC Cirebon',
            'is_active'      => true,
        ]);

        // CS mencoba mengedit rekening bank
        $response = $this->actingAs($this->csUser)->put(route('admin.bank-accounts.update', $bank->id), [
            'bank_name'      => 'Bank Mandiri Modif',
            'account_number' => '000-00-000000-0',
            'account_name'   => 'Hacker',
        ]);

        // Ditolak middleware role:Admin (403 Forbidden)
        $response->assertStatus(403);
        $this->assertEquals('130-00-112233-4', $bank->fresh()->account_number);
    }

    /**
     * Skenario 16: Pembayaran Midtrans berhasil -> Status transaksi tersinkronisasi.
     */
    public function test_16_pembayaran_midtrans_berhasil_status_transaksi_tersinkronisasi(): void
    {
        $prospek = Prospek::create([
            'name'     => 'Settlement User',
            'whatsapp' => '081299900016',
            'status'   => 'BARU',
        ]);

        $trx = Transaksi::create([
            'order_id'          => 'PMB-UCIC-SETTLEMENT-TEST',
            'prospek_id'        => $prospek->id,
            'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
            'nominal'           => 300000,
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_PENDING,
            'tanggal'           => now(),
        ]);

        $signature = $this->generateSignature($trx->order_id, '200', '300000');
        $payload = [
            'order_id'           => $trx->order_id,
            'status_code'        => '200',
            'gross_amount'       => '300000',
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
            'payment_type'       => 'bank_transfer',
        ];

        $response = $this->postJson(route('pmb.midtrans.notification'), $payload);
        $response->assertOk();

        $freshTrx = $trx->fresh();
        $this->assertEquals(Transaksi::STATUS_VERIFIED, $freshTrx->payment_status);
        $this->assertNotNull($freshTrx->verified_at);
        $this->assertEquals('FORMULIR', $prospek->fresh()->status);
    }

    /**
     * Skenario 17: Pembayaran pending -> Tidak dianggap lunas.
     */
    public function test_17_pembayaran_pending_tidak_dianggap_lunas(): void
    {
        $prospek = Prospek::create([
            'name'     => 'Pending Midtrans User',
            'whatsapp' => '081299900017',
            'status'   => 'BARU',
        ]);

        $trx = Transaksi::create([
            'order_id'          => 'PMB-UCIC-PENDING-TEST',
            'prospek_id'        => $prospek->id,
            'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
            'nominal'           => 300000,
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_PENDING,
            'tanggal'           => now(),
        ]);

        $signature = $this->generateSignature($trx->order_id, '201', '300000');
        $payload = [
            'order_id'           => $trx->order_id,
            'status_code'        => '201',
            'gross_amount'       => '300000',
            'signature_key'      => $signature,
            'transaction_status' => 'pending',
            'payment_type'       => 'bank_transfer',
        ];

        $response = $this->postJson(route('pmb.midtrans.notification'), $payload);
        $response->assertOk();

        $this->assertEquals(Transaksi::STATUS_PENDING, $trx->fresh()->payment_status);
        $this->assertNotEquals('LUNAS', $prospek->fresh()->status);
    }

    /**
     * Skenario 18: Pelunasan -> Menggunakan sisa tagihan yang benar.
     */
    public function test_18_pelunasan_menggunakan_sisa_tagihan_yang_benar(): void
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Kalkulasi Pelunasan User',
            'whatsapp' => '081299900018',
            'status'   => 'BARU',
        ]);

        // Total tagihan = 5.000.000
        // 1. Pendaftaran terverifikasi 300.000 (mengurangi sisa tagihan)
        Transaksi::create([
            'order_id'          => 'PMB-UCIC-' . $prospek->id . '-REG-OK',
            'prospek_id'        => $prospek->id,
            'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
            'nominal'           => 300000,
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'tanggal'           => now(),
        ]);

        // 2. DP PENDING 500.000 (TIDAK boleh mengurangi sisa tagihan)
        Transaksi::create([
            'order_id'          => 'PMB-UCIC-' . $prospek->id . '-DP-PEND',
            'prospek_id'        => $prospek->id,
            'kategori'          => Transaksi::KATEGORI_DP,
            'nominal'           => 500000,
            'payment_status'    => Transaksi::STATUS_PENDING,
            'tanggal'           => now(),
        ]);

        // 3. DP GAGAL 500.000 (TIDAK boleh mengurangi sisa tagihan)
        Transaksi::create([
            'order_id'          => 'PMB-UCIC-' . $prospek->id . '-DP-FAIL',
            'prospek_id'        => $prospek->id,
            'kategori'          => Transaksi::KATEGORI_DP,
            'nominal'           => 500000,
            'payment_status'    => Transaksi::STATUS_REJECTED,
            'tanggal'           => now(),
        ]);

        // Sisa tagihan pelunasan yang benar = 5.000.000 - 300.000 = 4.700.000
        $remaining = MidtransService::calculateRemainingPelunasan($prospek);
        $this->assertEquals(4700000, $remaining);

        // Checkout Pelunasan harus tepat 4.700.000
        $response = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PELUNASAN,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'nominal' => 4700000,
            ]);
    }

    /**
     * Skenario 19: Akses URL checkout kategori yang sudah lunas -> Ditolak backend.
     */
    public function test_19_akses_url_checkout_kategori_yang_sudah_lunas_ditolak_backend(): void
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Direct Url Hack User',
            'whatsapp' => '081299900019',
            'status'   => 'LUNAS',
        ]);

        // Kategori DP sudah lunas
        Transaksi::create([
            'order_id'          => 'PMB-UCIC-' . $prospek->id . '-DP-OK',
            'prospek_id'        => $prospek->id,
            'kategori'          => Transaksi::KATEGORI_DP,
            'nominal'           => 500000,
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'tanggal'           => now(),
        ]);

        // Panggilan API langsung ke endpoint checkout
        $response = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_DP,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Skenario 20: Dua request checkout bersamaan -> Tidak menghasilkan pembayaran ganda.
     */
    public function test_20_dua_request_checkout_bersamaan_tidak_menghasilkan_pembayaran_ganda(): void
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Concurrent Checkout User',
            'whatsapp' => '081299900020',
            'status'   => 'BARU',
        ]);

        $service = app(MidtransService::class);

        // Request 1: membuat transaksi
        $trx1 = $service->createCategoryTransaksi($prospek, Transaksi::KATEGORI_DP, $camaba);

        // Request 2 (bersamaan tanpa forceNew): mengambil transaksi pending yang sama
        $trx2 = $service->createCategoryTransaksi($prospek, Transaksi::KATEGORI_DP, $camaba, false);

        $this->assertEquals($trx1->order_id, $trx2->order_id, 'Kedua request harus me-reuse order ID yang sama tanpa membuat baris baru.');

        $count = Transaksi::where('prospek_id', $prospek->id)->where('kategori', Transaksi::KATEGORI_DP)->count();
        $this->assertEquals(1, $count, 'Hanya ada 1 baris transaksi yang tercatat di database.');
    }
}
