<?php

namespace Tests\Feature;

use App\Models\MasterData;
use App\Models\Prospek;
use App\Models\TahunAkademik;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineUpdateStatusModalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $spv;
    protected User $sales;
    protected User $otherSales;
    protected User $cs;
    protected Wilayah $wilayah;

    protected function setUp(): void
    {
        parent::setUp();

        TahunAkademik::create([
            'nama' => '2027/2028',
            'is_aktif' => true,
        ]);

        $this->wilayah = Wilayah::create([
            'nama' => 'Cirebon Kota',
            'kode' => 'CRB-01',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin Test',
            'role' => 'Admin',
            'status' => 'Aktif',
        ]);

        $this->spv = User::factory()->create([
            'name' => 'SPV Cirebon',
            'role' => 'SPV',
            'status' => 'Aktif',
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->sales = User::factory()->create([
            'name' => 'Sales Cirebon',
            'role' => 'Sales',
            'status' => 'Aktif',
            'supervisor_id' => $this->spv->id,
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->otherSales = User::factory()->create([
            'name' => 'Other Sales',
            'role' => 'Sales',
            'status' => 'Aktif',
        ]);

        $this->cs = User::factory()->create([
            'name' => 'CS Cirebon',
            'role' => 'CS',
            'status' => 'Aktif',
            'wilayah_id' => $this->wilayah->id,
        ]);
    }

    public function test_route_and_action_are_accessible_by_authorized_user()
    {
        $prospek = Prospek::create([
            'name'       => 'Siti Aisyah',
            'pic'        => 'Siti Aisyah',
            'whatsapp'   => '081234567891',
            'sales_id'   => $this->sales->id,
            'owner_id'   => $this->sales->id,
            'wilayah_id' => $this->wilayah->id,
            'status'     => 'BARU',
            'type'       => 'Individu',
        ]);

        $response = $this->actingAs($this->sales)->postJson(route('pipeline.update-status'), [
            'prospek_id' => $prospek->id,
            'status'     => 'KONTAK',
            'notes'      => 'Telah ditelepon via WhatsApp',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Status berhasil diperbarui.',
        ]);

        $this->assertEquals('KONTAK', $prospek->fresh()->status);
        $this->assertDatabaseHas('prospek_timelines', [
            'prospek_id'    => $prospek->id,
            'status_before' => 'BARU',
            'status_after'  => 'KONTAK',
            'notes'         => 'Telah ditelepon via WhatsApp',
        ]);
    }

    public function test_sales_cannot_change_status_to_lunas_via_modal()
    {
        $prospek = Prospek::create([
            'name'       => 'Bambang Sudirman',
            'pic'        => 'Bambang Sudirman',
            'whatsapp'   => '081234567892',
            'sales_id'   => $this->sales->id,
            'owner_id'   => $this->sales->id,
            'wilayah_id' => $this->wilayah->id,
            'status'     => 'BERKAS',
            'type'       => 'Individu',
        ]);

        $response = $this->actingAs($this->sales)->postJson(route('pipeline.update-status'), [
            'prospek_id' => $prospek->id,
            'status'     => 'LUNAS',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Sales tidak dapat mengubah status menjadi Lunas.',
        ]);
        $this->assertEquals('BERKAS', $prospek->fresh()->status);
    }

    public function test_cs_can_update_status_to_formulir_berkas_and_batal()
    {
        $prospek = Prospek::create([
            'name'       => 'Dewi Lestari',
            'pic'        => 'Dewi Lestari',
            'whatsapp'   => '081234567893',
            'sales_id'   => $this->sales->id,
            'owner_id'   => $this->sales->id,
            'cs_id'      => $this->cs->id,
            'wilayah_id' => $this->wilayah->id,
            'status'     => 'FORMULIR',
            'type'       => 'Individu',
        ]);

        // CS transitions to BERKAS
        $responseBerkas = $this->actingAs($this->cs)->postJson(route('pipeline.update-status'), [
            'prospek_id' => $prospek->id,
            'status'     => 'BERKAS',
        ]);

        $responseBerkas->assertStatus(200);
        $this->assertEquals('BERKAS', $prospek->fresh()->status);

        // CS transitions to BATAL DAFTAR
        $responseBatal = $this->actingAs($this->cs)->postJson(route('pipeline.update-status'), [
            'prospek_id' => $prospek->id,
            'status'     => 'BATAL DAFTAR',
        ]);

        $responseBatal->assertStatus(200);
        $this->assertEquals('BATAL DAFTAR', $prospek->fresh()->status);
    }

    public function test_cs_cannot_transition_to_unauthorized_stages()
    {
        $prospek = Prospek::create([
            'name'       => 'Eko Prasetyo',
            'pic'        => 'Eko Prasetyo',
            'whatsapp'   => '081234567894',
            'sales_id'   => $this->sales->id,
            'owner_id'   => $this->sales->id,
            'cs_id'      => $this->cs->id,
            'wilayah_id' => $this->wilayah->id,
            'status'     => 'FORMULIR',
            'type'       => 'Individu',
        ]);

        // CS tries to revert to early sales stage KONTAK
        $response = $this->actingAs($this->cs)->postJson(route('pipeline.update-status'), [
            'prospek_id' => $prospek->id,
            'status'     => 'KONTAK',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'CS hanya berwenang mengubah status ke FORMULIR, BERKAS, LUNAS, NO RESPON, atau BATAL DAFTAR.',
        ]);
        $this->assertEquals('FORMULIR', $prospek->fresh()->status);
    }

    public function test_changing_to_lunas_without_verified_payment_is_rejected()
    {
        $prospek = Prospek::create([
            'name'       => 'Fajar Nugroho',
            'pic'        => 'Fajar Nugroho',
            'whatsapp'   => '081234567895',
            'sales_id'   => $this->sales->id,
            'owner_id'   => $this->sales->id,
            'cs_id'      => $this->cs->id,
            'wilayah_id' => $this->wilayah->id,
            'status'     => 'BERKAS',
            'type'       => 'Individu',
        ]);

        // No verified payment in database
        $response = $this->actingAs($this->cs)->postJson(route('pipeline.update-status'), [
            'prospek_id' => $prospek->id,
            'status'     => 'LUNAS',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Status LUNAS memerlukan verifikasi pembayaran (Beli Formulir & Pembayaran Termin 1) oleh CS.',
        ]);
        $this->assertEquals('BERKAS', $prospek->fresh()->status);
    }

    public function test_changing_to_lunas_with_verified_payment_succeeds()
    {
        $prospek = Prospek::create([
            'name'       => 'Gita Savitri',
            'pic'        => 'Gita Savitri',
            'whatsapp'   => '081234567896',
            'sales_id'   => $this->sales->id,
            'owner_id'   => $this->sales->id,
            'cs_id'      => $this->cs->id,
            'wilayah_id' => $this->wilayah->id,
            'status'     => 'BERKAS',
            'type'       => 'Individu',
        ]);

        // Create required verified transactions
        Transaksi::create([
            'prospek_id'        => $prospek->id,
            'jenis'             => 'Beli Formulir',
            'nominal'           => 250000,
            'tanggal'           => now(),
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'status_verifikasi' => 'Valid',
        ]);

        Transaksi::create([
            'prospek_id'        => $prospek->id,
            'jenis'             => 'Pembayaran Termin 1',
            'nominal'           => 3000000,
            'tanggal'           => now(),
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'status_verifikasi' => 'Valid',
        ]);

        $response = $this->actingAs($this->cs)->postJson(route('pipeline.update-status'), [
            'prospek_id' => $prospek->id,
            'status'     => 'LUNAS',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertEquals('LUNAS', $prospek->fresh()->status);
    }

    public function test_lunas_prospect_cannot_be_reverted_to_another_status()
    {
        $prospek = Prospek::create([
            'name'       => 'Hadi Wijaya',
            'pic'        => 'Hadi Wijaya',
            'whatsapp'   => '081234567897',
            'sales_id'   => $this->sales->id,
            'owner_id'   => $this->sales->id,
            'cs_id'      => $this->cs->id,
            'wilayah_id' => $this->wilayah->id,
            'status'     => 'LUNAS',
            'type'       => 'Individu',
        ]);

        $response = $this->actingAs($this->spv)->postJson(route('pipeline.update-status'), [
            'prospek_id' => $prospek->id,
            'status'     => 'BERKAS',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Prospek yang sudah Lunas tidak dapat diubah statusnya.',
        ]);
        $this->assertEquals('LUNAS', $prospek->fresh()->status);
    }
}
