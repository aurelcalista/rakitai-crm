<?php

namespace App\Http\Controllers\Hm;

use App\Http\Controllers\Controller;
use App\Models\Target;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\AkademikService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class HmWilayahController extends Controller
{
    /**
     * Display Wilayahs managed under HM scope.
     */
    public function index(): View
    {
        $user = auth()->user();

        $activeWilayahIds = $user->activeWilayahIds();

        if (count($activeWilayahIds) > 0) {
            // HM mengelola level Kota/Kabupaten yang menjadi tanggung jawabnya
            $wilayahs = Wilayah::whereIn('id', $activeWilayahIds)
                ->with(['children', 'users' => function($q) {
                    $q->whereIn('role', ['SPV', 'Sales', 'CS']);
                }])
                ->withCount(['sekolahs', 'perusahaans'])
                ->get();
        } else {
            // Global HM / Admin: hanya daftar level Kota/Kabupaten (parent_id is null)
            $wilayahs = Wilayah::whereNull('parent_id')
                ->with(['children', 'users' => function($q) {
                    $q->whereIn('role', ['SPV', 'Sales', 'CS']);
                }])
                ->withCount(['sekolahs', 'perusahaans'])
                ->get();
        }

        // Kandidat SPV: Semua user dengan role SPV
        $spvCandidates = User::whereRaw('LOWER(role) = ?', ['spv'])
            ->orderBy('name')
            ->get();

        $activeTA = AkademikService::getAktif();

        return view('hm.wilayah.index', compact('wilayahs', 'spvCandidates', 'activeTA'));
    }

    /**
     * HM Appoint SPV for a Wilayah.
     * Strict backend scope checking: ID tampering or cross-scope SPV returns 403.
     */
    public function assignSpv(Request $request, Wilayah $wilayah): RedirectResponse
    {
        $user = auth()->user();

        $request->validate([
            'spv_id' => 'required|exists:users,id',
        ]);

        $spv = User::findOrFail($request->spv_id);

        Gate::authorize('assignSpv', [$spv, $wilayah]);

        if (strtolower($spv->role) !== 'spv') {
            abort(403, 'User selected is not an SPV.');
        }

        // Assign SPV to this Wilayah
        $spv->update(['wilayah_id' => $wilayah->id]);

        // Sync active assignment in user_wilayah pivot table
        \Illuminate\Support\Facades\DB::table('user_wilayah')->updateOrInsert(
            ['user_id' => $spv->id, 'wilayah_id' => $wilayah->id, 'role' => 'SPV'],
            ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
        );

        return redirect()->back()->with('success', "SPV {$spv->name} berhasil ditunjuk untuk Wilayah {$wilayah->nama}.");
    }

    /**
     * HM Deactivate SPV assignment from a Wilayah.
     */
    public function unassignSpv(Request $request, Wilayah $wilayah, User $spv): RedirectResponse
    {
        Gate::authorize('assignSpv', [$spv, $wilayah]);

        if ($spv->wilayah_id == $wilayah->id) {
            $spv->update(['wilayah_id' => null]);
        }

        \Illuminate\Support\Facades\DB::table('user_wilayah')
            ->where('user_id', $spv->id)
            ->where('wilayah_id', $wilayah->id)
            ->where('role', 'SPV')
            ->update([
                'is_active'      => false,
                'deactivated_at' => now(),
                'updated_at'     => now(),
            ]);

        return redirect()->back()->with('success', "Penugasan SPV {$spv->name} dari Wilayah {$wilayah->nama} telah dinonaktifkan.");
    }

    /**
     * HM Create new SPV Account
     */
    public function storeSpv(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'required|string|max:20',
            'password' => 'required|string|min:8',
        ]);

        $spv = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'role'     => 'SPV',
            'status'   => 'Aktif',
        ]);

        return redirect()->back()->with('success', 'Akun SPV ' . $spv->name . ' berhasil dibuat.');
    }

    /**
     * HM Set & Lock Target Wilayah.
     */
    public function setWilayahTarget(Request $request, Wilayah $wilayah): RedirectResponse
    {
        $user = auth()->user();

        $request->validate([
            'target_id'      => 'nullable|exists:targets,id',
            'spv_id'         => 'required|exists:users,id',
            'tipe_periode'   => 'required|in:Harian,Mingguan,Bulanan,Tahunan',
            'tanggal_mulai'  => 'required|date',
            'tanggal_selesai'=> 'required|date|after_or_equal:tanggal_mulai',
            'target_kontak'  => 'required|integer|min:0',
            'target_formulir'=> 'required|integer|min:0',
            'target_lunas'   => 'required|integer|min:0',
        ]);

        $spv = User::findOrFail($request->spv_id);

        Gate::authorize('assignSpv', [$spv, $wilayah]);
        Gate::authorize('createWilayahTarget', [\App\Models\Target::class, $wilayah->id]);

        if (strtolower($spv->role) !== 'spv') {
            abort(403, 'User selected is not an SPV.');
        }

        $activeTA = AkademikService::getAktifOrFail();

        // If target_id is provided, find it; otherwise find existing active target for this wilayah & TA
        $target = null;
        if ($request->filled('target_id')) {
            $target = Target::find($request->target_id);
        }

        if (!$target) {
            $target = Target::where('target_type', 'Wilayah')
                ->where('wilayah_id', $wilayah->id)
                ->where('academic_year_id', $activeTA->id)
                ->where('status', 'Aktif')
                ->latest()
                ->first();
        }

        $targetData = [
            'target_type'      => 'Wilayah',
            'wilayah_id'       => $wilayah->id,
            'spv_id'           => $spv->id,
            'sales_id'         => $spv->id,
            'academic_year_id' => $activeTA->id,
            'tahun_akademik'   => $activeTA->nama,
            'allocated_by'     => $user->id,
            'tipe_periode'     => $request->tipe_periode,
            'tanggal_mulai'    => $request->tanggal_mulai,
            'tanggal_selesai'  => $request->tanggal_selesai,
            'target_kontak'    => $request->target_kontak,
            'target_formulir'  => $request->target_formulir,
            'target_lunas'     => $request->target_lunas,
            'target_followup'  => (int) round($request->target_kontak * 0.8),
            'status'           => 'Aktif',
            'is_locked'        => true,
            'locked_at'        => now(),
            'locked_by'        => $user->id,
        ];


        if ($target) {
            $target->update($targetData);
        } else {
            Target::create($targetData);
        }

        // Also ensure SPV is linked to this Wilayah
        $spv->update(['wilayah_id' => $wilayah->id]);

        \Illuminate\Support\Facades\DB::table('user_wilayah')->updateOrInsert(
            ['user_id' => $spv->id, 'wilayah_id' => $wilayah->id, 'role' => 'SPV'],
            ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
        );

        return redirect()->back()->with('success', "Target Wilayah {$wilayah->nama} berhasil ditetapkan dan dikunci oleh HM!");
    }
}

