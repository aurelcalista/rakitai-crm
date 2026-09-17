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
            $request->session()->regenerate();

            $role = strtolower(Auth::user()->role);

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
}

