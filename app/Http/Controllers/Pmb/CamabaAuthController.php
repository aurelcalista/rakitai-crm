<?php

namespace App\Http\Controllers\Pmb;

use App\Http\Controllers\Controller;
use App\Mail\CamabaLoginOtpMail;
use App\Models\Prospek;
use App\Models\Sekolah;
use App\Models\User;
use App\Services\CamabaOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CamabaAuthController extends Controller
{
    /**
     * Tampilkan formulir login CAMABA.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            if (strtolower(Auth::user()->role) === 'camaba') {
                return redirect()->route('pmb.akun.dashboard');
            }
            return redirect()->route('dashboard');
        }

        return view('pmb.auth.login');
    }

    /**
     * Kirim kode OTP ke email CAMABA terdaftar (metode utama).
     */
    public function sendOtp(Request $request, CamabaOtpService $otpService)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
        ]);

        $rawEmail = (string) $request->input('email', '');
        $email = $otpService->normalizeEmail($rawEmail);

        if ($validator->fails()) {
            return redirect()->route('pmb.login')
                ->withErrors($validator)
                ->withInput(['email' => $rawEmail, 'login_mode' => 'otp', 'otp_step' => 'request']);
        }

        $user = $this->findCamabaByEmail($email);

        if (!$user) {
            return redirect()->route('pmb.login')
                ->withErrors(['email' => 'Email tidak terdaftar sebagai akun CAMABA.'])
                ->withInput(['email' => $email, 'login_mode' => 'otp', 'otp_step' => 'request']);
        }

        if ($user->status !== 'Aktif') {
            return redirect()->route('pmb.login')
                ->withErrors(['email' => 'Akun Anda saat ini dinonaktifkan. Silakan hubungi Sekretariat PMB UCIC.'])
                ->withInput(['email' => $email, 'login_mode' => 'otp', 'otp_step' => 'request']);
        }

        $issued = $otpService->issue($email);

        if (!$issued['ok']) {
            $remaining = $issued['cooldown_remaining'];
            $step = $otpService->hasActiveOtp($email) ? 'verify' : 'request';

            return redirect()->route('pmb.login')
                ->withErrors(['email' => "Mohon tunggu {$remaining} detik sebelum mengirim ulang kode OTP."])
                ->withInput(['email' => $email, 'login_mode' => 'otp', 'otp_step' => $step])
                ->with('otp_email', $email)
                ->with('otp_cooldown', $remaining);
        }

        try {
            Mail::to($user->email)->send(new CamabaLoginOtpMail(
                otpCode: $issued['otp'],
                recipientName: $user->name,
                expiresInMinutes: $issued['expires_minutes'],
            ));
        } catch (\Throwable $e) {
            $otpService->clearIssuedOtp($email);
            report($e);

            return redirect()->route('pmb.login')
                ->withErrors(['email' => 'Gagal mengirim kode OTP ke email. Silakan coba lagi atau gunakan Masuk dengan Password.'])
                ->withInput(['email' => $email, 'login_mode' => 'otp', 'otp_step' => 'request']);
        }

        return redirect()->route('pmb.login')
            ->with('success', "Kode OTP telah dikirim ke {$email}. Berlaku selama {$issued['expires_minutes']} menit.")
            ->with('login_mode', 'otp')
            ->with('otp_step', 'verify')
            ->with('otp_email', $email)
            ->with('otp_cooldown', $issued['cooldown_remaining']);
    }

    /**
     * Verifikasi kode OTP dan selesaikan proses login CAMABA.
     */
    public function verifyOtp(Request $request, CamabaOtpService $otpService)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp'   => 'required|digits:6',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
            'otp.required'   => 'Kode OTP wajib diisi.',
            'otp.digits'     => 'Kode OTP harus terdiri dari 6 digit angka.',
        ]);

        $rawEmail = (string) $request->input('email', '');
        $email = $otpService->normalizeEmail($rawEmail);

        if ($validator->fails()) {
            $step = $otpService->hasActiveOtp($email) ? 'verify' : 'request';

            return redirect()->route('pmb.login')
                ->withErrors($validator)
                ->withInput(['email' => $email ?: $rawEmail, 'login_mode' => 'otp', 'otp_step' => $step])
                ->with('otp_email', $email ?: $rawEmail)
                ->with('otp_cooldown', $email ? $otpService->getCooldownRemaining($email) : 0);
        }

        $user = $this->findCamabaByEmail($email);

        if (!$user) {
            return redirect()->route('pmb.login')
                ->withErrors(['otp' => 'Email atau kode OTP tidak valid.'])
                ->withInput(['email' => $email, 'login_mode' => 'otp', 'otp_step' => 'request']);
        }

        if ($user->status !== 'Aktif') {
            return redirect()->route('pmb.login')
                ->withErrors(['otp' => 'Akun Anda saat ini dinonaktifkan. Silakan hubungi Sekretariat PMB UCIC.'])
                ->withInput(['email' => $email, 'login_mode' => 'otp', 'otp_step' => 'request']);
        }

        $verification = $otpService->verify($email, (string) $request->input('otp'));

        if (!$verification['ok']) {
            return redirect()->route('pmb.login')
                ->withErrors(['otp' => $verification['message']])
                ->withInput(['email' => $email, 'login_mode' => 'otp', 'otp_step' => 'verify'])
                ->with('otp_email', $email)
                ->with('otp_cooldown', $otpService->getCooldownRemaining($email));
        }

        return $this->completeLogin($request, $user);
    }

    /**
     * Proses autentikasi CAMABA menggunakan email dan password (metode alternatif).
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('pmb.login')
                ->withErrors($validator)
                ->withInput($request->only('email') + ['login_mode' => 'password'])
                ->with('login_mode', 'password');
        }

        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');

        $user = $this->findCamabaByEmail($email);

        if ($user && Hash::check($password, $user->password)) {
            if ($user->status !== 'Aktif') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('pmb.login')
                    ->withErrors([
                        'email' => 'Akun Anda saat ini dinonaktifkan. Silakan hubungi Sekretariat PMB UCIC.',
                    ])
                    ->withInput(['email' => $email, 'login_mode' => 'password'])
                    ->with('login_mode', 'password');
            }

            return $this->completeLogin($request, $user);
        }

        return redirect()->route('pmb.login')
            ->withErrors([
                'email' => 'Email atau password yang Anda masukkan tidak sesuai.',
            ])
            ->withInput(['email' => $email, 'login_mode' => 'password'])
            ->with('login_mode', 'password');
    }

    private function findCamabaByEmail(string $email): ?User
    {
        return User::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])
            ->whereRaw('LOWER(role) = ?', ['camaba'])
            ->first();
    }

    private function completeLogin(Request $request, User $user)
    {
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('pmb.akun.dashboard')
            ->with('success', 'Selamat datang! Anda berhasil masuk ke Portal PMB UCIC.');
    }

    /**
     * Tampilkan formulir pendaftaran akun CAMABA.
     */
    public function showRegisterForm()
    {
        if (Auth::check()) {
            if (strtolower(Auth::user()->role) === 'camaba') {
                return redirect()->route('pmb.akun.dashboard');
            }
            return redirect()->route('dashboard');
        }

        return view('pmb.auth.register');
    }

    /**
     * Proses pendaftaran akun CAMABA & sinkronisasi dengan data Prospek PMB.
     */
    public function register(Request $request)
    {
        // Standardize WhatsApp input into canonical +628xxxxxxxx format
        if ($request->has('whatsapp') && $request->filled('whatsapp')) {
            $request->merge([
                'whatsapp' => \App\Support\WhatsAppHelper::prepareForValidation($request->input('whatsapp'))
            ]);
        }

        $validated = $request->validate([
            'name'                  => 'required|string|min:2|max:255',
            'email'                 => 'required|email|max:255',
            'whatsapp'              => ['required', 'string', 'regex:/^\+628[0-9]{8,12}$/'],
            'asal_sekolah'          => 'required|string|min:2|max:255',
            'password'              => ['required', 'confirmed', Password::min(8)],
            'minat_kuliah'          => 'nullable|string|in:ya,tidak',
            'minat_miniclass'       => 'nullable|string|in:ya,tidak',
        ], [
            'name.required'         => 'Nama lengkap wajib diisi.',
            'name.min'              => 'Nama lengkap minimal 2 karakter.',
            'email.required'        => 'Email aktif wajib diisi.',
            'email.email'           => 'Format email tidak valid.',
            'whatsapp.required'     => 'Nomor WhatsApp / HP wajib diisi.',
            'whatsapp.regex'        => 'Nomor WhatsApp wajib menggunakan format Indonesia (+62) yang valid (contoh: +6281234567890).',
            'asal_sekolah.required' => 'Asal sekolah wajib diisi.',
            'asal_sekolah.min'      => 'Asal sekolah minimal 2 karakter.',
            'password.required'     => 'Password wajib diisi.',
            'password.min'          => 'Password minimal 8 karakter.',
            'password.confirmed'    => 'Konfirmasi password tidak cocok.',
        ]);

        $emailClean = strtolower(trim($validated['email']));

        // Cek apakah sudah ada akun User aktif dengan email ini
        $existingUser = User::where('email', $emailClean)->first();
        if ($existingUser) {
            return back()->withErrors([
                'email' => 'Email ini sudah terdaftar. Silakan masuk menggunakan akun Anda.',
            ])->withInput($request->except(['password', 'password_confirmation']));
        }

        $formattedPhone = \App\Support\WhatsAppHelper::formatForDatabase($validated['whatsapp']);
        $digits = \App\Support\WhatsAppHelper::normalizeForDisplay($validated['whatsapp']);
        $possiblePhones = array_unique(array_filter([
            $formattedPhone,
            $validated['whatsapp'],
            '0' . $digits,
            '62' . $digits,
            '+62' . $digits,
            $digits,
        ]));

        // Cek apakah nomor WhatsApp sudah terdaftar pada akun User lain
        $existingPhoneUser = User::whereIn('phone', $possiblePhones)->first();
        if ($existingPhoneUser) {
            return back()->withErrors([
                'whatsapp' => 'Nomor WhatsApp / HP ini sudah terdaftar. Silakan gunakan nomor lain atau masuk ke akun Anda.',
            ])->withInput($request->except(['password', 'password_confirmation']));
        }

        // Cek apakah nomor WhatsApp sudah digunakan oleh akun calon mahasiswa (prospek) lain
        $existingPhoneProspek = Prospek::where(function ($q) use ($possiblePhones) {
            $q->whereIn('whatsapp', $possiblePhones)
              ->orWhereIn('pic_phone', $possiblePhones);
        })->whereNotNull('user_id')->first();

        if ($existingPhoneProspek) {
            return back()->withErrors([
                'whatsapp' => 'Nomor WhatsApp / HP ini sudah terdaftar pada akun calon mahasiswa lain.',
            ])->withInput($request->except(['password', 'password_confirmation']));
        }

        // Jalankan transaksi database untuk integritas data User & Prospek
        $user = DB::transaction(function () use ($validated, $emailClean, $formattedPhone, $possiblePhones) {
            // 1. Buat User Camaba
            $user = User::create([
                'name'     => trim($validated['name']),
                'email'    => $emailClean,
                'phone'    => $formattedPhone,
                'role'     => 'Camaba',
                'status'   => 'Aktif',
                'password' => Hash::make($validated['password']),
            ]);

            // 2. Hubungkan atau cari sekolah jika cocok, atau buat catatan baru
            $sekolah = Sekolah::where('nama', 'like', trim($validated['asal_sekolah']))->first();
            $sekolahId = $sekolah?->id;
            $wilayahId = $sekolah?->wilayah_id;

            // 3. Pencocokan Data Prospek CRM Saat Registrasi PMB (Anti-Duplikasi)
            // Periksa email dan nomor HP yang digunakan terhadap data prospek CRM yang belum terhubung akun (user_id IS NULL)
            $candidateByEmail = Prospek::whereNull('user_id')
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->whereRaw('LOWER(email) = ?', [$emailClean])
                ->first();

            $candidateByPhone = Prospek::whereNull('user_id')
                ->where(function ($q) use ($possiblePhones) {
                    $q->whereIn('whatsapp', $possiblePhones)
                      ->orWhereIn('pic_phone', $possiblePhones);
                })
                ->first();

            $prospek = null;
            if ($candidateByEmail && $candidateByPhone) {
                if ($candidateByEmail->id === $candidateByPhone->id) {
                    // Match sempurna: email dan nomor HP menunjuk ke prospek yang sama
                    $prospek = $candidateByEmail;
                } else {
                    // Jika menunjuk ke dua prospek berbeda, utamakan email namun jangan gabungkan sembarangan
                    $prospek = $candidateByEmail;
                }
            } elseif ($candidateByEmail) {
                $prospek = $candidateByEmail;
            } elseif ($candidateByPhone) {
                $prospek = $candidateByPhone;
            }

            $minatKuliah = $validated['minat_kuliah'] ?? 'ya';
            $minatMiniclass = $validated['minat_miniclass'] ?? 'tidak';

            if ($prospek) {
                // Hubungkan akun PMB dengan ID prospek yang sudah ada (pertahankan ID prospek lama)
                $prospek->user_id = $user->id;
                if (empty($prospek->email)) {
                    $prospek->email = $emailClean;
                }
                if (empty($prospek->whatsapp)) {
                    $prospek->whatsapp = $formattedPhone;
                    $prospek->pic_phone = $formattedPhone;
                }
                // Pertahankan informasi Sales, CS, sekolah, dan catatan lama (JANGAN OVERWRITE / AUTO ASSIGN)
                if (!$prospek->sekolah_id && $sekolahId) {
                    $prospek->sekolah_id = $sekolahId;
                }
                if (!$prospek->wilayah_id && $wilayahId) {
                    $prospek->wilayah_id = $wilayahId;
                }
                $prospek->minat_kuliah = $minatKuliah;
                $prospek->minat_miniclass = $minatMiniclass;
                // Status prospek CRM TIDAK diubah otomatis saat registrasi (misal HOT PROSPEK tetap HOT PROSPEK)
                $prospek->save();

                // Catat riwayat di ProspekTimeline
                \App\Models\ProspekTimeline::create([
                    'prospek_id' => $prospek->id,
                    'user_id'    => $user->id,
                    'title'      => 'Akun Portal PMB Terhubung',
                    'notes'      => 'Calon mahasiswa berhasil mendaftarkan akun Portal PMB dan ditautkan secara otomatis ke data prospek CRM.',
                    'time'       => now(),
                ]);
            } else {
                // Calon mahasiswa langsung mendaftar via PMB (tanpa prospek CRM sebelumnya)
                // Generate nomor registrasi baru PMB
                $year = date('Y');
                $nextNum = Prospek::whereYear('created_at', $year)->count() + 1;
                $noRegistrasi = 'PMB-' . $year . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

                // Dapatkan Tahun Akademik aktif secara dinamis
                $activeTa = \App\Models\TahunAkademik::getAktif();
                $gelombangName = $activeTa ? 'Gelombang 1 - ' . $activeTa->nama : 'Gelombang 1';

                // Buat record PMB pendaftar tanpa menetapkan penanggung jawab Sales/CS secara otomatis
                $prospek = Prospek::create([
                    'user_id'          => $user->id,
                    'academic_year_id' => $activeTa?->id,
                    'name'             => trim($validated['name']),
                    'email'            => $emailClean,
                    'whatsapp'         => $formattedPhone,
                    'pic_phone'        => $formattedPhone,
                    'pic'              => trim($validated['name']),
                    'type'             => 'Individu',
                    'category'         => 'PMB Mandiri',
                    'source'           => 'Website PMB',
                    'status'           => 'BARU',
                    'status_formulir'  => 'Belum Isi',
                    'status_berkas'    => 'Belum Upload',
                    'status_ujian'     => 'Belum Mengerjakan',
                    'stage_number'     => 1,
                    'gelombang'        => $gelombangName,
                    'no_registrasi'    => $noRegistrasi,
                    'sekolah_id'       => $sekolahId,
                    'wilayah_id'       => $wilayahId,
                    'cs_id'            => null,   // JANGAN tetapkan Sales/CS secara otomatis
                    'sales_id'         => null,  // JANGAN tetapkan Sales/CS secara otomatis
                    'minat_kuliah'     => $minatKuliah,
                    'minat_miniclass'  => $minatMiniclass,
                    'notes'            => 'Pendaftaran mandiri via Portal PMB Online. Asal sekolah: ' . trim($validated['asal_sekolah']),
                ]);
            }

            return $user;
        });

        // Setelah registrasi sukses, arahkan ke halaman login terlebih dahulu dengan notifikasi sukses
        return redirect()->route('pmb.login')
            ->with('success', 'Pendaftaran akun berhasil! Silakan masuk menggunakan email dan password yang telah Anda daftarkan.')
            ->with('registered_email', $emailClean);
    }

    /**
     * Logout akun CAMABA.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('pmb.portal')
            ->with('info', 'Anda telah berhasil keluar dari Portal PMB.');
    }
}
