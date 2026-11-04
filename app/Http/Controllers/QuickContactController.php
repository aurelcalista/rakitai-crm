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

    public function createCorporate()
    {
        $perusahaans = Perusahaan::where('status', 'Aktif')->orderBy('nama')->get();
        $prodis = Prodi::where('status', 'Aktif')->orderBy('nama')->get();

        return view('quick-contact.create-corporate', compact('perusahaans', 'prodis'));
    }

    public function store(Request $request)
    {
        if ($request->filled('no_whatsapp')) {
            $request->merge(['no_whatsapp' => \App\Support\WhatsAppHelper::prepareForValidation($request->no_whatsapp)]);
        }
        if ($request->filled('wa_ortu')) {
            $request->merge(['wa_ortu' => \App\Support\WhatsAppHelper::prepareForValidation($request->wa_ortu)]);
        }

        $validated = $request->validate([
            'jenis_instansi'    => 'required|in:Sekolah,PT',
            'sekolah_id'        => 'nullable|exists:sekolahs,id',
            'sekolah_manual'    => 'nullable|string|max:255',
            'perusahaan_id'     => 'nullable|exists:perusahaans,id',
            'perusahaan_manual' => 'nullable|string|max:255',
            'nama'              => 'required|string|max:255',
            'no_whatsapp'       => ['required', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: false)],
            'wa_ortu'           => ['nullable', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: true)],
            'asal_kelas'        => 'nullable|string|max:255',
            'prodi_id'          => 'required|string',
            'prodi_lainnya'     => 'nullable|required_if:prodi_id,lainnya|string|max:255',
            'kelas'             => 'required|in:Reguler,Karyawan',
        ]);

        if ($validated['jenis_instansi'] === 'Sekolah' && empty($validated['sekolah_id']) && empty($request->sekolah_manual)) {
            return redirect()->back()->withInput()->withErrors(['sekolah_id' => 'Nama sekolah wajib dipilih atau diisi manual jika belum terdaftar.']);
        }

        $name = $validated['nama'];
        $sekolahId = null;
        $perusahaanId = null;
        $wilayahId = null;
        $manualNotes = null;

        if ($validated['jenis_instansi'] === 'Sekolah') {
            if (!empty($validated['sekolah_id'])) {
                $sekolah = Sekolah::find($validated['sekolah_id']);
                if ($sekolah) {
                    $name = $sekolah->nama;
                    $sekolahId = $sekolah->id;
                    $wilayahId = $sekolah->wilayah_id;
                }
            } elseif (!empty($request->sekolah_manual)) {
                $manualName = trim($request->sekolah_manual);
                // Cek apakah nama manual cocok (case-insensitive) dengan data master sekolah yang ada
                $matched = Sekolah::whereRaw('LOWER(nama) = ?', [strtolower($manualName)])->first();
                if ($matched) {
                    $name = $matched->nama;
                    $sekolahId = $matched->id;
                    $wilayahId = $matched->wilayah_id;
                } else {
                    // Buat sekolah baru di master data, lokasi NULL untuk dilengkapi Admin
                    $newSchool = Sekolah::create([
                        'kode'   => 'SCH-' . strtoupper(Str::random(6)),
                        'nama'   => $manualName,
                        'status' => 'Aktif',
                    ]);
                    $name = $newSchool->nama;
                    $sekolahId = $newSchool->id;
                    $wilayahId = null;
                    $manualNotes = 'Sekolah baru otomatis ditambahkan ke Master Data: ' . $manualName;
                }
            }
        } elseif ($validated['jenis_instansi'] === 'PT') {
            if (!empty($validated['perusahaan_id'])) {
                $perusahaan = Perusahaan::find($validated['perusahaan_id']);
                if ($perusahaan) {
                    $name = $perusahaan->nama;
                    $perusahaanId = $perusahaan->id;
                    $wilayahId = $perusahaan->wilayah_id;
                }
            } elseif (!empty($request->perusahaan_manual)) {
                $name = trim($request->perusahaan_manual) ?: $validated['nama'];
                $manualNotes = 'Perusahaan (Belum Terdaftar): ' . trim($request->perusahaan_manual);
            }
        }

        $notes = 'Diinput mandiri melalui form Presensi Siswa';
        if ($manualNotes) {
            $notes .= ' | ' . $manualNotes;
        }

        $formattedWa = \App\Support\WhatsAppHelper::formatForDatabase($validated['no_whatsapp']);

        // Deduplication: cek apakah sudah ada prospek dengan nomor WA yang sama
        $existing = Prospek::where('whatsapp', $formattedWa)->first();

        if ($existing) {
            // Update data prospek yang sudah ada dan catat sebagai timeline baru
            $existing->update([
                'pic'           => $validated['nama'],
                'pic_phone'     => $formattedWa,
                'wa_ortu'       => \App\Support\WhatsAppHelper::formatForDatabase($validated['wa_ortu'] ?? null) ?: $existing->wa_ortu,
                'asal_kelas'    => $validated['asal_kelas'] ?? $existing->asal_kelas,
                'prodi_id'      => $validated['prodi_id'] === 'lainnya' ? $existing->prodi_id : ($validated['prodi_id'] ?? $existing->prodi_id),
                'prodi_lainnya' => $validated['prodi_id'] === 'lainnya' ? ($validated['prodi_lainnya'] ?? $existing->prodi_lainnya) : $existing->prodi_lainnya,
                'kelas'         => $validated['kelas'] ?? $existing->kelas,
                'sekolah_id'    => $sekolahId ?: $existing->sekolah_id,
                'wilayah_id'    => $wilayahId ?: $existing->wilayah_id,
                'perusahaan_id' => $perusahaanId ?: $existing->perusahaan_id,
                'source'        => Prospek::SOURCE_PRESENSI_SISWA,
            ]);

            ProspekTimeline::create([
                'prospek_id'   => $existing->id,
                'user_id'      => null,
                'title'        => 'Presensi Ulang – Data Diperbarui',
                'notes'        => 'Calon mahasiswa melakukan presensi kembali melalui form Presensi Siswa. Data diperbarui tanpa membuat duplikat.' . ($notes ? ' | ' . $notes : ''),
                'status_after' => $existing->status,
                'time'         => now(),
            ]);

            return redirect()->route('kontak-cepat.terima-kasih');
        }

        $prospek = Prospek::create([
            'name'             => $name,
            'whatsapp'         => $formattedWa,
            'wa_ortu'          => \App\Support\WhatsAppHelper::formatForDatabase($validated['wa_ortu'] ?? null),
            'pic'              => $validated['nama'],
            'pic_phone'        => \App\Support\WhatsAppHelper::formatForDatabase($validated['no_whatsapp']),
            'type'             => $validated['jenis_instansi'] === 'PT' ? 'Corporate' : 'Sekolah',
            'sekolah_id'       => $sekolahId,
            'wilayah_id'       => $wilayahId,
            'asal_kelas'       => $validated['asal_kelas'] ?? null,
            'perusahaan_id'    => $perusahaanId,
            'prodi_id'         => $validated['prodi_id'] === 'lainnya' ? null : $validated['prodi_id'],
            'prodi_lainnya'    => $validated['prodi_id'] === 'lainnya' ? $validated['prodi_lainnya'] : null,
            'kelas'            => $validated['kelas'],
            'status'           => 'BARU',
            'stage_number'     => 1,
            'source'           => Prospek::SOURCE_PRESENSI_SISWA,
            'notes'            => $notes,
            'owner_id'         => null, // Owner awal murni Sistem
            'sales_id'         => null, // Tidak otomatis menugaskan Sales
            'cs_id'            => null, // Tidak otomatis menugaskan CS
            'status_takeover'  => 'Belum Di-takeover',
            'takeover_by_id'   => null,
            'takeover_at'      => null,
        ]);

        ProspekTimeline::create([
            'prospek_id'   => $prospek->id,
            'user_id'      => null, // Owner awal: Sistem
            'title'        => 'Presensi Siswa Masuk',
            'notes'        => 'Data presensi siswa berhasil masuk ke CRM dari formulir presensi sekolah.',
            'status_after' => $prospek->status,
            'time'         => now(),
        ]);

        return redirect()->route('kontak-cepat.terima-kasih');
    }

    public function storeCorporate(Request $request)
    {
        $request->merge(['jenis_instansi' => 'PT']);
        
        if ($request->filled('no_whatsapp')) {
            $request->merge(['no_whatsapp' => \App\Support\WhatsAppHelper::prepareForValidation($request->no_whatsapp)]);
        }
        if ($request->filled('wa_ortu')) {
            $request->merge(['wa_ortu' => \App\Support\WhatsAppHelper::prepareForValidation($request->wa_ortu)]);
        }
        
        $validated = $request->validate([
            'perusahaan_id'     => 'nullable|exists:perusahaans,id',
            'perusahaan_manual' => 'nullable|string|max:255',
            'nama'              => 'required|string|max:255',
            'no_whatsapp'       => ['required', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: false)],
            'wa_ortu'           => ['nullable', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: true)],
            'asal_kelas'        => 'nullable|string|max:255',
            'prodi_id'          => 'required|string',
            'prodi_lainnya'     => 'nullable|required_if:prodi_id,lainnya|string|max:255',
            'kelas'             => 'required|in:Reguler,Karyawan',
        ]);

        $name = $validated['nama'];
        $perusahaanId = null;
        $wilayahId = null;
        $manualNotes = null;

        if (!empty($validated['perusahaan_id'])) {
            $perusahaan = Perusahaan::find($validated['perusahaan_id']);
            if ($perusahaan) {
                $name = $perusahaan->nama;
                $perusahaanId = $perusahaan->id;
                $wilayahId = $perusahaan->wilayah_id;
            }
        } elseif (!empty($request->perusahaan_manual)) {
            $name = trim($request->perusahaan_manual);
            $manualNotes = 'Perusahaan (Belum Terdaftar): ' . $name;
        }

        $notes = 'Diinput mandiri melalui form Presensi Corporate';
        if ($manualNotes) {
            $notes .= ' | ' . $manualNotes;
        }

        $formattedWa = \App\Support\WhatsAppHelper::formatForDatabase($validated['no_whatsapp']);

        // Deduplication: cek apakah sudah ada prospek dengan nomor WA yang sama
        $existing = Prospek::where('whatsapp', $formattedWa)->first();

        if ($existing) {
            $existing->update([
                'pic'           => $validated['nama'],
                'pic_phone'     => $formattedWa,
                'wa_ortu'       => \App\Support\WhatsAppHelper::formatForDatabase($validated['wa_ortu'] ?? null) ?: $existing->wa_ortu,
                'asal_kelas'    => $validated['asal_kelas'] ?? $existing->asal_kelas,
                'prodi_id'      => $validated['prodi_id'] === 'lainnya' ? $existing->prodi_id : ($validated['prodi_id'] ?? $existing->prodi_id),
                'prodi_lainnya' => $validated['prodi_id'] === 'lainnya' ? ($validated['prodi_lainnya'] ?? $existing->prodi_lainnya) : $existing->prodi_lainnya,
                'kelas'         => $validated['kelas'] ?? $existing->kelas,
                'perusahaan_id' => $perusahaanId ?: $existing->perusahaan_id,
                'wilayah_id'    => $wilayahId ?: $existing->wilayah_id,
            ]);

            ProspekTimeline::create([
                'prospek_id'   => $existing->id,
                'user_id'      => null,
                'title'        => 'Presensi Ulang (Corporate) – Data Diperbarui',
                'notes'        => 'Calon mahasiswa melakukan presensi kembali melalui form Presensi Corporate. Data diperbarui tanpa membuat duplikat.',
                'status_after' => $existing->status,
                'time'         => now(),
            ]);

            return redirect()->route('kontak-cepat.terima-kasih-corporate');
        }

        $prospek = Prospek::create([
            'name'             => $name,
            'whatsapp'         => $formattedWa,
            'wa_ortu'          => \App\Support\WhatsAppHelper::formatForDatabase($validated['wa_ortu'] ?? null),
            'pic'              => $validated['nama'],
            'pic_phone'        => \App\Support\WhatsAppHelper::formatForDatabase($validated['no_whatsapp']),
            'type'             => 'Corporate',
            'sekolah_id'       => null,
            'wilayah_id'       => $wilayahId,
            'asal_kelas'       => $validated['asal_kelas'] ?? null,
            'perusahaan_id'    => $perusahaanId,
            'prodi_id'         => $validated['prodi_id'] === 'lainnya' ? null : $validated['prodi_id'],
            'prodi_lainnya'    => $validated['prodi_id'] === 'lainnya' ? $validated['prodi_lainnya'] : null,
            'kelas'            => $validated['kelas'],
            'status'           => 'BARU',
            'stage_number'     => 1,
            'source'           => Prospek::SOURCE_PRESENSI_SISWA,
            'notes'            => $notes,
            'owner_id'         => null, // Owner awal Sistem
            'sales_id'         => null,
            'cs_id'            => null,
            'status_takeover'  => 'Belum Di-takeover',
            'takeover_by_id'   => null,
            'takeover_at'      => null,
        ]);

        ProspekTimeline::create([
            'prospek_id'   => $prospek->id,
            'user_id'      => null,
            'title'        => 'Presensi Corporate Masuk',
            'notes'        => 'Data presensi berhasil masuk ke CRM dari form presensi corporate.',
            'status_after' => $prospek->status,
            'time'         => now(),
        ]);

        return redirect()->route('kontak-cepat.terima-kasih-corporate');
    }

    public function terimaKasih()
    {
        $tkTitle = \App\Models\MasterData::where('type', 'setting')->where('kode', 'TK_TITLE')->first();
        $tkSubtitle = \App\Models\MasterData::where('type', 'setting')->where('kode', 'TK_SUBTITLE')->first();
        $tkImage = \App\Models\MasterData::where('type', 'setting')->where('kode', 'TK_IMAGE')->first();

        return view('quick-contact.terima-kasih', compact('tkTitle', 'tkSubtitle', 'tkImage'));
    }

    public function terimaKasihCorporate()
    {
        $tkTitle = \App\Models\MasterData::where('type', 'setting')->where('kode', 'TK_CORP_TITLE')->first();
        $tkSubtitle = \App\Models\MasterData::where('type', 'setting')->where('kode', 'TK_CORP_SUBTITLE')->first();
        $tkImage = \App\Models\MasterData::where('type', 'setting')->where('kode', 'TK_CORP_IMAGE')->first();

        return view('quick-contact.terima-kasih-corporate', compact('tkTitle', 'tkSubtitle', 'tkImage'));
    }
}
