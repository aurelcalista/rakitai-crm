<?php

namespace Tests\Feature;

use App\Models\Prospek;
use App\Models\User;
use App\Models\ProspekTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckHandoverSlaTest extends TestCase
{
    use RefreshDatabase;

    public function test_sla_detection()
    {
        $wilayah = \App\Models\Wilayah::first();
        if (!$wilayah) {
            $wilayah = \App\Models\Wilayah::create(['kode' => 'W1', 'nama' => 'Wilayah SLA', 'level' => 'Provinsi', 'status' => 'Aktif']);
        }

        $spv = User::factory()->create(['role' => 'SPV', 'wilayah_id' => $wilayah->id]);
        $cs = User::factory()->create(['role' => 'CS', 'wilayah_id' => $wilayah->id, 'supervisor_id' => $spv->id]);

        // Handover less than 2 hours ago -> No SLA breach
        $prospekOk = Prospek::create([
            'name' => 'Prospek OK',
            'type' => 'Individu',
            'category' => 'B2C',
            'source' => 'Website',
            'cs_id' => $cs->id,
            'status' => 'FORMULIR',
            'handover_at' => now()->subHour(1),
            'active_follow_up_count' => 0
        ]);

        // Handover > 2 hours ago, but already followed up -> No SLA breach
        $prospekFollowedUp = Prospek::create([
            'name' => 'Prospek Followed',
            'type' => 'Individu',
            'category' => 'B2C',
            'source' => 'Website',
            'cs_id' => $cs->id,
            'status' => 'FORMULIR',
            'handover_at' => now()->subHours(3),
            'active_follow_up_count' => 1
        ]);

        // Handover > 2 hours ago, no follow up -> SLA breach
        $prospekBreached = Prospek::create([
            'name' => 'Prospek Breached',
            'type' => 'Individu',
            'category' => 'B2C',
            'source' => 'Website',
            'cs_id' => $cs->id,
            'status' => 'FORMULIR',
            'handover_at' => now()->subHours(3),
            'active_follow_up_count' => 0
        ]);

        $this->artisan('crm:check-handover-sla')->assertExitCode(0);

        // Check timelines for SPV
        $timelinesOk = ProspekTimeline::where('prospek_id', $prospekOk->id)->where('title', 'SLA Handover Breached')->count();
        $this->assertEquals(0, $timelinesOk);

        $timelinesFollowedUp = ProspekTimeline::where('prospek_id', $prospekFollowedUp->id)->where('title', 'SLA Handover Breached')->count();
        $this->assertEquals(0, $timelinesFollowedUp);

        $timelinesBreached = ProspekTimeline::where('prospek_id', $prospekBreached->id)->where('title', 'SLA Handover Breached')->count();
        $this->assertEquals(1, $timelinesBreached);
    }
}
