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

            return redirect()->intended(route('pmb.akun.dashboard'));
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
        $validated = $request->validate([
            'name'                  => 'required|string|min:2|max:255',
            'email'                 => 'required|email|max:255',
            'whatsapp'              => ['required', 'string', 'regex:/^(\+62|62|0)[0-9]{8,14}$/'],
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
            'whatsapp.regex'        => 'Format nomor WhatsApp tidak valid (contoh: 081234567890).',
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

            // 3. Hubungkan dengan Prospek CRM jika pendaftar sudah ada sebelumnya di CRM via email / phone
            $prospek = Prospek::where(function ($q) use ($emailClean, $formattedPhone) {
                $q->where('email', $emailClean)
                  ->orWhere('whatsapp', $formattedPhone)
                  ->orWhere('pic_phone', $formattedPhone);
            })->first();

            $minatKuliah = $validated['minat_kuliah'] ?? 'ya';
            $minatMiniclass = $validated['minat_miniclass'] ?? 'tidak';

            if ($prospek) {
                // Link existing prospek to newly created user
                $prospek->user_id = $user->id;
                $prospek->email = $emailClean;
                if (empty($prospek->whatsapp)) {
                    $prospek->whatsapp = $formattedPhone;
                }
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

                // Buat record Prospek baru secara aman
                $prospek = Prospek::create([
                    'user_id'         => $user->id,
                    'name'            => trim($validated['name']),
                    'email'           => $emailClean,
                    'whatsapp'        => $formattedPhone,
                    'pic_phone'       => $formattedPhone,
                    'pic'             => trim($validated['name']),
                    'type'            => 'Individu',
                    'category'        => 'PMB Online',
                    'source'          => 'Website PMB',
                    'status'          => 'BARU',
                    'status_formulir' => 'Belum Isi',
                    'status_berkas'   => 'Belum Upload',
                    'status_ujian'    => 'Belum Mengerjakan',
                    'stage_number'    => 1,
                    'gelombang'       => 'Gelombang 1',
                    'no_registrasi'   => $noRegistrasi,
                    'sekolah_id'      => $sekolahId,
                    'minat_kuliah'    => $minatKuliah,
                    'minat_miniclass' => $minatMiniclass,
                    'notes'           => 'Pendaftaran mandiri via Portal PMB Online. Asal sekolah: ' . trim($validated['asal_sekolah']),
                ]);
            }

            return $user;
        });

        // Login otomatis setelah pendaftaran sukses
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('pmb.akun.dashboard')
            ->with('success', 'Selamat datang! Akun PMB Anda berhasil didaftarkan.');
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
