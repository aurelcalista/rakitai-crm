<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Prospek;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProspekValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sales = User::factory()->create(['role' => 'Sales']);
        $this->prodi = \App\Models\Prodi::first();
        if (!$this->prodi) {
            $this->prodi = \App\Models\Prodi::create(['kode' => 'P1', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1', 'fakultas' => 'FTIK', 'status' => 'Aktif']);
        }
    }

    protected function getValidPayload()
    {
        return [
            'name' => 'John Doe',
            'type' => 'Individu',
            'pic' => 'John Doe',
            'whatsapp' => '081234567890',
            'status' => 'BARU',
            'source' => 'Website CIC',
            'prodi_id' => $this->prodi->id,
        ];
    }

    public function test_create_prospek_without_prodi_id_fails()
    {
        $payload = $this->getValidPayload();
        unset($payload['prodi_id']);

        $response = $this->actingAs($this->sales)->post(route('sales.prospek.store'), $payload);
        $response->assertSessionHasErrors('prodi_id');
    }

    public function test_create_prospek_with_invalid_prodi_id_fails()
    {
        $payload = $this->getValidPayload();
        $payload['prodi_id'] = 999999;

        $response = $this->actingAs($this->sales)->post(route('sales.prospek.store'), $payload);
        $response->assertSessionHasErrors('prodi_id');
    }

    public function test_create_prospek_without_source_fails()
    {
        $payload = $this->getValidPayload();
        unset($payload['source']);

        $response = $this->actingAs($this->sales)->post(route('sales.prospek.store'), $payload);
        $response->assertSessionHasErrors('source');
    }

    public function test_create_prospek_with_non_canonical_source_fails()
    {
        $payload = $this->getValidPayload();
        $payload['source'] = 'Dari Langit';

        $response = $this->actingAs($this->sales)->post(route('sales.prospek.store'), $payload);
        $response->assertSessionHasErrors('source');
    }

    public function test_create_prospek_with_canonical_source_succeeds()
    {
        $payload = $this->getValidPayload();

        $response = $this->actingAs($this->sales)->post(route('sales.prospek.store'), $payload);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('prospeks', ['whatsapp' => '081234567890']);
    }

    public function test_duplicate_by_whatsapp_rejected()
    {
        Prospek::create(array_merge($this->getValidPayload(), ['sales_id' => $this->sales->id, 'owner_id' => $this->sales->id]));

        $payload = $this->getValidPayload();
        $payload['name'] = 'Different Name';
        
        $response = $this->actingAs($this->sales)->post(route('sales.prospek.store'), $payload);
        $response->assertSessionHasErrors('whatsapp');
        
        // Assert error message contains existing handler
        $errors = session('errors')->get('whatsapp');
        $this->assertStringContainsString('Sedang ditangani oleh', $errors[0]);
    }

    public function test_duplicate_by_name_rejected()
    {
        Prospek::create(array_merge($this->getValidPayload(), ['whatsapp' => '11111111', 'sales_id' => $this->sales->id, 'owner_id' => $this->sales->id]));

        $payload = $this->getValidPayload();
        $payload['whatsapp'] = '22222222';
        
        $response = $this->actingAs($this->sales)->post(route('sales.prospek.store'), $payload);
        $response->assertSessionHasErrors('name');
    }

    public function test_update_prospek_does_not_trigger_duplicate_on_itself()
    {
        $prospek = Prospek::create(array_merge($this->getValidPayload(), ['sales_id' => $this->sales->id, 'owner_id' => $this->sales->id]));

        $payload = $this->getValidPayload();
        $payload['notes'] = 'Some notes'; // update something

        $response = $this->actingAs($this->sales)->put(route('sales.prospek.update', $prospek), $payload);
        $response->assertSessionHasNoErrors();
    }

    public function test_catatan_max_10_words_succeeds()
    {
        $payload = $this->getValidPayload();
        $payload['notes'] = 'satu dua tiga empat lima enam tujuh delapan sembilan sepuluh'; // 10 words

        $response = $this->actingAs($this->sales)->post(route('sales.prospek.store'), $payload);
        $response->assertSessionHasNoErrors();
    }

    public function test_catatan_more_than_10_words_fails()
    {
        $payload = $this->getValidPayload();
        $payload['notes'] = 'satu dua tiga empat lima enam tujuh delapan sembilan sepuluh sebelas'; // 11 words

        $response = $this->actingAs($this->sales)->post(route('sales.prospek.store'), $payload);
        $response->assertSessionHasErrors('notes');
    }

    public function test_duplicate_check_includes_dingin_status()
    {
        $dinginProspek = Prospek::create(array_merge($this->getValidPayload(), [
            'status' => 'DINGIN',
            'sales_id' => $this->sales->id,
            'owner_id' => $this->sales->id
        ]));

        $payload = $this->getValidPayload(); // Same whatsapp and name as $dinginProspek

        $response = $this->actingAs($this->sales)->post(route('sales.prospek.store'), $payload);
        $response->assertSessionHasErrors('whatsapp');

        $errors = session('errors')->get('whatsapp');
        $this->assertStringContainsString('Status saat ini: DINGIN', $errors[0]);
        $this->assertStringContainsString('Prospek ini dimiliki oleh: ' . $this->sales->name, $errors[0]);
    }
}
