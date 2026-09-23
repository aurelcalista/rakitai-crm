<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\Target;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\SpvPerformanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
     * 4. Data Akumulasi Tim (TA 2027/2028)
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $ta = $request->get('ta', SpvPerformanceService::DEFAULT_TA);

        // 1. Target dari HM & Alokasi
        $targetHm = $this->spvService->getTargetHmForSpv($user, $ta);

        // 2. Target Team per Week (Image 1)
        $teamPerWeek = $this->spvService->getTargetTeamPerWeek($user, now(), $ta);

        // 3. Performa Harian / Perorang (Image 2)
        $performaHarian = $this->spvService->getPerformaHarianPerorang($user, now(), $ta);

        // 4. Data Akumulasi
        $akumulasi = $this->spvService->getDataAkumulasi($user, $ta);

        // Daftar anggota tim untuk modal alokasi target (Sales DAN CS)
        $teamMembers = User::whereIn('id', $user->teamMemberIds())->get();
        $teamSales   = $teamMembers->where('role', 'Sales');
        $teamCs      = $teamMembers->where('role', 'CS');
        $taAktif     = $this->spvService->getActiveTa();

        return view('spv.performa.index', compact(
            'targetHm',
            'teamPerWeek',
            'performaHarian',
            'akumulasi',
            'teamMembers',
            'teamSales',
            'teamCs',
            'taAktif',
            'ta'
        ));
    }

    /**
     * Alokasi Target oleh SPV ke personil Sales / CS di wilayahnya.
     */
    public function alokasi(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $teamMemberIds = $user->teamMemberIds();

        $validated = $request->validate([
            'sales_id'        => 'required|in:' . implode(',', $teamMemberIds),
            'tipe_periode'    => 'required|in:Harian,Mingguan,Bulanan',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'target_kontak'   => 'required|integer|min:0',
            'target_formulir' => 'required|integer|min:0',
            'target_lunas'    => 'required|integer|min:0',
            'tahun_akademik'  => 'nullable|string|max:20',
        ]);

        $ta = $validated['tahun_akademik'] ?? $this->spvService->getActiveTa();

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

        return redirect()->route('spv.performa.index')->with('success', "Target berhasil dialokasikan kepada {$roleLabel}: " . ($assignedUser ? $assignedUser->name : 'anggota tim') . '.');
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

        return redirect()->route('spv.performa.index')
            ->with('success', "Defisit target {$sales?->name} resmi dikunci oleh SPV untuk beban sasaran hari ini.");
    }
}

