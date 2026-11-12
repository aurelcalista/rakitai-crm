<?php

namespace Tests\Feature;

use App\Mail\CamabaLoginOtpMail;
use App\Models\User;
use App\Services\CamabaOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CamabaEmailOtpLoginTest extends TestCase
{
    use RefreshDatabase;

    private User $camaba;

    protected function setUp(): void
    {
        parent::setUp();

        $this->camaba = User::create([
            'name'     => 'Siti Aisyah',
            'username' => 'sitiais',
            'email'    => 'siti.camaba@example.com',
            'role'     => 'Camaba',
            'status'   => 'Aktif',
            'password' => Hash::make('SecretPass123!'),
        ]);
    }

    public function test_pmb_login_page_defaults_to_email_otp_mode_with_password_switch_button(): void
    {
        $response = $this->get(route('pmb.login'));

        $response->assertStatus(200);
        $response->assertSee('Kirim Kode OTP');
        $response->assertSee('Masuk dengan Password');
        $response->assertSee('Lupa Password?');
        $response->assertSee('Daftar disini');
        $response->assertDontSee('Ingat saya');
        $response->assertDontSee('name="remember"', false);
        $response->assertSee('data-initial-mode="otp"', false);
        $response->assertSee('data-initial-step="request"', false);
    }

    public function test_camaba_can_request_and_login_with_email_otp_and_otp_is_hashed_in_cache(): void
    {
        Mail::fake();

        $sendResponse = $this->post(route('pmb.login.otp.send'), [
            'email' => 'Siti.Camaba@Example.com',
        ]);

        $sendResponse->assertRedirect(route('pmb.login'));
        $sendResponse->assertSessionHas('login_mode', 'otp');
        $sendResponse->assertSessionHas('otp_step', 'verify');
        $sendResponse->assertSessionHas('otp_email', 'siti.camaba@example.com');

        $capturedOtp = null;
        Mail::assertSent(CamabaLoginOtpMail::class, function (CamabaLoginOtpMail $mail) use (&$capturedOtp) {
            $capturedOtp = $mail->otpCode;

            return $mail->hasTo('siti.camaba@example.com')
                && strlen($mail->otpCode) === 6
                && ctype_digit($mail->otpCode);
        });

        $this->assertNotNull($capturedOtp);

        // Pastikan OTP disimpan ter-hash di cache (tidak plaintext)
        $cacheKey = 'pmb_camaba_otp:' . hash('sha256', 'siti.camaba@example.com');
        $stored = Cache::get($cacheKey);
        $this->assertIsArray($stored);
        $this->assertSame('siti.camaba@example.com', $stored['email']);
        $this->assertNotSame($capturedOtp, $stored['otp_hash']);
        $this->assertStringNotContainsString($capturedOtp, json_encode($stored));
        $this->assertTrue(Hash::check('siti.camaba@example.com|' . $capturedOtp, $stored['otp_hash']));

        $originalPasswordHash = $this->camaba->password;

        $verifyResponse = $this->post(route('pmb.login.otp.verify'), [
            'email' => 'siti.camaba@example.com',
            'otp'   => $capturedOtp,
        ]);

        $verifyResponse->assertRedirect(route('pmb.akun.dashboard'));
        $this->assertAuthenticatedAs($this->camaba);

        // Password akun lama tidak boleh berubah
        $this->camaba->refresh();
        $this->assertSame($originalPasswordHash, $this->camaba->password);
    }

    public function test_wrong_otp_is_rejected_and_exceeding_max_attempts_invalidates_otp(): void
    {
        Mail::fake();

        $this->post(route('pmb.login.otp.send'), [
            'email' => $this->camaba->email,
        ])->assertRedirect(route('pmb.login'));

        $capturedOtp = null;
        Mail::assertSent(CamabaLoginOtpMail::class, function (CamabaLoginOtpMail $mail) use (&$capturedOtp) {
            $capturedOtp = $mail->otpCode;
            return true;
        });

        $wrongOtp = $capturedOtp === '000000' ? '999999' : '000000';

        // 4 kali salah masih memberi sisa percobaan
        for ($i = 1; $i < CamabaOtpService::MAX_ATTEMPTS; $i++) {
            $resp = $this->post(route('pmb.login.otp.verify'), [
                'email' => $this->camaba->email,
                'otp'   => $wrongOtp,
            ]);

            $resp->assertRedirect(route('pmb.login'));
            $resp->assertSessionHasErrors(['otp']);
            $this->assertGuest();
        }

        // Percobaan salah ke-5 menghanguskan OTP
        $fifthResp = $this->post(route('pmb.login.otp.verify'), [
            'email' => $this->camaba->email,
            'otp'   => $wrongOtp,
        ]);
        $fifthResp->assertRedirect(route('pmb.login'));
        $fifthResp->assertSessionHasErrors(['otp']);
        $this->assertGuest();

        // OTP yang benar sekalipun sekarang harus ditolak karena sudah hangus
        $afterLockResp = $this->post(route('pmb.login.otp.verify'), [
            'email' => $this->camaba->email,
            'otp'   => $capturedOtp,
        ]);
        $afterLockResp->assertRedirect(route('pmb.login'));
        $afterLockResp->assertSessionHasErrors(['otp']);
        $this->assertGuest();
    }

    public function test_expired_otp_is_rejected(): void
    {
        Mail::fake();

        $this->post(route('pmb.login.otp.send'), [
            'email' => $this->camaba->email,
        ])->assertRedirect(route('pmb.login'));

        $capturedOtp = null;
        Mail::assertSent(CamabaLoginOtpMail::class, function (CamabaLoginOtpMail $mail) use (&$capturedOtp) {
            $capturedOtp = $mail->otpCode;
            return true;
        });

        $this->travel(CamabaOtpService::EXPIRES_MINUTES + 1)->minutes();

        $response = $this->post(route('pmb.login.otp.verify'), [
            'email' => $this->camaba->email,
            'otp'   => $capturedOtp,
        ]);

        $response->assertRedirect(route('pmb.login'));
        $response->assertSessionHasErrors(['otp']);
        $this->assertGuest();
    }

    public function test_otp_is_single_use_and_cannot_be_reused_after_logout(): void
    {
        Mail::fake();

        $this->post(route('pmb.login.otp.send'), [
            'email' => $this->camaba->email,
        ]);

        $capturedOtp = null;
        Mail::assertSent(CamabaLoginOtpMail::class, function (CamabaLoginOtpMail $mail) use (&$capturedOtp) {
            $capturedOtp = $mail->otpCode;
            return true;
        });

        // Login pertama berhasil
        $this->post(route('pmb.login.otp.verify'), [
            'email' => $this->camaba->email,
            'otp'   => $capturedOtp,
        ])->assertRedirect(route('pmb.akun.dashboard'));
        $this->assertAuthenticatedAs($this->camaba);

        // Logout lalu coba pakai OTP yang sama lagi
        $this->post(route('pmb.logout'));
        $this->assertGuest();

        $reuseResponse = $this->post(route('pmb.login.otp.verify'), [
            'email' => $this->camaba->email,
            'otp'   => $capturedOtp,
        ]);

        $reuseResponse->assertRedirect(route('pmb.login'));
        $reuseResponse->assertSessionHasErrors(['otp']);
        $this->assertGuest();
    }

    public function test_otp_is_bound_strictly_to_requesting_email(): void
    {
        Mail::fake();

        User::create([
            'name'     => 'Budi Santoso',
            'email'    => 'budi.camaba@example.com',
            'role'     => 'Camaba',
            'status'   => 'Aktif',
            'password' => Hash::make('SecretPass123!'),
        ]);

        $this->post(route('pmb.login.otp.send'), [
            'email' => $this->camaba->email,
        ]);

        $capturedOtp = null;
        Mail::assertSent(CamabaLoginOtpMail::class, function (CamabaLoginOtpMail $mail) use (&$capturedOtp) {
            $capturedOtp = $mail->otpCode;
            return true;
        });

        // Akun lain mencoba memakai OTP milik Siti
        $response = $this->post(route('pmb.login.otp.verify'), [
            'email' => 'budi.camaba@example.com',
            'otp'   => $capturedOtp,
        ]);

        $response->assertRedirect(route('pmb.login'));
        $response->assertSessionHasErrors(['otp']);
        $this->assertGuest();
    }

    public function test_resend_otp_cooldown_blocks_rapid_requests_and_allows_after_expiry(): void
    {
        Mail::fake();

        $this->post(route('pmb.login.otp.send'), [
            'email' => $this->camaba->email,
        ])->assertRedirect(route('pmb.login'));

        Mail::assertSentCount(1);

        // Request ulang langsung harus ditolak oleh cooldown
        $blocked = $this->post(route('pmb.login.otp.send'), [
            'email' => $this->camaba->email,
        ]);

        $blocked->assertRedirect(route('pmb.login'));
        $blocked->assertSessionHasErrors(['email']);
        Mail::assertSentCount(1);

        // Setelah cooldown berlalu, kirim ulang berhasil
        $this->travel(CamabaOtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();

        $allowed = $this->post(route('pmb.login.otp.send'), [
            'email' => $this->camaba->email,
        ]);

        $allowed->assertRedirect(route('pmb.login'));
        $allowed->assertSessionHasNoErrors();
        Mail::assertSentCount(2);
    }

    public function test_unregistered_invalid_inactive_and_non_camaba_emails_are_handled_safely(): void
    {
        Mail::fake();

        // 1. Email tidak terdaftar
        $this->post(route('pmb.login.otp.send'), [
            'email' => 'tidak.ada@example.com',
        ])->assertRedirect(route('pmb.login'))
          ->assertSessionHasErrors(['email']);

        // 2. Format bukan email (misal username)
        $this->post(route('pmb.login.otp.send'), [
            'email' => 'sitiais',
        ])->assertRedirect(route('pmb.login'))
          ->assertSessionHasErrors(['email']);

        // 3. Role CRM non-Camaba
        User::create([
            'name'     => 'Staff Sales',
            'email'    => 'sales@cic.ac.id',
            'role'     => 'Sales',
            'status'   => 'Aktif',
            'password' => Hash::make('SecretPass123!'),
        ]);
        $this->post(route('pmb.login.otp.send'), [
            'email' => 'sales@cic.ac.id',
        ])->assertRedirect(route('pmb.login'))
          ->assertSessionHasErrors(['email']);

        // 4. Camaba Nonaktif
        User::create([
            'name'     => 'Camaba Nonaktif',
            'email'    => 'nonaktif@example.com',
            'role'     => 'Camaba',
            'status'   => 'Nonaktif',
            'password' => Hash::make('SecretPass123!'),
        ]);
        $this->post(route('pmb.login.otp.send'), [
            'email' => 'nonaktif@example.com',
        ])->assertRedirect(route('pmb.login'))
          ->assertSessionHasErrors(['email']);

        Mail::assertNothingSent();
        $this->assertGuest();
    }

    public function test_alternative_password_login_uses_email_not_username_and_works_alongside_otp(): void
    {
        Mail::fake();

        // Login menggunakan username harus ditolak (wajib format email)
        $usernameAttempt = $this->post(route('pmb.login.post'), [
            'email'      => 'sitiais',
            'password'   => 'SecretPass123!',
            'login_mode' => 'password',
        ]);
        $usernameAttempt->assertRedirect(route('pmb.login'));
        $usernameAttempt->assertSessionHasErrors(['email']);
        $usernameAttempt->assertSessionHas('login_mode', 'password');
        $this->assertGuest();

        // Password salah ditolak dan tetap di mode password
        $wrongPassword = $this->post(route('pmb.login.post'), [
            'email'      => $this->camaba->email,
            'password'   => 'WrongPassword!',
            'login_mode' => 'password',
        ]);
        $wrongPassword->assertRedirect(route('pmb.login'));
        $wrongPassword->assertSessionHasErrors(['email']);
        $wrongPassword->assertSessionHas('login_mode', 'password');
        $this->assertGuest();

        // Login password valid berhasil & redirect ke dashboard CAMABA
        $validPassword = $this->post(route('pmb.login.post'), [
            'email'      => $this->camaba->email,
            'password'   => 'SecretPass123!',
            'login_mode' => 'password',
        ]);
        $validPassword->assertRedirect(route('pmb.akun.dashboard'));
        $this->assertAuthenticatedAs($this->camaba);

        // Logout lalu login ulang menggunakan metode OTP dengan akun yang sama
        $this->post(route('pmb.logout'));
        $this->assertGuest();

        $this->post(route('pmb.login.otp.send'), [
            'email' => $this->camaba->email,
        ])->assertRedirect(route('pmb.login'));

        $capturedOtp = null;
        Mail::assertSent(CamabaLoginOtpMail::class, function (CamabaLoginOtpMail $mail) use (&$capturedOtp) {
            $capturedOtp = $mail->otpCode;
            return true;
        });

        $this->post(route('pmb.login.otp.verify'), [
            'email' => $this->camaba->email,
            'otp'   => $capturedOtp,
        ])->assertRedirect(route('pmb.akun.dashboard'));
        $this->assertAuthenticatedAs($this->camaba);
    }

    public function test_otp_email_renders_and_sends_through_log_mailer(): void
    {
        config(['mail.default' => 'log']);

        $mail = new CamabaLoginOtpMail('654321', $this->camaba->name, 5);
        $rendered = $mail->render();

        $this->assertStringContainsString('654321', $rendered);
        $this->assertStringContainsString('Siti Aisyah', $rendered);
        $this->assertStringContainsString('5 menit', $rendered);

        // Kirim langsung lewat endpoint tanpa Mail::fake untuk memastikan tidak ada exception transport log
        $response = $this->post(route('pmb.login.otp.send'), [
            'email' => $this->camaba->email,
        ]);

        $response->assertRedirect(route('pmb.login'));
        $response->assertSessionHas('otp_step', 'verify');
        $response->assertSessionHasNoErrors();
    }
}

