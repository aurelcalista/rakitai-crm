<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\Target;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\AkademikService;
use App\Services\SpvPerformanceService;
use App\Services\TargetAchievementService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PerformanceController extends Controller
{
    public function __construct(
        private SpvPerformanceService $spvService,
        private TargetAchievementService $targetAchievementService
    ) {
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

        // Daftar anggota tim untuk modal alokasi target (Sales DAN CS)
        $teamMembers = User::whereIn('id', $user->teamMemberIds())->get();
        $teamSales   = $teamMembers->where('role', 'Sales');
        $teamCs      = $teamMembers->where('role', 'CS');
        $taAktif     = $this->spvService->getActiveTa();

        // Data Dashboard Target & Pencapaian Berjenjang (PRD Bab 6.2)
        $targetAchievementData = $this->targetAchievementService->getDashboardTargetData(
            $user,
            $request->get('periode', 'bulanan'),
            $request->get('wilayah_id') ? (int)$request->get('wilayah_id') : null,
            $ta
        );

        return view('spv.performa.index', compact(
            'targetHm',
            'teamPerWeek',
            'performaHarian',
            'akumulasi',
            'teamMembers',
            'teamSales',
            'teamCs',
            'taAktif',
            'ta',
            'targetAchievementData'
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

        $ta = $this->spvService->getActiveTa();

        // Validasi: total alokasi lunas ke tim TIDAK boleh melebihi target lunas yang diterima SPV dari HM
        $targetHm = $this->spvService->getTargetHmForSpv($user, $ta);
        $targetLunasHm = $targetHm['target_lunas'];

        // Hitung existing alokasi (kecuali record yang akan diupdate)
        $existingAlokasi = Target::whereIn('sales_id', $teamMemberIds)
            ->where('allocated_by', $user->id)
            ->where('status', 'Aktif')
            ->where(function ($q) use ($ta) {
                $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
            })
            ->where('sales_id', '!=', $validated['sales_id']) // exclude current member being updated
            ->sum('target_lunas');

        $newTotal = $existingAlokasi + (int)$validated['target_lunas'];
        if ($newTotal > $targetLunasHm) {
            return redirect()->back()
                ->withErrors(['target_lunas' => "Total target Maba Lunas tim ({$newTotal}) melebihi target SPV dari HM ({$targetLunasHm}). Sisa yang bisa dialokasikan: " . max(0, $targetLunasHm - $existingAlokasi) . '.'])
                ->withInput();
        }

        // Ambil academic_year_id dari TA aktif
        $taModel = TahunAkademik::where('nama', $ta)->first() ?? TahunAkademik::getAktif();

        Target::updateOrCreate(
            [
                'sales_id'        => $validated['sales_id'],
                'tipe_periode'    => $validated['tipe_periode'],
                'tanggal_mulai'   => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
            ],
            [
                'allocated_by'    => $user->id,
                'tahun_akademik'  => $ta,
                'academic_year_id'=> $taModel?->id,
                'target_kontak'   => $validated['target_kontak'],
                'target_formulir' => $validated['target_formulir'],
                'target_lunas'    => $validated['target_lunas'],
                'target_followup' => (int) round($validated['target_kontak'] * 0.8),
                'status'          => 'Aktif',
            ]
        );

        $assignedUser = User::find($validated['sales_id']);
        $roleLabel = $assignedUser && $assignedUser->role === 'CS' ? 'CS' : 'Sales';

        // 1. Notifikasi ke Sales / CS penerima alokasi
        if ($assignedUser) {
            $assignedUser->notify(new \App\Notifications\TargetNotification(
                title: "🎯 Target Baru dari Supervisor ({$user->name})",
                message: "Supervisor Anda telah mengalokasikan target {$validated['tipe_periode']}: {$validated['target_lunas']} Maba Lunas, {$validated['target_formulir']} Formulir, dan {$validated['target_kontak']} Kontak Baru.",
                type: 'info',
                link: route('performa.index'),
                icon: '🎯',
                extraData: ['event_type' => 'target_allocated_member']
            ));
        }

        // 2. Notifikasi konfirmasi ke SPV sendiri
        $user->notify(new \App\Notifications\TargetNotification(
            title: '✓ Alokasi Target Tim Berhasil',
            message: "Target {$validated['tipe_periode']} untuk {$roleLabel} {$assignedUser?->name} ({$validated['target_lunas']} Maba Lunas) telah berhasil dialokasikan.",
            type: 'success',
            link: route('spv.performa.index'),
            icon: '📋',
            extraData: ['event_type' => 'target_allocated_spv']
        ));

        // 3. Notifikasi visibilitas ke Head of Marketing (HM)
        $hms = User::where('role', 'HM')->where('status', 'Aktif')->get();
        foreach ($hms as $hm) {
            $hm->notify(new \App\Notifications\TargetNotification(
                title: '📋 SPV Mengalokasikan Target Tim',
                message: "SPV {$user->name} telah mengalokasikan target ke {$assignedUser?->name} ({$roleLabel}) sejumlah {$validated['target_lunas']} Maba Lunas.",
                type: 'info',
                link: route('admin.target.index'),
                icon: '📋',
                extraData: ['event_type' => 'target_allocated_hm']
            ));
        }

        return redirect()->route('spv.performa.index')->with('success', "Target berhasil dialokasikan kepada {$roleLabel}: " . ($assignedUser ? $assignedUser->name : 'anggota tim') . '. Notifikasi telah dikirim.');
    }

    /**
     * Otorisasi / Kunci Defisit Harian oleh SPV (P0 Bab 6.1).
     * SPV menetapkan/mengunci penambahan defisit kemarin ke sasaran hari ini.
     */
    public function kunciDefisit(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validate([
            'sales_id'         => 'required', // specific id or 'all'
            'tanggal'          => 'required|date',
            'action'           => 'required|in:lock,unlock',
            'defisit_kontak'   => 'nullable|integer|min:0',
            'defisit_formulir' => 'nullable|integer|min:0',
            'defisit_lunas'    => 'nullable|integer|min:0',
            'catatan'          => 'nullable|string|max:255',
        ]);

        $tanggal = $validated['tanggal'];
        $teamMemberIds = $user->teamMemberIds();

        // Batch lock all sales with deficits
        if ($validated['sales_id'] === 'all') {
            $performa = $this->spvService->getPerformaHarianPerorang($user, Carbon::parse($tanggal)->addDay(), $this->spvService->getActiveTa());
            $lockedCount = 0;
            foreach ($performa['rows'] as $row) {
                if ($row['utang_angka']['has_utang']) {
                    $this->spvService->lockDailyDeficit(
                        $user,
                        $row['member_id'],
                        $tanggal,
                        $row['utang_angka']['kontak'],
                        $row['utang_angka']['formulir'],
                        $row['utang_angka']['lunas'],
                        $validated['catatan'] ?? 'Batch lock defisit oleh SPV'
                    );
                    $lockedCount++;
                }
            }

            return redirect()->route('spv.performa.index')
                ->with('success', "Berhasil! {$lockedCount} defisit personil tim untuk tanggal " . Carbon::parse($tanggal)->translatedFormat('d M Y') . ' resmi dikunci sebagai sasaran hari ini.');
        }

        // Single sales
        $salesId = (int)$validated['sales_id'];
        if (!in_array($salesId, $teamMemberIds)) {
            abort(403, 'Anggota tim di luar cakupan otorisasi Anda.');
        }

        $sales = User::find($salesId);

        if ($validated['action'] === 'unlock') {
            $this->spvService->unlockDailyDeficit($user, $salesId, $tanggal);
            return redirect()->route('spv.performa.index')
                ->with('success', "Kuncian defisit untuk {$sales?->name} berhasil dibuka.");
        }

        $this->spvService->lockDailyDeficit(
            $user,
            $salesId,
            $tanggal,
            (int)($validated['defisit_kontak'] ?? 0),
            (int)($validated['defisit_formulir'] ?? 0),
            (int)($validated['defisit_lunas'] ?? 0),
            $validated['catatan'] ?? null
        );

        if ($sales) {
            $defisitLunas = (int)($validated['defisit_lunas'] ?? 0);
            $defisitKontak = (int)($validated['defisit_kontak'] ?? 0);
            $sales->notify(new \App\Notifications\TargetNotification(
                title: "⚠️ Otorisasi Kunci Defisit oleh SPV ({$user->name})",
                message: "SPV {$user->name} telah mengunci defisit kemarin ({$defisitLunas} Lunas, {$defisitKontak} Kontak) untuk ditambahkan ke beban target harian Anda hari ini.",
                type: 'warning',
                link: route('performa.index'),
                icon: '⚠️',
                extraData: ['event_type' => 'deficit_locked']
            ));
        }

        return redirect()->route('spv.performa.index')
            ->with('success', "Defisit target {$sales?->name} resmi dikunci oleh SPV untuk beban sasaran hari ini.");
    }
}
