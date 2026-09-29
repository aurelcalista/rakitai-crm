<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sekolah;
use App\Models\Wilayah;
use App\Models\MasterData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSekolahController extends Controller
{
    public function index(Request $request): View
    {
        $query = Sekolah::with(['kategori', 'wilayah'])->withCount('kunjungans')->latest();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('nama', 'like', "%{$q}%")
                   ->orWhere('kode', 'like', "%{$q}%")
                   ->orWhere('kecamatan', 'like', "%{$q}%")
                   ->orWhere('pic_name', 'like', "%{$q}%");
            });
        }

        if ($request->filled('wilayah') && $request->wilayah !== 'all') {
            $query->whereHas('wilayah', function ($w) use ($request) {
                $w->where('nama', $request->wilayah);
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $sekolahsPaginated = $query->paginate(10)->withQueryString();

        $sekolah = $sekolahsPaginated->getCollection()->map(function ($s) {
            $s->kategori_nama = $s->kategori ? $s->kategori->nama : '-';
            $s->wilayah_nama  = $s->wilayah  ? $s->wilayah->nama  : '-';
            $s->jumlah_kunjungan = $s->kunjungans_count;
            $s->pic           = $s->pic_name    ?? '-';
            $s->kecamatan     = $s->kecamatan   ?? '-';
            $s->telepon       = $s->telepon      ?? '-';
            $s->email         = $s->email        ?? '-';
            $s->website       = $s->website      ?? '-';
            $s->alamat        = $s->alamat       ?? '-';
            return $s;
        });

        $wilayahList = Wilayah::whereNull('parent_id')->with('children')->get();
        $kategoriList = MasterData::where('type', 'kategori_sekolah')->get();

        return view('admin.sekolah.index', [
            'sekolah' => $sekolah,
            'sekolahsPaginated' => $sekolahsPaginated,
            'wilayahList' => $wilayahList,
            'kategoriList' => $kategoriList
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'nullable|string|max:50|unique:sekolahs,kode',
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
            $validated['kode'] = 'SCH-' . strtoupper(\Illuminate\Support\Str::random(6));
        }

        Sekolah::create($validated);

        return redirect()->back()->with('success', 'Sekolah berhasil ditambahkan!');
    }

    public function update(Request $request, Sekolah $sekolah)
    {
        $validated = $request->validate([
            'kode' => 'nullable|string|max:50|unique:sekolahs,kode,' . $sekolah->id,
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

        $sekolah->update($validated);

        return redirect()->back()->with('success', 'Sekolah berhasil diperbarui!');
    }

    public function destroy(Sekolah $sekolah)
    {
        $sekolah->delete();
        return redirect()->back()->with('success', 'Sekolah berhasil dihapus!');
    }

    public function toggleStatus(Sekolah $sekolah)
    {
        $sekolah->update([
            'status' => $sekolah->status === 'Aktif' ? 'Nonaktif' : 'Aktif'
        ]);
        return redirect()->back()->with('success', "Status sekolah {$sekolah->nama} berhasil diubah!");
    }
}
