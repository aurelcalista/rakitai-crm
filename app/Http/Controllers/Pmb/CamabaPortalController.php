<?php

namespace App\Http\Controllers\Pmb;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use App\Models\Sekolah;
use App\Services\MidtransService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CamabaPortalController extends Controller
{
    /**
     * Dapatkan record Prospek terkait akun CAMABA yang sedang login.
     */
    protected function getCamabaProspek(): ?Prospek
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        // Cari via relasi user_id, fallback ke email jika belum terlink
        $prospek = $user->prospek;
        if (!$prospek) {
            $prospek = Prospek::where('email', strtolower($user->email))->first();
            if ($prospek && !$prospek->user_id) {
                $prospek->user_id = $user->id;
                $prospek->save();
            }
        }

        return $prospek;
    }

    /**
     * Halaman Publik Utama Portal PMB (Landing Page).
     */
    public function landing(): View
    {
        $prodis = Prodi::where('status', 'Aktif')
            ->orWhereNull('status')
            ->orderBy('fakultas')
            ->orderBy('nama')
            ->get();

        return view('pmb.portal.landing', compact('prodis'));
    }

    /**
     * Dashboard Akun CAMABA.
     */
    public function dashboard(): View
    {
        $user = Auth::user();
        $prospek = $this->getCamabaProspek();

        $transaksiAktif = null;
        $riwayatTransaksi = collect();

        if ($prospek) {
            $riwayatTransaksi = $prospek->transaksis()
                ->orderBy('created_at', 'desc')
                ->get();

            $transaksiAktif = $prospek->transaksis()
                ->where('kategori', \App\Models\Transaksi::KATEGORI_DP_DAFTAR)
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return view('pmb.portal.dashboard', compact('user', 'prospek', 'transaksiAktif', 'riwayatTransaksi'));
    }

    /**
     * Halaman Status Pendaftaran CAMABA.
     */
    public function status(): View
    {
        $user = Auth::user();
        $prospek = $this->getCamabaProspek();

        $transaksiDaftar = null;
        if ($prospek) {
            $transaksiDaftar = $prospek->transaksis()
                ->where('kategori', \App\Models\Transaksi::KATEGORI_DP_DAFTAR)
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return view('pmb.portal.status', compact('user', 'prospek', 'transaksiDaftar'));
    }

    /**
     * Halaman Profil CAMABA (Lihat & Edit Data Pribadi).
     */
    public function profile(): View
    {
        $user = Auth::user();
        $prospek = $this->getCamabaProspek();
        $prodis = Prodi::where('status', 'Aktif')->orWhereNull('status')->orderBy('nama')->get();

        return view('pmb.portal.profile', compact('user', 'prospek', 'prodis'));
    }

    /**
     * Update Data Profil & Formulir Pendaftaran CAMABA.
     * Tidak mengizinkan perubahan status, verifikasi pembayaran, nilai ujian, atau role.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $prospek = $this->getCamabaProspek();

        $validated = $request->validate([
            'name'                  => 'required|string|min:2|max:255',
            'whatsapp'              => ['required', 'string', 'regex:/^(\+62|62|0)[0-9]{8,14}$/'],
            'asal_sekolah'          => 'nullable|string|max:255',
            'nama_sekolah'          => 'nullable|string|max:255',
            'prodi_id'              => 'nullable|exists:prodis,id',
            'prodi_pilihan_2_id'    => 'nullable|different:prodi_id|exists:prodis,id',
            'minat_kuliah'          => 'nullable|in:ya,tidak',
            'minat_miniclass'       => 'nullable|in:ya,tidak',
            'tempat_lahir'          => 'nullable|string|max:100',
            'tanggal_lahir'         => 'nullable|string|max:50',
            'nik'                   => 'nullable|string|max:30',
            'jenis_kelamin'         => 'nullable|string|max:30',
            'agama'                 => 'nullable|string|max:50',
            'status_tempat_tinggal' => 'nullable|string|max:100',
            'status_bekerja'        => 'nullable|string|max:100',
            'alamat'                => 'nullable|string|max:1000',
            'kelurahan_kecamatan'   => 'nullable|string|max:255',
            'kota_provinsi'         => 'nullable|string|max:255',
            'kode_pos'              => 'nullable|string|max:20',
            'telp_rumah'            => 'nullable|string|max:30',
            'jenis_sekolah'         => 'nullable|string|max:50',
            'jurusan_sekolah'       => 'nullable|string|max:100',
            'keterangan_kejuruan'   => 'nullable|string|max:255',
            'nisn'                  => 'nullable|string|max:50',
            'tahun_lulus'           => 'nullable|string|max:10',
            'nama_ibu_kandung'      => 'nullable|string|max:255',
            'alamat_orang_tua'      => 'nullable|string|max:1000',
            'pekerjaan_orang_tua'   => 'nullable|string|max:100',
            'wa_ortu'               => 'nullable|string|max:30',
            'tipe_kelas'            => 'nullable|string|max:50',
            'informasi_pendaftaran' => 'nullable|string|max:100',
            'kode_referal'          => 'nullable|string|max:50',
        ], [
            'name.required'                 => 'Nama lengkap wajib diisi.',
            'whatsapp.required'             => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex'                => 'Format nomor WhatsApp tidak valid.',
            'prodi_pilihan_2_id.different' => 'Pilihan program studi kedua tidak boleh sama dengan pilihan pertama.',
        ]);

        $formattedPhone = \App\Support\WhatsAppHelper::formatForDatabase($validated['whatsapp']);
        $schoolName = trim($validated['nama_sekolah'] ?? $validated['asal_sekolah'] ?? '');

        // Kumpulkan data tambahan untuk disimpan ke kolom json form_data
        $formDataFields = [
            'tempat_lahir', 'tanggal_lahir', 'nik', 'jenis_kelamin', 'agama',
            'status_tempat_tinggal', 'status_bekerja',
            'alamat', 'kelurahan_kecamatan', 'kota_provinsi', 'kode_pos', 'telp_rumah',
            'nama_sekolah', 'jenis_sekolah', 'jurusan_sekolah', 'keterangan_kejuruan', 'nisn', 'tahun_lulus',
            'nama_ibu_kandung', 'alamat_orang_tua', 'pekerjaan_orang_tua', 'wa_ortu',
            'tipe_kelas', 'informasi_pendaftaran', 'kode_referal',
        ];

        $formData = is_array($prospek?->form_data) ? $prospek->form_data : [];
        foreach ($formDataFields as $key) {
            if ($request->has($key)) {
                $formData[$key] = $request->input($key);
            }
        }

        DB::transaction(function () use ($user, $prospek, $validated, $formattedPhone, $schoolName, $formData) {
            // Update User record
            $user->name = trim($validated['name']);
            $user->phone = $formattedPhone;
            $user->save();

            // Update Prospek record
            if ($prospek) {
                $prospek->name = trim($validated['name']);
                $prospek->whatsapp = $formattedPhone;
                $prospek->pic_phone = $formattedPhone;
                $prospek->pic = trim($validated['name']);

                if (!empty($schoolName)) {
                    $sekolah = Sekolah::where('nama', 'like', $schoolName)->first();
                    if ($sekolah) {
                        $prospek->sekolah_id = $sekolah->id;
                    }
                    $notes = $prospek->notes ?? '';
                    if (!str_contains($notes, $schoolName)) {
                        $prospek->notes = trim($notes . ' | Asal sekolah: ' . $schoolName);
                    }
                }

                if (isset($validated['prodi_id'])) {
                    $prospek->prodi_id = $validated['prodi_id'];
                }
                if (isset($validated['prodi_pilihan_2_id'])) {
                    $prospek->prodi_pilihan_2_id = $validated['prodi_pilihan_2_id'];
                }
                if (isset($validated['minat_kuliah'])) {
                    $prospek->minat_kuliah = $validated['minat_kuliah'];
                }
                if (isset($validated['minat_miniclass'])) {
                    $prospek->minat_miniclass = $validated['minat_miniclass'];
                }
                if (!empty($validated['wa_ortu'])) {
                    $prospek->wa_ortu = \App\Support\WhatsAppHelper::formatForDatabase($validated['wa_ortu']);
                }
                if (!empty($validated['tipe_kelas'])) {
                    $prospek->kelas = $validated['tipe_kelas'];
                }
                if (!empty($validated['informasi_pendaftaran'])) {
                    $prospek->source = $validated['informasi_pendaftaran'];
                }

                $prospek->form_data = $formData;
                $prospek->status_formulir = 'Sudah Isi';
                $prospek->save();
            }
        });

        return redirect()->route('pmb.akun.profil')
            ->with('success', 'Data pendaftaran Anda berhasil disimpan.');
    }

    /**
     * Halaman Instruksi Ujian Online CBT / Tes Potensi Akademik (TPA).
     */
    public function ujian(): View
    {
        $user = Auth::user();
        $prospek = $this->getCamabaProspek();

        return view('pmb.portal.ujian', compact('user', 'prospek'));
    }

    /**
     * Halaman Pembayaran CAMABA (Integrasi Midtrans Snap).
     */
    public function bayar(Request $request, MidtransService $midtransService)
    {
        $user = Auth::user();
        $prospek = $this->getCamabaProspek();

        if (!$prospek) {
            return redirect()->route('pmb.akun.dashboard')
                ->with('error', 'Data pendaftaran Anda belum terhubung. Silakan hubungi admin PMB.');
        }

        // Dapatkan transaksi pendaftaran (Rp 300.000) yang terhubung ke prospek
        $forceNew = $request->boolean('fresh') || $request->boolean('new_order');
        $requestedOrderId = $request->query('order_id');
        $transaksi = null;
        if ($requestedOrderId && !$forceNew) {
            $transaksi = $prospek->transaksis()->where('order_id', $requestedOrderId)->first();
        }

        if (!$transaksi) {
            $transaksi = $midtransService->getOrCreateRegistrationTransaksi($prospek, $user, $forceNew);
        }

        // Jika transaksi belum verified, lakukan sinkronisasi status langsung dari backend Midtrans API
        // Verifikasi dilakukan secara otoritatif oleh backend via signature SHA-512 (bukan percaya query string browser)
        if ($transaksi->payment_status !== \App\Models\Transaksi::STATUS_VERIFIED) {
            $synced = $midtransService->syncStatusFromMidtransApi($transaksi->order_id);
            if ($synced) {
                $transaksi = $synced;
            }
        }

        // Jika token Snap belum ada dan belum verified, buat token
        if ($transaksi->payment_status !== \App\Models\Transaksi::STATUS_VERIFIED && empty($transaksi->snap_token)) {
            $midtransService->createSnapToken($transaksi);
            $transaksi->refresh();
        }

        $riwayatTransaksi = $prospek->transaksis()
            ->orderBy('created_at', 'desc')
            ->get();

        $initialItems = MidtransService::resolveInitialItems($transaksi);
        $initialTotal = (int) $transaksi->nominal ?: 300000;

        return view('pmb.portal.pembayaran', compact('user', 'prospek', 'transaksi', 'riwayatTransaksi', 'initialItems', 'initialTotal'));
    }

    /**
     * Halaman Bantuan dan Kontak PMB UCIC.
     */
    public function bantuan(): View
    {
        return view('pmb.portal.bantuan');
    }

    /**
     * 7 Dokumen Persyaratan Resmi PMB UCIC.
     */
    public const DOKUMEN_PERSYARATAN = [
        'pas_foto' => [
            'id'               => 'pas_foto',
            'title'            => 'Pas Foto Formal',
            'subtitle'         => 'Background merah, baju bebas sopan',
            'icon'             => 'user',
            'mimes'            => 'jpg,jpeg,png',
            'mimes_label'      => 'JPG atau PNG',
            'max_size_kb'      => 2048,
            'max_label'        => '2MB',
            'button_text'      => 'Pilih Pas Foto',
            'requirements'     => [
                'Format: JPG atau PNG',
                'Ukuran maksimal: 2MB',
                'Background merah',
                'Format nama: Foto_Nama',
            ],
            'file_name_prefix' => 'Foto',
        ],
        'ktp' => [
            'id'               => 'ktp',
            'title'            => 'Kartu Tanda Penduduk (KTP)',
            'subtitle'         => 'KTP elektronik yang masih berlaku',
            'icon'             => 'id-card',
            'mimes'            => 'jpg,jpeg,png,pdf',
            'mimes_label'      => 'JPG, JPEG, atau PDF',
            'max_size_kb'      => 2048,
            'max_label'        => '2MB',
            'button_text'      => 'Pilih File KTP',
            'requirements'     => [
                'Format: JPG, JPEG, atau PDF',
                'Ukuran maksimal: 2MB',
                'Scan dengan jelas',
                'Format nama: KTP_Nama',
            ],
            'file_name_prefix' => 'KTP',
        ],
        'kk' => [
            'id'               => 'kk',
            'title'            => 'Kartu Keluarga (KK)',
            'subtitle'         => 'Kartu keluarga terbaru',
            'icon'             => 'users',
            'mimes'            => 'jpg,jpeg,png,pdf',
            'mimes_label'      => 'JPG, JPEG, atau PDF',
            'max_size_kb'      => 2048,
            'max_label'        => '2MB',
            'button_text'      => 'Pilih File KK',
            'requirements'     => [
                'Format: JPG, JPEG, atau PDF',
                'Ukuran maksimal: 2MB',
                'Scan dengan jelas',
                'Format nama: KK_Nama',
            ],
            'file_name_prefix' => 'KK',
        ],
        'akta' => [
            'id'               => 'akta',
            'title'            => 'Akta Kelahiran',
            'subtitle'         => 'Akta kelahiran asli',
            'icon'             => 'document',
            'mimes'            => 'jpg,jpeg,png,pdf',
            'mimes_label'      => 'JPG, JPEG, atau PDF',
            'max_size_kb'      => 2048,
            'max_label'        => '2MB',
            'button_text'      => 'Pilih File Akta',
            'requirements'     => [
                'Format: JPG, JPEG, atau PDF',
                'Ukuran maksimal: 2MB',
                'Scan dengan jelas',
                'Format nama: Akta_Nama',
            ],
            'file_name_prefix' => 'Akta',
        ],
        'ijazah' => [
            'id'               => 'ijazah',
            'title'            => 'Ijazah / Surat Keterangan Lulus (SKL)',
            'subtitle'         => 'Ijazah SMA/SMK/MA atau SKL bagi lulusan baru',
            'icon'             => 'academic',
            'mimes'            => 'jpg,jpeg,png,pdf',
            'mimes_label'      => 'JPG, JPEG, atau PDF',
            'max_size_kb'      => 2048,
            'max_label'        => '2MB',
            'button_text'      => 'Pilih File Ijazah/SKL',
            'requirements'     => [
                'Format: JPG, JPEG, atau PDF',
                'Ukuran maksimal: 2MB',
                'Scan halaman depan & nilai/belakang',
                'Format nama: Ijazah_Nama',
            ],
            'file_name_prefix' => 'Ijazah',
        ],
        'transkrip' => [
            'id'               => 'transkrip',
            'title'            => 'Transkrip Nilai / Rapor',
            'subtitle'         => 'Transkrip nilai rapor semester 1 - 5 atau nilai ujian sekolah',
            'icon'             => 'clipboard-list',
            'mimes'            => 'jpg,jpeg,png,pdf',
            'mimes_label'      => 'JPG, JPEG, atau PDF',
            'max_size_kb'      => 2048,
            'max_label'        => '2MB',
            'button_text'      => 'Pilih File Transkrip',
            'requirements'     => [
                'Format: JPG, JPEG, atau PDF',
                'Ukuran maksimal: 2MB',
                'Scan dengan jelas dan terbaca',
                'Format nama: Transkrip_Nama',
            ],
            'file_name_prefix' => 'Transkrip',
        ],
        'surat_keterangan' => [
            'id'               => 'surat_keterangan',
            'title'            => 'Surat Keterangan Bebas Narkoba / Sehat',
            'subtitle'         => 'Surat keterangan sehat dari klinik/puskesmas/RS atau sertifikat pendukung',
            'icon'             => 'shield-check',
            'mimes'            => 'jpg,jpeg,png,pdf',
            'mimes_label'      => 'JPG, JPEG, atau PDF',
            'max_size_kb'      => 2048,
            'max_label'        => '2MB',
            'button_text'      => 'Pilih File Surat Sehat',
            'requirements'     => [
                'Format: JPG, JPEG, atau PDF',
                'Ukuran maksimal: 2MB',
                'Dikeluarkan oleh instansi berwenang',
                'Format nama: SuratSehat_Nama',
            ],
            'file_name_prefix' => 'SuratSehat',
        ],
    ];

    /**
     * Halaman Upload Dokumen Persyaratan CAMABA.
     */
    public function uploadDokumen(Request $request): View
    {
        $user = Auth::user();
        $prospek = $this->getCamabaProspek();

        $dokumenList = self::DOKUMEN_PERSYARATAN;
        $berkasData = is_array($prospek?->berkas_data) ? $prospek->berkas_data : [];

        // Hitung progres kelengkapan dokumen
        $totalDokumen = count($dokumenList);
        $uploadedCount = 0;
        foreach ($dokumenList as $key => $doc) {
            if (!empty($berkasData[$key]['file_path'])) {
                $uploadedCount++;
            }
        }

        $progressPercentage = $totalDokumen > 0 ? (int) round(($uploadedCount / $totalDokumen) * 100) : 0;

        return view('pmb.portal.upload-dokumen', compact(
            'user',
            'prospek',
            'dokumenList',
            'berkasData',
            'totalDokumen',
            'uploadedCount',
            'progressPercentage'
        ));
    }

    /**
     * Simpan file dokumen persyaratan yang diunggah oleh CAMABA.
     */
    public function storeDokumen(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $prospek = $this->getCamabaProspek();

        if (!$prospek) {
            return redirect()->route('pmb.akun.dashboard')
                ->with('error', 'Data calon mahasiswa Anda belum terhubung. Silakan hubungi admin PMB.');
        }

        $allowedKeys = array_keys(self::DOKUMEN_PERSYARATAN);

        $validated = $request->validate([
            'dokumen_type' => ['required', 'string', 'in:' . implode(',', $allowedKeys)],
            'dokumen_file' => [
                'required',
                'file',
                $request->input('dokumen_type') === 'pas_foto' ? 'mimes:jpg,jpeg,png' : 'mimes:jpg,jpeg,png,pdf',
                'max:2048', // 2MB
            ],
        ], [
            'dokumen_type.required' => 'Jenis dokumen wajib dipilih.',
            'dokumen_type.in'       => 'Jenis dokumen tidak valid.',
            'dokumen_file.required' => 'File dokumen wajib dipilih.',
            'dokumen_file.file'     => 'File yang diunggah tidak valid.',
            'dokumen_file.mimes'    => $request->input('dokumen_type') === 'pas_foto'
                ? 'Format file Pas Foto harus berupa JPG atau PNG.'
                : 'Format file dokumen harus berupa JPG, JPEG, PNG, atau PDF.',
            'dokumen_file.max'      => 'Ukuran file dokumen maksimal 2MB.',
        ]);

        $type = $validated['dokumen_type'];
        $docDef = self::DOKUMEN_PERSYARATAN[$type];
        $file = $request->file('dokumen_file');

        $currentBerkas = is_array($prospek->berkas_data) ? $prospek->berkas_data : [];

        // Hapus file fisik lama jika sebelumnya sudah ada
        if (!empty($currentBerkas[$type]['file_path']) && Storage::disk('local')->exists($currentBerkas[$type]['file_path'])) {
            Storage::disk('local')->delete($currentBerkas[$type]['file_path']);
        }

        // Generate nama file aman dan simpan di disk local terproteksi
        $extension = $file->getClientOriginalExtension() ?: ($type === 'pas_foto' ? 'jpg' : 'pdf');
        $prospekNameClean = preg_replace('/[^a-zA-Z0-9]/', '_', $prospek->name ?: 'Camaba');
        $safeFileName = "{$docDef['file_name_prefix']}_{$prospekNameClean}_{$prospek->id}_" . time() . '.' . $extension;

        $path = $file->storeAs("dokumen_pmb/{$prospek->id}", $safeFileName, 'local');

        $currentBerkas[$type] = [
            'file_path'     => $path,
            'file_name'     => $safeFileName,
            'original_name' => $file->getClientOriginalName(),
            'file_size'     => $file->getSize(),
            'mime_type'     => $file->getMimeType(),
            'uploaded_at'   => now()->format('Y-m-d H:i:s'),
            'status'        => 'Menunggu Verifikasi',
            'catatan'       => null,
        ];

        // Hitung total dokumen terunggah & perbarui status_berkas pendaftar
        $uploadedCount = 0;
        foreach (self::DOKUMEN_PERSYARATAN as $key => $d) {
            if (!empty($currentBerkas[$key]['file_path'])) {
                $uploadedCount++;
            }
        }

        $totalDokumen = count(self::DOKUMEN_PERSYARATAN);
        if ($uploadedCount >= $totalDokumen) {
            $prospek->status_berkas = 'Sudah Upload';
        } elseif ($uploadedCount > 0) {
            $prospek->status_berkas = 'Pending';
        } else {
            $prospek->status_berkas = 'Belum Upload';
        }

        $prospek->berkas_data = $currentBerkas;
        $prospek->save();

        // Rekam jejak di timeline pendaftar
        ProspekTimeline::create([
            'prospek_id' => $prospek->id,
            'user_id'    => $user->id,
            'title'      => "Upload Dokumen: {$docDef['title']}",
            'notes'      => "Calon mahasiswa berhasil mengunggah berkas {$docDef['title']} ({$file->getClientOriginalName()}). Progres: {$uploadedCount}/{$totalDokumen} dokumen.",
            'action'     => 'upload_berkas',
        ]);

        return redirect()->route('pmb.akun.upload-dokumen')
            ->with('success', "Dokumen {$docDef['title']} berhasil diunggah!");
    }

    /**
     * Tampilkan atau unduh dokumen persyaratan secara aman.
     * Mencegah akses langsung publik atau lintas calon mahasiswa.
     */
    public function viewDokumen(Request $request, string $type)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        $allowedKeys = array_keys(self::DOKUMEN_PERSYARATAN);
        if (!in_array($type, $allowedKeys)) {
            abort(404, 'Jenis dokumen tidak dikenali.');
        }

        $prospek = $this->getCamabaProspek();

        // Otorisasi: Staff CRM atau CAMABA pemilik data
        $userRole = strtolower($user->role ?? '');
        $allowedStaff = ['admin', 'hm', 'cs', 'sales', 'spv'];
        $isStaff = in_array($userRole, $allowedStaff);

        if (!$prospek && !$isStaff) {
            abort(403, 'Akses dokumen ditolak.');
        }

        // Jika staff mengakses dokumen pendaftar tertentu via parameter prospek_id
        if ($isStaff && $request->has('prospek_id')) {
            $prospek = Prospek::findOrFail($request->query('prospek_id'));
        }

        $berkasData = is_array($prospek?->berkas_data) ? $prospek->berkas_data : [];
        if (empty($berkasData[$type]['file_path'])) {
            abort(404, 'Dokumen belum diunggah.');
        }

        $filePath = $berkasData[$type]['file_path'];
        if (!Storage::disk('local')->exists($filePath)) {
            abort(404, 'File dokumen tidak ditemukan pada penyimpanan server.');
        }

        if ($request->boolean('download')) {
            $downloadName = $berkasData[$type]['original_name'] ?? $berkasData[$type]['file_name'];
            return Storage::disk('local')->download($filePath, $downloadName);
        }

        return Storage::disk('local')->response($filePath);
    }

    /**
     * Hapus dokumen persyaratan yang telah diunggah.
     */
    public function deleteDokumen(Request $request, string $type): RedirectResponse
    {
        $user = Auth::user();
        $prospek = $this->getCamabaProspek();

        if (!$prospek) {
            return redirect()->route('pmb.akun.upload-dokumen')
                ->with('error', 'Data calon mahasiswa tidak ditemukan.');
        }

        $allowedKeys = array_keys(self::DOKUMEN_PERSYARATAN);
        if (!in_array($type, $allowedKeys)) {
            return redirect()->route('pmb.akun.upload-dokumen')
                ->with('error', 'Jenis dokumen tidak valid.');
        }

        $currentBerkas = is_array($prospek->berkas_data) ? $prospek->berkas_data : [];
        if (empty($currentBerkas[$type])) {
            return redirect()->route('pmb.akun.upload-dokumen')
                ->with('error', 'Dokumen belum diunggah.');
        }

        // Cegah penghapusan jika dokumen sudah Disetujui
        if (($currentBerkas[$type]['status'] ?? '') === 'Disetujui') {
            return redirect()->route('pmb.akun.upload-dokumen')
                ->with('error', 'Dokumen yang sudah disetujui tidak dapat dihapus.');
        }

        if (!empty($currentBerkas[$type]['file_path']) && Storage::disk('local')->exists($currentBerkas[$type]['file_path'])) {
            Storage::disk('local')->delete($currentBerkas[$type]['file_path']);
        }

        $docTitle = self::DOKUMEN_PERSYARATAN[$type]['title'] ?? $type;
        unset($currentBerkas[$type]);

        $uploadedCount = 0;
        foreach (self::DOKUMEN_PERSYARATAN as $key => $d) {
            if (!empty($currentBerkas[$key]['file_path'])) {
                $uploadedCount++;
            }
        }

        if ($uploadedCount >= count(self::DOKUMEN_PERSYARATAN)) {
            $prospek->status_berkas = 'Sudah Upload';
        } elseif ($uploadedCount > 0) {
            $prospek->status_berkas = 'Pending';
        } else {
            $prospek->status_berkas = 'Belum Upload';
        }

        $prospek->berkas_data = $currentBerkas;
        $prospek->save();

        return redirect()->route('pmb.akun.upload-dokumen')
            ->with('success', "Dokumen {$docTitle} berhasil dihapus.");
    }
}
