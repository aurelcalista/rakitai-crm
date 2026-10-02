<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBankAccountTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->sales = User::factory()->create(['role' => 'Sales']);
    }

    public function test_admin_can_view_bank_accounts_index(): void
    {
        BankAccount::create([
            'bank_name' => 'Bank Mandiri',
            'account_number' => '1380010015599',
            'account_name' => 'Universitas Catur Insan Cendekia',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bank-accounts.index'));

        $response->assertOk();
        $response->assertSee('Master Rekening Bank');
        $response->assertSee('Bank Mandiri');
        $response->assertSee('1380010015599');
    }

    public function test_admin_can_create_bank_account(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.bank-accounts.store'), [
            'bank_name' => 'BCA',
            'account_number' => '8210998877',
            'account_name' => 'Universitas Catur Insan Cendekia',
            'notes' => 'Rekening operasional PMB',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.bank-accounts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bank_accounts', [
            'bank_name' => 'BCA',
            'account_number' => '8210998877',
            'account_name' => 'Universitas Catur Insan Cendekia',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_bank_account(): void
    {
        $bank = BankAccount::create([
            'bank_name' => 'BRI',
            'account_number' => '0011223344',
            'account_name' => 'Yayasan UCIC',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.bank-accounts.update', $bank->id), [
            'bank_name' => 'Bank BRI',
            'account_number' => '001122334455',
            'account_name' => 'Universitas Catur Insan Cendekia',
            'notes' => 'Rekening diperbarui',
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('admin.bank-accounts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bank_accounts', [
            'id' => $bank->id,
            'bank_name' => 'Bank BRI',
            'account_number' => '001122334455',
            'account_name' => 'Universitas Catur Insan Cendekia',
            'is_active' => false,
            'notes' => 'Rekening diperbarui',
        ]);
    }

    public function test_admin_can_toggle_bank_account_status(): void
    {
        $bank = BankAccount::create([
            'bank_name' => 'BNI',
            'account_number' => '0298877665',
            'account_name' => 'Universitas Catur Insan Cendekia',
            'is_active' => true,
        ]);

        // Toggle to inactive
        $response = $this->actingAs($this->admin)->post(route('admin.bank-accounts.toggle-status', $bank->id));
        $response->assertRedirect(route('admin.bank-accounts.index'));
        $this->assertFalse($bank->fresh()->is_active);

        // Toggle back to active
        $response = $this->actingAs($this->admin)->post(route('admin.bank-accounts.toggle-status', $bank->id));
        $response->assertRedirect(route('admin.bank-accounts.index'));
        $this->assertTrue($bank->fresh()->is_active);
    }

    public function test_admin_can_delete_bank_account(): void
    {
        $bank = BankAccount::create([
            'bank_name' => 'Bank Danamon',
            'account_number' => '9988776655',
            'account_name' => 'UCIC',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.bank-accounts.destroy', $bank->id));

        $response->assertRedirect(route('admin.bank-accounts.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('bank_accounts', ['id' => $bank->id]);
    }

    public function test_non_admin_cannot_manage_bank_accounts(): void
    {
        $response = $this->actingAs($this->sales)->get(route('admin.bank-accounts.index'));
        $this->assertTrue($response->isRedirect() || $response->isForbidden());
    }
}
