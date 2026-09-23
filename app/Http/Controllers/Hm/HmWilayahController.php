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

        $mainWilayah = $user->wilayah_id ? Wilayah::find($user->wilayah_id) : null;

        if ($mainWilayah) {
            // HM mengelola level Kota/Kabupaten yang menjadi tanggung jawabnya
            $wilayahs = Wilayah::where('id', $mainWilayah->id)
                ->with(['children', 'users' => function($q) {
                    $q->whereIn('role', ['SPV', 'Sales', 'CS']);
                }])
                ->withCount(['sekolahs', 'perusahaans'])
                ->get();

            $descendantIds = $mainWilayah->getDescendantIds();
            $spvCandidates = User::where('role', 'SPV')
                ->where(function($q) use ($descendantIds) {
                    $q->whereIn('wilayah_id', $descendantIds)->orWhereNull('wilayah_id');
                })
                ->orderBy('name')
                ->get();
        } else {
            // Global HM / Admin: hanya daftar level Kota/Kabupaten (parent_id is null)
            $wilayahs = Wilayah::whereNull('parent_id')
                ->with(['children', 'users' => function($q) {
                    $q->whereIn('role', ['SPV', 'Sales', 'CS']);
                }])
                ->withCount(['sekolahs', 'perusahaans'])
                ->get();

            $spvCandidates = User::where('role', 'SPV')
                ->orderBy('name')
                ->get();
        }

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

        return redirect()->back()->with('success', "SPV {$spv->name} berhasil ditunjuk untuk Wilayah {$wilayah->nama}.");
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

        return redirect()->back()->with('success', "Target Wilayah {$wilayah->nama} berhasil ditetapkan dan dikunci oleh HM!");
    }
}
