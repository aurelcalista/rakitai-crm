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
            'prodi_id'       => 'required|exists:prodis,id',
            'kelas'          => 'required|in:Reguler,Karyawan',
        ]);

        $cs = \App\Models\User::where('role', 'CS')->where('status', 'Aktif')->first();

        $prospek = Prospek::create([
            'name'             => $validated['nama'],
            'whatsapp'         => $validated['no_whatsapp'],
            'pic'              => $validated['nama'],
            'pic_phone'        => $validated['no_whatsapp'],
            'type'             => $validated['jenis_instansi'] === 'PT' ? 'Corporate' : 'Sekolah',
            'sekolah_id'       => $validated['jenis_instansi'] === 'Sekolah' ? $validated['sekolah_id'] : null,
            'perusahaan_id'    => $validated['jenis_instansi'] === 'PT' ? $validated['perusahaan_id'] : null,
            'prodi_id'         => $validated['prodi_id'],
            'kelas'            => $validated['kelas'],
            'status'           => 'BARU',
            'stage_number'     => 1,
            'source'           => 'Website CIC', // Change to 'Website CIC' for better tracking
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
        return view('quick-contact.terima-kasih');
    }
}
