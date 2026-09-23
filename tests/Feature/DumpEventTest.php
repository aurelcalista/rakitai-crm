<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DumpEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_dump_event()
    {
        $user = \App\Models\User::factory()->create(['role' => 'EO']);
        $event = Event::create([
            'nama' => 'Testing 123',
            'lokasi' => 'Test Location',
            'tanggal_mulai' => now(),
            'tanggal_selesai' => now()->addHour(),
            'eo_id' => $user->id,
            'qr_code' => 'TEST-QR-123',
        ]);
        dump('event qr_code attribute:', $event->qr_code);
        dump('fresh event qr_code:', $event->fresh()->qr_code);
        $this->assertNotNull($event->qr_code);
    }
}
