<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
            'role'     => 'nullable|string',
        ]);

        $email = strtolower(trim($request->input('email')));
        $password = $request->input('password');
        $selectedRole = $request->input('role');

        $query = \App\Models\User::where(function($q) use ($email) {
            $q->where('email', $email)
              ->orWhere('username', $email);
        });

        if (!empty($selectedRole) && $selectedRole !== 'all') {
            $query->whereRaw('LOWER(role) = ?', [strtolower($selectedRole)]);
        }

        $user = $query->first();

        if ($user && \Illuminate\Support\Facades\Hash::check($password, $user->password)) {
            if ($user->status !== 'Aktif') {
                \Illuminate\Support\Facades\Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $msg = $user->status === 'Pending' 
                    ? 'Akun Anda masih berstatus Pending dan sedang menunggu persetujuan Admin.' 
                    : 'Akun Anda telah dinonaktifkan atau ditolak oleh Admin.';

                return back()->withErrors(['email' => $msg])->onlyInput('email');
            }

            \Illuminate\Support\Facades\Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            $role = strtolower($user->role);

            return match($role) {
                'admin' => redirect()->route('dashboard.admin'),
                'hm'    => redirect()->route('dashboard.hm'),
                'spv'   => redirect()->route('dashboard.spv'),
                'cs'    => redirect()->route('dashboard.cs'),
                'eo'    => redirect()->route('dashboard.eo'),
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
        return redirect('/');
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

