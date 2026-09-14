<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminMasterDataController extends Controller
{
    public function index(): View
    {
        $types = [
            'status_prospek', 'status_followup', 'jenis_kunjungan', 
            'kategori_prospek', 'sumber_prospek', 'kategori_sekolah', 'kategori_perusahaan', 'fakultas', 'jenjang'
        ];
        
        $masterData = [];
        foreach ($types as $type) {
            $masterData[$type] = MasterData::where('type', $type)->get()->map(function ($item) {
                // TODO: count related records for 'jumlah' based on type
                $item->jumlah = 0; 
                return $item;
            });
        }

        return view('admin.master-data.index', compact('masterData'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:50',
            'kode' => 'required|string|max:50|unique:master_data,kode',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:Aktif,Nonaktif',
        ]);

        MasterData::create($validated);

        return redirect()->back()->with('success', 'Master data berhasil ditambahkan!');
    }

    public function update(Request $request, MasterData $masterData)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:50',
            'kode' => 'required|string|max:50|unique:master_data,kode,' . $masterData->id,
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:Aktif,Nonaktif',
        ]);

        $masterData->update($validated);

        return redirect()->back()->with('success', 'Master data berhasil diperbarui!');
    }

    public function destroy(MasterData $masterData)
    {
        $masterData->delete();
        return redirect()->back()->with('success', 'Master data berhasil dihapus!');
    }

    public function toggleStatus(MasterData $masterData)
    {
        $masterData->update([
            'status' => $masterData->status === 'Aktif' ? 'Nonaktif' : 'Aktif'
        ]);
        return redirect()->back()->with('success', "Status {$masterData->nama} berhasil diubah!");
    }
}
