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
            'biaya_pendaftaran' => 'nullable',
            'biaya_perlengkapan' => 'nullable',
            'biaya_dpp' => 'nullable',
            'biaya_uang_gedung' => 'nullable',
            'ukt_options' => 'nullable|array',
        ]);

        // 4 Komponen Biaya 1x Bayar (Bisa Rp 0)
        $validated['biaya_pendaftaran']  = (int) preg_replace('/[^0-9]/', '', (string) $request->input('biaya_pendaftaran', 0));
        $validated['biaya_perlengkapan'] = (int) preg_replace('/[^0-9]/', '', (string) $request->input('biaya_perlengkapan', 0));
        $validated['biaya_dpp']          = (int) preg_replace('/[^0-9]/', '', (string) $request->input('biaya_dpp', 0));
        $validated['biaya_uang_gedung']  = (int) preg_replace('/[^0-9]/', '', (string) $request->input('biaya_uang_gedung', 0));

        // 7 Opsi Pilihan Periode UKT (per 1, 3, 6, 12, 24, 36, 48 bulan)
        $cleanUktOptions = [];
        $rawOptions = (array) $request->input('ukt_options', []);
        foreach (Prodi::UKT_PERIODS as $key => $label) {
            $item = $rawOptions[$key] ?? [];
            $nominal = isset($item['nominal']) ? (int) preg_replace('/[^0-9]/', '', (string) $item['nominal']) : 0;
            $isActive = !empty($item['is_active']);
            $cleanUktOptions[$key] = [
                'key'       => $key,
                'label'     => $label,
                'nominal'   => $nominal,
                'is_active' => $isActive,
            ];
        }
        $validated['ukt_options'] = $cleanUktOptions;

        if (empty($validated['ukt'])) {
            $semNom = $cleanUktOptions['6_bulan']['nominal'] ?? 0;
            if ($semNom > 0) {
                $validated['ukt'] = 'Rp ' . number_format($semNom, 0, ',', '.');
            } elseif (!empty($validated['spp'])) {
                $validated['ukt'] = $validated['spp'];
            }
        }

        $fakultas = trim($validated['fakultas']);
        if (!empty($fakultas)) {
            \App\Models\MasterData::firstOrCreate(
                ['type' => 'fakultas', 'nama' => $fakultas],
                ['kode' => 'FAK-' . strtoupper(\Illuminate\Support\Str::slug($fakultas, '-')), 'status' => 'Aktif']
            );
        }

        Prodi::create($validated);

        return redirect()->back()->with('success', 'Program Studi beserta komponen biaya & pilihan UKT berhasil ditambahkan!');
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
            'biaya_pendaftaran' => 'nullable',
            'biaya_perlengkapan' => 'nullable',
            'biaya_dpp' => 'nullable',
            'biaya_uang_gedung' => 'nullable',
            'ukt_options' => 'nullable|array',
        ]);

        // 4 Komponen Biaya 1x Bayar (Bisa Rp 0)
        $validated['biaya_pendaftaran']  = (int) preg_replace('/[^0-9]/', '', (string) $request->input('biaya_pendaftaran', 0));
        $validated['biaya_perlengkapan'] = (int) preg_replace('/[^0-9]/', '', (string) $request->input('biaya_perlengkapan', 0));
        $validated['biaya_dpp']          = (int) preg_replace('/[^0-9]/', '', (string) $request->input('biaya_dpp', 0));
        $validated['biaya_uang_gedung']  = (int) preg_replace('/[^0-9]/', '', (string) $request->input('biaya_uang_gedung', 0));

        // 7 Opsi Pilihan Periode UKT (per 1, 3, 6, 12, 24, 36, 48 bulan)
        $cleanUktOptions = [];
        $rawOptions = (array) $request->input('ukt_options', []);
        foreach (Prodi::UKT_PERIODS as $key => $label) {
            $item = $rawOptions[$key] ?? [];
            $nominal = isset($item['nominal']) ? (int) preg_replace('/[^0-9]/', '', (string) $item['nominal']) : 0;
            $isActive = !empty($item['is_active']);
            $cleanUktOptions[$key] = [
                'key'       => $key,
                'label'     => $label,
                'nominal'   => $nominal,
                'is_active' => $isActive,
            ];
        }
        $validated['ukt_options'] = $cleanUktOptions;

        if (empty($validated['ukt'])) {
            $semNom = $cleanUktOptions['6_bulan']['nominal'] ?? 0;
            if ($semNom > 0) {
                $validated['ukt'] = 'Rp ' . number_format($semNom, 0, ',', '.');
            } elseif (!empty($validated['spp'])) {
                $validated['ukt'] = $validated['spp'];
            }
        }

        $fakultas = trim($validated['fakultas']);
        if (!empty($fakultas)) {
            \App\Models\MasterData::firstOrCreate(
                ['type' => 'fakultas', 'nama' => $fakultas],
                ['kode' => 'FAK-' . strtoupper(\Illuminate\Support\Str::slug($fakultas, '-')), 'status' => 'Aktif']
            );
        }

        $prodi->update($validated);

        return redirect()->back()->with('success', 'Program Studi beserta komponen biaya & pilihan UKT berhasil diperbarui!');
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
