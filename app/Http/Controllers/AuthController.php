<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if ($user->status !== 'Aktif') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $msg = $user->status === 'Pending' 
                    ? 'Akun Anda masih berstatus Pending dan sedang menunggu persetujuan Admin.' 
                    : 'Akun Anda telah dinonaktifkan atau ditolak oleh Admin.';

                return back()->withErrors(['email' => $msg])->onlyInput('email');
            }

            $request->session()->regenerate();

            $role = strtolower($user->role);

            return match($role) {
                'admin' => redirect()->route('dashboard.admin'),
                'hm'    => redirect()->route('dashboard.hm'),
                'spv'   => redirect()->route('dashboard.spv'),
                'cs'    => redirect()->route('dashboard.cs'),
                default => redirect()->route('dashboard.sales'), // Sales
            };
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan tidak sesuai.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    /**
     * Tampilkan Halaman Registrasi Mandiri Sales.
     */
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    /**
     * Proses Registrasi Mandiri Sales (FLOW A).
     * Account created with status = 'Pending', kode = null.
     * Requires Admin Approval before account is active and code is generated.
     */
    public function register(Request $request)
    {
        $rawEmail = trim((string) $request->input('email'));
        if (!empty($rawEmail) && !str_contains($rawEmail, '@')) {
            $rawEmail .= '@cic.ac.id';
            $request->merge(['email' => $rawEmail]);
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
                'regex:/^[a-zA-Z0-9._%+-]+@cic\.ac\.id$/i',
            ],
            'phone'    => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'email.regex' => 'Email wajib menggunakan domain @cic.ac.id.',
        ]);

        $user = \App\Models\User::create([
            'name'     => $validated['name'],
            'email'    => strtolower($validated['email']),
            'phone'    => $validated['phone'] ?? '-',
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'role'     => 'Sales',
            'jabatan'  => 'Sales',
            'status'   => 'Pending',
            'kode'     => null, // Will be generated upon Admin approval
        ]);

        return redirect()->route('login')->with('success', "Pendaftaran Sales atas nama {$user->name} berhasil! Akun Anda berstatus 'Pending' dan sedang menunggu persetujuan (ACC) dari Admin. Kode Sales akan dibuat otomatis setelah disetujui.");
    }
}

