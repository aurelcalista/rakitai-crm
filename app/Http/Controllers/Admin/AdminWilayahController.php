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
        $wilayah = Wilayah::whereNull('parent_id')
            ->with(['children'])
            ->withCount(['sekolahs', 'perusahaans'])
            ->latest()
            ->get()
            ->map(function ($item) {
                $item->jumlah_sekolah = $item->sekolahs_count;
                $item->jumlah_perusahaan = $item->perusahaans_count;
                $item->kecamatan = $item->children->pluck('nama')->toArray();
                return $item;
            });
        
        return view('admin.wilayah.index', compact('wilayah'));
    }

    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('create', Wilayah::class);

        $validated = $request->validate([
            'kode' => 'required|string|max:50|unique:wilayahs,kode',
            'nama' => 'required|string|max:255',
            'kecamatans' => 'nullable|string',
            'status' => 'required|in:Aktif,Nonaktif',
        ]);

        $kecamatansArray = array_map('trim', explode(',', $validated['kecamatans'] ?? ''));
        $validated['kecamatans'] = array_filter($kecamatansArray);

        $wilayah = Wilayah::create([
            'kode' => $validated['kode'],
            'nama' => $validated['nama'],
            'status' => $validated['status'],
            'level' => 'Kota/Kabupaten',
            'parent_id' => null,
        ]);

        foreach ($validated['kecamatans'] as $index => $kec) {
            Wilayah::create([
                'kode' => $wilayah->kode . '-' . uniqid(),
                'nama' => $kec,
                'level' => 'Kecamatan',
                'parent_id' => $wilayah->id,
                'status' => 'Aktif',
            ]);
        }

        return redirect()->back()->with('success', 'Wilayah berhasil ditambahkan!');
    }

    public function update(Request $request, Wilayah $wilayah)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $wilayah);
        $validated = $request->validate([
            'kode' => 'required|string|max:50|unique:wilayahs,kode,' . $wilayah->id,
            'nama' => 'required|string|max:255',
            'kecamatans' => 'nullable|string',
            'status' => 'required|in:Aktif,Nonaktif',
        ]);

        $kecamatansArray = array_map('trim', explode(',', $validated['kecamatans'] ?? ''));
        $validated['kecamatans'] = array_filter($kecamatansArray);

        $wilayah->update([
            'kode' => $validated['kode'],
            'nama' => $validated['nama'],
            'status' => $validated['status'],
        ]);

        // Keep existing kecamatans that match, delete removed, add new
        $existingKecs = $wilayah->children()->get()->keyBy('nama');
        $newKecNames = $validated['kecamatans'];

        // Delete removed
        foreach ($existingKecs as $nama => $kecModel) {
            if (!in_array($nama, $newKecNames)) {
                $kecModel->delete();
            }
        }

        // Add new
        foreach ($newKecNames as $index => $nama) {
            if (!$existingKecs->has($nama)) {
                Wilayah::create([
                    'kode' => $wilayah->kode . '-' . uniqid(),
                    'nama' => $nama,
                    'level' => 'Kecamatan',
                    'parent_id' => $wilayah->id,
                    'status' => 'Aktif',
                ]);
            }
        }

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
