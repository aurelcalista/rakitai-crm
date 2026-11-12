<?php

namespace Tests\Feature;

use App\Http\Controllers\Pmb\PmbPembayaranController;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PmbPembayaranStageCardsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Prodi $prodiFreeGedung;
    protected Prodi $prodiPaidGedung;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'Admin']);

        $this->prodiFreeGedung = Prodi::create([
            'kode'               => 'TI-TEST',
            'nama'               => 'Teknik Informatika',
            'jenjang'            => 'S1',
            'fakultas'           => 'FTI',
            'kuota'              => 100,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 300000,
            'biaya_perlengkapan' => 600000,
            'biaya_dpp'          => 500000,
            'biaya_uang_gedung'  => 0,
            'ukt_options'        => [
                '1_bulan' => ['key' => '1_bulan', 'label' => 'Per 1 Bulan', 'nominal' => 700000, 'is_active' => true],
                '6_bulan' => ['key' => '6_bulan', 'label' => 'Per 6 Bulan', 'nominal' => 4000000, 'is_active' => true],
            ],
        ]);

        $this->prodiPaidGedung = Prodi::create([
            'kode'               => 'DKV-TEST',
            'nama'               => 'DKV',
            'jenjang'            => 'S1',
            'fakultas'           => 'FTI',
            'kuota'              => 100,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 300000,
            'biaya_perlengkapan' => 600000,
            'biaya_dpp'          => 500000,
            'biaya_uang_gedung'  => 1000000,
            'ukt_options'        => [
                '6_bulan' => ['key' => '6_bulan', 'label' => 'Per 6 Bulan', 'nominal' => 4000000, 'is_active' => true],
            ],
        ]);
    }

    private function makeCamaba(string $name, ?Prodi $prodi = null): Prospek
    {
        static $counter = 1;
        return Prospek::create([
            'name'          => $name,
            'email'         => 'camaba' . $counter . '@test.ucic.ac.id',
            'whatsapp'      => '0812000000' . str_pad((string) $counter++, 2, '0', STR_PAD_LEFT),
            'no_registrasi' => 'PMB-TEST-' . $counter,
            'prodi_id'      => $prodi?->id,
            'status'        => 'BARU',
        ]);
    }

    private function addTrx(Prospek $prospek, array $attrs): Transaksi
    {
        return Transaksi::create(array_merge([
            'prospek_id'        => $prospek->id,
            'user_id'           => $this->admin->id,
            'jenis'             => 'Beli Formulir',
            'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'tanggal'           => now(),
        ], $attrs));
    }

    public function test_stage_cards_count_exclusive_highest_paid_stage_and_filter_table(): void
    {
        $this->actingAs($this->admin);

        // 1. Belum bayar apa-apa
        $belumBayar = $this->makeCamaba('Camaba Belum Bayar', $this->prodiFreeGedung);

        // 2. Baru punya transaksi pending (belum lunas, tidak dihitung sudah bayar)
        $pendingOnly = $this->makeCamaba('Camaba Pending Pendaftaran', $this->prodiFreeGedung);
        $this->addTrx($pendingOnly, [
            'nominal'           => 300000,
            'detail_pembayaran' => '• Biaya Pendaftaran PMB UCIC (Rp 300.000)',
            'payment_status'    => Transaksi::STATUS_PENDING,
        ]);

        // 3. Sudah bayar sampai Pendaftaran saja
        $sampaiPendaftaran = $this->makeCamaba('Camaba Sampai Pendaftaran', $this->prodiFreeGedung);
        $this->addTrx($sampaiPendaftaran, [
            'nominal'           => 300000,
            'detail_pembayaran' => '• Biaya Pendaftaran PMB UCIC (Rp 300.000)',
        ]);

        // 4. Sudah bayar sampai Perlengkapan (Pendaftaran + Perlengkapan)
        $sampaiPerlengkapan = $this->makeCamaba('Camaba Sampai Perlengkapan', $this->prodiFreeGedung);
        $this->addTrx($sampaiPerlengkapan, [
            'nominal'           => 300000,
            'detail_pembayaran' => '• Biaya Pendaftaran PMB UCIC (Rp 300.000)',
        ]);
        $this->addTrx($sampaiPerlengkapan, [
            'nominal'           => 600000,
            'detail_pembayaran' => '• Biaya Perlengkapan Kuliah (Rp 600.000)',
        ]);

        // 5. Sudah bayar sampai DPP (Pendaftaran + Perlengkapan + DPP, meski biaya_uang_gedung Prodi = 0)
        $sampaiDpp = $this->makeCamaba('Camaba Sampai DPP', $this->prodiFreeGedung);
        $this->addTrx($sampaiDpp, [
            'nominal'           => 900000,
            'detail_pembayaran' => "• Biaya Pendaftaran PMB UCIC (Rp 300.000)\n• Biaya Perlengkapan Kuliah (Rp 600.000)",
        ]);
        $this->addTrx($sampaiDpp, [
            'nominal'           => 500000,
            'detail_pembayaran' => '• Biaya Dana Pengembangan Pendidikan (DPP) (Rp 500.000)',
        ]);

        // 6. Sudah bayar sampai Uang Gedung
        $sampaiGedung = $this->makeCamaba('Camaba Sampai Uang Gedung', $this->prodiPaidGedung);
        $this->addTrx($sampaiGedung, [
            'nominal'           => 1400000,
            'detail_pembayaran' => "• Biaya Pendaftaran PMB UCIC (Rp 300.000)\n• Biaya Perlengkapan Kuliah (Rp 600.000)\n• Biaya Dana Pengembangan Pendidikan (DPP) (Rp 500.000)",
        ]);
        $this->addTrx($sampaiGedung, [
            'nominal'           => 1000000,
            'detail_pembayaran' => '• Biaya Uang Gedung (Rp 1.000.000)',
        ]);

        $res = $this->get(route('pmb.pembayaran.index'));
        $res->assertOk();
        $res->assertViewHas('countSemua', 6);
        $res->assertViewHas('countStagePendaftaran', 1);
        $res->assertViewHas('countStagePerlengkapan', 1);
        $res->assertViewHas('countStageDpp', 1);
        $res->assertViewHas('countStageUangGedung', 1);

        $res->assertSee('Sampai Pendaftaran');
        $res->assertSee('Sampai Perlengkapan');
        $res->assertSee('Sampai DPP');
        $res->assertSee('Sampai Uang Gedung');
        $res->assertSee('tahap=pendaftaran', false);
        $res->assertSee('tahap=perlengkapan', false);
        $res->assertSee('tahap=dpp', false);
        $res->assertSee('tahap=uang_gedung', false);
        $res->assertDontSee('status=refund', false);

        $extractTbody = static function (string $html): string {
            if (preg_match('/<tbody[^>]*>(.*?)<\/tbody>/s', $html, $m)) {
                return $m[1];
            }
            return '';
        };

        // Filter klik card Pendaftaran: hanya tampil Camaba Sampai Pendaftaran, card counts tetap utuh
        $resPend = $this->get(route('pmb.pembayaran.index', ['tahap' => 'pendaftaran']));
        $resPend->assertOk();
        $resPend->assertViewHas('countSemua', 6);
        $resPend->assertViewHas('countStagePendaftaran', 1);
        $resPend->assertViewHas('rekapList', fn ($list) => $list->pluck('name')->all() === ['Camaba Sampai Pendaftaran']);
        $tbodyPend = $extractTbody($resPend->getContent());
        $this->assertStringContainsString('Camaba Sampai Pendaftaran', $tbodyPend);
        $this->assertStringNotContainsString('Camaba Sampai Perlengkapan', $tbodyPend);
        $this->assertStringNotContainsString('Camaba Sampai DPP', $tbodyPend);
        $this->assertStringNotContainsString('Camaba Belum Bayar', $tbodyPend);

        // Filter klik card Perlengkapan
        $resPerl = $this->get(route('pmb.pembayaran.index', ['tahap' => 'perlengkapan']));
        $resPerl->assertOk();
        $resPerl->assertViewHas('rekapList', fn ($list) => $list->pluck('name')->all() === ['Camaba Sampai Perlengkapan']);
        $tbodyPerl = $extractTbody($resPerl->getContent());
        $this->assertStringContainsString('Camaba Sampai Perlengkapan', $tbodyPerl);
        $this->assertStringNotContainsString('Camaba Sampai Pendaftaran', $tbodyPerl);
        $this->assertStringNotContainsString('Camaba Sampai DPP', $tbodyPerl);

        // Filter klik card DPP
        $resDpp = $this->get(route('pmb.pembayaran.index', ['tahap' => 'dpp']));
        $resDpp->assertOk();
        $resDpp->assertViewHas('rekapList', fn ($list) => $list->pluck('name')->all() === ['Camaba Sampai DPP']);
        $tbodyDpp = $extractTbody($resDpp->getContent());
        $this->assertStringContainsString('Camaba Sampai DPP', $tbodyDpp);
        $this->assertStringNotContainsString('Camaba Sampai Uang Gedung', $tbodyDpp);

        // Filter klik card Uang Gedung
        $resGedung = $this->get(route('pmb.pembayaran.index', ['tahap' => 'uang_gedung']));
        $resGedung->assertOk();
        $resGedung->assertViewHas('rekapList', fn ($list) => $list->pluck('name')->all() === ['Camaba Sampai Uang Gedung']);
        $tbodyGedung = $extractTbody($resGedung->getContent());
        $this->assertStringContainsString('Camaba Sampai Uang Gedung', $tbodyGedung);
        $this->assertStringNotContainsString('Camaba Sampai DPP', $tbodyGedung);

        unset($belumBayar);
    }

    public function test_crm_manual_transaksi_and_free_gedung_do_not_misclassify_stage(): void
    {
        $camaba = $this->makeCamaba('Camaba CRM Manual', $this->prodiFreeGedung);

        $this->addTrx($camaba, [
            'jenis'             => 'Pendaftaran',
            'kategori'          => Transaksi::KATEGORI_LUNAS,
            'nominal'           => 300000,
            'detail_pembayaran' => null,
        ]);
        $this->assertSame('pendaftaran', PmbPembayaranController::determinePaymentStage($camaba->fresh(['prodi', 'transaksis'])));

        $this->addTrx($camaba, [
            'jenis'             => 'Perlengkapan',
            'kategori'          => Transaksi::KATEGORI_LUNAS,
            'nominal'           => 600000,
            'detail_pembayaran' => null,
        ]);
        $this->assertSame('perlengkapan', PmbPembayaranController::determinePaymentStage($camaba->fresh(['prodi', 'transaksis'])));

        // CRM auto-records Gedung (FREE) nominal 0 -> tidak boleh memindahkan tahap ke uang_gedung
        $this->addTrx($camaba, [
            'jenis'             => 'Gedung',
            'kategori'          => Transaksi::KATEGORI_LUNAS,
            'nominal'           => 0,
            'detail_pembayaran' => null,
        ]);
        $this->assertSame('perlengkapan', PmbPembayaranController::determinePaymentStage($camaba->fresh(['prodi', 'transaksis'])));

        $this->addTrx($camaba, [
            'jenis'             => 'DPP',
            'kategori'          => Transaksi::KATEGORI_LUNAS,
            'nominal'           => 500000,
            'detail_pembayaran' => null,
        ]);
        $this->addTrx($camaba, [
            'jenis'             => 'UKT',
            'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
            'nominal'           => 700000,
            'detail_pembayaran' => '• Biaya UKT (Per 1 Bulan) (Rp 700.000)',
        ]);

        $rekap = PmbPembayaranController::calculateRekap($camaba->fresh(['prodi', 'transaksis']));
        $this->assertEquals(700000, $rekap['rincian']['pelunasan']['terbayar']);
        $this->assertSame('dpp', PmbPembayaranController::determinePaymentStage($camaba->fresh(['prodi', 'transaksis']), $rekap));
    }

    public function test_export_csv_respects_active_tahap_filter(): void
    {
        $this->actingAs($this->admin);

        $onlyDaftar = $this->makeCamaba('Export Pendaftaran Only', $this->prodiFreeGedung);
        $this->addTrx($onlyDaftar, [
            'nominal'           => 300000,
            'detail_pembayaran' => '• Biaya Pendaftaran PMB UCIC (Rp 300.000)',
        ]);

        $onlyDpp = $this->makeCamaba('Export DPP User', $this->prodiFreeGedung);
        $this->addTrx($onlyDpp, [
            'nominal'           => 500000,
            'detail_pembayaran' => '• Biaya Dana Pengembangan Pendidikan (DPP) (Rp 500.000)',
        ]);

        $response = $this->get(route('pmb.pembayaran.export', ['tahap' => 'dpp']));
        $response->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Export DPP User', $csv);
        $this->assertStringNotContainsString('Export Pendaftaran Only', $csv);
    }
}

