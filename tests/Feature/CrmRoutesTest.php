<?php

namespace Tests\Feature;

use Tests\TestCase;

class CrmRoutesTest extends TestCase
{
    public function test_root_redirects_to_dashboard(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('dashboard'));

        $dashboardResponse = $this->get(route('dashboard'));
        $dashboardResponse->assertRedirect(route('dashboard.sales'));
    }

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('CRM Inbound');
    }

    public function test_all_dashboards_are_accessible(): void
    {
        $this->get(route('dashboard.sales'))->assertStatus(200)->assertSee('Halo, Aurel Calista');
        $this->get(route('dashboard.cs'))->assertStatus(200)->assertSee('Halo, Dina Marlina');
        $this->get(route('dashboard.spv'))->assertStatus(200)->assertSee('Halo, Hendra Setiawan');
        $this->get(route('dashboard.hm'))->assertStatus(200)->assertSee('Executive Dashboard Marketing');
        $this->get(route('dashboard.admin'))->assertStatus(200)->assertSee('Admin System Control Panel');
    }

    public function test_admin_modules_are_accessible(): void
    {
        $this->get(route('admin.users.index'))->assertStatus(200)->assertSee('Daftar Pengguna');
        $this->get(route('admin.master-data.index'))->assertStatus(200)->assertSee('Master Data');
        $this->get(route('admin.audit-logs.index'))->assertStatus(200)->assertSee('Audit Trail');
        $this->get(route('admin.settings.index'))->assertStatus(200)->assertSee('WhatsApp API Gateway');
    }

    public function test_crm_core_modules_are_accessible(): void
    {
        $this->get(route('prospek.index'))->assertStatus(200)->assertSee('Manajemen Prospek');
        $this->get(route('prospek.show', 1))->assertStatus(200)->assertSee('Detail Prospek');
        $this->get(route('kunjungan.index'))->assertStatus(200)->assertSee('Kunjungan');
        $this->get(route('follow-up.index'))->assertStatus(200)->assertSee('Aktivitas Follow Up');
        $this->get(route('pipeline.index'))->assertStatus(200)->assertSee('Board Pipeline Inbound');
        $this->get(route('performa.index'))->assertStatus(200)->assertSee('Evaluasi Target');
        $this->get(route('laporan.index'))->assertStatus(200)->assertSee('Laporan');
        $this->get(route('profil.index'))->assertStatus(200)->assertSee('Profil & Akun');
    }
}
