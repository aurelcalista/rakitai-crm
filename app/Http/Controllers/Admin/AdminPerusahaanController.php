<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Perusahaan;
use App\Models\Wilayah;
use App\Models\MasterData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPerusahaanController extends Controller
{
    public function index(Request $request): View
    {
        $query = Perusahaan::with(['kategori', 'wilayah'])->withCount('kunjungans')->latest();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('nama', 'like', "%{$q}%")
                   ->orWhere('kode', 'like', "%{$q}%")
                   ->orWhere('kecamatan', 'like', "%{$q}%")
                   ->orWhere('pic_name', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $perusahaansPaginated = $query->paginate(10)->withQueryString();

        $perusahaan = $perusahaansPaginated->getCollection()->map(function ($p) {
            $p->kategori_nama = $p->kategori ? $p->kategori->nama : '-';
            $p->wilayah_nama = $p->wilayah ? $p->wilayah->kode . ' ' . $p->wilayah->nama : '-';
            $p->jumlah_kunjungan = $p->kunjungans_count;
            return $p;
        });

        $wilayahList = Wilayah::whereNull('parent_id')->with('children')->get();
        $kategoriList = MasterData::where('type', 'kategori_perusahaan')->get();

        return view('admin.perusahaan.index', compact('perusahaan', 'perusahaansPaginated', 'wilayahList', 'kategoriList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'nullable|string|max:50|unique:perusahaans,kode',
            'nama' => 'required|string|max:255',
            'kategori_id' => 'required|exists:master_data,id',
            'wilayah_id' => 'required|exists:wilayahs,id',
            'kecamatan' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'pic_name' => 'nullable|string|max:255',
            'pic_jabatan' => 'nullable|string|max:255',
            'pic_phone' => 'nullable|string|max:50',
            'status' => 'required|in:Aktif,Nonaktif',
        ]);

        if (empty($validated['kode'])) {
            $validated['kode'] = 'PRS-' . strtoupper(\Illuminate\Support\Str::random(6));
        }

        Perusahaan::create($validated);

        return redirect()->back()->with('success', 'Perusahaan berhasil ditambahkan!');
    }

    public function update(Request $request, Perusahaan $perusahaan)
    {
        $validated = $request->validate([
            'kode' => 'nullable|string|max:50|unique:perusahaans,kode,' . $perusahaan->id,
            'nama' => 'required|string|max:255',
            'kategori_id' => 'required|exists:master_data,id',
            'wilayah_id' => 'required|exists:wilayahs,id',
            'kecamatan' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'pic_name' => 'nullable|string|max:255',
            'pic_jabatan' => 'nullable|string|max:255',
            'pic_phone' => 'nullable|string|max:50',
            'status' => 'required|in:Aktif,Nonaktif',
        ]);

        if (empty($validated['kode'])) {
            unset($validated['kode']);
        }

        $perusahaan->update($validated);

        return redirect()->back()->with('success', 'Perusahaan berhasil diperbarui!');
    }

    public function destroy(Perusahaan $perusahaan)
    {
        $perusahaan->delete();
        return redirect()->back()->with('success', 'Perusahaan berhasil dihapus!');
    }

    public function toggleStatus(Perusahaan $perusahaan)
    {
        $perusahaan->update([
            'status' => $perusahaan->status === 'Aktif' ? 'Nonaktif' : 'Aktif'
        ]);
        return redirect()->back()->with('success', "Status perusahaan {$perusahaan->nama} berhasil diubah!");
    }
}
