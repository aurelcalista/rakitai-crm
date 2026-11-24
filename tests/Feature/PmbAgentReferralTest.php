<?php

namespace Tests\Feature;

use App\Models\Prospek;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PmbAgentReferralTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $csUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'name'     => 'Admin Test PMB',
            'username' => 'admin_test_' . uniqid(),
            'email'    => 'admin_' . uniqid() . '@cic.ac.id',
            'password' => Hash::make('password123'),
            'role'     => 'Admin',
            'status'   => 'Aktif',
        ]);

        $this->csUser = User::factory()->create([
            'name'     => 'CS Test PMB',
            'username' => 'cs_test_' . uniqid(),
            'email'    => 'cs_' . uniqid() . '@cic.ac.id',
            'password' => Hash::make('password123'),
            'role'     => 'CS',
            'status'   => 'Aktif',
        ]);
    }

    public function test_referral_url_shows_registration_form_with_agent()
    {
        // 1. Root level /register/{agent}
        $response = $this->get('/register/guru-smk1');
        $response->assertStatus(200);
        $response->assertSee('guru-smk1');
        $response->assertSee('Jalur Undangan / Referral Agent');

        // 2. Prefix /pmb/register/{agent}
        $responsePmb = $this->get('/pmb/register/guru-smk1');
        $responsePmb->assertStatus(200);
        $responsePmb->assertSee('guru-smk1');
    }

    public function test_registration_with_agent_saves_agent_to_user_and_prospek()
    {
        $uniqueEmail = 'camaba_' . uniqid() . '@example.com';
        $uniquePhone = '+62812' . rand(10000000, 99999999);

        $payload = [
            'name'                  => 'Siswa Afiliasi Test',
            'email'                 => $uniqueEmail,
            'whatsapp'              => $uniquePhone,
            'asal_sekolah'          => 'SMKN 1 Cirebon',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'agent'                 => 'guru_smk1',
        ];

        $response = $this->post(route('pmb.register.post'), $payload);
        $response->assertRedirect(route('pmb.login'));

        $this->assertDatabaseHas('users', [
            'email' => $uniqueEmail,
            'role'  => 'Camaba',
            'agent' => 'guru_smk1',
        ]);

        $this->assertDatabaseHas('prospeks', [
            'email' => $uniqueEmail,
            'agent' => 'guru_smk1',
        ]);
    }

    public function test_registration_without_agent_saves_null()
    {
        $uniqueEmail = 'mandiri_' . uniqid() . '@example.com';
        $uniquePhone = '+62812' . rand(10000000, 99999999);

        $payload = [
            'name'                  => 'Siswa Mandiri Test',
            'email'                 => $uniqueEmail,
            'whatsapp'              => $uniquePhone,
            'asal_sekolah'          => 'SMA 1 Cirebon',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'agent'                 => '',
        ];

        $response = $this->post(route('pmb.register.post'), $payload);
        $response->assertRedirect(route('pmb.login'));

        $user = User::where('email', $uniqueEmail)->first();
        $this->assertNotNull($user);
        $this->assertNull($user->agent);

        $prospek = Prospek::where('email', $uniqueEmail)->first();
        $this->assertNotNull($prospek);
        $this->assertNull($prospek->agent);
    }

    public function test_admin_can_access_rekapitulasi_agent_page()
    {
        // Buat data prospek dengan agent dan tanpa agent
        Prospek::create([
            'name'          => 'Pendaftar Via Agent 1',
            'email'         => 'agent1_' . uniqid() . '@test.com',
            'whatsapp'      => '+628123456701',
            'agent'         => 'ahmad_tim_a',
            'status'        => 'BARU',
            'no_registrasi' => 'PMB-TEST-001',
        ]);

        Prospek::create([
            'name'          => 'Pendaftar Tanpa Agent',
            'email'         => 'mandiri_' . uniqid() . '@test.com',
            'whatsapp'      => '+628123456702',
            'agent'         => null,
            'status'        => 'BARU',
            'no_registrasi' => 'PMB-TEST-002',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('pmb.agent.index'));
        $response->assertStatus(200);
        $response->assertSee('Daftar Rekapitulasi Pendaftar per Agent');
        $response->assertSee('ahmad_tim_a');
        $response->assertSee('Tanpa Agent');
    }

    public function test_cs_can_access_rekapitulasi_agent_page()
    {
        $response = $this->actingAs($this->csUser)->get(route('pmb.agent.index'));
        $response->assertStatus(200);
        $response->assertSee('Total Seluruh Pendaftar');
    }

    public function test_agent_export_csv()
    {
        $response = $this->actingAs($this->adminUser)->get(route('pmb.agent.export'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_pendaftar_list_can_be_filtered_by_agent()
    {
        $prospekAgent = Prospek::create([
            'name'          => 'Murid Binaan Pak Guru',
            'email'         => 'murid_' . uniqid() . '@test.com',
            'whatsapp'      => '+628123456703',
            'agent'         => 'guru-smk1',
            'status'        => 'BARU',
            'no_registrasi' => 'PMB-TEST-003',
        ]);

        $prospekMandiri = Prospek::create([
            'name'          => 'Murid Belajar Mandiri',
            'email'         => 'mandiri_' . uniqid() . '@test.com',
            'whatsapp'      => '+628123456704',
            'agent'         => null,
            'status'        => 'BARU',
            'no_registrasi' => 'PMB-TEST-004',
        ]);

        // Filter agent=guru-smk1
        $response = $this->actingAs($this->adminUser)->get(route('pmb.pendaftar.index', ['agent' => 'guru-smk1']));
        $response->assertStatus(200);
        $response->assertSee('Murid Binaan Pak Guru');
        $response->assertDontSee('Murid Belajar Mandiri');

        // Filter agent=none
        $responseNone = $this->actingAs($this->adminUser)->get(route('pmb.pendaftar.index', ['agent' => 'none']));
        $responseNone->assertStatus(200);
        $responseNone->assertSee('Murid Belajar Mandiri');
        $responseNone->assertDontSee('Murid Binaan Pak Guru');
    }
}
