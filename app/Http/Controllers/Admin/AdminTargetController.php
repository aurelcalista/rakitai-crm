<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Target;
use App\Models\User;
use App\Models\Kunjungan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTargetController extends Controller
{
    public function index(): View
    {
        $targets = Target::with('sales')->latest()->get()->map(function ($t) {
            $salesName = $t->sales ? $t->sales->name : '-';
            $avatar = implode('', array_map(function($word) { return strtoupper($word[0] ?? ''); }, explode(' ', $salesName)));
            $avatar = substr($avatar, 0, 2);
            if (!$avatar) $avatar = 'NA';
            $realisasi_kunjungan = Kunjungan::where('sales_id', $t->sales_id)
                ->whereBetween('tanggal', [$t->tanggal_mulai, $t->tanggal_selesai])
                ->count();

            // Realisasi Kontak
            $realisasi_kontak = \App\Models\Prospek::where(function($q) use ($t) {
                if ($t->sales && $t->sales->role === 'CS') {
                    $q->where('cs_id', $t->sales_id);
                } else {
                    $q->where('sales_id', $t->sales_id);
                }
            })->whereBetween('created_at', [$t->tanggal_mulai . ' 00:00:00', $t->tanggal_selesai . ' 23:59:59'])->count();

            // Realisasi Menghubungi (khusus CS)
            $realisasi_menghubungi = 0;
            if ($t->sales && $t->sales->role === 'CS') {
                $realisasi_menghubungi = \App\Models\FollowUp::where('user_id', $t->sales_id)
                    ->whereBetween('tanggal', [$t->tanggal_mulai, $t->tanggal_selesai])
                    ->distinct('prospek_id')
                    ->count('prospek_id');
            }

            // Realisasi Follow-up
            $realisasi_followup = \App\Models\FollowUp::where('user_id', $t->sales_id)
                ->whereBetween('tanggal', [$t->tanggal_mulai, $t->tanggal_selesai])
                ->count();
            // Realisasi Lunas & Formulir
            if ($t->sales && $t->sales->role === 'SPV') {
                $teamIds = $t->sales->teamMemberIds();
                $teamIds[] = $t->sales_id;
                $realisasi_lunas = \App\Models\Prospek::whereIn('sales_id', $teamIds)
                    ->where('status', 'LUNAS')
                    ->whereBetween('updated_at', [$t->tanggal_mulai . ' 00:00:00', $t->tanggal_selesai . ' 23:59:59'])
                    ->count();
                $realisasi_formulir = \App\Models\Prospek::where(function($q) use ($teamIds) {
                        $q->whereIn('sales_id', $teamIds)->orWhereIn('cs_id', $teamIds);
                    })
                    ->whereIn('status', ['FORMULIR', 'BERKAS', 'LUNAS'])
                    ->whereBetween('updated_at', [$t->tanggal_mulai . ' 00:00:00', $t->tanggal_selesai . ' 23:59:59'])
                    ->count();
            } else {
                $realisasi_lunas = \App\Models\Prospek::where('sales_id', $t->sales_id)
                    ->where('status', 'LUNAS')
                    ->whereBetween('updated_at', [$t->tanggal_mulai . ' 00:00:00', $t->tanggal_selesai . ' 23:59:59'])
                    ->count();
                $realisasi_formulir = \App\Models\Prospek::where(function($q) use ($t) {
                        $q->where('sales_id', $t->sales_id)->orWhere('cs_id', $t->sales_id);
                    })
                    ->whereIn('status', ['FORMULIR', 'BERKAS', 'LUNAS'])
                    ->whereBetween('updated_at', [$t->tanggal_mulai . ' 00:00:00', $t->tanggal_selesai . ' 23:59:59'])
                    ->count();
            }

                $kekurangan_lunas = max(0, ($t->target_lunas ?? 0) - $realisasi_lunas);
                $kekurangan_formulir = max(0, ($t->target_formulir ?? 0) - $realisasi_formulir);
                $kekurangan_kontak = max(0, $t->target_kontak - $realisasi_kontak);
                $kekurangan_menghubungi = max(0, ($t->target_menghubungi ?? 0) - $realisasi_menghubungi);
                $kekurangan_followup = max(0, $t->target_followup - $realisasi_followup);
                $kekurangan_kunjungan = max(0, $t->target_kunjungan - $realisasi_kunjungan);

                // Target Besok (Carry-Over akumulasi kekurangan sesuai aturan PRD)
                if ($t->tipe_periode === 'Harian') {
                    $target_besok_kontak = $t->target_kontak + $kekurangan_kontak;
                    $target_besok_followup = $t->target_followup + $kekurangan_followup;
                } else {
                    $endDate = \Carbon\Carbon::parse($t->tanggal_selesai)->endOfDay();
                    $sisaHari = max(1, \Carbon\Carbon::now()->diffInDays($endDate, false) + 1);
                    $target_besok_kontak = (int)ceil($kekurangan_kontak / $sisaHari);
                    $target_besok_followup = (int)ceil($kekurangan_followup / $sisaHari);
                }

                return [
                    'id' => $t->id,
                    'sales' => $salesName,
                    'role' => $t->sales ? $t->sales->role : '-',
                    'avatar' => $avatar,
                    'allocated_by' => $t->allocator?->name ?? 'Head of Marketing',
                    'tahun_akademik' => $t->tahun_akademik ?? '2027/2028',
                    'periode' => \Carbon\Carbon::parse($t->tanggal_mulai)->translatedFormat('F Y'),
                    'periode_type' => $t->tipe_periode,
                    'tanggal_mulai' => \Carbon\Carbon::parse($t->tanggal_mulai)->format('d M Y'),
                    'tanggal_selesai' => \Carbon\Carbon::parse($t->tanggal_selesai)->format('d M Y'),
                    'target_lunas' => $t->target_lunas ?? 0,
                    'target_formulir' => $t->target_formulir ?? 0,
                    'target_kontak' => $t->target_kontak,
                    'target_menghubungi' => $t->target_menghubungi ?? 0,
                    'target_followup' => $t->target_followup,
                    'target_kunjungan' => $t->target_kunjungan,
                    
                    'realisasi_lunas' => $realisasi_lunas,
                    'realisasi_formulir' => $realisasi_formulir,
                    'realisasi_kontak' => $realisasi_kontak,
                    'realisasi_menghubungi' => $realisasi_menghubungi,
                    'realisasi_followup' => $realisasi_followup,
                    'realisasi_kunjungan' => $realisasi_kunjungan,
                    
                    'kekurangan_lunas' => $kekurangan_lunas,
                    'kekurangan_formulir' => $kekurangan_formulir,
                    'kekurangan_kontak' => $kekurangan_kontak,
                    'kekurangan_menghubungi' => $kekurangan_menghubungi,
                    'kekurangan_followup' => $kekurangan_followup,
                    'kekurangan_kunjungan' => $kekurangan_kunjungan,

                    'akum_kontak' => $kekurangan_kontak,
                    'akum_menghubungi' => $kekurangan_menghubungi,
                    'akum_followup' => $kekurangan_followup,
                    'target_besok_kontak' => $target_besok_kontak,
                    'target_besok_followup' => $target_besok_followup,
                    
                    'status' => $t->status,
                    'sales_id' => $t->sales_id,
                    'tipe_periode' => $t->tipe_periode,
                    'raw_tanggal_mulai' => $t->tanggal_mulai,
                    'raw_tanggal_selesai' => $t->tanggal_selesai,
                ];
            });

        // Sertakan SPV, Sales, dan CS agar HM dapat memberikan target langsung ke SPV
        $salesList = User::whereIn('role', ['SPV', 'Sales', 'CS'])->orderBy('role')->orderBy('name')->get();
        return view('admin.target.index', compact('targets', 'salesList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_id'           => 'required|exists:users,id',
            'tipe_periode'       => 'required|in:Harian,Mingguan,Bulanan',
            'tanggal_mulai'      => 'required|date',
            'tanggal_selesai'    => 'required|date|after_or_equal:tanggal_mulai',
            'target_lunas'       => 'nullable|integer|min:0',
            'target_formulir'    => 'nullable|integer|min:0',
            'target_kontak'      => 'required|integer|min:0',
            'target_menghubungi' => 'nullable|integer|min:0',
            'target_followup'    => 'required|integer|min:0',
            'target_kunjungan'   => 'required|integer|min:0',
            'status'             => 'required|in:Aktif,Selesai,Nonaktif',
            'tahun_akademik'     => 'nullable|string|max:20',
        ]);

        $validated['allocated_by'] = auth()->id();
        $validated['target_lunas'] = (int)($validated['target_lunas'] ?? 0);
        $validated['target_formulir'] = (int)($validated['target_formulir'] ?? 0);
        if (empty($validated['tahun_akademik'])) {
            $validated['tahun_akademik'] = '2027/2028';
        }

        $target = Target::create($validated);

        $targetUser = User::find($validated['sales_id']);
        $allocator = auth()->user();
        $tipe = $target->tipe_periode ?? 'Bulanan';
        $lunas = $target->target_lunas ?? 0;
        $kontak = $target->target_kontak ?? 0;

        if ($targetUser) {
            $isSpv = $targetUser->role === 'SPV';
            $targetUser->notify(new \App\Notifications\TargetNotification(
                title: $isSpv ? '🎯 Target Baru dari Head of Marketing' : '🎯 Target Baru Ditugaskan',
                message: $isSpv
                    ? "Head of Marketing telah menetapkan target {$tipe}: {$lunas} Maba Lunas, {$target->target_formulir} Formulir, dan {$kontak} Kontak Baru. Segera distribusikan ke tim Sales & CS Anda."
                    : "Target {$tipe} telah ditetapkan untuk Anda: {$lunas} Maba Lunas, {$target->target_formulir} Formulir, dan {$kontak} Kontak Baru.",
                type: 'info',
                link: route($isSpv ? 'spv.performa.index' : 'performa.index'),
                icon: '🎯',
                extraData: ['target_id' => $target->id, 'event_type' => 'target_assigned']
            ));
        }

        if ($allocator && $targetUser && $allocator->id !== $targetUser->id) {
            $allocator->notify(new \App\Notifications\TargetNotification(
                title: '✓ Target Berhasil Diberikan',
                message: "Target {$tipe} berhasil diberikan kepada {$targetUser->name} ({$targetUser->role}) sejumlah {$lunas} Maba Lunas.",
                type: 'success',
                link: route('admin.target.index'),
                icon: '✓',
                extraData: ['target_id' => $target->id, 'event_type' => 'target_given']
            ));
        }

        $roleName = $targetUser ? $targetUser->role : 'User';
        return redirect()->back()->with('success', "Target untuk {$roleName} '{$targetUser->name}' berhasil ditambahkan dan notifikasi telah dikirim!");
    }

    public function update(Request $request, Target $target)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $target);

        $validated = $request->validate([
            'sales_id'           => 'required|exists:users,id',
            'tipe_periode'       => 'required|in:Harian,Mingguan,Bulanan',
            'tanggal_mulai'      => 'required|date',
            'tanggal_selesai'    => 'required|date|after_or_equal:tanggal_mulai',
            'target_lunas'       => 'nullable|integer|min:0',
            'target_formulir'    => 'nullable|integer|min:0',
            'target_kontak'      => 'required|integer|min:0',
            'target_menghubungi' => 'nullable|integer|min:0',
            'target_followup'    => 'required|integer|min:0',
            'target_kunjungan'   => 'required|integer|min:0',
            'status'             => 'required|in:Aktif,Selesai,Nonaktif',
            'tahun_akademik'     => 'nullable|string|max:20',
        ]);

        $validated['allocated_by'] = auth()->id();
        $target->update($validated);

        $targetUser = User::find($target->sales_id);
        if ($targetUser) {
            $isSpv = $targetUser->role === 'SPV';
            $targetUser->notify(new \App\Notifications\TargetNotification(
                title: '✏️ Pembaruan Target',
                message: "Target {$target->tipe_periode} Anda telah diperbarui menjadi {$target->target_lunas} Maba Lunas dan {$target->target_kontak} Kontak Baru.",
                type: 'info',
                link: route($isSpv ? 'spv.performa.index' : 'performa.index'),
                icon: '✏️',
                extraData: ['target_id' => $target->id, 'event_type' => 'target_updated']
            ));
        }

        return redirect()->back()->with('success', 'Target berhasil diperbarui dan notifikasi telah dikirim!');
    }

    public function lock(Request $request, Target $target)
    {
        \Illuminate\Support\Facades\Gate::authorize('lock', $target);

        $target->lock(auth()->user());

        return redirect()->back()->with('success', 'Target berhasil dikunci (locked).');
    }

    public function unlock(Request $request, Target $target)
    {
        \Illuminate\Support\Facades\Gate::authorize('unlock', $target);

        $target->unlock();

        return redirect()->back()->with('success', 'Target berhasil dibuka kuncinya (unlocked).');
    }

    public function destroy(Target $target)
    {
        \Illuminate\Support\Facades\Gate::authorize('delete', $target);

        $target->delete();
        return redirect()->back()->with('success', 'Target berhasil dihapus!');
    }
}
