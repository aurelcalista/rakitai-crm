<?php

namespace Tests\Feature;

use App\Models\Prospek;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataAnalystProspekDateFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $analyst;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyst = User::factory()->create([
            'role'   => 'Data Analyst',
            'status' => 'Aktif',
        ]);
    }

    /** @test */
    public function test_data_analyst_can_filter_prospeks_by_date_range()
    {
        // Prospek A: created on 2026-10-10
        $prospekA = Prospek::factory()->create([
            'name'       => 'Calon Masuk Oktober',
            'created_at' => Carbon::parse('2026-10-10 10:00:00'),
        ]);

        // Prospek B: created on 2026-09-01 (outside date range)
        $prospekB = Prospek::factory()->create([
            'name'       => 'Calon Masuk September',
            'created_at' => Carbon::parse('2026-09-01 10:00:00'),
        ]);

        // Filter: 2026-10-01 s/d 2026-10-20
        $response = $this->actingAs($this->analyst)
            ->get(route('analyst.prospek.index', [
                'from_date' => '2026-10-01',
                'to_date'   => '2026-10-20',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Calon Masuk Oktober');
        $response->assertDontSee('Calon Masuk September');
    }

    /** @test */
    public function test_data_analyst_can_filter_prospeks_using_start_date_and_end_date_aliases()
    {
        $prospekA = Prospek::factory()->create([
            'name'       => 'Calon Alias Oktober',
            'created_at' => Carbon::parse('2026-10-15 14:00:00'),
        ]);

        $prospekB = Prospek::factory()->create([
            'name'       => 'Calon Alias Agustus',
            'created_at' => Carbon::parse('2026-08-01 09:00:00'),
        ]);

        $response = $this->actingAs($this->analyst)
            ->get(route('analyst.prospek.index', [
                'start_date' => '2026-10-01',
                'end_date'   => '2026-10-31',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Calon Alias Oktober');
        $response->assertDontSee('Calon Alias Agustus');
    }

    /** @test */
    public function test_data_analyst_export_prospek_respects_date_range_filter()
    {
        Prospek::factory()->create([
            'name'       => 'Prospek Terfilter Export',
            'whatsapp'   => '0812999001',
            'created_at' => Carbon::parse('2026-10-12 11:00:00'),
        ]);

        Prospek::factory()->create([
            'name'       => 'Prospek Luar Tanggal Export',
            'whatsapp'   => '0812999002',
            'created_at' => Carbon::parse('2026-05-01 11:00:00'),
        ]);

        $response = $this->actingAs($this->analyst)
            ->get(route('analyst.export', [
                'type'      => 'prospek',
                'from_date' => '2026-10-01',
                'to_date'   => '2026-10-31',
            ]));

        $response->assertStatus(200);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $tempFile = tempnam(sys_get_temp_dir(), 'exp_test_') . '.xlsx';
        file_put_contents($tempFile, $content);

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tempFile);
        $sheet = $spreadsheet->getActiveSheet();

        // Row 2 should be the filtered prospect
        $this->assertEquals('Prospek Terfilter Export', $sheet->getCell('B2')->getValue());

        // Row 3 should be empty because Prospek B was outside date range
        $this->assertEmpty($sheet->getCell('B3')->getValue());

        unlink($tempFile);
    }
}
