<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::latest()->get()->map(function ($user) {
            $user->avatar = strtoupper(substr($user->name, 0, 2));
            return $user;
        });

        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'role' => 'required|in:Admin,HM,SPV,Sales,CS',
            'status' => 'required|in:Aktif,Nonaktif',
            'password' => 'required|string|confirmed',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->back()->with('success', 'Akun pengguna baru berhasil dibuat!');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'required|string|max:20',
            'role' => 'required|in:Admin,HM,SPV,Sales,CS',
            'status' => 'required|in:Aktif,Nonaktif',
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Data pengguna berhasil diperbarui!');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->back()->with('success', 'Pengguna berhasil dihapus!');
    }

    public function resetPassword(User $user)
    {
        $user->update([
            'password' => Hash::make('password123')
        ]);
        return redirect()->back()->with('success', 'Password berhasil di-reset ke "password123"');
    }

    public function toggleStatus(User $user)
    {
        $user->update([
            'status' => $user->status === 'Aktif' ? 'Nonaktif' : 'Aktif'
        ]);
        return redirect()->back()->with('success', "Status pengguna {$user->name} berhasil diubah!");
    }
}
