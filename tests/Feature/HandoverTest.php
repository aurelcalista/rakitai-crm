<?php

namespace Tests\Feature;

use App\Models\Prospek;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HandoverTest extends TestCase
{
    use DatabaseTransactions;

    public function test_handover_logic()
    {
        $wilayah1 = \App\Models\Wilayah::create(['kode' => 'W1', 'nama' => 'Wilayah 1', 'level' => 'Provinsi', 'status' => 'Aktif']);
        $wilayah2 = \App\Models\Wilayah::create(['kode' => 'W2', 'nama' => 'Wilayah 2', 'level' => 'Provinsi', 'status' => 'Aktif']);

        $sales = User::factory()->create(['role' => 'Sales', 'wilayah_id' => $wilayah2->id]);
        $cs1 = User::factory()->create(['role' => 'CS', 'wilayah_id' => $wilayah1->id, 'status' => 'Aktif']);
        $cs2 = User::factory()->create(['role' => 'CS', 'wilayah_id' => $wilayah2->id, 'status' => 'Aktif']);

        // A. Status berubah ke FORMULIR -> handover ter-trigger.
        // B. owner_id Sales tetap sama setelah handover.
        // C. CS yang dipilih sesuai wilayah.
        // D. active_follow_up_count reset menjadi 0.
        // E. handover_at tercatat.
        
        $prospek = Prospek::create([
            'name' => 'Prospek A',
            'type' => 'Individu',
            'category' => 'B2C',
            'source' => 'Website',
            'sales_id' => $sales->id,
            'owner_id' => $sales->id,
            'wilayah_id' => $wilayah2->id,
            'status' => 'BARU',
            'cs_id' => null,
            'active_follow_up_count' => 5
        ]);

        $prospek->status = 'FORMULIR';
        $prospek->save();

        $prospek->refresh();

        $this->assertEquals($cs2->id, $prospek->cs_id);
        $this->assertEquals($sales->id, $prospek->owner_id);
        $this->assertEquals($sales->id, $prospek->sales_id);
        $this->assertEquals(0, $prospek->active_follow_up_count);
        $this->assertNotNull($prospek->handover_at);
        
        // F. Prospek yang belum FORMULIR tidak terkena handover
        $prospek2 = Prospek::create([
            'name' => 'Prospek B',
            'type' => 'Individu',
            'category' => 'B2C',
            'source' => 'Website',
            'sales_id' => $sales->id,
            'wilayah_id' => $wilayah2->id,
            'status' => 'BARU',
            'cs_id' => null
        ]);
        
        $prospek2->status = 'HANGAT';
        $prospek2->save();
        $prospek2->refresh();
        $this->assertNull($prospek2->cs_id);
    }
}
