<?php

namespace App\Services;

use App\Models\FollowUp;
use App\Models\MasterData;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use App\Models\Sekolah;
use App\Models\TahunAkademik;
use App\Models\Target;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Wilayah;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
     * Export dataset as a genuine, professionally formatted Microsoft Excel .xlsx file.
     *
     * @param string $type
     * @param array $filterParams
     * @return StreamedResponse
     */
    public function export(string $type, array $filterParams = []): StreamedResponse
    {
        // Graceful fallback: if PhpSpreadsheet is unavailable (vendor not installed in production),
        // stream a UTF-8 BOM CSV that Excel can open seamlessly without a fatal error.
        if (!class_exists(Spreadsheet::class)) {
            return $this->exportCsvFallback($type, $filterParams);
        }

        try {
            $spreadsheet = new Spreadsheet();
        } catch (\Throwable $e) {
            return $this->exportCsvFallback($type, $filterParams);
        }
        $spreadsheet->getProperties()
            ->setCreator('CRM Marketing & Sales Inbound UCIC')
            ->setLastModifiedBy('UCIC Data Analyst')
            ->setTitle('Ekspor Data Analitik CRM PMB')
            ->setSubject('Analisis PMB Universitas Catur Insan Cendekia')
            ->setDescription('Hasil ekspor data analitik PMB UCIC resmi.')
            ->setCategory('Laporan Analitik');

        $typeNormalized = strtolower(trim($type));

        switch ($typeNormalized) {
            case 'prospek':
                $this->exportProspek($spreadsheet, $filterParams);
                break;
            case 'funnel':
                $this->exportFunnel($spreadsheet, $filterParams);
                break;
            case 'transaksi':
                $this->exportTransaksi($spreadsheet, $filterParams);
                break;
            case 'performa_sales':
                $this->exportPerformaSales($spreadsheet, $filterParams);
                break;
            case 'sumber':
                $this->exportSumber($spreadsheet, $filterParams);
                break;
            case 'sekolah':
                $this->exportSekolah($spreadsheet, $filterParams);
                break;
            case 'kunjungan':
                $this->exportKunjungan($spreadsheet, $filterParams);
                break;
            case 'target':
                $this->exportTarget($spreadsheet, $filterParams);
                break;
            case 'follow_up':
                $this->exportFollowUp($spreadsheet, $filterParams);
                break;
            case 'timeline':
                $this->exportTimeline($spreadsheet, $filterParams);
                break;
            case 'kualitas_data':
                $this->exportKualitasData($spreadsheet, $filterParams);
                break;
            case 'all':
            case 'rekap_lengkap':
                $this->exportAllSheets($spreadsheet, $filterParams);
                break;
            default:
                $this->exportSummary($spreadsheet, $filterParams);
                break;
        }

        $filename = ($typeNormalized === 'all' || $typeNormalized === 'rekap_lengkap')
            ? 'UCIC_CRM_Analyst_Laporan_Lengkap_MultiSheet_' . date('Ymd_His') . '.xlsx'
            : 'UCIC_CRM_Analyst_' . ucfirst($typeNormalized) . '_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            // Write directly to php://output
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }

    /**
     * Export detail prospek ke sheet XLSX.
     */
    protected function exportProspek(Spreadsheet $spreadsheet, array $params): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Prospek');

        $headers = [
            'ID Prospek',
            'Nama Calon Mahasiswa',
            'No WhatsApp',
            'Tipe',
            'Kategori Prospek',
            'Status Respons',
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
            'No. Registrasi',
            'Gelombang Pendaftaran',
            'Tanggal Dibuat',
            'Terakhir Diupdate'
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

        $query = $this->buildProspekQuery($params);
        $row = 2;
        $stringCols = [1, 3, 17, 21]; // ID Prospek, No WhatsApp, Kode Sales, No. Registrasi

        $query->chunk(250, function ($prospeks) use ($sheet, &$row, $stringCols) {
            foreach ($prospeks as $p) {
                $camabaName = (!empty($p->pic) && $p->pic !== '-') ? $p->pic : $p->name;
                $institusiName = $p->sekolah?->nama ?: ($p->perusahaan?->nama ?: ($p->type === 'Sekolah' || $p->type === 'Corporate' ? $p->name : '-'));
                
                $isSudahRespons = in_array($p->status, ['PROSPEK', 'HOT PROSPEK', 'FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS', 'HANGAT', 'PANAS']) 
                    || ($p->followUps && $p->followUps->whereNotIn('hasil', ['Tidak Dijawab', 'Belum Respon'])->count() > 0);
                $statusRespons = $isSudahRespons ? 'Sudah Merespons' : 'Belum Merespons';

                $values = [
                    $p->id,
                    $camabaName,
                    $p->whatsapp ?: '-',
                    $p->type,
                    $p->category ?: 'Umum / Belum Dikategorikan',
                    $statusRespons,
                    self::mapToPrdStatus($p->status),
                    $p->status,
                    (int) ($p->stage_number ?: 1),
                    $p->prodi?->nama ?: ($p->prodi_lainnya ?: '-'),
                    $p->kelas ?: 'Reguler',
                    $institusiName,
                    $p->wilayah?->nama ?: '-',
                    $p->sekolah?->kecamatan ?: ($p->wilayah?->parent?->nama ?: '-'),
                    $p->source ?: 'Belum Ada Sumber',
                    $p->sales?->name ?: 'Direct / Tanpa Sales',
                    $p->sales?->kode ?: '-',
                    $p->cs?->name ?: '-',
                    (int) ($p->follow_up_count ?: 0),
                    $p->isMabaLunas() ? 'LUNAS RESMI' : ($p->status === 'LUNAS' ? 'LUNAS' : 'Belum Lunas'),
                    $p->no_registrasi ?: '-',
                    $p->gelombang ?: '-',
                    $p->created_at ? $p->created_at->format('Y-m-d H:i:s') : '-',
                    $p->updated_at ? $p->updated_at->format('Y-m-d H:i:s') : '-',
                ];

                $this->writeTableRow($sheet, $row, $values, $stringCols);
                $row++;
            }
        });

        $this->finalizeTable($sheet, count($headers), max($row - 1, 1), 1);
    }

    /**
     * Export funnel PMB summary ke sheet XLSX.
     */
    protected function exportFunnel(Spreadsheet $spreadsheet, array $params): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Funnel PMB');

        $query = $this->buildProspekQuery($params);
        $total = (clone $query)->count();

        $sudahFollowUp = (clone $query)->where(function ($q) {
            $q->where('prospeks.follow_up_count', '>', 0)
              ->orHas('followUps');
        })->count();

        $formulirCount = (clone $query)->whereIn('prospeks.status', ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS'])->count();
        $lunasCount    = (clone $query)->where('prospeks.status', 'LUNAS')->count();
        $lostCount     = (clone $query)->whereIn('prospeks.status', ['DINGIN', 'NO RESPON', 'CANCEL'])->count();

        $convProspekFollowup  = $total > 0 ? round(($sudahFollowUp / $total) * 100, 1) : 0;
        $convFollowupFormulir = $sudahFollowUp > 0 ? round(($formulirCount / $sudahFollowUp) * 100, 1) : 0;
        $convFormulirLunas    = $formulirCount > 0 ? round(($lunasCount / $formulirCount) * 100, 1) : 0;
        $convTotalLunas       = $total > 0 ? round(($lunasCount / $total) * 100, 1) : 0;

        $headers = [
            'No',
            'Tahap Funnel Standar PMB',
            'Status Operasional Terkait',
            'Jumlah Calon Mahasiswa',
            'Persentase dari Total (%)',
            'Konversi dari Tahap Sebelumnya (%)',
            'Drop-off / Belum Lanjut (Orang)',
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

        $dataRows = [
            [
                1,
                '1. Total Prospek',
                'Semua Lead Masuk (Baru, Presensi, Web, dsb)',
                $total,
                '100.0%',
                '100.0%',
                0
            ],
            [
                2,
                '2. Sudah Follow-up',
                'Telah Dihubungi Minimal 1x',
                $sudahFollowUp,
                $total > 0 ? round(($sudahFollowUp / $total) * 100, 1) . '%' : '0%',
                $convProspekFollowup . '%',
                max(0, $total - $sudahFollowUp)
            ],
            [
                3,
                '3. Pengisian Formulir',
                'FORMULIR, BERKAS, CLOSING, LUNAS',
                $formulirCount,
                $total > 0 ? round(($formulirCount / $total) * 100, 1) . '%' : '0%',
                $convFollowupFormulir . '%',
                max(0, $sudahFollowUp - $formulirCount)
            ],
            [
                4,
                '4. Pembayaran Lunas',
                'Mahasiswa Lunas Tahap 1 (Maba Resmi)',
                $lunasCount,
                $total > 0 ? round(($lunasCount / $total) * 100, 1) . '%' : '0%',
                $convFormulirLunas . '%',
                max(0, $formulirCount - $lunasCount)
            ],
            [
                5,
                '5. Prospek Batal / Arsip (Lost)',
                'DINGIN, NO RESPON, CANCEL',
                $lostCount,
                $total > 0 ? round(($lostCount / $total) * 100, 1) . '%' : '0%',
                '-',
                '-'
            ],
        ];

        $row = 2;
        foreach ($dataRows as $item) {
            $this->writeTableRow($sheet, $row, $item, [1]);
            $row++;
        }

        // Row tambahan untuk catatan ringkasan funnel konversi
        $row++;
        $sheet->setCellValueExplicit('B' . $row, 'Rasio Konversi Total (Prospek -> Beli Formulir):', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D' . $row, $total > 0 ? round(($formulirCount / $total) * 100, 2) . '%' : '0%', DataType::TYPE_STRING);
        $sheet->getStyle('B' . $row . ':D' . $row)->getFont()->setBold(true);

        $row++;
        $sheet->setCellValueExplicit('B' . $row, 'Rasio Konversi Akhir (Prospek -> Pembayaran Lunas):', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D' . $row, $total > 0 ? round(($lunasCount / $total) * 100, 2) . '%' : '0%', DataType::TYPE_STRING);
        $sheet->getStyle('B' . $row . ':D' . $row)->getFont()->setBold(true);

        $this->finalizeTable($sheet, count($headers), 6, 1);
    }

    /**
     * Export transaksi pembayaran ke sheet XLSX.
     */
    protected function exportTransaksi(Spreadsheet $spreadsheet, array $params): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Transaksi');

        $headers = [
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
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

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

        // Sinkronisasi dengan filter prospek aktif bila tersedia
        $hasProspekFilter = !empty($params['ta']) || !empty($params['wilayah_id']) || !empty($params['prodi_id'])
            || !empty($params['kelas']) || !empty($params['source']) || !empty($params['sales_id']);

        if ($hasProspekFilter) {
            $query->whereHas('prospek', function ($q) use ($params) {
                if (!empty($params['ta']) && $params['ta'] !== 'all') {
                    $q->where('tahun_akademik', $params['ta']);
                }
                if (!empty($params['wilayah_id']) && $params['wilayah_id'] !== 'all') {
                    $wilayah = Wilayah::find($params['wilayah_id']);
                    if ($wilayah) {
                        $descendantIds = array_merge([$wilayah->id], $wilayah->getDescendantIds());
                        $q->whereIn('wilayah_id', $descendantIds);
                    } else {
                        $q->where('wilayah_id', $params['wilayah_id']);
                    }
                }
                if (!empty($params['prodi_id']) && $params['prodi_id'] !== 'all') {
                    $q->where('prodi_id', $params['prodi_id']);
                }
                if (!empty($params['kelas']) && $params['kelas'] !== 'all') {
                    $q->where('kelas', $params['kelas']);
                }
                if (!empty($params['source']) && $params['source'] !== 'all') {
                    $q->where('source', $params['source']);
                }
                if (!empty($params['sales_id']) && $params['sales_id'] !== 'all') {
                    $q->where('sales_id', $params['sales_id']);
                }
            });
        }

        $row = 2;
        $stringCols = [1, 2]; // ID Transaksi, ID Prospek
        $currencyCols = [5];  // Nominal

        $query->chunk(250, function ($transaksis) use ($sheet, &$row, $stringCols, $currencyCols) {
            foreach ($transaksis as $t) {
                $camabaName = (!empty($t->prospek?->pic) && $t->prospek?->pic !== '-') ? $t->prospek?->pic : ($t->prospek?->name ?: '-');
                $values = [
                    $t->id,
                    $t->prospek_id,
                    $camabaName,
                    $t->jenis,
                    (float) $t->nominal,
                    $t->metode_label,
                    $t->payment_status ?: 'verified (legacy)',
                    $t->verifier?->name ?: 'System / Otomatis',
                    $t->tanggal ? $t->tanggal->format('Y-m-d H:i:s') : '-',
                    $t->prospek?->prodi?->nama ?: '-',
                    $t->prospek?->kelas ?: 'Reguler',
                    $t->prospek?->sales?->name ?: 'Direct / Tanpa Sales',
                    $t->notes ?: '-'
                ];

                $this->writeTableRow($sheet, $row, $values, $stringCols, $currencyCols);
                $row++;
            }
        });

        $this->finalizeTable($sheet, count($headers), max($row - 1, 1), 1);
    }

    /**
     * Export performa tim sales dan customer service ke sheet XLSX.
     */
    protected function exportPerformaSales(Spreadsheet $spreadsheet, array $params): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Performa Tim Sales & CS');

        $headers = [
            'Tipe Personel',
            'ID User',
            'Kode / Email',
            'Nama Personel',
            'Wilayah Penugasan',
            'Supervisor / Peran',
            'Kontak Handled',
            'Aktivitas Follow-Up',
            'Formulir',
            'Closing / Lunas',
            'Verifikasi Pembayaran (Tx)',
            'Total Verifikasi (Rp)',
            'Rasio Konversi (%)'
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

        $salesUsers = User::where('role', 'Sales')
            ->where('status', 'Aktif')
            ->with(['supervisor', 'wilayah'])
            ->get();

        $csUsers = User::where('role', 'CS')
            ->where('status', 'Aktif')
            ->with(['wilayah'])
            ->get();

        $row = 2;
        $stringCols = [2, 3]; // ID User, Kode/Email
        $currencyCols = [12]; // Total Verifikasi Rp

        // 1. TIM SALES LAPANGAN
        foreach ($salesUsers as $sales) {
            $prospekQuery = Prospek::where('sales_id', $sales->id);
            if (!empty($params['from_date'])) {
                $prospekQuery->whereDate('created_at', '>=', $params['from_date']);
            }
            if (!empty($params['to_date'])) {
                $prospekQuery->whereDate('created_at', '<=', $params['to_date']);
            }
            if (!empty($params['ta']) && $params['ta'] !== 'all') {
                $prospekQuery->where('tahun_akademik', $params['ta']);
            }

            $totalKontak   = (clone $prospekQuery)->count();
            $formulirCount = (clone $prospekQuery)->whereIn('status', ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS'])->count();
            $lunasCount    = (clone $prospekQuery)->where('status', 'LUNAS')->count();
            $followUpCount = FollowUp::where('user_id', $sales->id)->count();

            $konversi = $totalKontak > 0 ? round(($lunasCount / $totalKontak) * 100, 2) : 0;

            $values = [
                'Sales Lapangan',
                $sales->id,
                $sales->kode ?: '-',
                $sales->name,
                $sales->wilayah?->nama ?: 'Lintas Wilayah',
                $sales->supervisor?->name ?: 'Supervisor',
                (int) $totalKontak,
                (int) $followUpCount,
                (int) $formulirCount,
                (int) $lunasCount,
                0,
                0,
                $konversi . '%'
            ];

            $this->writeTableRow($sheet, $row, $values, $stringCols, $currencyCols);
            $row++;
        }

        // 2. TIM CUSTOMER SERVICE (CS)
        foreach ($csUsers as $cs) {
            $prospekQuery = Prospek::where('cs_id', $cs->id);
            if (!empty($params['from_date'])) {
                $prospekQuery->whereDate('created_at', '>=', $params['from_date']);
            }
            if (!empty($params['to_date'])) {
                $prospekQuery->whereDate('created_at', '<=', $params['to_date']);
            }
            if (!empty($params['ta']) && $params['ta'] !== 'all') {
                $prospekQuery->where('tahun_akademik', $params['ta']);
            }

            $totalKontak   = (clone $prospekQuery)->count();
            $formulirCount = (clone $prospekQuery)->whereIn('status', ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS'])->count();
            $lunasCount    = (clone $prospekQuery)->where('status', 'LUNAS')->count();
            $followUpCount = FollowUp::where('user_id', $cs->id)->count();
            $verifikasiCount = Transaksi::where('verified_by', $cs->id)->count();
            $verifikasiNominal = (float) Transaksi::where('verified_by', $cs->id)->sum('nominal');

            $konversi = $totalKontak > 0 ? round(($lunasCount / $totalKontak) * 100, 2) : 0;

            $values = [
                'Customer Service',
                $cs->id,
                $cs->email ?: '-',
                $cs->name,
                $cs->wilayah?->nama ?: 'Centralized Inbound',
                'Inbound CS',
                (int) $totalKontak,
                (int) $followUpCount,
                (int) $formulirCount,
                (int) $lunasCount,
                (int) $verifikasiCount,
                $verifikasiNominal,
                $konversi . '%'
            ];

            $this->writeTableRow($sheet, $row, $values, $stringCols, $currencyCols);
            $row++;
        }

        $this->finalizeTable($sheet, count($headers), max($row - 1, 1), 1);
    }

    /**
     * Export sumber & kanal marketing ke sheet XLSX.
     */
    protected function exportSumber(Spreadsheet $spreadsheet, array $params): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kanal & Sumber Prospek');

        $headers = [
            'Sumber / Kanal Informasi',
            'Total Kontak',
            'Jumlah Formulir',
            'Mahasiswa Lunas',
            'Konversi Lead ke Lunas (%)',
            'Share dari Total Kontak (%)'
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

        $query = $this->buildProspekQuery($params);
        $totalAll = (clone $query)->count();

        $sources = Prospek::SOURCES;
        $row = 2;

        foreach ($sources as $source) {
            $srcQuery = (clone $query)->where('source', $source);
            $kontakCount   = (clone $srcQuery)->count();
            $formulirCount = (clone $srcQuery)->whereIn('status', ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS'])->count();
            $lunasCount    = (clone $srcQuery)->where('status', 'LUNAS')->count();

            $konversi = $kontakCount > 0 ? round(($lunasCount / $kontakCount) * 100, 2) : 0;
            $share = $totalAll > 0 ? round(($kontakCount / $totalAll) * 100, 2) : 0;

            $values = [
                $source,
                (int) $kontakCount,
                (int) $formulirCount,
                (int) $lunasCount,
                $konversi . '%',
                $share . '%'
            ];

            $this->writeTableRow($sheet, $row, $values, []);
            $row++;
        }

        $this->finalizeTable($sheet, count($headers), max($row - 1, 1), 1);
    }

    /**
     * Export sekolah & perusahaan mitra ke sheet XLSX.
     */
    protected function exportSekolah(Spreadsheet $spreadsheet, array $params): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sekolah & Mitra');

        $headers = [
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
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

        $row = 2;
        $stringCols = [1, 11]; // Kode, Telepon PIC (preserve leading zeroes)

        $sekolahs = Sekolah::with(['wilayah'])->get();
        foreach ($sekolahs as $s) {
            $prospekCount = Prospek::where('sekolah_id', $s->id)->count();
            $lunasCount   = Prospek::where('sekolah_id', $s->id)->where('status', 'LUNAS')->count();
            $visitCount   = \App\Models\Kunjungan::where('tujuan_id', $s->id)->where('jenis', 'Sekolah')->count();

            $isContacted = ($visitCount > 0 || $prospekCount > 0) ? 'Sudah Dihubungi' : 'Belum Dihubungi';

            $values = [
                $s->kode,
                $s->nama,
                'Sekolah (SMA/SMK/MA)',
                $s->wilayah?->nama ?: '-',
                $s->kecamatan ?: '-',
                $isContacted,
                (int) $visitCount,
                (int) $prospekCount,
                (int) $lunasCount,
                $s->pic_name ?: '-',
                $s->pic_phone ?: '-'
            ];

            $this->writeTableRow($sheet, $row, $values, $stringCols);
            $row++;
        }

        $perusahaans = Perusahaan::with(['wilayah'])->get();
        foreach ($perusahaans as $p) {
            $prospekCount = Prospek::where('perusahaan_id', $p->id)->count();
            $lunasCount   = Prospek::where('perusahaan_id', $p->id)->where('status', 'LUNAS')->count();
            $visitCount   = \App\Models\Kunjungan::where('tujuan_id', $p->id)->where('jenis', 'Perusahaan')->count();

            $isContacted = ($visitCount > 0 || $prospekCount > 0) ? 'Sudah Dihubungi' : 'Belum Dihubungi';

            $values = [
                $p->kode,
                $p->nama,
                'Perusahaan Mitra',
                $p->wilayah?->nama ?: '-',
                $p->kecamatan ?: '-',
                $isContacted,
                (int) $visitCount,
                (int) $prospekCount,
                (int) $lunasCount,
                $p->pic_name ?: '-',
                $p->pic_phone ?: '-'
            ];

            $this->writeTableRow($sheet, $row, $values, $stringCols);
            $row++;
        }

        $this->finalizeTable($sheet, count($headers), max($row - 1, 1), 1);
    }

    /**
     * Export rekap data kunjungan sekolah dan perusahaan lintas role ke sheet XLSX.
     */
    protected function exportKunjungan(Spreadsheet $spreadsheet, array $params): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Data Kunjungan');

        $headers = [
            'Nomor Kunjungan',
            'Tanggal',
            'Waktu',
            'Jenis Kunjungan',
            'Nama Institusi',
            'Petugas Kunjungan',
            'Role Petugas',
            'Wilayah',
            'PIC Institusi',
            'WhatsApp PIC',
            'Tujuan Kunjungan',
            'Potensi Mahasiswa / Kemitraan',
            'Hasil Kunjungan',
            'Status Kunjungan',
            'Status Verifikasi'
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

        $query = \App\Models\Kunjungan::with(['sales.wilayah', 'sekolah.wilayah', 'perusahaan.wilayah']);

        if (!empty($params['from_date'])) {
            $query->whereDate('tanggal', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $query->whereDate('tanggal', '<=', $params['to_date']);
        }
        if (!empty($params['sales_id']) && $params['sales_id'] !== 'all') {
            $query->where('sales_id', $params['sales_id']);
        }
        if (!empty($params['wilayah_id']) && $params['wilayah_id'] !== 'all') {
            $wId = $params['wilayah_id'];
            $query->where(function ($wq) use ($wId) {
                $wq->whereHas('sekolah', fn($sq) => $sq->where('wilayah_id', $wId))
                   ->orWhereHas('perusahaan', fn($pq) => $pq->where('wilayah_id', $wId))
                   ->orWhereHas('sales', fn($uq) => $uq->where('wilayah_id', $wId));
            });
        }

        $query->orderBy('tanggal', 'desc');

        $row = 2;
        $stringCols = [1, 3, 10]; // Nomor, Waktu, WhatsApp PIC

        foreach ($query->get() as $k) {
            $instName = $k->nama_institusi
                ?: ($k->jenis === 'Sekolah' ? $k->sekolah?->nama : $k->perusahaan?->nama)
                ?: '-';

            $wilayahName = $k->sekolah?->wilayah?->nama
                ?: $k->perusahaan?->wilayah?->nama
                ?: $k->sales?->wilayah?->nama
                ?: '-';

            $potensi = $k->jenis === 'Sekolah'
                ? ($k->potensi_mahasiswa ? $k->potensi_mahasiswa . ' Siswa' : '-')
                : ($k->potensi_s1 || $k->potensi_s2 ? ('S1: ' . ($k->potensi_s1 ?: 0) . ' / S2: ' . ($k->potensi_s2 ?: 0)) : '-');

            $values = [
                $k->nomor,
                $k->tanggal ? Carbon::parse($k->tanggal)->format('d/m/Y') : '-',
                $k->waktu ? substr($k->waktu, 0, 5) : '-',
                in_array($k->jenis, ['Perusahaan', 'Corporate']) ? 'Perusahaan' : 'Sekolah',
                $instName,
                $k->sales?->name ?: '-',
                $k->sales?->role ?: '-',
                $wilayahName,
                $k->pic_name ?: ($k->sekolah?->pic_name ?: '-'),
                $k->pic_whatsapp ?: ($k->sekolah?->pic_phone ?: '-'),
                $k->tujuan_kunjungan ?: '-',
                $potensi,
                $k->hasil ?: '-',
                $k->status ?: 'Menunggu',
                $k->status_verifikasi ?: '-'
            ];

            $this->writeTableRow($sheet, $row, $values, $stringCols);
            $row++;
        }

        $this->finalizeTable($sheet, count($headers), max($row - 1, 1), 1);
    }

    /**
     * Export target vs realisasi ke sheet XLSX.
     */
    protected function exportTarget(Spreadsheet $spreadsheet, array $params): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Target & Capaian');

        $headers = [
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
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

        $targets = Target::with(['sales', 'wilayah'])->where('status', 'Aktif')->get();
        $row = 2;
        $stringCols = [1]; // ID Target

        foreach ($targets as $t) {
            $realisasiQuery = Prospek::where('status', 'LUNAS');
            if ($t->sales_id) {
                $realisasiQuery->where('sales_id', $t->sales_id);
            } elseif ($t->wilayah_id) {
                $realisasiQuery->where('wilayah_id', $t->wilayah_id);
            }
            if (!empty($params['ta']) && $params['ta'] !== 'all') {
                $realisasiQuery->where('tahun_akademik', $params['ta']);
            }

            $realisasiLunas = $realisasiQuery->count();
            $targetLunas = (int) $t->target_lunas;
            $defisit = max(0, $targetLunas - $realisasiLunas);
            $pct = $targetLunas > 0 ? round(($realisasiLunas / $targetLunas) * 100, 1) : 0;

            $values = [
                $t->id,
                $t->target_type ?: 'Bulanan',
                $t->wilayah?->nama ?: 'Global',
                $t->spv_id ? (User::find($t->spv_id)?->name ?: '-') : '-',
                $t->sales?->name ?: 'Tim / Wilayah',
                $t->tahun_akademik ?: '-',
                (int) ($t->target_kontak ?: 0),
                (int) ($t->target_formulir ?: 0),
                $targetLunas,
                $realisasiLunas,
                $defisit,
                $pct . '%'
            ];

            $this->writeTableRow($sheet, $row, $values, $stringCols);
            $row++;
        }

        $this->finalizeTable($sheet, count($headers), max($row - 1, 1), 1);
    }

    /**
     * Export executive summary ke sheet XLSX.
     */
    protected function exportSummary(Spreadsheet $spreadsheet, array $params): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ringkasan Eksekutif');

        $query = $this->buildProspekQuery($params);

        $totalProspek  = (clone $query)->count();
        $sudahFollowUp = (clone $query)->where(function ($q) {
            $q->where('prospeks.follow_up_count', '>', 0)
              ->orHas('followUps');
        })->count();
        $belumFollowUp = max(0, $totalProspek - $sudahFollowUp);

        $sudahRespons = (clone $query)->where(function ($q) {
            $q->whereIn('prospeks.status', ['PROSPEK', 'HOT PROSPEK', 'FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS', 'HANGAT', 'PANAS'])
              ->orWhereHas('followUps', function ($fq) {
                  $fq->where(function ($sq) {
                      $sq->whereIn('hasil', ['Dijawab', 'Tertarik & Minta Brosur', 'Jadwalkan Kunjungan', 'FORMULIR'])
                         ->orWhere(function ($ssq) {
                             $ssq->whereNotNull('hasil')->whereNotIn('hasil', ['Tidak Dijawab', 'Belum Respon']);
                         });
                  });
              });
        })->count();
        $belumRespons = max(0, $totalProspek - $sudahRespons);

        $formulir     = (clone $query)->whereIn('status', ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS'])->count();
        $lunas        = (clone $query)->where('status', 'LUNAS')->count();
        $lost         = (clone $query)->whereIn('status', ['DINGIN', 'NO RESPON', 'CANCEL'])->count();

        // Duplikat nomor WA
        $duplicateWa = (clone $query)->whereNotNull('prospeks.whatsapp')->where('prospeks.whatsapp', '!=', '')
            ->select('prospeks.whatsapp')->groupBy('prospeks.whatsapp')->havingRaw('COUNT(*) > 1')->count();

        // Data belum lengkap
        $incomplete = (clone $query)->where(function ($q) {
            $q->whereNull('prospeks.whatsapp')->orWhere('prospeks.whatsapp', '')->orWhereNull('prospeks.prodi_id')
              ->orWhereNull('prospeks.source')->orWhere(function ($sq) { $sq->whereNull('prospeks.sekolah_id')->whereNull('prospeks.perusahaan_id'); });
        })->count();

        // Transaksi Valid
        $transaksiQuery = Transaksi::where(function ($q) {
            $q->where('payment_status', Transaksi::STATUS_VERIFIED)
              ->orWhereNull('payment_status');
        });
        if (!empty($params['from_date'])) {
            $transaksiQuery->whereDate('tanggal', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $transaksiQuery->whereDate('tanggal', '<=', $params['to_date']);
        }
        $revenueValid = (float) $transaksiQuery->sum('nominal');

        $headers = [
            'Indikator Metrik PMB',
            'Nilai / Capaian',
            'Satuan',
            'Keterangan Analitis'
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

        $rows = [
            ['Total Kontak / Prospek Terdata', $totalProspek, 'Orang', 'Seluruh prospek unik yang masuk ke sistem CRM'],
            ['Total Sudah Follow-Up', $sudahFollowUp, 'Orang', 'Prospek yang telah memiliki minimal 1 riwayat follow-up'],
            ['Total Belum Follow-Up', $belumFollowUp, 'Orang', 'Prospek yang belum pernah dihubungi sama sekali'],
            ['Total Sudah Merespons (Respons Positif)', $sudahRespons, 'Orang', 'Prospek yang aktif merespons atau berada di tahap prospek+'],
            ['Total Belum Merespons (No Respon / Antrean)', $belumRespons, 'Orang', 'Prospek berstatus No Respon atau belum merespons'],
            ['Total Pembelian Formulir (Pendaftar)', $formulir, 'Orang', 'Prospek yang telah membeli formulir pendaftaran'],
            ['Total Mahasiswa Lunas Tahap 1 (Maba Resmi)', $lunas, 'Orang', 'Mahasiswa yang telah melunasi pembayaran tahap 1'],
            ['Total Prospek Batal / Arsip (Lost)', $lost, 'Orang', 'Prospek dengan status Dingin, No Respon, atau Batal'],
            ['Rasio Konversi Prospek ke Follow-Up', $totalProspek > 0 ? round(($sudahFollowUp / $totalProspek) * 100, 2) . '%' : '0%', 'Persentase', 'Tingkat jangkauan follow-up terhadap lead masuk'],
            ['Rasio Konversi Follow-Up ke Formulir', $sudahFollowUp > 0 ? round(($formulir / $sudahFollowUp) * 100, 2) . '%' : '0%', 'Persentase', 'Tingkat efektivitas follow-up menghasilkan pendaftar formulir'],
            ['Rasio Konversi Formulir ke Lunas', $formulir > 0 ? round(($lunas / $formulir) * 100, 2) . '%' : '0%', 'Persentase', 'Tingkat pelunasan dari pendaftar formulir'],
            ['Rasio Konversi Total Prospek ke Lunas', $totalProspek > 0 ? round(($lunas / $totalProspek) * 100, 2) . '%' : '0%', 'Persentase', 'Tingkat konversi akhir prospek menjadi mahasiswa lunas resmi'],
            ['Total Pendapatan Terverifikasi', $revenueValid, 'Rupiah', 'Total penerimaan pendaftaran dan registrasi yang telah divalidasi'],
            ['Kontak Duplikat WhatsApp Terdeteksi', $duplicateWa, 'Nomor', 'Nomor telepon terdaftar lebih dari satu kali'],
            ['Data Belum Lengkap (Profil Rendah)', $incomplete, 'Data', 'Prospek tanpa pilihan program studi atau asal sekolah/mitra'],
            ['Periode Analisis', $params['periode'] ?? 'Semua Periode', 'Rentang', 'Rentang tanggal filter data yang diterapkan'],
            ['Tahun Akademik', $params['ta'] ?? 'Semua TA', 'Akademik', 'Tahun akademik yang dievaluasi'],
            ['Waktu Ekspor Dibuat', date('Y-m-d H:i:s'), 'Waktu Server', 'Timestamp data diekstrak dari database'],
        ];

        $rowIdx = 2;
        foreach ($rows as $item) {
            $isRevenueRow = ($item[0] === 'Total Pendapatan Terverifikasi');
            $this->writeTableRow($sheet, $rowIdx, $item, [], $isRevenueRow ? [2] : []);
            $rowIdx++;
        }

        $this->finalizeTable($sheet, count($headers), count($rows) + 1, 1);
    }

    /**
     * Export detail riwayat follow-up yang terhubung dengan prospek sesuai filter ke sheet XLSX.
     */
    protected function exportFollowUp(Spreadsheet $spreadsheet, array $params): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Aktivitas Follow-Up');

        $headers = [
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
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

        $prospekIdsQuery = $this->buildProspekQuery($params)->select('prospeks.id');

        $row = 2;
        $stringCols = [1, 3, 5]; // ID Follow-Up, ID Prospek, No WhatsApp

        FollowUp::with(['prospek.prodi', 'prospek.wilayah', 'user'])
            ->whereIn('prospek_id', $prospekIdsQuery)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->chunk(250, function ($followUps) use ($sheet, &$row, $stringCols) {
                foreach ($followUps as $f) {
                    $tanggal = $f->tanggal ?? $f->created_at;
                    $camabaName = (!empty($f->prospek?->pic) && $f->prospek?->pic !== '-') ? $f->prospek?->pic : ($f->prospek?->name ?: '-');
                    $values = [
                        $f->id,
                        $tanggal ? $tanggal->format('Y-m-d H:i') : '-',
                        $f->prospek_id,
                        $camabaName,
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
                    ];

                    $this->writeTableRow($sheet, $row, $values, $stringCols);
                    $row++;
                }
            });

        $this->finalizeTable($sheet, count($headers), max($row - 1, 1), 1);
    }

    /**
     * Export log timeline riwayat perubahan status prospek sesuai filter ke sheet XLSX.
     */
    protected function exportTimeline(Spreadsheet $spreadsheet, array $params): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Timeline Status');

        $headers = [
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
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

        $prospekIdsQuery = $this->buildProspekQuery($params)->select('prospeks.id');

        $row = 2;
        $stringCols = [1, 3, 5]; // ID Log, ID Prospek, No WhatsApp

        ProspekTimeline::with(['prospek', 'user'])
            ->whereIn('prospek_id', $prospekIdsQuery)
            ->orderByDesc('time')
            ->orderByDesc('id')
            ->chunk(250, function ($timelines) use ($sheet, &$row, $stringCols) {
                foreach ($timelines as $t) {
                    $waktu = $t->time ?? $t->created_at;
                    $camabaName = (!empty($t->prospek?->pic) && $t->prospek?->pic !== '-') ? $t->prospek?->pic : ($t->prospek?->name ?: '-');
                    $values = [
                        $t->id,
                        $waktu ? $waktu->format('Y-m-d H:i:s') : '-',
                        $t->prospek_id,
                        $camabaName,
                        $t->prospek?->whatsapp ?: '-',
                        $t->title ?: 'Perubahan Status',
                        $t->status_before ?: '-',
                        $t->status_after ?: '-',
                        $t->notes ?: '-',
                        $t->user?->name ?: 'Sistem',
                        $t->user?->role ?: 'System',
                        $t->prospek?->status ?: '-'
                    ];

                    $this->writeTableRow($sheet, $row, $values, $stringCols);
                    $row++;
                }
            });

        $this->finalizeTable($sheet, count($headers), max($row - 1, 1), 1);
    }

    /**
     * Export hasil audit kualitas data dan anomali operasional ke sheet XLSX.
     */
    protected function exportKualitasData(Spreadsheet $spreadsheet, array $params): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Audit Kualitas Data');

        $headers = [
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
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

        $row = 2;
        $stringCols = [3, 5]; // ID Prospek, No WhatsApp

        // A. Prospek Belum Di-follow-up (0 follow-up)
        $uncontactedQuery = (clone $this->buildProspekQuery($params))
            ->whereDoesntHave('followUps')
            ->whereNotIn('status', ['DINGIN', 'NO RESPON', 'CANCEL']);

        $uncontactedQuery->chunk(250, function ($prospeks) use ($sheet, &$row, $stringCols) {
            foreach ($prospeks as $p) {
                $camabaName = (!empty($p->pic) && $p->pic !== '-') ? $p->pic : $p->name;
                $values = [
                    'Belum Pernah Di-follow-up',
                    'Perlu Tindak Lanjut',
                    $p->id,
                    $camabaName,
                    $p->whatsapp ?: '-',
                    self::mapToPrdStatus($p->status),
                    $p->status,
                    $p->sales?->name ?: 'Direct / Belum Di-assign',
                    $p->wilayah?->nama ?: '-',
                    $p->created_at ? $p->created_at->format('Y-m-d H:i') : '-',
                    'Prospek aktif belum pernah memiliki catatan follow-up sejak pertama kali masuk sistem'
                ];
                $this->writeTableRow($sheet, $row, $values, $stringCols);
                $row++;
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

            $duplicateQuery->chunk(250, function ($prospeks) use ($sheet, &$row, $stringCols) {
                foreach ($prospeks as $p) {
                    $camabaName = (!empty($p->pic) && $p->pic !== '-') ? $p->pic : $p->name;
                    $values = [
                        'Kontak Duplikat (No WhatsApp)',
                        'Potensi Redundansi',
                        $p->id,
                        $camabaName,
                        $p->whatsapp ?: '-',
                        self::mapToPrdStatus($p->status),
                        $p->status,
                        $p->sales?->name ?: 'Direct / Tanpa Sales',
                        $p->wilayah?->nama ?: '-',
                        $p->created_at ? $p->created_at->format('Y-m-d H:i') : '-',
                        'Nomor WhatsApp ini terdaftar lebih dari satu kali pada database CRM'
                    ];
                    $this->writeTableRow($sheet, $row, $values, $stringCols);
                    $row++;
                }
            });
        }

        // C. Data Profil Belum Lengkap
        $incompleteQuery = (clone $this->buildProspekQuery($params))
            ->where(function ($q) {
                $q->whereNull('prodi_id')
                  ->orWhere(function ($sq) {
                      $sq->whereNull('sekolah_id')->whereNull('perusahaan_id');
                  });
            });

        $incompleteQuery->chunk(250, function ($prospeks) use ($sheet, &$row, $stringCols) {
            foreach ($prospeks as $p) {
                $missingFields = [];
                if (!$p->prodi_id && !$p->prodi_lainnya) {
                    $missingFields[] = 'Pilihan Program Studi';
                }
                if (!$p->sekolah_id && !$p->perusahaan_id) {
                    $missingFields[] = 'Asal Sekolah / Mitra';
                }

                $camabaName = (!empty($p->pic) && $p->pic !== '-') ? $p->pic : $p->name;
                $values = [
                    'Profil Belum Lengkap',
                    'Kualitas Data Rendah',
                    $p->id,
                    $camabaName,
                    $p->whatsapp ?: '-',
                    self::mapToPrdStatus($p->status),
                    $p->status,
                    $p->sales?->name ?: 'Direct / Tanpa Sales',
                    $p->wilayah?->nama ?: '-',
                    $p->created_at ? $p->created_at->format('Y-m-d H:i') : '-',
                    'Data belum terisi: ' . implode(', ', $missingFields)
                ];
                $this->writeTableRow($sheet, $row, $values, $stringCols);
                $row++;
            }
        });

        // D. Data Prospek Belum Merespons (No Respon / Butuh Intervensi Komunikasi)
        $noResponQuery = (clone $this->buildProspekQuery($params))
            ->where(function ($q) {
                $q->where('status', 'NO RESPON')
                  ->orWhere(function ($sub) {
                      $sub->whereIn('status', ['BARU', 'KONTAK', 'DINGIN'])
                          ->whereDoesntHave('followUps', function ($fq) {
                              $fq->where(function ($sq) {
                                  $sq->whereIn('hasil', ['Dijawab', 'Tertarik & Minta Brosur', 'Jadwalkan Kunjungan', 'FORMULIR'])
                                     ->orWhere(function ($ssq) {
                                         $ssq->whereNotNull('hasil')->whereNotIn('hasil', ['Tidak Dijawab', 'Belum Respon']);
                                     });
                              });
                          });
                  });
            });

        $noResponQuery->chunk(250, function ($prospeks) use ($sheet, &$row, $stringCols) {
            foreach ($prospeks as $p) {
                $camabaName = (!empty($p->pic) && $p->pic !== '-') ? $p->pic : $p->name;
                $values = [
                    'Belum Merespons (No Respon)',
                    'Perlu Pendekatan Ulang',
                    $p->id,
                    $camabaName,
                    $p->whatsapp ?: '-',
                    self::mapToPrdStatus($p->status),
                    $p->status,
                    $p->sales?->name ?: 'Direct / Tanpa Sales',
                    $p->wilayah?->nama ?: '-',
                    $p->created_at ? $p->created_at->format('Y-m-d H:i') : '-',
                    'Belum ada tanggapan positif atau berstatus no respon dari calon mahasiswa'
                ];
                $this->writeTableRow($sheet, $row, $values, $stringCols);
                $row++;
            }
        });

        $this->finalizeTable($sheet, count($headers), max($row - 1, 1), 1);
    }

    /**
     * Ekspor seluruh kumpulan analitik ke dalam satu workbook multi-sheet XLSX.
     */
    protected function exportAllSheets(Spreadsheet $spreadsheet, array $params): void
    {
        // 1. Sheet Ringkasan
        $this->exportSummary($spreadsheet, $params);

        // 2. Sheet Prospek
        $prospekSheet = $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndexByName($prospekSheet->getTitle());
        $this->exportProspek($spreadsheet, $params);

        // 3. Sheet Funnel
        $funnelSheet = $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndexByName($funnelSheet->getTitle());
        $this->exportFunnel($spreadsheet, $params);

        // 4. Sheet Transaksi
        $txSheet = $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndexByName($txSheet->getTitle());
        $this->exportTransaksi($spreadsheet, $params);

        // 5. Sheet Performa Sales
        $salesSheet = $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndexByName($salesSheet->getTitle());
        $this->exportPerformaSales($spreadsheet, $params);

        // 6. Sheet Sumber
        $sumberSheet = $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndexByName($sumberSheet->getTitle());
        $this->exportSumber($spreadsheet, $params);

        // 7. Sheet Sekolah & Mitra
        $sekolahSheet = $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndexByName($sekolahSheet->getTitle());
        $this->exportSekolah($spreadsheet, $params);

        // 8. Sheet Rekap Kunjungan
        $kunjunganSheet = $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndexByName($kunjunganSheet->getTitle());
        $this->exportKunjungan($spreadsheet, $params);

        // 9. Sheet Target & Realisasi
        $targetSheet = $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndexByName($targetSheet->getTitle());
        $this->exportTarget($spreadsheet, $params);

        // 10. Sheet Riwayat Follow-Up
        $fuSheet = $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndexByName($fuSheet->getTitle());
        $this->exportFollowUp($spreadsheet, $params);

        // 11. Sheet Timeline Status
        $timelineSheet = $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndexByName($timelineSheet->getTitle());
        $this->exportTimeline($spreadsheet, $params);

        // 12. Sheet Audit Kualitas Data
        $auditSheet = $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndexByName($auditSheet->getTitle());
        $this->exportKualitasData($spreadsheet, $params);

        // Kembalikan active sheet ke sheet pertama (Ringkasan Eksekutif)
        $spreadsheet->setActiveSheetIndex(0);
    }

    /**
     * Helper layout & styling header tabel.
     */
    protected function setupSheetHeader(Worksheet $sheet, array $headers, int $headerRow = 1): void
    {
        $totalCols = count($headers);
        $lastColLetter = Coordinate::stringFromColumnIndex($totalCols);

        foreach ($headers as $idx => $title) {
            $colLetter = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValueExplicit($colLetter . $headerRow, (string) $title, DataType::TYPE_STRING);
        }

        $headerRange = "A{$headerRow}:{$lastColLetter}{$headerRow}";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
                'name'  => 'Calibri',
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A8A'], // Navy UCIC Blue
            ],
            'alignment' => [
                'vertical'   => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '64748B'],
                ],
            ],
        ]);

        $sheet->getRowDimension($headerRow)->setRowHeight(28);
        $freezeRow = $headerRow + 1;
        $sheet->freezePane("A{$freezeRow}");
    }

    /**
     * Helper tulis satu baris data dengan penanganan tipe data yang presisi & format angka rapi.
     */
    protected function writeTableRow(Worksheet $sheet, int $row, array $values, array $stringColIndices = [], array $currencyColIndices = [], array $centerColIndices = []): void
    {
        foreach ($values as $idx => $val) {
            $colIdx = $idx + 1;
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $coord = $colLetter . $row;

            if (in_array($colIdx, $stringColIndices, true)) {
                // Teks murni (mempertahankan angka 0 di depan, ID, kode wilayah/sales)
                $sheet->setCellValueExplicit($coord, (string) ($val ?? '-'), DataType::TYPE_STRING);
                $sheet->getStyle($coord)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
            } elseif (in_array($colIdx, $currencyColIndices, true) && is_numeric($val)) {
                // Format angka mata uang / rupiah
                $sheet->setCellValueExplicit($coord, (float) $val, DataType::TYPE_NUMERIC);
                $sheet->getStyle($coord)->getNumberFormat()->setFormatCode('"Rp "#,##0');
                $sheet->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            } elseif (is_numeric($val) && !is_string($val)) {
                // Nilai numerik murni (misal integer hitungan)
                $sheet->setCellValueExplicit($coord, $val, DataType::TYPE_NUMERIC);
                $sheet->getStyle($coord)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            } else {
                $sheet->setCellValueExplicit($coord, (string) ($val ?? '-'), DataType::TYPE_STRING);
                if (is_string($val) && str_ends_with($val, '%')) {
                    $sheet->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } elseif (in_array($colIdx, $centerColIndices, true)) {
                    $sheet->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }
        }

        // Soft zebra striping pada baris genap untuk keterbacaan tinggi
        if ($row % 2 === 0) {
            $lastColLetter = Coordinate::stringFromColumnIndex(count($values));
            $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")->applyFromArray([
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8FAFC'],
                ],
            ]);
        }

        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    /**
     * Helper finalisasi border, auto-filter, dan auto-size kolom.
     */
    protected function finalizeTable(Worksheet $sheet, int $totalCols, int $lastRow, int $headerRow = 1): void
    {
        $lastColLetter = Coordinate::stringFromColumnIndex($totalCols);

        if ($lastRow >= $headerRow + 1) {
            $dataRange = "A" . ($headerRow + 1) . ":{$lastColLetter}{$lastRow}";
            $sheet->getStyle($dataRange)->applyFromArray([
                'font' => [
                    'size' => 10,
                    'name' => 'Calibri',
                    'color' => ['rgb' => '1E293B'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'E2E8F0'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $filterRange = "A{$headerRow}:{$lastColLetter}{$lastRow}";
            $sheet->setAutoFilter($filterRange);
        }

        for ($col = 1; $col <= $totalCols; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
    }

    /**
     * Build base query for prospeks respecting all filters.
     * All column references are qualified with 'prospeks.' to avoid SQL ambiguity
     * when the caller later joins prodis, wilayahs, or other tables.
     */
    public function buildProspekQuery(array $params): Builder
    {
        $query = Prospek::with(['sales', 'cs', 'owner', 'prodi', 'wilayah', 'sekolah', 'perusahaan']);

        // 1. Periode filter — qualify with table prefix to avoid ambiguity on joins
        $fromDate = $params['from_date'] ?? ($params['start_date'] ?? null);
        $toDate = $params['to_date'] ?? ($params['end_date'] ?? null);

        if (!empty($fromDate)) {
            $query->whereDate('prospeks.created_at', '>=', $fromDate);
        }
        if (!empty($toDate)) {
            $query->whereDate('prospeks.created_at', '<=', $toDate);
        }

        // 2. Tahun Akademik filter
        if (!empty($params['ta']) && $params['ta'] !== 'all') {
            $query->where('prospeks.tahun_akademik', $params['ta']);
        }

        // 3. Wilayah filter (including descendants)
        if (!empty($params['wilayah_id']) && $params['wilayah_id'] !== 'all') {
            $wilayah = Wilayah::find($params['wilayah_id']);
            if ($wilayah) {
                $descendantIds = array_merge([$wilayah->id], $wilayah->getDescendantIds());
                $query->whereIn('prospeks.wilayah_id', $descendantIds);
            } else {
                $query->where('prospeks.wilayah_id', $params['wilayah_id']);
            }
        }

        // 4. Prodi filter
        if (!empty($params['prodi_id']) && $params['prodi_id'] !== 'all') {
            $query->where('prospeks.prodi_id', $params['prodi_id']);
        }

        // 5. Kelas filter (Reguler / Karyawan)
        if (!empty($params['kelas']) && $params['kelas'] !== 'all') {
            $query->where('prospeks.kelas', $params['kelas']);
        }

        // 6. Source filter
        if (!empty($params['source']) && $params['source'] !== 'all') {
            $query->where('prospeks.source', $params['source']);
        }

        // 7. Status filter
        if (!empty($params['status']) && $params['status'] !== 'all') {
            $statusUpper = strtoupper($params['status']);
            if ($statusUpper === 'HANGAT') {
                $query->whereIn('prospeks.status', ['PROSPEK', 'HANGAT']);
            } elseif ($statusUpper === 'PANAS') {
                $query->whereIn('prospeks.status', ['HOT PROSPEK', 'PANAS']);
            } elseif ($statusUpper === 'DINGIN') {
                $query->whereIn('prospeks.status', ['DINGIN', 'NO RESPON']);
            } else {
                $query->where('prospeks.status', $params['status']);
            }
        }

        // 8. Sales filter
        if (!empty($params['sales_id']) && $params['sales_id'] !== 'all') {
            $query->where('prospeks.sales_id', $params['sales_id']);
        }

        // 9. Gelombang filter
        if (!empty($params['gelombang']) && $params['gelombang'] !== 'all') {
            $query->where('prospeks.gelombang', $params['gelombang']);
        }

        // 10. No. Registrasi filter / search
        if (!empty($params['no_registrasi'])) {
            $query->where('prospeks.no_registrasi', 'like', '%' . trim($params['no_registrasi']) . '%');
        }

        // 11. Kategori filter (Kategori resmi CRM)
        if (!empty($params['category']) && $params['category'] !== 'all') {
            $query->where('prospeks.category', $params['category']);
        }

        // 12. Scope filter (presensi, sudah_follow_up, belum_follow_up, sudah_merespons, belum_merespons, duplikat_wa, incomplete)
        if (!empty($params['scope'])) {
            $scope = $params['scope'];
            if ($scope === 'presensi') {
                $query->where(function ($pq) {
                    $pq->where('prospeks.source', Prospek::SOURCE_PRESENSI_SISWA)
                       ->orWhere('prospeks.source', 'Presensi Siswa')
                       ->orWhere('prospeks.source', 'like', '%presensi%');
                });
            } elseif ($scope === 'sudah_follow_up') {
                $query->where(function ($q) {
                    $q->where('prospeks.follow_up_count', '>', 0)
                      ->orHas('followUps');
                });
            } elseif ($scope === 'belum_follow_up') {
                $query->where('prospeks.follow_up_count', 0)
                      ->whereNotIn('prospeks.status', ['LUNAS', 'CANCEL']);
            } elseif ($scope === 'sudah_merespons') {
                $query->where(function ($q) {
                    $q->whereIn('prospeks.status', ['PROSPEK', 'HOT PROSPEK', 'FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS', 'HANGAT', 'PANAS'])
                      ->orWhereHas('followUps', function ($fq) {
                          $fq->where(function ($sq) {
                              $sq->whereIn('hasil', ['Dijawab', 'Tertarik & Minta Brosur', 'Jadwalkan Kunjungan', 'FORMULIR'])
                                 ->orWhere(function ($ssq) {
                                     $ssq->whereNotNull('hasil')
                                         ->whereNotIn('hasil', ['Tidak Dijawab', 'Belum Respon']);
                                 });
                          });
                      });
                });
            } elseif ($scope === 'belum_merespons') {
                $query->where(function ($q) {
                    $q->where('prospeks.status', 'NO RESPON')
                      ->orWhere(function ($sub) {
                          $sub->whereIn('prospeks.status', ['BARU', 'KONTAK', 'DINGIN'])
                              ->whereDoesntHave('followUps', function ($fq) {
                                  $fq->where(function ($sq) {
                                      $sq->whereIn('hasil', ['Dijawab', 'Tertarik & Minta Brosur', 'Jadwalkan Kunjungan', 'FORMULIR'])
                                         ->orWhere(function ($ssq) {
                                             $ssq->whereNotNull('hasil')
                                                 ->whereNotIn('hasil', ['Tidak Dijawab', 'Belum Respon']);
                                         });
                                  });
                              });
                      });
                });
            } elseif ($scope === 'duplikat_wa') {
                $query->whereIn('prospeks.whatsapp', function ($sq) {
                    $sq->select('whatsapp')
                       ->from('prospeks')
                       ->whereNotNull('whatsapp')
                       ->where('whatsapp', '!=', '')
                       ->groupBy('whatsapp')
                       ->havingRaw('COUNT(*) > 1');
                });
            } elseif ($scope === 'incomplete') {
                $query->where(function ($q) {
                    $q->whereNull('prospeks.whatsapp')
                      ->orWhere('prospeks.whatsapp', '')
                      ->orWhereNull('prospeks.prodi_id')
                      ->orWhereNull('prospeks.source')
                      ->orWhere(function ($sq) {
                          $sq->whereNull('prospeks.sekolah_id')->whereNull('prospeks.perusahaan_id');
                      });
                });
            }
        }

        return $query;
    }

    /**
     * CSV fallback export (UTF-8 BOM) when PhpSpreadsheet is unavailable.
     * Excel recognises this BOM and opens it correctly without encoding issues.
     */
    protected function exportCsvFallback(string $type, array $filterParams = []): StreamedResponse
    {
        $filename = 'UCIC_CRM_Analyst_' . ucfirst(strtolower(trim($type))) . '_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($type, $filterParams) {
            // UTF-8 BOM so Excel opens the file with correct encoding
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');

            // Build a simple prospek query as default CSV dataset
            $query = $this->buildProspekQuery($filterParams);
            $prospeks = $query->with(['sales', 'prodi', 'wilayah'])->get();

            // Header row
            fputcsv($out, [
                'No', 'Nama', 'WhatsApp', 'Status', 'Prodi', 'Wilayah',
                'Sales', 'Sumber', 'Kelas', 'Gelombang', 'Tahun Akademik', 'Tanggal Masuk',
            ]);

            // Data rows
            foreach ($prospeks as $idx => $p) {
                $camabaName = (!empty($p->pic) && $p->pic !== '-') ? $p->pic : ($p->name ?? $p->nama ?? '-');
                fputcsv($out, [
                    $idx + 1,
                    $camabaName,
                    $p->whatsapp ?? '-',
                    $p->status ?? '-',
                    $p->prodi->nama ?? '-',
                    $p->wilayah->nama ?? '-',
                    $p->sales->name ?? '-',
                    $p->source ?? '-',
                    $p->kelas ?? '-',
                    $p->gelombang ?? '-',
                    $p->tahun_akademik ?? '-',
                    $p->created_at ? $p->created_at->format('Y-m-d H:i') : '-',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store',
            'Pragma'              => 'no-cache',
        ]);
    }
}
