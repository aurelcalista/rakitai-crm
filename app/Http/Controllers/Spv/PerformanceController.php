<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\Target;
use App\Models\User;
use App\Services\AkademikService;
use App\Services\SpvPerformanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PerformanceController extends Controller
{
    public function __construct(private SpvPerformanceService $spvService)
    {
    }

    /**
     * Target & Performa Tim SPV (P0):
     * 1. Target dari HM ke SPV & Alokasi ke Sales/CS
     * 2. Target Team per Week (Whiteboard Image 1)
     * 3. Performa Harian / Perorang (Whiteboard Image 2)
     * 4. Data Akumulasi Tim (Active TA)
     *
     * `?ta=` query param enables historical view (explicit override).
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Resolve TA: explicit override for historical view, or active TA
        $taOverride = $request->get('ta'); // null = use active TA

        try {
            $activeTaNama = AkademikService::getAktifNama() ?? AkademikService::getAktifOrFail()->nama;
        } catch (RuntimeException $e) {
            // No active TA — show error page or empty state
            return view('spv.performa.index', [
                'error'           => $e->getMessage(),
                'targetHm'        => null,
                'teamPerWeek'     => null,
                'performaHarian'  => null,
                'akumulasi'       => null,
                'teamMembers'     => collect(),
                'teamSales'       => collect(),
                'teamCs'          => collect(),
                'ta'              => null,
            ]);
        }

        $ta = $taOverride ?? $activeTaNama;

        $targetHm       = $this->spvService->getTargetHmForSpv($user, $ta);
        $teamPerWeek    = $this->spvService->getTargetTeamPerWeek($user, now(), $ta);
        $performaHarian = $this->spvService->getPerformaHarianPerorang($user, now(), $ta);
        $akumulasi      = $this->spvService->getDataAkumulasi($user, $ta);

        $teamMembers = User::whereIn('id', $user->teamMemberIds())->get();
        $teamSales   = $teamMembers->where('role', 'Sales');
        $teamCs      = $teamMembers->where('role', 'CS');

        return view('spv.performa.index', compact(
            'targetHm', 'teamPerWeek', 'performaHarian', 'akumulasi',
            'teamMembers', 'teamSales', 'teamCs', 'ta'
        ));
    }

    /**
     * Alokasi Target oleh SPV ke personil Sales / CS.
     * Uses active TA by default; form may provide explicit ta for historical entry.
     */
    public function alokasi(Request $request): RedirectResponse
    {
        $user          = auth()->user();
        $teamMemberIds = $user->teamMemberIds();

        $validated = $request->validate([
            'sales_id'       => 'required|in:' . implode(',', $teamMemberIds),
            'tipe_periode'   => 'required|in:Harian,Mingguan,Bulanan',
            'tanggal_mulai'  => 'required|date',
            'tanggal_selesai'=> 'required|date|after_or_equal:tanggal_mulai',
            'target_kontak'  => 'required|integer|min:0',
            'target_formulir'=> 'required|integer|min:0',
            'target_lunas'   => 'required|integer|min:0',
        ]);

        // Resolve active TA — fail explicitly if none configured
        $activeTA = AkademikService::getAktifOrFail();

        $existing = Target::where([
            'sales_id'        => $validated['sales_id'],
            'tipe_periode'    => $validated['tipe_periode'],
            'tanggal_mulai'   => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
        ])->first();

        if ($existing) {
            \Illuminate\Support\Facades\Gate::authorize('update', $existing);
        }

        Target::updateOrCreate(
            [
                'sales_id'        => $validated['sales_id'],
                'tipe_periode'    => $validated['tipe_periode'],
                'tanggal_mulai'   => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
            ],
            [
                'allocated_by'    => $user->id,
                'academic_year_id'=> $activeTA->id,
                'tahun_akademik'  => $activeTA->nama, // keep legacy column in sync
                'target_kontak'   => $validated['target_kontak'],
                'target_formulir' => $validated['target_formulir'],
                'target_lunas'    => $validated['target_lunas'],
                'target_followup' => (int) round($validated['target_kontak'] * 0.8),
                'status'          => 'Aktif',
            ]
        );

        $assignedUser = User::find($validated['sales_id']);

        return redirect()->route('spv.performa.index')
            ->with('success', 'Target berhasil dialokasikan kepada ' . ($assignedUser?->name ?? 'anggota tim') . '.');
    }
}
