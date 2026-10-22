<?php

namespace App\Http\Controllers\Pmb;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PmbAdminController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = auth()->user();
        if (strtolower($authUser->role ?? '') === 'cs') {
            abort(403, 'Akses Ditolak. Role Customer Service (CS) tidak memiliki izin mengelola Data Admin.');
        }

        $query = User::whereIn('role', ['Admin', 'HM']);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $adminList = $query->orderBy('created_at', 'asc')->paginate(15)->withQueryString();

        return view('pmb.admin.index', compact('adminList'));
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();
        if (strtolower($authUser->role ?? '') === 'cs') {
            abort(403, 'Role Customer Service (CS) tidak diizinkan membuat akun admin.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'role' => 'required|in:Admin,HM',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.required' => 'Nama admin wajib diisi.',
            'email.required' => 'Email admin wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar di sistem. Gunakan email lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = 'Aktif';

        User::create($validated);

        return redirect()->back()->with('success', "Akun admin {$validated['name']} berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $authUser = auth()->user();
        if (strtolower($authUser->role ?? '') === 'cs') {
            abort(403, 'Role Customer Service (CS) tidak diizinkan mengubah data admin.');
        }

        $admin = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $admin->id,
            'role' => 'required|in:Admin,HM',
            'status' => 'required|in:Aktif,Nonaktif',
            'password' => 'nullable|string|min:6|confirmed',
        ], [
            'name.required' => 'Nama admin wajib diisi.',
            'email.required' => 'Email admin wajib diisi.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $admin->update($validated);

        return redirect()->back()->with('success', "Data admin {$admin->name} berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $authUser = auth()->user();
        if (strtolower($authUser->role ?? '') === 'cs') {
            abort(403, 'Role Customer Service (CS) tidak diizinkan menghapus data admin.');
        }

        $admin = User::findOrFail($id);

        // 1. Prevent self-deletion
        if ($admin->id === $authUser->id) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri saat sedang login.');
        }

        // 2. Protect primary super admin account
        if ($admin->id === 1 || in_array($admin->email, ['admin@cic.ac.id', 'admin@ucic.ac.id'])) {
            return redirect()->back()->with('error', 'Akun Administrator Utama UCIC dilindungi sistem dan tidak dapat dihapus.');
        }

        $nama = $admin->name;
        $admin->delete();

        return redirect()->back()->with('success', "Akun admin {$nama} berhasil dihapus.");
    }
}
