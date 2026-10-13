<?php

namespace Tests\Feature;

use App\Models\MasterData;
use App\Models\Prospek;
use App\Models\Sekolah;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MobileSecurityAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $salesA;
    protected User $salesB;
    protected User $spv;

    protected function setUp(): void
    {
        parent::setUp();

        TahunAkademik::create([
            'nama' => '2027/2028',
            'is_aktif' => true,
        ]);

        MasterData::create([
            'kode' => 'SRC-01',
            'type' => 'sumber_prospek',
            'nama' => 'Brosur',
            'status' => 'Aktif',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Super Admin',
            'role' => 'Admin',
            'status' => 'Aktif',
        ]);

        $this->spv = User::factory()->create([
            'name' => 'Supervisor Wilayah',
            'role' => 'SPV',
            'status' => 'Aktif',
        ]);

        $this->salesA = User::factory()->create([
            'name' => 'Sales Lapangan A',
            'role' => 'Sales',
            'status' => 'Aktif',
            'supervisor_id' => $this->spv->id,
        ]);

        $this->salesB = User::factory()->create([
            'name' => 'Sales Lapangan B',
            'role' => 'Sales',
            'status' => 'Aktif',
            'supervisor_id' => $this->spv->id,
        ]);
    }

    public function test_anonymous_request_with_user_id_query_param_cannot_take_over_admin_account()
    {
        // Attacker attempts to pass ?user_id=admin_id
        $response = $this->get('/mobile/prospek/create?user_id=' . $this->admin->id);

        // Must redirect to login, NOT authenticate as admin
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_anonymous_post_with_user_id_cannot_create_prospect()
    {
        $response = $this->post('/mobile/prospek', [
            'user_id'   => $this->admin->id,
            'type'      => 'Individu',
            'pic'       => 'Calon Maba Palsu',
            'whatsapp'  => '081234567890',
            'source'    => 'Brosur',
        ]);

        $response->assertStatus(401);
        $this->assertGuest();
        $this->assertDatabaseMissing('prospeks', [
            'pic' => 'Calon Maba Palsu',
        ]);
    }

    public function test_authenticated_sales_user_can_access_mobile_form_and_create_prospect()
    {
        $response = $this->actingAs($this->salesA)->get('/mobile/prospek/create');
        $response->assertStatus(200);

        $storeResponse = $this->actingAs($this->salesA)->post('/mobile/prospek', [
            'type'      => 'Individu',
            'pic'       => 'Budi Santoso',
            'whatsapp'  => '081234567891',
            'source'    => 'Brosur',
            'notes'     => 'Hasil temu sapa di mall',
        ]);

        $storeResponse->assertRedirect(route('mobile.sukses', [
            'type'    => 'prospek',
            'title'   => 'Prospek Berhasil Disimpan!',
            'message' => 'Data prospek Budi Santoso telah masuk ke alur pipeline.',
        ]));

        $this->assertDatabaseHas('prospeks', [
            'pic'      => 'Budi Santoso',
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);
    }

    public function test_sales_cannot_forge_sales_id_to_assign_prospect_to_another_sales()
    {
        // Sales A tries to pass sales_id = Sales B
        $response = $this->actingAs($this->salesA)->post('/mobile/prospek', [
            'type'      => 'Individu',
            'pic'       => 'Citra Kirana',
            'whatsapp'  => '081234567892',
            'source'    => 'Brosur',
            'sales_id'  => $this->salesB->id, // Forge attempt
        ]);

        $response->assertRedirect(route('mobile.sukses', [
            'type'    => 'prospek',
            'title'   => 'Prospek Berhasil Disimpan!',
            'message' => 'Data prospek Citra Kirana telah masuk ke alur pipeline.',
        ]));

        // Sales A must remain the sales_id and owner_id
        $this->assertDatabaseHas('prospeks', [
            'pic'      => 'Citra Kirana',
            'sales_id' => $this->salesA->id, // NOT salesB
            'owner_id' => $this->salesA->id,
        ]);
    }

    public function test_spv_can_assign_prospect_to_subordinate_sales_via_mobile()
    {
        // SPV can legitimately assign to Sales B
        $response = $this->actingAs($this->spv)->post('/mobile/prospek', [
            'type'      => 'Individu',
            'pic'       => 'Dedi Prasetyo',
            'whatsapp'  => '081234567893',
            'source'    => 'Brosur',
            'sales_id'  => $this->salesB->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('prospeks', [
            'pic'      => 'Dedi Prasetyo',
            'sales_id' => $this->salesB->id,
            'owner_id' => $this->spv->id, // Creator is SPV
        ]);
    }

    public function test_valid_temporary_signed_url_allows_mobile_webview_access()
    {
        // Generate valid temporary signed URL
        $signedUrl = URL::temporarySignedRoute(
            'mobile.prospek.create',
            now()->addMinutes(15),
            ['signed_user_id' => $this->salesA->id]
        );

        $response = $this->get($signedUrl);
        $response->assertStatus(200);
        $this->assertAuthenticatedAs($this->salesA);
    }

    public function test_tampered_signed_url_is_rejected()
    {
        $signedUrl = URL::temporarySignedRoute(
            'mobile.prospek.create',
            now()->addMinutes(15),
            ['signed_user_id' => $this->salesA->id]
        );

        // Tamper user id
        $tamperedUrl = str_replace(
            'signed_user_id=' . $this->salesA->id,
            'signed_user_id=' . $this->admin->id,
            $signedUrl
        );

        $response = $this->get($tamperedUrl);
        // Signature fails validation
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
