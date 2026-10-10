<?php

namespace App\Services;

use App\Models\Prospek;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\Target;
use App\Models\Wilayah;
use App\Models\Prodi;
use App\Models\TahunAkademik;
use App\Models\FollowUp;
use App\Models\ProspekTimeline;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalystExportService
{
    /**
     * Pemetaan analitik status lead terhadap 8 status kanonis PRD PMB.
     * Tidak mengubah pipeline status P0 di database.
     */
    public static function mapToPrdStatus(?string $status): string
    {
        $statusUpper = strtoupper(trim((string)$status));
        return match ($statusUpper) {
            'BARU', 'LEAD IN', 'COLD LEAD'                  => 'Baru',
            'KONTAK', 'FOLLOW UP 1', 'INTERESTED'           => 'Kontak',
            'PROSPEK', 'HANGAT', 'WARM LEAD', 'FOLLOW UP'   => 'Hangat',
            'HOT PROSPEK', 'PANAS', 'HOT LEAD', 'NEGOSIASI' => 'Panas',
            'FORMULIR', 'BELI FORMULIR', 'MENDAFTAR'        => 'Formulir',
            'BERKAS', 'LULUS TES'                           => 'Berkas',
            'LUNAS', 'CLOSING', 'PEMBAYARAN TERMIN 1'       => 'Lunas',
            'DINGIN', 'NO RESPON', 'CANCEL', 'LOST', 'DITOLAK/BATAL' => 'Dingin',
            default                                         => ucfirst(strtolower($status ?: 'Baru')),
        };
    }
    /**
     * Export dataset as a clean, Excel-compatible CSV file with UTF-8 BOM.
     *
     * @param string $type
     * @param array $filterParams
     * @return StreamedResponse
     */
    public function export(string $type, array $filterParams = []): StreamedResponse
    {
        $filename = 'UCIC_CRM_Analyst_' . ucfirst($type) . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return new StreamedResponse(function () use ($type, $filterParams) {
            $handle = fopen('php://output', 'w');

            // Write UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            switch ($type) {
                case 'prospek':
                    $this->exportProspek($handle, $filterParams);
                    break;
                case 'funnel':
                    $this->exportFunnel($handle, $filterParams);
                    break;
                case 'transaksi':
                    $this->exportTransaksi($handle, $filterParams);
                    break;
                case 'performa_sales':
                    $this->exportPerformaSales($handle, $filterParams);
                    break;
                case 'sumber':
                    $this->exportSumber($handle, $filterParams);
                    break;
                case 'sekolah':
                    $this->exportSekolah($handle, $filterParams);
                    break;
                case 'target':
                    $this->exportTarget($handle, $filterParams);
                    break;
                case 'follow_up':
                    $this->exportFollowUp($handle, $filterParams);
                    break;
                case 'timeline':
                    $this->exportTimeline($handle, $filterParams);
                    break;
                case 'kualitas_data':
                    $this->exportKualitasData($handle, $filterParams);
                    break;
                default:
                    $this->exportSummary($handle, $filterParams);
                    break;
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export detail prospek.
     */
    protected function exportProspek($handle, array $params): void
    {
        fputcsv($handle, [
            'ID Prospek',
            'Nama Calon Mahasiswa',
            'No WhatsApp',
            'Tipe',
            'Status PRD Kanonis',
            'Status Pipeline Operasional',
            'Tahap Pipeline',
            'Program Studi Pilihan',
            'Kelas',
            'Asal Sekolah / Mitra',
            'Wilayah',
            'Kecamatan',
            'Sumber Informasi',
            'Nama Sales',
            'Kode Sales',
            'Nama CS',
            'Jumlah Follow-Up',
            'Status Lunas',
            'Tanggal Dibuat',
            'Terakhir Diupdate'
        ]);

        $query = $this->buildProspekQuery($params);

        $query->chunk(250, function ($prospeks) use ($handle) {
            foreach ($prospeks as $p) {
                fputcsv($handle, [
                    $p->id,
                    $p->name,
                    $p->whatsapp ?: '-',
                    $p->type,
                    self::mapToPrdStatus($p->status),
                    $p->status,
                    $p->stage_number,
                    $p->prodi?->nama ?: ($p->prodi_lainnya ?: '-'),
                    $p->kelas ?: 'Reguler',
                    $p->sekolah?->nama ?: ($p->perusahaan?->nama ?: '-'),
                    $p->wilayah?->nama ?: '-',
                    $p->sekolah?->kecamatan ?: ($p->wilayah?->parent?->nama ?: '-'),
                    $p->source ?: 'Belum Ada Sumber',
                    $p->sales?->name ?: 'Direct / Tanpa Sales',
                    $p->sales?->kode ?: '-',
                    $p->cs?->name ?: '-',
                    $p->follow_up_count ?: 0,
                    $p->isMabaLunas() ? 'LUNAS RESMI' : ($p->status === 'LUNAS' ? 'LUNAS' : 'Belum Lunas'),
                    $p->created_at ? $p->created_at->format('Y-m-d H:i:s') : '-',
                    $p->updated_at ? $p->updated_at->format('Y-m-d H:i:s') : '-',
                ]);
            }
        });
    }

    /**
     * Export funnel PMB summary.
     */
    protected function exportFunnel($handle, array $params): void
    {
        $query = $this->buildProspekQuery($params);
        $total = (clone $query)->count();

        $baruCount     = (clone $query)->whereIn('status', ['BARU', 'KONTAK'])->count();
        $prospekCount  = (clone $query)->whereIn('status', ['PROSPEK', 'HOT PROSPEK', 'HANGAT', 'PANAS'])->count();
        $formulirCount = (clone $query)->whereIn('status', ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS'])->count();
        $berkasCount   = (clone $query)->whereIn('status', ['BERKAS', 'CLOSING', 'LUNAS'])->count();
        $lunasCount    = (clone $query)->where('status', 'LUNAS')->count();

        $lostCount     = (clone $query)->whereIn('status', ['DINGIN', 'NO RESPON', 'CANCEL'])->count();

        fputcsv($handle, ['REKAPITULASI FUNNEL PMB CRM UCIC']);
        fputcsv($handle, ['Filter Periode:', $params['periode'] ?? 'Semua Periode']);
        fputcsv($handle, ['Tahun Akademik:', $params['ta'] ?? 'Semua TA']);
        fputcsv($handle, ['Total Kontak Terdata:', $total]);
        fputcsv($handle, []);

        fputcsv($handle, ['Tahap Funnel', 'Jumlah Prospek', 'Persentase dari Total', 'Konversi dari Tahap Sebelumnya']);
        fputcsv($handle, [
            '1. Kontak & Lead Masuk',
            $total,
            '100%',
            '-'
        ]);
        fputcsv($handle, [
            '2. Audiensi & Prospek Aktif',
            $prospekCount + $formulirCount,
            $total > 0 ? round((($prospekCount + $formulirCount) / $total) * 100, 1) . '%' : '0%',
            $total > 0 ? round((($prospekCount + $formulirCount) / $total) * 100, 1) . '%' : '0%'
        ]);
        fputcsv($handle, [
            '3. Pembelian Formulir (Pendaftar)',
            $formulirCount,
            $total > 0 ? round(($formulirCount / $total) * 100, 1) . '%' : '0%',
            ($prospekCount + $formulirCount) > 0 ? round(($formulirCount / ($prospekCount + $formulirCount)) * 100, 1) . '%' : '0%'
        ]);
        fputcsv($handle, [
            '4. Pemberkasan Dokumen',
            $berkasCount,
            $total > 0 ? round(($berkasCount / $total) * 100, 1) . '%' : '0%',
            $formulirCount > 0 ? round(($berkasCount / $formulirCount) * 100, 1) . '%' : '0%'
        ]);
        fputcsv($handle, [
            '5. Mahasiswa Lunas Tahap 1 (Maba Resmi)',
            $lunasCount,
            $total > 0 ? round(($lunasCount / $total) * 100, 1) . '%' : '0%',
            $berkasCount > 0 ? round(($lunasCount / $berkasCount) * 100, 1) . '%' : '0%'
        ]);

        fputcsv($handle, []);
        fputcsv($handle, ['Metrik Tambahan:']);
        fputcsv($handle, ['Konversi Total (Kontak ke Formulir):', $total > 0 ? round(($formulirCount / $total) * 100, 2) . '%' : '0%']);
        fputcsv($handle, ['Konversi Akhir (Kontak ke Lunas):', $total > 0 ? round(($lunasCount / $total) * 100, 2) . '%' : '0%']);
        fputcsv($handle, ['Prospek Berhenti / Drop-off (Dingin & Batal):', $lostCount]);
    }

    /**
     * Export transaksi pembayaran.
     */
    protected function exportTransaksi($handle, array $params): void
    {
        fputcsv($handle, [
            'ID Transaksi',
            'ID Prospek',
            'Nama Calon Mahasiswa',
            'Jenis Pembayaran',
            'Nominal (Rp)',
            'Metode Pembayaran',
            'Status Verifikasi',
            'Diverifikasi Oleh',
            'Tanggal Bayar',
            'Program Studi',
            'Kelas',
            'Nama Sales',
            'Catatan'
        ]);

        $query = Transaksi::with(['prospek.prodi', 'prospek.sales', 'verifier'])
            ->where(function ($q) {
                $q->where('payment_status', Transaksi::STATUS_VERIFIED)
                  ->orWhereNull('payment_status');
            });

        if (!empty($params['from_date'])) {
            $query->whereDate('tanggal', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $query->whereDate('tanggal', '<=', $params['to_date']);
        }

        $query->chunk(250, function ($transaksis) use ($handle) {
            foreach ($transaksis as $t) {
                fputcsv($handle, [
                    $t->id,
                    $t->prospek_id,
                    $t->prospek?->name ?: '-',
                    $t->jenis,
                    $t->nominal,
                    $t->metode_label,
                    $t->payment_status ?: 'verified (legacy)',
                    $t->verifier?->name ?: 'System / Otomatis',
                    $t->tanggal ? $t->tanggal->format('Y-m-d H:i:s') : '-',
                    $t->prospek?->prodi?->nama ?: '-',
                    $t->prospek?->kelas ?: 'Reguler',
                    $t->prospek?->sales?->name ?: 'Direct / Tanpa Sales',
                    $t->notes ?: '-'
                ]);
            }
        });
    }

    /**
     * Export performa sales.
     */
    protected function exportPerformaSales($handle, array $params): void
    {
        fputcsv($handle, [
            'ID Sales',
            'Kode Pegawai',
            'Nama Sales',
            'Wilayah Penugasan',
            'Supervisor (SPV)',
            'Total Kontak Ditangani',
            'Aktivitas Follow-Up',
            'Jumlah Formulir',
            'Mahasiswa Lunas (Closing)',
            'Rasio Konversi (%)'
        ]);

        $salesUsers = User::where('role', 'Sales')
            ->where('status', 'Aktif')
            ->with(['supervisor', 'wilayah'])
            ->get();

        foreach ($salesUsers as $sales) {
            $prospekQuery = Prospek::where('sales_id', $sales->id);
            if (!empty($params['from_date'])) {
                $prospekQuery->whereDate('created_at', '>=', $params['from_date']);
            }
            if (!empty($params['to_date'])) {
                $prospekQuery->whereDate('created_at', '<=', $params['to_date']);
            }

            $totalKontak   = (clone $prospekQuery)->count();
            $formulirCount = (clone $prospekQuery)->whereIn('status', ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS'])->count();
            $lunasCount    = (clone $prospekQuery)->where('status', 'LUNAS')->count();
            $followUpCount = \App\Models\FollowUp::where('user_id', $sales->id)->count();

            $konversi = $totalKontak > 0 ? round(($lunasCount / $totalKontak) * 100, 2) : 0;

            fputcsv($handle, [
                $sales->id,
                $sales->kode ?: '-',
                $sales->name,
                $sales->wilayah?->nama ?: 'Lintas Wilayah',
                $sales->supervisor?->name ?: '-',
                $totalKontak,
                $followUpCount,
                $formulirCount,
                $lunasCount,
                $konversi . '%'
            ]);
        }
    }

    /**
     * Export sumber & kanal marketing.
     */
    protected function exportSumber($handle, array $params): void
    {
        fputcsv($handle, [
            'Sumber / Kanal Informasi',
            'Total Kontak',
            'Jumlah Formulir',
            'Mahasiswa Lunas',
            'Konversi Lead ke Lunas (%)',
            'Share dari Total Kontak (%)'
        ]);

        $query = $this->buildProspekQuery($params);
        $totalAll = (clone $query)->count();

        $sources = Prospek::SOURCES;

        foreach ($sources as $source) {
            $srcQuery = (clone $query)->where('source', $source);
            $kontakCount   = (clone $srcQuery)->count();
            $formulirCount = (clone $srcQuery)->whereIn('status', ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS'])->count();
            $lunasCount    = (clone $srcQuery)->where('status', 'LUNAS')->count();

            $konversi = $kontakCount > 0 ? round(($lunasCount / $kontakCount) * 100, 2) : 0;
            $share = $totalAll > 0 ? round(($kontakCount / $totalAll) * 100, 2) : 0;

            fputcsv($handle, [
                $source,
                $kontakCount,
                $formulirCount,
                $lunasCount,
                $konversi . '%',
                $share . '%'
            ]);
        }
    }

    /**
     * Export sekolah & perusahaan mitra.
     */
    protected function exportSekolah($handle, array $params): void
    {
        fputcsv($handle, [
            'Kode',
            'Nama Sekolah / Perusahaan',
            'Tipe Institusi',
            'Wilayah',
            'Kecamatan',
            'Status Hubungan',
            'Total Kunjungan Lapangan',
            'Total Kontak Didapat',
            'Total Mahasiswa Lunas',
            'PIC Institusi',
            'Telepon / WhatsApp PIC'
        ]);

        $sekolahs = Sekolah::with(['wilayah'])->get();
        foreach ($sekolahs as $s) {
            $prospekCount = Prospek::where('sekolah_id', $s->id)->count();
            $lunasCount   = Prospek::where('sekolah_id', $s->id)->where('status', 'LUNAS')->count();
            $visitCount   = \App\Models\Kunjungan::where('tujuan_id', $s->id)->where('jenis', 'Sekolah')->count();

            $isContacted = ($visitCount > 0 || $prospekCount > 0) ? 'Sudah Dihubungi' : 'Belum Dihubungi';

            fputcsv($handle, [
                $s->kode,
                $s->nama,
                'Sekolah (SMA/SMK/MA)',
                $s->wilayah?->nama ?: '-',
                $s->kecamatan ?: '-',
                $isContacted,
                $visitCount,
                $prospekCount,
                $lunasCount,
                $s->pic_name ?: '-',
                $s->pic_phone ?: '-'
            ]);
        }

        $perusahaans = Perusahaan::with(['wilayah'])->get();
        foreach ($perusahaans as $p) {
            $prospekCount = Prospek::where('perusahaan_id', $p->id)->count();
            $lunasCount   = Prospek::where('perusahaan_id', $p->id)->where('status', 'LUNAS')->count();
            $visitCount   = \App\Models\Kunjungan::where('tujuan_id', $p->id)->where('jenis', 'Perusahaan')->count();

            $isContacted = ($visitCount > 0 || $prospekCount > 0) ? 'Sudah Dihubungi' : 'Belum Dihubungi';

            fputcsv($handle, [
                $p->kode,
                $p->nama,
                'Perusahaan Mitra',
                $p->wilayah?->nama ?: '-',
                $p->kecamatan ?: '-',
                $isContacted,
                $visitCount,
                $prospekCount,
                $lunasCount,
                $p->pic_name ?: '-',
                $p->pic_phone ?: '-'
            ]);
        }
    }

    /**
     * Export target vs realisasi.
     */
    protected function exportTarget($handle, array $params): void
    {
        fputcsv($handle, [
            'ID Target',
            'Tipe Target',
            'Wilayah',
            'Supervisor (SPV)',
            'Sales',
            'Tahun Akademik',
            'Target Kontak',
            'Target Formulir',
            'Target Lunas',
            'Realisasi Lunas',
            'Defisit / Kekurangan',
            'Persentase Pencapaian (%)'
        ]);

        $targets = Target::with(['sales', 'wilayah'])->where('status', 'Aktif')->get();
        foreach ($targets as $t) {
            $realisasiQuery = Prospek::where('status', 'LUNAS');
            if ($t->sales_id) {
                $realisasiQuery->where('sales_id', $t->sales_id);
            } elseif ($t->wilayah_id) {
                $realisasiQuery->where('wilayah_id', $t->wilayah_id);
            }

            $realisasiLunas = $realisasiQuery->count();
            $targetLunas = (int) $t->target_lunas;
            $defisit = max(0, $targetLunas - $realisasiLunas);
            $pct = $targetLunas > 0 ? round(($realisasiLunas / $targetLunas) * 100, 1) : 0;

            fputcsv($handle, [
                $t->id,
                $t->target_type ?: 'Bulanan',
                $t->wilayah?->nama ?: 'Global',
                $t->spv_id ? (User::find($t->spv_id)?->name ?: '-') : '-',
                $t->sales?->name ?: 'Tim / Wilayah',
                $t->tahun_akademik ?: '-',
                $t->target_kontak ?: 0,
                $t->target_formulir ?: 0,
                $targetLunas,
                $realisasiLunas,
                $defisit,
                $pct . '%'
            ]);
        }
    }

    /**
     * Export executive summary.
     */
    protected function exportSummary($handle, array $params): void
    {
        $query = $this->buildProspekQuery($params);

        $totalProspek = (clone $query)->count();
        $formulir     = (clone $query)->whereIn('status', ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS'])->count();
        $lunas        = (clone $query)->where('status', 'LUNAS')->count();
        $lost         = (clone $query)->whereIn('status', ['DINGIN', 'NO RESPON', 'CANCEL'])->count();

        fputcsv($handle, ['RINGKASAN EKSEKUTIF ANALISIS PMB UCIC']);
        fputcsv($handle, ['Waktu Download:', date('Y-m-d H:i:s')]);
        fputcsv($handle, []);
        fputcsv($handle, ['Metrik', 'Nilai']);
        fputcsv($handle, ['Total Prospek / Kontak:', $totalProspek]);
        fputcsv($handle, ['Total Pembelian Formulir:', $formulir]);
        fputcsv($handle, ['Total Mahasiswa Lunas Tahap 1:', $lunas]);
        fputcsv($handle, ['Total Prospek Batal / Arsip:', $lost]);
        fputcsv($handle, ['Rasio Konversi Kontak ke Formulir:', $totalProspek > 0 ? round(($formulir / $totalProspek) * 100, 2) . '%' : '0%']);
        fputcsv($handle, ['Rasio Konversi Kontak ke Lunas:', $totalProspek > 0 ? round(($lunas / $totalProspek) * 100, 2) . '%' : '0%']);
    }

    /**
     * Build base query for prospeks respecting all filters.
     */
    public function buildProspekQuery(array $params): Builder
    {
        $query = Prospek::with(['sales', 'cs', 'owner', 'prodi', 'wilayah', 'sekolah', 'perusahaan']);

        // 1. Periode filter
        if (!empty($params['from_date'])) {
            $query->whereDate('created_at', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $query->whereDate('created_at', '<=', $params['to_date']);
        }

        // 2. Tahun Akademik filter
        if (!empty($params['ta']) && $params['ta'] !== 'all') {
            $query->where('tahun_akademik', $params['ta']);
        }

        // 3. Wilayah filter (including descendants)
        if (!empty($params['wilayah_id']) && $params['wilayah_id'] !== 'all') {
            $wilayah = Wilayah::find($params['wilayah_id']);
            if ($wilayah) {
                $descendantIds = array_merge([$wilayah->id], $wilayah->getDescendantIds());
                $query->whereIn('wilayah_id', $descendantIds);
            } else {
                $query->where('wilayah_id', $params['wilayah_id']);
            }
        }

        // 4. Prodi filter
        if (!empty($params['prodi_id']) && $params['prodi_id'] !== 'all') {
            $query->where('prodi_id', $params['prodi_id']);
        }

        // 5. Kelas filter (Reguler / Karyawan)
        if (!empty($params['kelas']) && $params['kelas'] !== 'all') {
            $query->where('kelas', $params['kelas']);
        }

        // 6. Source filter
        if (!empty($params['source']) && $params['source'] !== 'all') {
            $query->where('source', $params['source']);
        }

        // 7. Status filter
        if (!empty($params['status']) && $params['status'] !== 'all') {
            $statusUpper = strtoupper($params['status']);
            if ($statusUpper === 'HANGAT') {
                $query->whereIn('status', ['PROSPEK', 'HANGAT']);
            } elseif ($statusUpper === 'PANAS') {
                $query->whereIn('status', ['HOT PROSPEK', 'PANAS']);
            } elseif ($statusUpper === 'DINGIN') {
                $query->whereIn('status', ['DINGIN', 'NO RESPON']);
            } else {
                $query->where('status', $params['status']);
            }
        }

        // 8. Sales filter
        if (!empty($params['sales_id']) && $params['sales_id'] !== 'all') {
            $query->where('sales_id', $params['sales_id']);
        }

        return $query;
    }

    /**
     * Export detail riwayat follow-up yang terhubung dengan prospek sesuai filter.
     */
    protected function exportFollowUp($handle, array $params): void
    {
        fputcsv($handle, [
            'ID Follow-Up',
            'Waktu Follow-Up',
            'ID Prospek',
            'Nama Calon Mahasiswa',
            'No WhatsApp',
            'Status PRD Kanonis',
            'Status Pipeline Operasional',
            'Program Studi Pilihan',
            'Wilayah',
            'Petugas Follow-Up',
            'Role Petugas',
            'Metode Komunikasi',
            'Catatan & Hasil Interaksi',
            'Jadwal Follow-Up Berikutnya'
        ]);

        $prospekIdsQuery = $this->buildProspekQuery($params)->select('prospeks.id');

        FollowUp::with(['prospek.prodi', 'prospek.wilayah', 'user'])
            ->whereIn('prospek_id', $prospekIdsQuery)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->chunk(250, function ($followUps) use ($handle) {
                foreach ($followUps as $f) {
                    $tanggal = $f->tanggal ?? $f->created_at;
                    fputcsv($handle, [
                        $f->id,
                        $tanggal ? $tanggal->format('Y-m-d H:i') : '-',
                        $f->prospek_id,
                        $f->prospek?->name ?: '-',
                        $f->prospek?->whatsapp ?: '-',
                        self::mapToPrdStatus($f->prospek?->status),
                        $f->prospek?->status ?: '-',
                        $f->prospek?->prodi?->nama ?: '-',
                        $f->prospek?->wilayah?->nama ?: '-',
                        $f->user?->name ?: ($f->prospek?->sales?->name ?: '-'),
                        $f->user?->role ?: 'Sales',
                        $f->metode ?: 'WhatsApp',
                        $f->catatan ?: ($f->hasil ?: '-'),
                        $f->next_follow_up ? $f->next_follow_up->format('Y-m-d H:i') : '-'
                    ]);
                }
            });
    }

    /**
     * Export log timeline riwayat perubahan status prospek sesuai filter.
     */
    protected function exportTimeline($handle, array $params): void
    {
        fputcsv($handle, [
            'ID Log Timeline',
            'Waktu Kejadian',
            'ID Prospek',
            'Nama Calon Mahasiswa',
            'No WhatsApp',
            'Peristiwa / Aktivitas',
            'Status Sebelum',
            'Status Sesudah',
            'Keterangan & Catatan',
            'User Aktor',
            'Role Aktor',
            'Status Prospek Terkini'
        ]);

        $prospekIdsQuery = $this->buildProspekQuery($params)->select('prospeks.id');

        ProspekTimeline::with(['prospek', 'user'])
            ->whereIn('prospek_id', $prospekIdsQuery)
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->chunk(250, function ($timelines) use ($handle) {
                foreach ($timelines as $t) {
                    $waktu = $t->time ?? $t->created_at;
                    fputcsv($handle, [
                        $t->id,
                        $waktu ? $waktu->format('Y-m-d H:i:s') : '-',
                        $t->prospek_id,
                        $t->prospek?->name ?: '-',
                        $t->prospek?->whatsapp ?: '-',
                        $t->title ?: 'Perubahan Status',
                        $t->status_before ?: '-',
                        $t->status_after ?: '-',
                        $t->notes ?: '-',
                        $t->user?->name ?: 'Sistem',
                        $t->user?->role ?: 'System',
                        $t->prospek?->status ?: '-'
                    ]);
                }
            });
    }

    /**
     * Export hasil audit kualitas data dan anomali operasional.
     */
    protected function exportKualitasData($handle, array $params): void
    {
        fputcsv($handle, [
            'Kategori Temuan Audit',
            'Tingkat Urgensi',
            'ID Prospek',
            'Nama Calon Mahasiswa',
            'No WhatsApp',
            'Status Lead Kanonis',
            'Status Pipeline Operasional',
            'Sales Penanggung Jawab',
            'Wilayah',
            'Tanggal Didaftarkan',
            'Detail Masalah & Anomali'
        ]);

        // A. Prospek Belum Di-follow-up (0 follow-up)
        $uncontactedQuery = (clone $this->buildProspekQuery($params))
            ->whereDoesntHave('followUps')
            ->whereNotIn('status', ['DINGIN', 'NO RESPON', 'CANCEL']);

        $uncontactedQuery->chunk(250, function ($prospeks) use ($handle) {
            foreach ($prospeks as $p) {
                fputcsv($handle, [
                    'Belum Pernah Di-follow-up',
                    'Perlu Tindak Lanjut',
                    $p->id,
                    $p->name,
                    $p->whatsapp ?: '-',
                    self::mapToPrdStatus($p->status),
                    $p->status,
                    $p->sales?->name ?: 'Direct / Belum Di-assign',
                    $p->wilayah?->nama ?: '-',
                    $p->created_at ? $p->created_at->format('Y-m-d H:i') : '-',
                    'Prospek aktif belum pernah memiliki catatan follow-up sejak pertama kali masuk sistem'
                ]);
            }
        });

        // B. Data Kontak Duplikat (WhatsApp)
        $duplicateWhatsapps = (clone $this->buildProspekQuery($params))
            ->whereNotNull('whatsapp')
            ->where('whatsapp', '!=', '')
            ->select('whatsapp')
            ->groupBy('whatsapp')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('whatsapp');

        if ($duplicateWhatsapps->isNotEmpty()) {
            $duplicateQuery = (clone $this->buildProspekQuery($params))
                ->whereIn('whatsapp', $duplicateWhatsapps)
                ->orderBy('whatsapp')
                ->orderBy('id');

            $duplicateQuery->chunk(250, function ($prospeks) use ($handle) {
                foreach ($prospeks as $p) {
                    fputcsv($handle, [
                        'Kontak Duplikat (No WhatsApp)',
                        'Potensi Redundansi',
                        $p->id,
                        $p->name,
                        $p->whatsapp ?: '-',
                        self::mapToPrdStatus($p->status),
                        $p->status,
                        $p->sales?->name ?: 'Direct / Tanpa Sales',
                        $p->wilayah?->nama ?: '-',
                        $p->created_at ? $p->created_at->format('Y-m-d H:i') : '-',
                        'Nomor WhatsApp ini terdaftar lebih dari satu kali pada database CRM'
                    ]);
                }
            });
        }

        // C. Data Profil Belum Lengkap (Asal Sekolah / Prodi kosong)
        $incompleteQuery = (clone $this->buildProspekQuery($params))
            ->where(function ($q) {
                $q->whereNull('prodi_id')
                  ->orWhere(function ($sq) {
                      $sq->whereNull('sekolah_id')->whereNull('perusahaan_id');
                  });
            });

        $incompleteQuery->chunk(250, function ($prospeks) use ($handle) {
            foreach ($prospeks as $p) {
                $missingFields = [];
                if (!$p->prodi_id && !$p->prodi_lainnya) {
                    $missingFields[] = 'Pilihan Program Studi';
                }
                if (!$p->sekolah_id && !$p->perusahaan_id) {
                    $missingFields[] = 'Asal Sekolah / Mitra';
                }

                fputcsv($handle, [
                    'Profil Belum Lengkap',
                    'Kualitas Data Rendah',
                    $p->id,
                    $p->name,
                    $p->whatsapp ?: '-',
                    self::mapToPrdStatus($p->status),
                    $p->status,
                    $p->sales?->name ?: 'Direct / Tanpa Sales',
                    $p->wilayah?->nama ?: '-',
                    $p->created_at ? $p->created_at->format('Y-m-d H:i') : '-',
                    'Data belum terisi: ' . implode(', ', $missingFields)
                ]);
            }
        });
    }
}
