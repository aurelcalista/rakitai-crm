<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Kunjungan;
use App\Models\Prospek;
use App\Models\Sekolah;
use App\Models\User;
use App\Rules\IndonesianWhatsApp;
use App\Support\WhatsAppHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class WhatsAppStandardizationTest extends TestCase
{
    use RefreshDatabase;

    protected $wilayah;
    protected $kategori;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->wilayah = \App\Models\Wilayah::create([
            'nama' => 'Wilayah Cirebon',
            'kode' => 'CRB',
            'status' => 'Aktif',
        ]);

        $this->kategori = \App\Models\MasterData::create([
            'kode' => 'KAT-SMA',
            'type' => 'kategori_sekolah',
            'nama' => 'SMA',
            'status' => 'Aktif',
        ]);
    }

    public function test_whatsapp_helper_normalization_and_validation()
    {
        // Valid formatted numbers
        $this->assertTrue(WhatsAppHelper::isValid('+6281234567890'));
        $this->assertTrue(WhatsAppHelper::isValid('+6289999999999'));

        // Disallowed numbers
        $this->assertFalse(WhatsAppHelper::isValid('081234567890'));
        $this->assertFalse(WhatsAppHelper::isValid('6281234567890'));
        $this->assertFalse(WhatsAppHelper::isValid('+62081234567890'));
        $this->assertFalse(WhatsAppHelper::isValid('+62+6281234567890'));
        $this->assertFalse(WhatsAppHelper::isValid('0812')); // too short
        $this->assertFalse(WhatsAppHelper::isValid('0211234567')); // landline / non-mobile

        // Format for DB when user inputs subscriber number (e.g. 81234567890)
        $this->assertEquals('+6281234567890', WhatsAppHelper::formatForDatabase('81234567890'));
        $this->assertEquals('+6281234567890', WhatsAppHelper::formatForDatabase('+6281234567890'));
        $this->assertEquals('+6281234567890', WhatsAppHelper::formatForDatabase('081234567890'));

        // Normalization for display in form with prefix +62
        $this->assertEquals('81234567890', WhatsAppHelper::normalizeForDisplay('+6281234567890'));
        $this->assertEquals('81234567890', WhatsAppHelper::normalizeForDisplay('081234567890'));
        $this->assertEquals('81234567890', WhatsAppHelper::normalizeForDisplay('6281234567890'));
        $this->assertEquals('81234567890', WhatsAppHelper::normalizeForDisplay('+62081234567890'));
        $this->assertEquals('', WhatsAppHelper::normalizeForDisplay(''));
        $this->assertEquals('', WhatsAppHelper::normalizeForDisplay(null));
    }

    public function test_indonesian_whatsapp_rule_rejects_disallowed_formats()
    {
        $rule = new IndonesianWhatsApp();

        // 08... format must be rejected
        $v1 = Validator::make(['phone' => '081234567890'], ['phone' => [$rule]]);
        $this->assertTrue($v1->fails());
        $this->assertStringContainsString('Gunakan format nomor WhatsApp +62. Masukkan nomor setelah prefix +62 tanpa angka 0 di awal.', $v1->errors()->first('phone'));

        // 628... without + must be rejected
        $v2 = Validator::make(['phone' => '6281234567890'], ['phone' => [$rule]]);
        $this->assertTrue($v2->fails());
        $this->assertStringContainsString('Gunakan format nomor WhatsApp +62. Masukkan nomor setelah prefix +62 tanpa angka 0 di awal.', $v2->errors()->first('phone'));

        // +6208... must be rejected
        $v3 = Validator::make(['phone' => '+62081234567890'], ['phone' => [$rule]]);
        $this->assertTrue($v3->fails());

        // +62+62... must be rejected
        $v4 = Validator::make(['phone' => '+62+6281234567890'], ['phone' => [$rule]]);
        $this->assertTrue($v4->fails());

        // Valid +6281234567890 passes
        $v5 = Validator::make(['phone' => '+6281234567890'], ['phone' => [$rule]]);
        $this->assertFalse($v5->fails());
    }

    public function test_prospect_store_accepts_clean_subscriber_number_and_saves_plus62()
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $sekolah = Sekolah::create([
            'nama' => 'SMA Test Standarisasi WA',
            'kode' => 'SCH-TESTWA',
            'kategori_id' => $this->kategori->id,
            'wilayah_id' => $this->wilayah->id,
            'status' => 'Aktif',
        ]);

        $response = $this->actingAs($sales)->post('/prospek', [
            'name' => 'Budi WhatsApp Test',
            'pic' => 'Budi Siswa',
            'type' => 'Sekolah',
            'sekolah_id' => $sekolah->id,
            'whatsapp' => '81234567890', // user inputs digits after prefix
            'wa_ortu' => '89876543210',
            'source' => 'Sekolah',
            'status' => 'DIPROSES',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('prospeks', [
            'pic' => 'Budi Siswa',
            'whatsapp' => '+6281234567890',
            'wa_ortu' => '+6289876543210',
        ]);
    }

    public function test_prospect_store_rejects_direct_disallowed_08_input()
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $sekolah = Sekolah::create([
            'nama' => 'SMA Test Reject WA',
            'kode' => 'SCH-TESTREJ',
            'kategori_id' => $this->kategori->id,
            'wilayah_id' => $this->wilayah->id,
            'status' => 'Aktif',
        ]);

        // Attempt direct submission with raw 081234567890 without going through component
        $response = $this->actingAs($sales)->post('/prospek', [
            'name' => 'Reject Test',
            'pic' => 'Reject PIC',
            'type' => 'Sekolah',
            'sekolah_id' => $sekolah->id,
            'whatsapp' => '081234567890',
            'source' => 'Sekolah',
            'status' => 'DIPROSES',
        ]);

        $response->assertSessionHasErrors('whatsapp');
    }

    public function test_prospect_update_and_backward_compatibility()
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $sekolah = Sekolah::create([
            'nama' => 'SMA Test Edit WA',
            'kode' => 'SCH-TESTEDT',
            'kategori_id' => $this->kategori->id,
            'wilayah_id' => $this->wilayah->id,
            'status' => 'Aktif',
        ]);

        // Create old prospect with 08 format directly in DB
        $prospek = Prospek::create([
            'name' => 'Old Prospect',
            'pic' => 'Old Prospect',
            'sales_id' => $sales->id,
            'type' => 'Sekolah',
            'sekolah_id' => $sekolah->id,
            'whatsapp' => '081299998888',
            'wa_ortu' => '081211112222',
            'source' => 'Sekolah',
            'status' => 'DIPROSES',
        ]);

        // Verify normalizer for display prepares it safely
        $this->assertEquals('81299998888', WhatsAppHelper::normalizeForDisplay($prospek->whatsapp));

        // When saved via update form with 81299998888
        $response = $this->actingAs($sales)->put("/prospek/{$prospek->id}", [
            'name' => 'Old Prospect Updated',
            'pic' => 'Old Prospect Updated',
            'type' => 'Sekolah',
            'sekolah_id' => $sekolah->id,
            'whatsapp' => '81299998888',
            'wa_ortu' => '81211112222',
            'source' => 'Sekolah',
            'status' => 'DIPROSES',
        ]);

        $response->assertRedirect();
        $prospek->refresh();
        $this->assertEquals('+6281299998888', $prospek->whatsapp);
        $this->assertEquals('+6281211112222', $prospek->wa_ortu);
        // Prefix is not duplicated
        $this->assertFalse(str_starts_with($prospek->whatsapp, '+62+62'));
        $this->assertFalse(str_starts_with($prospek->whatsapp, '+6208'));
    }

    public function test_kunjungan_store_standardizes_whatsapp()
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $sekolah = Sekolah::create([
            'nama' => 'SMA Kunjungan WA',
            'kode' => 'SCH-TESTV',
            'kategori_id' => $this->kategori->id,
            'wilayah_id' => $this->wilayah->id,
            'status' => 'Aktif',
            'lat' => -6.72620000,
            'lng' => 108.55740000,
        ]);

        $response = $this->actingAs($sales)->post('/kunjungan', [
            'tanggal' => now()->format('Y-m-d'),
            'waktu' => '09:00',
            'jenis' => 'Sekolah',
            'sekolah_id' => $sekolah->id,
            'nama_institusi' => 'SMA Kunjungan WA',
            'pic_name' => 'Bapak Kepala',
            'pic_whatsapp' => '81345678901',
            'catatan' => 'Koordinasi awal',
            'foto' => \Illuminate\Http\UploadedFile::fake()->image('visit.jpg'),
            'lat' => -6.72620000,
            'lng' => 108.55740000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('kunjungans', [
            'nama_institusi' => 'SMA Kunjungan WA',
            'pic_whatsapp' => '+6281345678901',
        ]);
    }
}
