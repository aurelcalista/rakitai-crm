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

            return [
                'id' => $t->id,
                'sales' => $salesName,
                'avatar' => $avatar,
                'periode' => \Carbon\Carbon::parse($t->tanggal_mulai)->translatedFormat('F Y'),
                'periode_type' => $t->tipe_periode,
                'tanggal_mulai' => \Carbon\Carbon::parse($t->tanggal_mulai)->format('d M Y'),
                'tanggal_selesai' => \Carbon\Carbon::parse($t->tanggal_selesai)->format('d M Y'),
                'target_kontak' => $t->target_kontak,
                'target_followup' => $t->target_followup,
                'target_kunjungan' => $t->target_kunjungan,
                
                // Mocking kontak & followup for now as no tracking implemented yet
                'realisasi_kontak' => 0,
                'realisasi_followup' => 0,
                'realisasi_kunjungan' => $realisasi_kunjungan,
                
                'kekurangan_kontak' => $t->target_kontak,
                'kekurangan_followup' => $t->target_followup,
                'kekurangan_kunjungan' => max(0, $t->target_kunjungan - $realisasi_kunjungan),
                
                'akum_kontak' => 0,
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
            'target_followup' => 'required|integer|min:0',
            'target_kunjungan' => 'required|integer|min:0',
            'status' => 'required|in:Aktif,Selesai,Nonaktif',
        ]);

        Target::create($validated);

        return redirect()->back()->with('success', 'Target berhasil ditambahkan!');
    }

    public function update(Request $request, Target $target)
    {
        $validated = $request->validate([
            'sales_id' => 'required|exists:users,id',
            'tipe_periode' => 'required|in:Harian,Mingguan,Bulanan',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'target_kontak' => 'required|integer|min:0',
            'target_followup' => 'required|integer|min:0',
            'target_kunjungan' => 'required|integer|min:0',
            'status' => 'required|in:Aktif,Selesai,Nonaktif',
        ]);

        $target->update($validated);

        return redirect()->back()->with('success', 'Target berhasil diperbarui!');
    }

    public function destroy(Target $target)
    {
        $target->delete();
        return redirect()->back()->with('success', 'Target berhasil dihapus!');
    }
}
