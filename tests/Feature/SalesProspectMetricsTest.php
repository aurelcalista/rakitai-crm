<?php

namespace Tests\Feature;

use App\Models\FollowUp;
use App\Models\Prospek;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesProspectMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_prospect_metrics_and_date_filter_are_scoped_to_logged_in_sales()
    {
        $sales1 = User::factory()->create(['role' => 'Sales', 'name' => 'Sales Satu']);
        $sales2 = User::factory()->create(['role' => 'Sales', 'name' => 'Sales Dua']);

        // Prospek diinput Sales 1
        $p1 = Prospek::create([
            'name'         => 'Prospek Sales 1',
            'type'         => 'Sekolah',
            'pic'          => 'PIC 1',
            'whatsapp'     => '08123456781',
            'status'       => 'HOT PROSPEK',
            'stage_number' => 3,
            'sales_id'     => $sales1->id,
            'owner_id'     => $sales1->id,
        ]);
        $p1->created_at = now()->subDays(2);
        $p1->saveQuietly();

        // Follow up oleh Sales 1 pada prospek miliknya
        FollowUp::create([
            'prospek_id' => $p1->id,
            'user_id'    => $sales1->id,
            'tanggal'    => now()->subDays(1),
            'metode'     => 'WhatsApp',
            'catatan'    => 'Chatting prospek',
        ]);

        // Prospek diinput Sales 2 (tidak boleh dihitung di input Sales 1)
        $p2 = Prospek::create([
            'name'         => 'Prospek Sales 2',
            'type'         => 'Corporate',
            'pic'          => 'PIC 2',
            'whatsapp'     => '08123456782',
            'status'       => 'BARU',
            'stage_number' => 1,
            'sales_id'     => $sales2->id,
            'owner_id'     => $sales2->id,
            'created_at'   => now()->subDays(2),
        ]);

        // Login sebagai Sales 1
        $response = $this->actingAs($sales1)->get(route('sales.prospek.index'));

        $response->assertStatus(200);
        $response->assertSee('Aktivitas & Metrik Prospek Sales');
        $response->assertSee('Prospek Diinput');
        $response->assertSee('Prospek Aktif');
        $response->assertSee('Di-Follow-up');
        $response->assertSee('Hot Prospect');

        // Check metrics: diinput = 1 (milik sales 1), bukan 2
        $response->assertViewHas('salesMetrics', function ($metrics) {
            return $metrics['diinput'] === 1
                && $metrics['dihubungi'] === 1
                && $metrics['follow_up'] === 1
                && $metrics['hot_prospect'] === 1;
        });

        // Test filter by date range
        $responseFiltered = $this->actingAs($sales1)->get(route('sales.prospek.index', [
            'start_date' => now()->toDateString(),
            'end_date'   => now()->toDateString(),
        ]));

        $responseFiltered->assertStatus(200);
        $responseFiltered->assertViewHas('salesMetrics', function ($metrics) {
            return $metrics['diinput'] === 0; // Karena dibuat subDays(2)
        });
    }

    public function test_cs_prospect_metrics_still_work_for_cs()
    {
        $cs = User::factory()->create(['role' => 'CS', 'name' => 'CS User']);

        $response = $this->actingAs($cs)->get(route('prospek.index'));

        $response->assertStatus(200);
        $response->assertSee('Aktivitas & Metrik Prospek CS');
        $response->assertViewHas('csMetrics');
    }

    public function test_sales_metrics_includes_presensi_siswa_and_corporate_prospects()
    {
        $wilayah = \App\Models\Wilayah::create(['kode' => 'W1', 'nama' => 'Wilayah Demo', 'level' => 'Provinsi', 'status' => 'Aktif']);
        $sales = User::factory()->create(['role' => 'Sales', 'name' => 'Sales Demo', 'wilayah_id' => $wilayah->id]);

        // 1. Prospek manual (sekolah)
        Prospek::create([
            'name'         => 'Prospek Manual Sekolah',
            'type'         => 'Sekolah',
            'pic'          => 'Budi',
            'whatsapp'     => '081234567801',
            'status'       => 'BARU',
            'stage_number' => 1,
            'sales_id'     => $sales->id,
            'owner_id'     => $sales->id,
        ]);

        // 2. Presensi Siswa (source = Presensi Siswa, sales_id = null)
        $pPresensi = Prospek::create([
            'name'         => 'MAN 1 Cirebon',
            'type'         => 'Sekolah',
            'pic'          => 'Abdulah',
            'whatsapp'     => '081234567802',
            'status'       => 'BARU',
            'stage_number' => 1,
            'source'       => Prospek::SOURCE_PRESENSI_SISWA,
            'sales_id'     => null,
            'owner_id'     => null,
        ]);

        // 3. Prospek Corporate
        Prospek::create([
            'name'         => 'PT Maju Bersama',
            'type'         => 'Corporate',
            'pic'          => 'Direktur HR',
            'whatsapp'     => '081234567803',
            'status'       => 'BARU',
            'stage_number' => 1,
            'sales_id'     => $sales->id,
            'owner_id'     => $sales->id,
        ]);

        $response = $this->actingAs($sales)->get(route('sales.prospek.index'));
        $response->assertStatus(200);

        // Check metrics: all 3 should be counted in diinput!
        $response->assertViewHas('salesMetrics', function ($metrics) {
            return $metrics['diinput'] === 3;
        });

        // Also test via CrmController (route prospek.index)
        $crmResponse = $this->actingAs($sales)->get(route('prospek.index'));
        $crmResponse->assertStatus(200);
        $crmResponse->assertViewHas('salesMetrics', function ($metrics) {
            return $metrics['diinput'] === 3;
        });
    }
}
