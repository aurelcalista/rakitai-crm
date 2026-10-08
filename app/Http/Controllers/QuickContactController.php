<?php

namespace App\Http\Controllers;

use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuickContactController extends Controller
{
    public function create()
    {
        $sekolahs = Sekolah::where('status', 'Aktif')->orderBy('nama')->get();
        $perusahaans = Perusahaan::where('status', 'Aktif')->orderBy('nama')->get();
        $prodis = Prodi::where('status', 'Aktif')->orderBy('nama')->get();

        return view('quick-contact.create', compact('sekolahs', 'perusahaans', 'prodis'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'jenis_instansi' => 'required|in:Sekolah,PT',
            'sekolah_id'     => 'nullable|required_if:jenis_instansi,Sekolah|exists:sekolahs,id',
            'perusahaan_id'  => 'nullable|required_if:jenis_instansi,PT|exists:perusahaans,id',
            'nama'           => 'required|string|max:255',
            'no_whatsapp'    => 'required|string|max:20',
            'wa_ortu'        => 'nullable|string|max:20',
            'asal_kelas'     => 'nullable|required_if:jenis_instansi,Sekolah|string|max:255',
            'prodi_id'       => 'required|string',
            'prodi_lainnya'  => 'nullable|required_if:prodi_id,lainnya|string|max:255',
            'kelas'          => 'required|in:Reguler,Karyawan',
        ]);

        $cs = \App\Models\User::where('role', 'CS')->where('status', 'Aktif')->first();

        $name = $validated['nama'];
        if ($validated['jenis_instansi'] === 'Sekolah' && !empty($validated['sekolah_id'])) {
            $sekolah = Sekolah::find($validated['sekolah_id']);
            if ($sekolah) {
                $name = $sekolah->nama;
            }
        } elseif ($validated['jenis_instansi'] === 'PT' && !empty($validated['perusahaan_id'])) {
            $perusahaan = Perusahaan::find($validated['perusahaan_id']);
            if ($perusahaan) {
                $name = $perusahaan->nama;
            }
        }

        $prospek = Prospek::create([
            'name'             => $name,
            'whatsapp'         => $validated['no_whatsapp'],
            'wa_ortu'          => $validated['wa_ortu'] ?? null,
            'pic'              => $validated['nama'],
            'pic_phone'        => $validated['no_whatsapp'],
            'type'             => $validated['jenis_instansi'] === 'PT' ? 'Corporate' : 'Sekolah',
            'sekolah_id'       => $validated['jenis_instansi'] === 'Sekolah' ? $validated['sekolah_id'] : null,
            'asal_kelas'       => $validated['asal_kelas'] ?? null,
            'perusahaan_id'    => $validated['jenis_instansi'] === 'PT' ? $validated['perusahaan_id'] : null,
            'prodi_id'         => $validated['prodi_id'] === 'lainnya' ? null : $validated['prodi_id'],
            'prodi_lainnya'    => $validated['prodi_id'] === 'lainnya' ? $validated['prodi_lainnya'] : null,
            'kelas'            => $validated['kelas'],
            'status'           => 'BARU',
            'stage_number'     => 1,
            'source'           => 'Lainnya',
            'notes'            => 'Diinput mandiri melalui form Tambah Kontak Cepat',
            'cs_id'            => $cs ? $cs->id : null,
        ]);

        ProspekTimeline::create([
            'prospek_id'   => $prospek->id,
            'user_id'      => null, // System or unauthenticated user
            'title'        => 'Prospek Masuk (Kontak Cepat)',
            'notes'        => 'Prospek diinput mandiri oleh calon mahasiswa melalui form Tambah Kontak Cepat dari Landing Page.',
            'status_after' => $prospek->status,
            'time'         => now(),
        ]);

        return redirect()->route('kontak-cepat.terima-kasih');
    }

    public function terimaKasih()
    {
        $tkTitle = \App\Models\MasterData::where('type', 'setting')->where('kode', 'TK_TITLE')->first();
        $tkSubtitle = \App\Models\MasterData::where('type', 'setting')->where('kode', 'TK_SUBTITLE')->first();
        $tkImage = \App\Models\MasterData::where('type', 'setting')->where('kode', 'TK_IMAGE')->first();

        return view('quick-contact.terima-kasih', compact('tkTitle', 'tkSubtitle', 'tkImage'));
    }
}
