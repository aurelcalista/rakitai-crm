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
    public function index(): View
    {
        $kunjungan = Kunjungan::with(['sales', 'tujuan'])->latest()->get()->map(function ($k) {
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

            return [
                'id' => $k->id,
                'nomor' => $k->nomor,
                'tanggal' => \Carbon\Carbon::parse($k->tanggal)->format('d M Y'),
                'waktu' => $k->waktu,
                'sales' => $k->sales_nama,
                'jenis' => $k->jenis,
                'nama_tempat' => $namaTempat,
                'tujuan' => $k->tujuan_kunjungan,
                'hasil' => $k->hasil,
                'pic' => $pic,
                'pic_phone' => $pic_phone,
                'catatan' => $k->catatan,
                'status' => $k->status,
                'riwayat' => [] // Riwayat feature can be added later
            ];
        });

        $salesList = User::where('role', 'Sales')->pluck('name')->prepend('Semua Sales');

        return view('admin.kunjungan.index', compact('kunjungan', 'salesList'));
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
