<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Support\WhatsAppHelper;
use Illuminate\Support\Facades\DB;

class PresensiController extends Controller
{
    /**
     * Store presensi sekolah via API.
     */
    public function storeSchool(Request $request)
    {
        $request->merge(['jenis_instansi' => 'Sekolah']);

        if ($request->filled('no_whatsapp')) {
            $request->merge(['no_whatsapp' => WhatsAppHelper::prepareForValidation($request->no_whatsapp)]);
        }
        if ($request->filled('wa_ortu')) {
            $request->merge(['wa_ortu' => WhatsAppHelper::prepareForValidation($request->wa_ortu)]);
        }

        $validated = $request->validate([
            'jenis_instansi'    => 'required|in:Sekolah,PT',
            'sekolah_id'        => 'nullable|exists:sekolahs,id',
            'sekolah_manual'    => 'nullable|string|max:255',
            'perusahaan_id'     => 'nullable|exists:perusahaans,id',
            'perusahaan_manual' => 'nullable|string|max:255',
            'nama'              => 'required|string|max:255',
            'no_whatsapp'       => ['required', 'string', 'max:25', new IndonesianWhatsApp(allowEmpty: false)],
            'wa_ortu'           => ['nullable', 'string', 'max:25', new IndonesianWhatsApp(allowEmpty: true)],
            'asal_kelas'        => 'nullable|string|max:255',
            'prodi_id'          => 'required|string',
            'prodi_lainnya'     => 'nullable|required_if:prodi_id,lainnya|string|max:255',
            'kelas'             => 'required|in:Reguler,Karyawan',
        ]);

        if ($validated['jenis_instansi'] === 'Sekolah' && empty($validated['sekolah_id']) && empty($request->sekolah_manual)) {
            return response()->json(['message' => 'Nama sekolah wajib dipilih atau diisi manual jika belum terdaftar.'], 422);
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
                $matched = Sekolah::whereRaw('LOWER(nama) = ?', [strtolower($manualName)])->first();
                if ($matched) {
                    $name = $matched->nama;
                    $sekolahId = $matched->id;
                    $wilayahId = $matched->wilayah_id;
                } else {
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

        $notes = 'Diinput mandiri melalui API Presensi Siswa';
        if ($manualNotes) {
            $notes .= ' | ' . $manualNotes;
        }

        $prospek = DB::transaction(function () use ($validated, $request, $name, $sekolahId, $perusahaanId, $wilayahId, $manualNotes) {
            // Create prospect
            $prospek = Prospek::create([
                'name'             => $name,
                'whatsapp'         => WhatsAppHelper::formatForDatabase($validated['no_whatsapp']),
                'wa_ortu'          => WhatsAppHelper::formatForDatabase($validated['wa_ortu'] ?? null),
                'pic'              => $validated['nama'],
                'pic_phone'        => WhatsAppHelper::formatForDatabase($validated['no_whatsapp']),
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
                'owner_id'         => null,
                'sales_id'         => null,
                'cs_id'            => null,
                'status_takeover'  => 'Belum Di-takeover',
                'takeover_by_id'   => null,
                'takeover_at'      => null,
            ]);

            // Create timeline entry
            ProspekTimeline::create([
                'prospek_id'   => $prospek->id,
                'user_id'      => null,
                'title'        => 'Presensi Siswa Masuk',
                'notes'        => 'Data presensi siswa berhasil masuk ke CRM dari API presensi sekolah.',
                'status_after' => $prospek->status,
                'time'         => now(),
            ]);

            return $prospek;
        });

        return response()->json([
            'message' => 'Prospek presensi sekolah berhasil dibuat',
            'data'    => $prospek,
        ], 201);
    }

    /**
     * Store presensi corporate via API.
     */
    public function storeCorporate(Request $request)
    {
        $request->merge(['jenis_instansi' => 'PT']);

        if ($request->filled('no_whatsapp')) {
            $request->merge(['no_whatsapp' => WhatsAppHelper::prepareForValidation($request->no_whatsapp)]);
        }
        if ($request->filled('wa_ortu')) {
            $request->merge(['wa_ortu' => WhatsAppHelper::prepareForValidation($request->wa_ortu)]);
        }

        $validated = $request->validate([
            'perusahaan_id'     => 'nullable|exists:perusahaans,id',
            'perusahaan_manual' => 'nullable|string|max:255',
            'nama'              => 'required|string|max:255',
            'no_whatsapp'       => ['required', 'string', 'max:25', new IndonesianWhatsApp(allowEmpty: false)],
            'wa_ortu'           => ['nullable', 'string', 'max:25', new IndonesianWhatsApp(allowEmpty: true)],
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

        $notes = 'Diinput mandiri melalui API Presensi Corporate';
        if ($manualNotes) {
            $notes .= ' | ' . $manualNotes;
        }

        $prospek = DB::transaction(function () use ($validated, $request, $name, $perusahaanId, $wilayahId, $manualNotes) {
            // Create prospect
            $prospek = Prospek::create([
                'name'             => $name,
                'whatsapp'         => WhatsAppHelper::formatForDatabase($validated['no_whatsapp']),
                'wa_ortu'          => WhatsAppHelper::formatForDatabase($validated['wa_ortu'] ?? null),
                'pic'              => $validated['nama'],
                'pic_phone'        => WhatsAppHelper::formatForDatabase($validated['no_whatsapp']),
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
                'owner_id'         => null,
                'sales_id'         => null,
                'cs_id'            => null,
                'status_takeover'  => 'Belum Di-takeover',
                'takeover_by_id'   => null,
                'takeover_at'      => null,
            ]);

            // Create timeline entry
            ProspekTimeline::create([
                'prospek_id'   => $prospek->id,
                'user_id'      => null,
                'title'        => 'Presensi Corporate Masuk',
                'notes'        => 'Data presensi corporate berhasil masuk ke CRM dari API presensi corporate.',
                'status_after' => $prospek->status,
                'time'         => now(),
            ]);

            return $prospek;
        });

        return response()->json([
            'message' => 'Prospek presensi corporate berhasil dibuat',
            'data'    => $prospek,
        ], 201);
    }
}
?>
