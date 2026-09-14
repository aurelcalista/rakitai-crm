<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminWilayahController extends Controller
{
    public function index(): View
    {
        $wilayah = Wilayah::withCount(['sekolahs', 'perusahaans'])->latest()->get()->map(function ($item) {
            $item->jumlah_sekolah = $item->sekolahs_count;
            $item->jumlah_perusahaan = $item->perusahaans_count;
            $item->kecamatan = $item->kecamatans ?? [];
            return $item;
        });
        
        return view('admin.wilayah.index', compact('wilayah'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:50|unique:wilayahs,kode',
            'nama' => 'required|string|max:255',
            'kecamatans' => 'nullable|string',
            'status' => 'required|in:Aktif,Nonaktif',
        ]);

        $kecamatansArray = array_map('trim', explode(',', $validated['kecamatans'] ?? ''));
        $validated['kecamatans'] = array_filter($kecamatansArray);

        Wilayah::create($validated);

        return redirect()->back()->with('success', 'Wilayah berhasil ditambahkan!');
    }

    public function update(Request $request, Wilayah $wilayah)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:50|unique:wilayahs,kode,' . $wilayah->id,
            'nama' => 'required|string|max:255',
            'kecamatans' => 'nullable|string',
            'status' => 'required|in:Aktif,Nonaktif',
        ]);

        $kecamatansArray = array_map('trim', explode(',', $validated['kecamatans'] ?? ''));
        $validated['kecamatans'] = array_filter($kecamatansArray);

        $wilayah->update($validated);

        return redirect()->back()->with('success', 'Wilayah berhasil diperbarui!');
    }

    public function destroy(Wilayah $wilayah)
    {
        $wilayah->delete();
        return redirect()->back()->with('success', 'Wilayah berhasil dihapus!');
    }

    public function toggleStatus(Wilayah $wilayah)
    {
        $wilayah->update([
            'status' => $wilayah->status === 'Aktif' ? 'Nonaktif' : 'Aktif'
        ]);
        return redirect()->back()->with('success', "Status wilayah {$wilayah->nama} berhasil diubah!");
    }
}
