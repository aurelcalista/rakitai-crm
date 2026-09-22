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

            return [
                'id' => $t->id,
                'sales' => $salesName,
                'role' => $t->sales ? $t->sales->role : '-',
                'avatar' => $avatar,
                'periode' => \Carbon\Carbon::parse($t->tanggal_mulai)->translatedFormat('F Y'),
                'periode_type' => $t->tipe_periode,
                'tanggal_mulai' => \Carbon\Carbon::parse($t->tanggal_mulai)->format('d M Y'),
                'tanggal_selesai' => \Carbon\Carbon::parse($t->tanggal_selesai)->format('d M Y'),
                'target_kontak' => $t->target_kontak,
                'target_menghubungi' => $t->target_menghubungi ?? 0,
                'target_followup' => $t->target_followup,
                'target_kunjungan' => $t->target_kunjungan,
                
                'realisasi_kontak' => $realisasi_kontak,
                'realisasi_menghubungi' => $realisasi_menghubungi,
                'realisasi_followup' => $realisasi_followup,
                'realisasi_kunjungan' => $realisasi_kunjungan,
                
                'kekurangan_kontak' => max(0, $t->target_kontak - $realisasi_kontak),
                'kekurangan_menghubungi' => max(0, ($t->target_menghubungi ?? 0) - $realisasi_menghubungi),
                'kekurangan_followup' => max(0, $t->target_followup - $realisasi_followup),
                'kekurangan_kunjungan' => max(0, $t->target_kunjungan - $realisasi_kunjungan),
                
                'akum_kontak' => 0,
                'akum_menghubungi' => 0,
                'akum_followup' => 0,
                
                'target_besok_kontak' => $t->target_kontak,
                'target_besok_followup' => $t->target_followup,
                
                'status' => $t->status,
                'sales_id' => $t->sales_id,
                'tipe_periode' => $t->tipe_periode,
                'raw_tanggal_mulai' => $t->tanggal_mulai,
                'raw_tanggal_selesai' => $t->tanggal_selesai,
            ];
        });

        $salesList = User::whereIn('role', ['Sales', 'CS'])->get();
        return view('admin.target.index', compact('targets', 'salesList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_id' => 'required|exists:users,id',
            'tipe_periode' => 'required|in:Harian,Mingguan,Bulanan',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'target_kontak' => 'required|integer|min:0',
            'target_menghubungi' => 'nullable|integer|min:0',
            'target_followup' => 'required|integer|min:0',
            'target_kunjungan' => 'required|integer|min:0',
            'status' => 'required|in:Aktif,Selesai,Nonaktif',
        ]);
        $lastTarget = Target::where('sales_id', $validated['sales_id'])
            ->where('tanggal_selesai', '<', $validated['tanggal_mulai'])
            ->orderBy('tanggal_selesai', 'desc')
            ->first();

        if ($lastTarget) {
            $realisasi_kontak = \App\Models\Prospek::where(function($q) use ($lastTarget) {
                if ($lastTarget->sales && $lastTarget->sales->role === 'CS') {
                    $q->where('cs_id', $lastTarget->sales_id);
                } else {
                    $q->where('sales_id', $lastTarget->sales_id);
                }
            })->whereBetween('created_at', [$lastTarget->tanggal_mulai . ' 00:00:00', $lastTarget->tanggal_selesai . ' 23:59:59'])->count();

            $realisasi_followup = \App\Models\FollowUp::where('user_id', $lastTarget->sales_id)
                ->whereBetween('tanggal', [$lastTarget->tanggal_mulai, $lastTarget->tanggal_selesai])
                ->count();

            $realisasi_kunjungan = Kunjungan::where('sales_id', $lastTarget->sales_id)
                ->whereBetween('tanggal', [$lastTarget->tanggal_mulai, $lastTarget->tanggal_selesai])
                ->count();

            $realisasi_menghubungi = 0;
            if ($lastTarget->sales && $lastTarget->sales->role === 'CS') {
                $realisasi_menghubungi = \App\Models\FollowUp::where('user_id', $lastTarget->sales_id)
                    ->whereBetween('tanggal', [$lastTarget->tanggal_mulai, $lastTarget->tanggal_selesai])
                    ->distinct('prospek_id')
                    ->count('prospek_id');
            }

            $defisit_kontak = max(0, $lastTarget->target_kontak - $realisasi_kontak);
            $defisit_followup = max(0, $lastTarget->target_followup - $realisasi_followup);
            $defisit_kunjungan = max(0, $lastTarget->target_kunjungan - $realisasi_kunjungan);
            $defisit_menghubungi = max(0, ($lastTarget->target_menghubungi ?? 0) - $realisasi_menghubungi);

            $validated['target_kontak'] += $defisit_kontak;
            $validated['target_followup'] += $defisit_followup;
            $validated['target_kunjungan'] += $defisit_kunjungan;
            if (isset($validated['target_menghubungi'])) {
                $validated['target_menghubungi'] += $defisit_menghubungi;
            }
        }

        Target::create($validated);

        return redirect()->back()->with('success', 'Target berhasil ditambahkan!');
    }

    public function update(Request $request, Target $target)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $target);

        $validated = $request->validate([
            'sales_id' => 'required|exists:users,id',
            'tipe_periode' => 'required|in:Harian,Mingguan,Bulanan',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'target_kontak' => 'required|integer|min:0',
            'target_menghubungi' => 'nullable|integer|min:0',
            'target_followup' => 'required|integer|min:0',
            'target_kunjungan' => 'required|integer|min:0',
            'status' => 'required|in:Aktif,Selesai,Nonaktif',
        ]);

        $target->update($validated);

        return redirect()->back()->with('success', 'Target berhasil diperbarui!');
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
