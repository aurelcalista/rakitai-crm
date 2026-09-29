<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminProdiController extends Controller
{
    public function index(Request $request): View
    {
        // Exclude Prodi MI (Manajemen Informatika) per requirement
        $query = Prodi::where('kode', '!=', 'MI-D3')
            ->where('nama', 'NOT LIKE', '%Manajemen Informatika%')
            ->latest();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('nama', 'like', "%{$q}%")
                   ->orWhere('kode', 'like', "%{$q}%")
                   ->orWhere('fakultas', 'like', "%{$q}%");
            });
        }

        if ($request->filled('fakultas') && $request->fakultas !== 'all') {
            $query->where('fakultas', $request->fakultas);
        }

        if ($request->filled('jenjang') && $request->jenjang !== 'all') {
            $query->where('jenjang', $request->jenjang);
        }

        $prodisPaginated = $query->paginate(10)->withQueryString();

        $prodi = $prodisPaginated->getCollection()->map(function ($item) {
            $item->terdaftar = 0; // Mocked until we have a real relation
            $item->ukt = $item->ukt ?: $item->spp;
            return $item;
        });

        $totalS1 = Prodi::where('kode', '!=', 'MI-D3')->where('nama', 'NOT LIKE', '%Manajemen Informatika%')->where('jenjang', 'S1')->count();
        $totalD3 = Prodi::where('kode', '!=', 'MI-D3')->where('nama', 'NOT LIKE', '%Manajemen Informatika%')->where('jenjang', 'D3')->count();
        $totalS2 = Prodi::where('kode', '!=', 'MI-D3')->where('nama', 'NOT LIKE', '%Manajemen Informatika%')->where('jenjang', 'S2')->count();

        $fakultasList = \App\Models\MasterData::where('type', 'fakultas')->pluck('nama');
        $jenjangList  = \App\Models\MasterData::where('type', 'jenjang')->pluck('nama');
        
        return view('admin.prodi.index', compact('prodi', 'prodisPaginated', 'fakultasList', 'jenjangList', 'totalS1', 'totalD3', 'totalS2'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:50|unique:prodis,kode',
            'nama' => 'required|string|max:255',
            'fakultas' => 'required|string|max:255',
            'jenjang' => 'required|string|max:50',
            'kuota' => 'required|integer|min:0',
            'ukt' => 'nullable|string|max:100',
            'ukt_reguler' => 'nullable|string|max:100',
            'spp' => 'nullable|string|max:100',
            'status' => 'required|in:Aktif,Nonaktif',
        ]);

        if (empty($validated['ukt']) && !empty($validated['spp'])) {
            $validated['ukt'] = $validated['spp'];
        }

        Prodi::create($validated);

        return redirect()->back()->with('success', 'Program Studi berhasil ditambahkan!');
    }

    public function update(Request $request, Prodi $prodi)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:50|unique:prodis,kode,' . $prodi->id,
            'nama' => 'required|string|max:255',
            'fakultas' => 'required|string|max:255',
            'jenjang' => 'required|string|max:50',
            'kuota' => 'required|integer|min:0',
            'ukt' => 'nullable|string|max:100',
            'ukt_reguler' => 'nullable|string|max:100',
            'spp' => 'nullable|string|max:100',
            'status' => 'required|in:Aktif,Nonaktif',
        ]);

        if (empty($validated['ukt']) && !empty($validated['spp'])) {
            $validated['ukt'] = $validated['spp'];
        }

        $prodi->update($validated);

        return redirect()->back()->with('success', 'Program Studi berhasil diperbarui!');
    }

    public function destroy(Prodi $prodi)
    {
        $prodi->delete();
        return redirect()->back()->with('success', 'Program Studi berhasil dihapus!');
    }

    public function toggleStatus(Prodi $prodi)
    {
        $prodi->update([
            'status' => $prodi->status === 'Aktif' ? 'Nonaktif' : 'Aktif'
        ]);
        return redirect()->back()->with('success', "Status Prodi {$prodi->nama} berhasil diubah!");
    }
}
