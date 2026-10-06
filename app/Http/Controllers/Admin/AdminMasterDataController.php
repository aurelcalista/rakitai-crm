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
            'kategori_prospek', 'sumber_prospek', 'kategori_sekolah', 'kategori_perusahaan', 'program_studi', 'jenjang'
        ];
        
        $masterData = [];
        foreach ($types as $type) {
            $masterData[$type] = MasterData::where('type', $type)->orderBy('id')->get()->map(function ($item) use ($type) {
                $count = 0;
                try {
                    switch ($type) {
                        case 'status_prospek':
                            $count = \App\Models\Prospek::where('status', $item->nama)->count();
                            break;
                        case 'status_followup':
                            $count = \App\Models\FollowUp::where('hasil', $item->nama)->count();
                            break;
                        case 'jenis_kunjungan':
                            $count = \App\Models\Kunjungan::where('tujuan', $item->nama)->orWhere('tipe', $item->nama)->count();
                            break;
                        case 'kategori_prospek':
                            $count = \App\Models\Prospek::where('category', $item->nama)->count();
                            break;
                        case 'sumber_prospek':
                            $count = \App\Models\Prospek::where('source', $item->nama)->count();
                            break;
                        case 'kategori_sekolah':
                            $count = \App\Models\Sekolah::where('kategori_id', $item->id)->orWhere('kategori', $item->nama)->count();
                            break;
                        case 'kategori_perusahaan':
                            $count = \App\Models\Perusahaan::where('kategori_id', $item->id)->orWhere('kategori', $item->nama)->count();
                            break;
                        case 'program_studi':
                            $count = \App\Models\Prospek::where('prodi_id', $item->id)->count();
                            break;
                        case 'jenjang':
                            $count = \App\Models\Prodi::where('jenjang', $item->nama)->count();
                            break;
                        default:
                            $count = 0;
                    }
                } catch (\Throwable $e) {
                    $count = 0;
                }
                $item->jumlah = $count;
                return $item;
            });
        }

        $tahunAkademiks = \App\Models\TahunAkademik::orderBy('nama', 'desc')->get();

        return view('admin.master-data.index', compact('masterData', 'tahunAkademiks'));
    }

    public function storeTahunAkademik(Request $request)
    {
        $request->validate([
            'nama'   => ['required', 'string', 'max:20', 'unique:tahun_akademiks,nama', 'regex:/^\d{4}\/\d{4}$/'],
            'status' => 'required|in:Aktif,Non-Aktif',
        ], [
            'nama.regex' => 'Format Tahun Akademik harus YYYY/YYYY (contoh: 2028/2029)',
        ]);

        if ($request->status === 'Aktif') {
            $ta = \App\Models\TahunAkademik::create([
                'nama'   => $request->nama,
                'status' => 'Non-Aktif',
            ]);
            \App\Services\AkademikService::activate($ta);
        } else {
            \App\Models\TahunAkademik::create([
                'nama'   => $request->nama,
                'status' => 'Non-Aktif',
            ]);
        }

        return redirect()->back()->with('success', "Tahun Akademik {$request->nama} berhasil ditambahkan!");
    }

    public function activateTahunAkademik(\App\Models\TahunAkademik $tahunAkademik)
    {
        \App\Services\AkademikService::activate($tahunAkademik);
        return redirect()->back()->with('success', "Tahun Akademik {$tahunAkademik->nama} sekarang menjadi SATU-SATUNYA yang aktif sebagai dasar data sistem!");
    }

    public function destroyTahunAkademik(\App\Models\TahunAkademik $tahunAkademik)
    {
        if ($tahunAkademik->status === 'Aktif') {
            return redirect()->back()->with('error', 'Tidak dapat menghapus Tahun Akademik yang sedang AKTIF.');
        }
        $tahunAkademik->delete();
        return redirect()->back()->with('success', "Tahun Akademik {$tahunAkademik->nama} berhasil dihapus.");
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

    /**
     * Admin update dynamic weights for Indikator Wilayah (Kontak + Closing = 100%).
     */
    public function updateWeights(Request $request)
    {
        if (strtolower(auth()->user()->role) !== 'admin') {
            abort(403, 'Hanya Admin yang berwenang mengubah konfigurasi bobot indikator wilayah.');
        }

        $request->validate([
            'bobot_kontak'  => 'required|numeric|min:0|max:100',
            'bobot_closing' => 'required|numeric|min:0|max:100',
        ]);

        $service = new \App\Services\WilayahPerformanceService();
        $service->updateWeights((float)$request->bobot_kontak, (float)$request->bobot_closing);

        return redirect()->back()->with('success', "Bobot Indikator Wilayah berhasil diperbarui! (Kontak: {$request->bobot_kontak}%, Closing: {$request->bobot_closing}%)");
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
