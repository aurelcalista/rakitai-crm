<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminProdiController extends Controller
{
    public function index(): View
    {
        // Exclude Prodi MI (Manajemen Informatika) per requirement
        $prodi = Prodi::where('kode', '!=', 'MI-D3')
            ->where('nama', 'NOT LIKE', '%Manajemen Informatika%')
            ->latest()
            ->get()
            ->map(function ($item) {
                $item->terdaftar = 0; // Mocked until we have a real relation
                $item->ukt = $item->ukt ?: $item->spp;
                return $item;
            });

        $fakultasList = \App\Models\MasterData::where('type', 'fakultas')->pluck('nama');
        $jenjangList  = \App\Models\MasterData::where('type', 'jenjang')->pluck('nama');
        
        return view('admin.prodi.index', compact('prodi', 'fakultasList', 'jenjangList'));
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
