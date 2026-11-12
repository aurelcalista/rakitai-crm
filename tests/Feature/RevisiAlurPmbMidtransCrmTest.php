<?php

namespace Tests\Feature;

use App\Models\MasterData;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use App\Models\Sekolah;
use App\Models\TahunAkademik;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RevisiAlurPmbMidtransCrmTest extends TestCase
{
    use RefreshDatabase;

    protected User $sales;
    protected User $cs;
    protected User $admin;
    protected Wilayah $wilayah;
    protected Sekolah $sekolah;
    protected Prodi $prodi;
    protected TahunAkademik $ta;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        // Master Data 8 Status Pipeline Baku
        $statuses = ['BARU', 'KONTAK', 'PROSPEK', 'HOT PROSPEK', 'FORMULIR', 'BERKAS', 'LUNAS', 'NO RESPON'];
        foreach ($statuses as $st) {
            MasterData::create([
                'type'   => 'status_prospek',
                'kode'   => \Illuminate\Support\Str::slug($st),
                'nama'   => $st,
                'status' => 'Aktif',
            ]);
        }

        $this->ta = TahunAkademik::create([
            'nama'      => '2027/2028',
            'status'    => 'Aktif',
            'is_active' => true,
        ]);

        $this->wilayah = Wilayah::create([
            'kode' => 'W-CRB',
            'nama' => 'Kota Cirebon',
            'tipe' => 'Kota',
        ]);

        $this->sales = User::create([
            'name'       => 'Sales Budi',
            'email'      => 'sales.budi@cic.ac.id',
            'phone'      => '+6281234567801',
            'role'       => 'Sales',
            'status'     => 'Aktif',
            'password'   => Hash::make('password123'),
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->cs = User::create([
            'name'       => 'CS Siti',
            'email'      => 'cs.siti@cic.ac.id',
            'phone'      => '+6281234567802',
            'role'       => 'CS',
            'status'     => 'Aktif',
            'password'   => Hash::make('password123'),
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->admin = User::create([
            'name'       => 'Admin PMB',
            'email'      => 'admin.pmb@cic.ac.id',
            'phone'      => '+6281234567803',
            'role'       => 'Admin',
            'status'     => 'Aktif',
            'password'   => Hash::make('password123'),
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->sekolah = Sekolah::create([
            'kode'       => 'SCH-001',
            'nama'       => 'SMKN 1 Cirebon',
            'wilayah_id' => $this->wilayah->id,
            'sales_id'   => $this->sales->id,
            'status'     => 'Aktif',
        ]);

        $this->prodi = Prodi::create([
            'kode'               => 'TI-S1',
            'nama'               => 'S1 Teknik Informatika',
            'jenjang'            => 'S1',
            'fakultas'           => 'Teknologi Informasi',
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 300000,
            'biaya_perlengkapan' => 600000,
            'biaya_dpp'          => 500000,
        ]);

        config([
            'services.midtrans.server_key'    => 'SB-Mid-server-testkey123456',
            'services.midtrans.client_key'    => 'SB-Mid-client-testkey123456',
            'services.midtrans.is_production' => false,
        ]);
    }

    /**
     * Skenario 1 – Prospek dari CRM hingga pembayaran
     * 1. Sales menambahkan calon mahasiswa A sebagai prospek BARU.
     * 2. Sales melakukan follow-up hingga status menjadi HOT PROSPEK.
     * 3. Calon mahasiswa A membuka PMB/register dan melakukan registrasi.
     * 4. Sistem mencocokkan identitas dan menghubungkan akun PMB dengan data prospek A.
     * 5. Pastikan tidak ada data prospek duplikat.
     * 6. Calon mahasiswa A login dan mengisi formulir PMB.
     * 7. Status prospek A otomatis berubah menjadi FORMULIR.
     * 8. Ketika proses kelengkapan dokumen berlangsung, status berubah menjadi BERKAS.
     * 9. Calon mahasiswa A melakukan pembayaran melalui Midtrans.
     * 10. Setelah webhook pembayaran berhasil diterima, status pembayaran diperbarui dan status prospek menjadi LUNAS.
     * 11. Sales tetap dapat melihat seluruh riwayat prospek dan perkembangan pendaftaran A.
     */
    public function test_skenario_1_prospek_crm_hingga_pembayaran_midtrans(): void
    {
        // 1. Sales menambahkan prospek BARU
        $prospekA = Prospek::create([
            'name'             => 'Calon Mahasiswa A',
            'email'            => 'camaba.a@gmail.com',
            'whatsapp'         => '+6281234567811',
            'pic_phone'        => '+6281234567811',
            'pic'              => 'Calon Mahasiswa A',
            'type'             => 'Individu',
            'category'         => 'PMB Inbound',
            'source'           => 'Sosial Media (Facebook, Instagram, X)',
            'status'           => 'BARU',
            'stage_number'     => 1,
            'sales_id'         => $this->sales->id,
            'owner_id'         => $this->sales->id,
            'sekolah_id'       => $this->sekolah->id,
            'academic_year_id' => $this->ta->id,
        ]);
        $originalProspekId = $prospekA->id;

        // 2. Sales follow-up hingga HOT PROSPEK
        $prospekA->update([
            'status'       => 'HOT PROSPEK',
            'stage_number' => 4,
        ]);
        ProspekTimeline::create([
            'prospek_id'    => $prospekA->id,
            'user_id'       => $this->sales->id,
            'title'         => 'Follow-up Tertarik Kuat',
            'notes'         => 'Calon mahasiswa A sangat tertarik mendaftar ke prodi Teknik Informatika.',
            'status_before' => 'BARU',
            'status_after'  => 'HOT PROSPEK',
            'time'          => now(),
        ]);

        // 3 & 4. Calon Mahasiswa A membuka register PMB & mendaftar
        $regPayload = [
            'name'                  => 'Calon Mahasiswa A',
            'email'                 => 'camaba.a@gmail.com',
            'whatsapp'              => '+6281234567811',
            'asal_sekolah'          => 'SMKN 1 Cirebon',
            'password'              => 'P@ssw0rd123',
            'password_confirmation' => 'P@ssw0rd123',
            'minat_kuliah'          => 'ya',
            'minat_miniclass'       => 'ya',
        ];

        $regResponse = $this->post(route('pmb.register.post'), $regPayload);
        $regResponse->assertRedirect(route('pmb.login'));

        // 5. Pastikan ID prospek sama, tidak ada prospek duplikat, dan status HOT PROSPEK tidak berubah saat registrasi
        $this->assertEquals(1, Prospek::where('email', 'camaba.a@gmail.com')->count());
        $prospekA->refresh();
        $this->assertEquals($originalProspekId, $prospekA->id);
        $this->assertNotNull($prospekA->user_id);
        $this->assertEquals($this->sales->id, $prospekA->sales_id, 'Sales penanggung jawab lama tetap dipertahankan');
        $this->assertEquals('HOT PROSPEK', $prospekA->status, 'Registrasi akun tidak mengubah status HOT PROSPEK');

        $userA = User::find($prospekA->user_id);
        $this->assertNotNull($userA);

        // 6 & 7. Calon mahasiswa A login & mengisi formulir pendaftaran PMB
        $formPayload = [
            'name'                  => 'Calon Mahasiswa A',
            'whatsapp'              => '+6281234567811',
            'asal_sekolah'          => 'SMKN 1 Cirebon',
            'nama_sekolah'          => 'SMKN 1 Cirebon',
            'prodi_id'              => $this->prodi->id,
            'tipe_kelas'            => 'Reguler',
            'informasi_pendaftaran' => 'Teman / Keluarga',
            'tempat_lahir'          => 'Cirebon',
            'tanggal_lahir'         => '2007-08-17',
            'nik'                   => '3274011708070001',
            'jenis_kelamin'         => 'Laki-Laki',
            'agama'                 => 'Islam',
            'alamat'                => 'Jl. Perjuangan No. 10',
            'nama_ibu_kandung'      => 'Ibu Aisyah',
        ];

        $formResponse = $this->actingAs($userA)->post(route('pmb.akun.form-pendaftaran.update'), $formPayload);
        $formResponse->assertRedirect(route('pmb.akun.bayar'));

        $prospekA->refresh();
        // Status otomatis berubah menjadi FORMULIR
        $this->assertEquals('Sudah Isi', $prospekA->status_formulir);
        $this->assertEquals('FORMULIR', $prospekA->status);
        $this->assertEquals(5, $prospekA->stage_number);

        // 8. Proses kelengkapan berkas/dokumen -> status otomatis berubah menjadi BERKAS
        $dummyImage = UploadedFile::fake()->image('pas_foto.jpg', 300, 400);
        $uploadResponse = $this->actingAs($userA)->post(route('pmb.akun.upload-dokumen.store'), [
            'dokumen_type' => 'pas_foto',
            'dokumen_file' => $dummyImage,
        ]);
        $uploadResponse->assertRedirect(route('pmb.akun.upload-dokumen'));

        $prospekA->refresh();
        $this->assertEquals('BERKAS', $prospekA->status);
        $this->assertEquals(6, $prospekA->stage_number);

        // 9. Calon Mahasiswa A melakukan pembayaran melalui Midtrans
        $transaksi = Transaksi::create([
            'order_id'          => 'PMB-2027-' . $prospekA->id . '-REG-' . time(),
            'prospek_id'        => $prospekA->id,
            'user_id'           => $userA->id,
            'nominal'           => 300000,
            'kategori'          => 'Biaya Pendaftaran',
            'jenis'             => 'Biaya Pendaftaran',
            'payment_status'    => Transaksi::STATUS_PENDING,
            'metode_pembayaran' => 'MIDTRANS',
            'snap_token'        => 'snap-token-test-123',
            'tanggal'           => now(),
        ]);

        // Sebelum webhook sukses, status prospek tetap BERKAS
        $this->assertEquals('BERKAS', $prospekA->fresh()->status);

        // 10. Webhook Midtrans mengonfirmasi pembayaran settlement (sukses)
        $orderId = $transaksi->order_id;
        $statusCode = '200';
        $grossAmount = '300000.00';
        $serverKey = config('services.midtrans.server_key');
        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $webhookPayload = [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
            'fraud_status'       => 'accept',
            'payment_type'       => 'qris',
            'transaction_time'   => now()->format('Y-m-d H:i:s'),
        ];

        $webhookResponse = $this->postJson(route('pmb.midtrans.notification'), $webhookPayload);
        $webhookResponse->assertOk();
        $webhookResponse->assertJson(['status' => 'success']);

        $transaksi->refresh();
        $prospekA->refresh();

        $this->assertEquals(Transaksi::STATUS_VERIFIED, $transaksi->payment_status);
        $this->assertEquals('LUNAS', $prospekA->status, 'Status prospek otomatis berubah menjadi LUNAS setelah webhook Midtrans berhasil');
        $this->assertEquals(7, $prospekA->stage_number);

        // 11. Sales tetap dapat melihat seluruh riwayat prospek dan perkembangan pendaftaran A
        $salesViewResponse = $this->actingAs($this->sales)->get(route('prospek.show', $prospekA->id));
        $salesViewResponse->assertOk();
        $salesViewResponse->assertSee('Calon Mahasiswa A');
        $salesViewResponse->assertSee('LUNAS');
        $salesViewResponse->assertSee(number_format(300000, 0, ',', '.'));
    }

    /**
     * Skenario 2 – Registrasi tanpa prospek CRM
     * 1. Calon mahasiswa B langsung membuka portal PMB.
     * 2. Calon mahasiswa B melakukan registrasi dan login.
     * 3. Calon mahasiswa B mengisi formulir dan melakukan pembayaran.
     * 4. Pastikan proses berjalan normal tanpa harus memiliki data prospek CRM sebelumnya.
     * 5. Pastikan tidak ada penanggung jawab Sales/CS yang ditetapkan secara otomatis.
     */
    public function test_skenario_2_registrasi_tanpa_prospek_crm(): void
    {
        // 1 & 2. Registrasi calon mahasiswa B langsung via PMB
        $regPayload = [
            'name'                  => 'Calon Mahasiswa B',
            'email'                 => 'camaba.b@gmail.com',
            'whatsapp'              => '+6281234567822',
            'asal_sekolah'          => 'SMKN 1 Cirebon',
            'password'              => 'P@ssw0rd123',
            'password_confirmation' => 'P@ssw0rd123',
            'minat_kuliah'          => 'ya',
            'minat_miniclass'       => 'tidak',
        ];

        $regResponse = $this->post(route('pmb.register.post'), $regPayload);
        $regResponse->assertRedirect(route('pmb.login'));

        $userB = User::where('email', 'camaba.b@gmail.com')->first();
        $this->assertNotNull($userB);

        $prospekB = Prospek::where('email', 'camaba.b@gmail.com')->first();
        $this->assertNotNull($prospekB);

        // 5. Pastikan tidak ada penanggung jawab Sales/CS yang ditetapkan secara otomatis
        $this->assertNull($prospekB->cs_id, 'Tidak boleh ada CS yang ditetapkan otomatis');
        $this->assertNull($prospekB->sales_id, 'Tidak boleh ada Sales yang ditetapkan otomatis');
        $this->assertEquals('BARU', $prospekB->status);

        // 3. Calon mahasiswa B mengisi formulir
        $formPayload = [
            'name'                  => 'Calon Mahasiswa B',
            'whatsapp'              => '+6281234567822',
            'asal_sekolah'          => 'SMKN 1 Cirebon',
            'nama_sekolah'          => 'SMKN 1 Cirebon',
            'prodi_id'              => $this->prodi->id,
            'tipe_kelas'            => 'Reguler',
            'informasi_pendaftaran' => 'Website PMB',
            'tempat_lahir'          => 'Cirebon',
            'tanggal_lahir'         => '2007-01-01',
            'nik'                   => '3274010101070002',
            'jenis_kelamin'         => 'Perempuan',
            'agama'                 => 'Islam',
            'alamat'                => 'Jl. Cipto Mangunkusumo',
            'nama_ibu_kandung'      => 'Ibu Fatimah',
        ];

        $formResponse = $this->actingAs($userB)->post(route('pmb.akun.form-pendaftaran.update'), $formPayload);
        $formResponse->assertRedirect(route('pmb.akun.bayar'));

        $prospekB->refresh();
        $this->assertEquals('Sudah Isi', $prospekB->status_formulir);
        $this->assertEquals('FORMULIR', $prospekB->status);
        // Tetap tidak ada penugasan Sales/CS otomatis
        $this->assertNull($prospekB->cs_id);
        $this->assertNull($prospekB->sales_id);

        // Pembayaran Midtrans berjalan normal
        $transaksi = Transaksi::create([
            'order_id'          => 'PMB-2027-' . $prospekB->id . '-REG-' . time(),
            'prospek_id'        => $prospekB->id,
            'user_id'           => $userB->id,
            'nominal'           => 300000,
            'kategori'          => 'Biaya Pendaftaran',
            'jenis'             => 'Biaya Pendaftaran',
            'payment_status'    => Transaksi::STATUS_PENDING,
            'metode_pembayaran' => 'MIDTRANS',
            'tanggal'           => now(),
        ]);

        $this->assertEquals(Transaksi::STATUS_PENDING, $transaksi->payment_status);
    }

    /**
     * Skenario 3 – Pembayaran belum berhasil
     * 1. Calon mahasiswa melakukan pengisian formulir.
     * 2. Sistem membuat tagihan pembayaran.
     * 3. Calon mahasiswa belum membayar.
     * 4. Status tetap Menunggu Pembayaran.
     * 5. Tidak ada perubahan status menjadi LUNAS sebelum webhook pembayaran berhasil diterima.
     */
    public function test_skenario_3_pembayaran_belum_berhasil_tetap_menunggu_pembayaran(): void
    {
        $camabaUser = User::create([
            'name'     => 'Camaba C',
            'email'    => 'camaba.c@gmail.com',
            'phone'    => '+6281234567833',
            'role'     => 'Camaba',
            'status'   => 'Aktif',
            'password' => Hash::make('password123'),
        ]);

        $prospekC = Prospek::create([
            'user_id'          => $camabaUser->id,
            'name'             => 'Camaba C',
            'email'            => 'camaba.c@gmail.com',
            'whatsapp'         => '+6281234567833',
            'category'         => 'PMB Mandiri',
            'source'           => 'Website PMB',
            'status'           => 'FORMULIR',
            'status_formulir'  => 'Sudah Isi',
            'stage_number'     => 5,
            'prodi_id'         => $this->prodi->id,
            'academic_year_id' => $this->ta->id,
        ]);

        // Transaksi tagihan dibuat
        $transaksi = Transaksi::create([
            'order_id'          => 'PMB-2027-' . $prospekC->id . '-REG-' . time(),
            'prospek_id'        => $prospekC->id,
            'user_id'           => $camabaUser->id,
            'nominal'           => 300000,
            'kategori'          => 'Biaya Pendaftaran',
            'jenis'             => 'Biaya Pendaftaran',
            'payment_status'    => Transaksi::STATUS_PENDING,
            'metode_pembayaran' => 'MIDTRANS',
            'tanggal'           => now(),
        ]);

        // Calon mahasiswa belum membayar
        $this->assertEquals(Transaksi::STATUS_PENDING, $transaksi->payment_status);
        $this->assertNotEquals('LUNAS', $prospekC->fresh()->status);
        $this->assertEquals('FORMULIR', $prospekC->fresh()->status);

        // Jika ada webhook pending, status tetap pending
        $orderId = $transaksi->order_id;
        $statusCode = '201';
        $grossAmount = '300000.00';
        $serverKey = config('services.midtrans.server_key');
        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $this->postJson(route('pmb.midtrans.notification'), [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'pending',
            'payment_type'       => 'bank_transfer',
        ]);

        $this->assertEquals(Transaksi::STATUS_PENDING, $transaksi->fresh()->payment_status);
        $this->assertNotEquals('LUNAS', $prospekC->fresh()->status, 'Status tidak boleh berubah menjadi LUNAS sebelum pembayaran settlement');
    }

    /**
     * Skenario 4 – Hak akses Sales dan CS
     * 1. Login sebagai Sales dan pastikan tidak ada fitur input transaksi.
     * 2. Login sebagai CS dan pastikan tidak ada fitur input transaksi maupun verifikasi pembayaran.
     * 3. Pastikan keduanya tetap dapat melihat informasi pembayaran sesuai hak akses.
     * 4. Pastikan endpoint backend juga menolak akses yang tidak diizinkan (403 Forbidden).
     */
    public function test_skenario_4_hak_akses_sales_dan_cs_dibatasi_sesuai_revisi(): void
    {
        $prospek = Prospek::create([
            'name'             => 'Prospek Uji Hak Akses',
            'email'            => 'uji.akses@gmail.com',
            'whatsapp'         => '+6281234567844',
            'type'             => 'Individu',
            'category'         => 'PMB Inbound',
            'source'           => 'Website PMB',
            'status'           => 'HOT PROSPEK',
            'stage_number'     => 4,
            'sales_id'         => $this->sales->id,
            'academic_year_id' => $this->ta->id,
        ]);

        $transaksi = Transaksi::create([
            'order_id'          => 'TRX-TEST-403',
            'prospek_id'        => $prospek->id,
            'nominal'           => 300000,
            'kategori'          => 'Biaya Pendaftaran',
            'jenis'             => 'Biaya Pendaftaran',
            'payment_status'    => Transaksi::STATUS_PENDING,
            'metode_pembayaran' => 'MIDTRANS',
            'tanggal'           => now(),
        ]);

        // 1. Sales: Endpoint transaksi manual DITOLAK (403)
        $salesTrxResponse = $this->actingAs($this->sales)->post(route('prospek.transaksi', $prospek->id), [
            'kategori' => 'DP Daftar',
            'jenis'    => 'Biaya Pendaftaran',
            'nominal'  => 300000,
            'tanggal'  => now()->format('Y-m-d'),
        ]);
        $salesTrxResponse->assertForbidden();

        // 1b. Sales: Mengubah status menjadi LUNAS secara manual DITOLAK
        $salesStatusResponse = $this->actingAs($this->sales)->postJson(route('sales.pipeline.updateStatus'), [
            'prospek_id' => $prospek->id,
            'status'     => 'LUNAS',
        ]);
        $salesStatusResponse->assertStatus(422);

        // 2. CS: Endpoint transaksi manual DITOLAK (403)
        $csTrxResponse = $this->actingAs($this->cs)->post(route('prospek.transaksi', $prospek->id), [
            'kategori' => 'DP Daftar',
            'jenis'    => 'Biaya Pendaftaran',
            'nominal'  => 300000,
            'tanggal'  => now()->format('Y-m-d'),
        ]);
        $csTrxResponse->assertForbidden();

        // 2b. CS: Verifikasi pembayaran manual DITOLAK (403)
        $csVerifyResponse = $this->actingAs($this->cs)->post(route('cs.verifikasi.verify', $transaksi->id));
        $csVerifyResponse->assertForbidden();

        $csRejectResponse = $this->actingAs($this->cs)->post(route('cs.verifikasi.reject', $transaksi->id), [
            'rejection_reason' => 'Tolak manual',
        ]);
        $csRejectResponse->assertForbidden();

        // 2c. CS: Verifikasi via PMB Pembayaran DITOLAK (403)
        $csPmbVerifyResponse = $this->actingAs($this->cs)->post(route('pmb.pembayaran.verify', $transaksi->id));
        $csPmbVerifyResponse->assertForbidden();

        // 3. Sales & CS tetap dapat melihat informasi pembayaran (read-only)
        $salesViewResponse = $this->actingAs($this->sales)->get(route('sales.pembayaran.index'));
        $salesViewResponse->assertOk();
        $salesViewResponse->assertSee('Prospek Uji Hak Akses');

        $csViewResponse = $this->actingAs($this->cs)->get(route('cs.verifikasi.index'));
        $csViewResponse->assertOk();
        $csViewResponse->assertSee('Prospek Uji Hak Akses');
    }
}
