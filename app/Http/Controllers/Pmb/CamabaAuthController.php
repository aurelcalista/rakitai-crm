<?php

namespace App\Http\Controllers\Pmb;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
     * Proses autentikasi CAMABA.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
        ], [
            'email.required'    => 'Email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $rawInput = trim($request->input('email'));
        $email = strtolower($rawInput);
        $password = $request->input('password');

        // Cari user dengan role Camaba berdasarkan email atau username
        $user = User::where(function ($q) use ($email, $rawInput) {
            $q->where('email', $email)
              ->orWhere('username', $email)
              ->orWhere('kode', $rawInput);
        })
        ->whereRaw('LOWER(role) = ?', ['camaba'])
        ->first();

        if ($user && Hash::check($password, $user->password)) {
            if ($user->status !== 'Aktif') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Akun Anda saat ini dinonaktifkan. Silakan hubungi Sekretariat PMB UCIC.',
                ])->onlyInput('email');
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->route('pmb.akun.dashboard')
                ->with('success', 'Selamat datang! Anda berhasil masuk ke Portal PMB UCIC.');
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan tidak sesuai.',
        ])->onlyInput('email');
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
        $user = DB::transaction(function () use ($validated, $emailClean, $formattedPhone) {
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

            // 3. Hubungkan dengan Prospek CRM HANYA jika ada pendaftar CRM yang belum memiliki akun (user_id IS NULL) dengan email yang sama DAN BELUM MEMILIKI TRANSAKSI
            $prospek = Prospek::where('email', $emailClean)
                ->whereNull('user_id')
                ->first();

            if ($prospek && $prospek->transaksis()->exists()) {
                // Prospek lama yang sudah memiliki transaksi tidak boleh ditautkan ke akun pendaftar baru
                $prospek = null;
            }

            $minatKuliah = $validated['minat_kuliah'] ?? 'ya';
            $minatMiniclass = $validated['minat_miniclass'] ?? 'tidak';

            if ($prospek) {
                // Hubungkan prospek CRM yang belum bertuan ke akun user baru ini
                $prospek->user_id = $user->id;
                $prospek->name = trim($validated['name']);
                $prospek->whatsapp = $formattedPhone;
                $prospek->pic_phone = $formattedPhone;
                if (!$prospek->sekolah_id && $sekolahId) {
                    $prospek->sekolah_id = $sekolahId;
                }
                $prospek->minat_kuliah = $minatKuliah;
                $prospek->minat_miniclass = $minatMiniclass;
                $prospek->save();
            } else {
                // Generate nomor registrasi baru PMB jika belum ada
                $year = date('Y');
                $nextNum = Prospek::whereYear('created_at', $year)->count() + 1;
                $noRegistrasi = 'PMB-' . $year . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

                // Dapatkan Tahun Akademik aktif secara dinamis
                $activeTa = \App\Models\TahunAkademik::getAktif();
                $gelombangName = $activeTa ? 'Gelombang 1 - ' . $activeTa->nama : 'Gelombang 1';

                // Buat record Prospek baru secara aman
                $prospek = Prospek::create([
                    'user_id'          => $user->id,
                    'academic_year_id' => $activeTa?->id,
                    'name'             => trim($validated['name']),
                    'email'            => $emailClean,
                    'whatsapp'         => $formattedPhone,
                    'pic_phone'        => $formattedPhone,
                    'pic'              => trim($validated['name']),
                    'type'             => 'Individu',
                    'category'         => 'PMB Online',
                    'source'           => 'Website PMB',
                    'status'           => 'BARU',
                    'status_formulir'  => 'Belum Isi',
                    'status_berkas'    => 'Belum Upload',
                    'status_ujian'     => 'Belum Mengerjakan',
                    'stage_number'     => 1,
                    'gelombang'        => $gelombangName,
                    'no_registrasi'    => $noRegistrasi,
                    'sekolah_id'       => $sekolahId,
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
