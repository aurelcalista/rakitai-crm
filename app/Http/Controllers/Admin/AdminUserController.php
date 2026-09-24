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
            if (empty($user->kode)) {
                $user->kode = User::generateUserCode($user->role);
                $user->save();
            }
            $user->avatar_url = $user->avatar_url;
            $user->avatar = $user->initials;
            return $user;
        });

        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();
        \Illuminate\Support\Facades\Gate::authorize('create', User::class);

        $allowedRoles = strtolower($authUser->role) === 'hm'
            ? 'SPV,CS,EO,Admin'
            : 'Admin,HM,SPV,Sales,CS,EO';

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'role' => 'required|in:' . $allowedRoles,
            'status' => 'required|in:Aktif,Nonaktif',
            'wilayah_id' => 'nullable|exists:wilayahs,id',
            'supervisor_id' => 'nullable|exists:users,id',
            'password' => 'required|string|confirmed',
        ]);

        if (strtolower($authUser->role) === 'hm' && !empty($validated['wilayah_id'])) {
            if (!$authUser->isWithinWilayahScope($validated['wilayah_id'])) {
                abort(403, 'Wilayah penugasan berada di luar cakupan tanggung jawab HM.');
            }
        }

        $validated['password'] = Hash::make($validated['password']);

        // Auto-generate unique user code for Admin, HM, SPV or if not set
        if (in_array(strtolower($validated['role']), ['admin', 'hm', 'spv']) || empty($validated['kode'])) {
            $validated['kode'] = User::generateUserCode($validated['role']);
        }

        User::create($validated);

        return redirect()->back()->with('success', 'Akun pengguna baru berhasil dibuat!');
    }

    public function update(Request $request, User $user)
    {
        $authUser = auth()->user();
        \Illuminate\Support\Facades\Gate::authorize('update', $user);

        $allowedRoles = strtolower($authUser->role) === 'hm'
            ? 'SPV,CS,EO,Admin'
            : 'Admin,HM,SPV,Sales,CS,EO';

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'required|string|max:20',
            'role' => 'required|in:' . $allowedRoles,
            'status' => 'required|in:Aktif,Nonaktif',
            'wilayah_id' => 'nullable|exists:wilayahs,id',
            'supervisor_id' => 'nullable|exists:users,id',
        ]);

        if (strtolower($authUser->role) === 'hm' && !empty($validated['wilayah_id'])) {
            if (!$authUser->isWithinWilayahScope($validated['wilayah_id'])) {
                abort(403, 'Wilayah penugasan berada di luar cakupan tanggung jawab HM.');
            }
        }

        // Auto-generate code if user doesn't have one and is Admin/HM/SPV
        if (empty($user->kode) && in_array(strtolower($validated['role']), ['admin', 'hm', 'spv'])) {
            $validated['kode'] = User::generateUserCode($validated['role']);
        }

        $user->update($validated);

        return redirect()->back()->with('success', 'Data pengguna berhasil diperbarui!');
    }

    public function destroy(User $user)
    {
        \Illuminate\Support\Facades\Gate::authorize('delete', $user);

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

    /**
     * ACC / Approve Pending User Baru by Admin.
     * Non-admin returns 403 Forbidden.
     * Generates unique user code (YYMMSXXX) upon approval if code is empty.
     */
    public function approve(User $user)
    {
        if (strtolower(auth()->user()->role) !== 'admin') {
            abort(403, 'Hanya Admin yang berwenang melakukan ACC/Approval user baru.');
        }

        if ($user->status === 'Aktif') {
            return redirect()->back()->with('error', "User {$user->name} sudah berstatus Aktif.");
        }

        $kode = $user->kode;
        if (empty($kode)) {
            $kode = User::generateUserCode($user->role ?? 'Sales');
        }

        $user->update([
            'status' => 'Aktif',
            'kode'   => $kode,
        ]);

        return redirect()->back()->with('success', "User baru {$user->name} ({$user->kode}) berhasil di-ACC dan berstatus Aktif!");
    }

    /**
     * Reject Pending User Baru by Admin.
     * Non-admin returns 403 Forbidden.
     */
    public function reject(User $user)
    {
        if (strtolower(auth()->user()->role) !== 'admin') {
            abort(403, 'Hanya Admin yang berwenang menolak pendaftaran user.');
        }

        $user->update(['status' => 'Nonaktif']);

        return redirect()->back()->with('success', "Pendaftaran user {$user->name} telah ditolak.");
    }
}
