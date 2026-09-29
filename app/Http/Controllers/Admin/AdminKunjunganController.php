<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kunjungan;
use App\Models\User;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminKunjunganController extends Controller
{
    public function index(Request $request): View
    {
        $query = Kunjungan::with(['sales', 'tujuan'])->latest();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('nomor', 'like', "%{$q}%")
                   ->orWhere('tujuan_kunjungan', 'like', "%{$q}%")
                   ->orWhereHas('sales', function ($u) use ($q) {
                       $u->where('name', 'like', "%{$q}%");
                   });
            });
        }

        if ($request->filled('sales') && $request->sales !== 'Semua Sales') {
            $query->whereHas('sales', function ($u) use ($request) {
                $u->where('name', $request->sales);
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $kunjunganPaginated = $query->paginate(10)->withQueryString();
        $startUrut = ($kunjunganPaginated->currentPage() - 1) * $kunjunganPaginated->perPage();

        $kunjungan = $kunjunganPaginated->getCollection()->map(function ($k, $idx) use ($startUrut) {
            $k->sales_nama = $k->sales ? $k->sales->name : '-';
            
            $namaTempat = '-';
            $pic = '-';
            $pic_phone = '-';

            if ($k->jenis === 'Sekolah' && $k->tujuan) {
                $namaTempat = $k->tujuan->nama;
                $pic = $k->tujuan->pic_name ?? '-';
                $pic_phone = $k->tujuan->pic_phone ?? '-';
            } elseif ($k->jenis === 'Perusahaan' && $k->tujuan) {
                $namaTempat = $k->tujuan->nama;
                $pic = $k->tujuan->pic_name ?? '-';
                $pic_phone = $k->tujuan->pic_phone ?? '-';
            }

            $noUrut = $startUrut + $idx + 1;

            return [
                'id' => $k->id,
                'no_urut' => $noUrut,
                'nomor' => $k->nomor ?: ('KJ-' . str_pad($noUrut, 3, '0', STR_PAD_LEFT)),
                'tanggal' => \Carbon\Carbon::parse($k->tanggal)->format('d M Y'),
                'waktu' => $k->waktu,
                'sales' => $k->sales_nama,
                'sales_initials' => $k->sales ? $k->sales->initials : ($k->sales_nama !== '-' ? strtoupper(substr($k->sales_nama, 0, 2)) : '-'),
                'sales_avatar_url' => $k->sales?->avatar_url,
                'jenis' => $k->jenis,
                'nama_tempat' => $namaTempat,
                'tujuan' => $k->tujuan_kunjungan,
                'hasil' => $k->hasil,
                'pic' => $pic,
                'pic_phone' => $pic_phone,
                'catatan' => $k->catatan,
                'status' => $k->status,
                'foto' => $k->foto_path ? \Storage::url($k->foto_path) : null,
                'riwayat' => []
            ];
        });

        $salesList = User::where('role', 'Sales')->pluck('name')->prepend('Semua Sales');

        return view('admin.kunjungan.index', compact('kunjungan', 'kunjunganPaginated', 'salesList'));
    }

    public function update(Request $request, Kunjungan $kunjungan)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'jenis' => 'required|string|in:Sekolah,Perusahaan',
            'tujuan_kunjungan' => 'required|string',
            'hasil' => 'nullable|string',
            'status' => 'required|in:Menunggu,Proses,Selesai',
        ]);

        $kunjungan->update($validated);

        return redirect()->back()->with('success', 'Kunjungan berhasil diperbarui!');
    }

    public function destroy(Kunjungan $kunjungan)
    {
        $kunjungan->delete();
        return redirect()->back()->with('success', 'Kunjungan berhasil dihapus!');
    }
}
