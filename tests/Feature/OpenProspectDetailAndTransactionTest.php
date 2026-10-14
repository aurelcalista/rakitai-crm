<?php

namespace Tests\Feature;

use App\Models\Prospek;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenProspectDetailAndTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $salesA;
    protected User $salesB;
    protected User $csUser;
    protected User $spvUser;
    protected Wilayah $wilayahA;
    protected Wilayah $wilayahB;
    protected Prospek $prospect;

    protected function setUp(): void
    {
        parent::setUp();

        $ta = TahunAkademik::create([
            'nama' => '2026/2027',
            'status' => 'Aktif',
            'is_active' => true,
        ]);

        $this->wilayahA = Wilayah::create(['kode' => 'W-A', 'nama' => 'Kota Cirebon', 'status' => 'Aktif']);
        $this->wilayahB = Wilayah::create(['kode' => 'W-B', 'nama' => 'Kabupaten Majalengka', 'status' => 'Aktif']);

        $this->salesA = User::factory()->create([
            'name' => 'Sales Pemilik',
            'role' => 'Sales',
            'wilayah_id' => $this->wilayahA->id,
            'status' => 'Aktif',
        ]);

        $this->salesB = User::factory()->create([
            'name' => 'Sales Lain',
            'role' => 'Sales',
            'wilayah_id' => $this->wilayahB->id,
            'status' => 'Aktif',
        ]);

        $this->csUser = User::factory()->create([
            'name' => 'CS Front Office',
            'role' => 'CS',
            'wilayah_id' => $this->wilayahB->id, // Wilayah berbeda
            'status' => 'Aktif',
        ]);

        $this->spvUser = User::factory()->create([
            'name' => 'SPV Tim Lain',
            'role' => 'SPV',
            'wilayah_id' => $this->wilayahB->id,
            'status' => 'Aktif',
        ]);

        // Prospek tahap awal (01 BARU) milik Sales A di Wilayah A
        $this->prospect = Prospek::create([
            'name' => 'Fajar Nugraha',
            'type' => 'Individu',
            'source' => 'WhatsApp Inbound',
            'status' => 'BARU',
            'stage_number' => 1,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
            'wilayah_id' => $this->wilayahA->id,
            'academic_year_id' => $ta->id,
            'whatsapp' => '081234567890',
        ]);
    }

    /**
     * User (CS, Sales Lain, SPV) dapat melihat detail prospek milik user lain tanpa 403 Forbidden.
     */
    public function test_any_user_can_view_prospect_detail_of_other_users_without_403()
    {
        // 1. CS dari wilayah berbeda mengakses prospek Sales A tahap BARU
        $responseCs = $this->actingAs($this->csUser)->get(route('prospek.show', $this->prospect->id));
        $responseCs->assertStatus(200);
        $responseCs->assertSee('Fajar Nugraha');
        $responseCs->assertSee('Input Transaksi');

        // 2. Sales lain dari wilayah berbeda mengakses prospek Sales A
        $responseSales = $this->actingAs($this->salesB)->get(route('prospek.show', $this->prospect->id));
        $responseSales->assertStatus(200);
        $responseSales->assertSee('Fajar Nugraha');
        $responseSales->assertSee('Input Transaksi');

        // 3. SPV dari wilayah berbeda mengakses prospek Sales A
        $responseSpv = $this->actingAs($this->spvUser)->get(route('prospek.show', $this->prospect->id));
        $responseSpv->assertStatus(200);
        $responseSpv->assertSee('Fajar Nugraha');
        $responseSpv->assertSee('Input Transaksi');
    }

    /**
     * User dapat menginput transaksi pada prospek milik user lain tanpa pembatasan 403.
     */
    public function test_user_can_input_transaction_on_other_user_prospect()
    {
        $payload = [
            'kategori'          => 'DP Daftar',
            'jenis'             => 'Beli Formulir',
            'nominal'           => 250000,
            'tanggal'           => now()->format('Y-m-d'),
            'metode_pembayaran' => 'tunai',
            'notes'             => 'Pembayaran langsung di counter CS',
        ];

        // CS menginput transaksi pada prospek milik Sales A
        $response = $this->actingAs($this->csUser)
            ->from(route('prospek.show', $this->prospect->id))
            ->post(
                route('prospek.transaksi', $this->prospect->id),
                $payload
            );

        $response->assertRedirect(route('prospek.show', $this->prospect->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transaksis', [
            'prospek_id' => $this->prospect->id,
            'nominal'    => 250000,
            'jenis'      => 'Beli Formulir',
        ]);
    }
}
