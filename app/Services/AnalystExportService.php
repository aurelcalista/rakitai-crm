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
        $spreadsheet = new Spreadsheet();
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

        $filename = 'UCIC_CRM_Analyst_' . ucfirst($typeNormalized) . '_' . date('Ymd_His') . '.xlsx';

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
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

        $query = $this->buildProspekQuery($params);
        $row = 2;
        $stringCols = [1, 3, 15]; // ID Prospek, No WhatsApp, Kode Sales

        $query->chunk(250, function ($prospeks) use ($sheet, &$row, $stringCols) {
            foreach ($prospeks as $p) {
                $values = [
                    $p->id,
                    $p->name,
                    $p->whatsapp ?: '-',
                    $p->type,
                    self::mapToPrdStatus($p->status),
                    $p->status,
                    (int) ($p->stage_number ?: 1),
                    $p->prodi?->nama ?: ($p->prodi_lainnya ?: '-'),
                    $p->kelas ?: 'Reguler',
                    $p->sekolah?->nama ?: ($p->perusahaan?->nama ?: '-'),
                    $p->wilayah?->nama ?: '-',
                    $p->sekolah?->kecamatan ?: ($p->wilayah?->parent?->nama ?: '-'),
                    $p->source ?: 'Belum Ada Sumber',
                    $p->sales?->name ?: 'Direct / Tanpa Sales',
                    $p->sales?->kode ?: '-',
                    $p->cs?->name ?: '-',
                    (int) ($p->follow_up_count ?: 0),
                    $p->isMabaLunas() ? 'LUNAS RESMI' : ($p->status === 'LUNAS' ? 'LUNAS' : 'Belum Lunas'),
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

        $baruCount     = (clone $query)->whereIn('status', ['BARU', 'KONTAK'])->count();
        $prospekCount  = (clone $query)->whereIn('status', ['PROSPEK', 'HOT PROSPEK', 'HANGAT', 'PANAS'])->count();
        $formulirCount = (clone $query)->whereIn('status', ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS'])->count();
        $berkasCount   = (clone $query)->whereIn('status', ['BERKAS', 'CLOSING', 'LUNAS'])->count();
        $lunasCount    = (clone $query)->where('status', 'LUNAS')->count();
        $lostCount     = (clone $query)->whereIn('status', ['DINGIN', 'NO RESPON', 'CANCEL'])->count();

        $headers = [
            'Tahap Funnel',
            'Status Operasional Terkait',
            'Jumlah Prospek (Orang)',
            'Persentase dari Total (%)',
            'Konversi dari Tahap Sebelumnya (%)',
            'Drop-off / Selisih (Orang)',
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

        $dataRows = [
            [
                '1. Kontak & Lead Masuk',
                'BARU, KONTAK',
                $total,
                '100.0%',
                '100.0%',
                0
            ],
            [
                '2. Audiensi & Prospek Aktif',
                'PROSPEK, HANGAT, PANAS, FORMULIR+',
                $prospekCount + $formulirCount,
                $total > 0 ? round((($prospekCount + $formulirCount) / $total) * 100, 1) . '%' : '0%',
                $total > 0 ? round((($prospekCount + $formulirCount) / $total) * 100, 1) . '%' : '0%',
                max(0, $total - ($prospekCount + $formulirCount))
            ],
            [
                '3. Pembelian Formulir (Pendaftar)',
                'FORMULIR, BERKAS, CLOSING, LUNAS',
                $formulirCount,
                $total > 0 ? round(($formulirCount / $total) * 100, 1) . '%' : '0%',
                ($prospekCount + $formulirCount) > 0 ? round(($formulirCount / ($prospekCount + $formulirCount)) * 100, 1) . '%' : '0%',
                max(0, ($prospekCount + $formulirCount) - $formulirCount)
            ],
            [
                '4. Pemberkasan Dokumen',
                'BERKAS, CLOSING, LUNAS',
                $berkasCount,
                $total > 0 ? round(($berkasCount / $total) * 100, 1) . '%' : '0%',
                $formulirCount > 0 ? round(($berkasCount / $formulirCount) * 100, 1) . '%' : '0%',
                max(0, $formulirCount - $berkasCount)
            ],
            [
                '5. Mahasiswa Lunas Tahap 1 (Maba Resmi)',
                'LUNAS, CLOSING',
                $lunasCount,
                $total > 0 ? round(($lunasCount / $total) * 100, 1) . '%' : '0%',
                $berkasCount > 0 ? round(($lunasCount / $berkasCount) * 100, 1) . '%' : '0%',
                max(0, $berkasCount - $lunasCount)
            ],
            [
                '6. Prospek Berhenti / Drop-off (Lost)',
                'DINGIN, NO RESPON, CANCEL',
                $lostCount,
                $total > 0 ? round(($lostCount / $total) * 100, 1) . '%' : '0%',
                '-',
                '-'
            ],
        ];

        $row = 2;
        foreach ($dataRows as $item) {
            $this->writeTableRow($sheet, $row, $item, []);
            $row++;
        }

        // Row tambahan untuk catatan ringkasan funnel
        $row++;
        $sheet->setCellValueExplicit('A' . $row, 'Konversi Total (Kontak ke Formulir):', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B' . $row, $total > 0 ? round(($formulirCount / $total) * 100, 2) . '%' : '0%', DataType::TYPE_STRING);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);

        $row++;
        $sheet->setCellValueExplicit('A' . $row, 'Konversi Akhir (Kontak ke Lunas Resmi):', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B' . $row, $total > 0 ? round(($lunasCount / $total) * 100, 2) . '%' : '0%', DataType::TYPE_STRING);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);

        $this->finalizeTable($sheet, count($headers), 7, 1);
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
                $values = [
                    $t->id,
                    $t->prospek_id,
                    $t->prospek?->name ?: '-',
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
     * Export performa sales ke sheet XLSX.
     */
    protected function exportPerformaSales(Spreadsheet $spreadsheet, array $params): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Performa Sales');

        $headers = [
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
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

        $salesUsers = User::where('role', 'Sales')
            ->where('status', 'Aktif')
            ->with(['supervisor', 'wilayah'])
            ->get();

        $row = 2;
        $stringCols = [1, 2]; // ID Sales, Kode Pegawai

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
                $sales->id,
                $sales->kode ?: '-',
                $sales->name,
                $sales->wilayah?->nama ?: 'Lintas Wilayah',
                $sales->supervisor?->name ?: '-',
                (int) $totalKontak,
                (int) $followUpCount,
                (int) $formulirCount,
                (int) $lunasCount,
                $konversi . '%'
            ];

            $this->writeTableRow($sheet, $row, $values, $stringCols);
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

        $totalProspek = (clone $query)->count();
        $formulir     = (clone $query)->whereIn('status', ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS'])->count();
        $lunas        = (clone $query)->where('status', 'LUNAS')->count();
        $lost         = (clone $query)->whereIn('status', ['DINGIN', 'NO RESPON', 'CANCEL'])->count();

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
            ['Total Pembelian Formulir (Pendaftar)', $formulir, 'Orang', 'Prospek yang telah membeli formulir pendaftaran'],
            ['Total Mahasiswa Lunas Tahap 1 (Maba Resmi)', $lunas, 'Orang', 'Mahasiswa yang telah melunasi pembayaran tahap 1'],
            ['Total Prospek Batal / Arsip (Lost)', $lost, 'Orang', 'Prospek dengan status Dingin, No Respon, atau Batal'],
            ['Rasio Konversi Kontak ke Formulir', $totalProspek > 0 ? round(($formulir / $totalProspek) * 100, 2) . '%' : '0%', 'Persentase', 'Tingkat konversi prospek menjadi pembeli formulir'],
            ['Rasio Konversi Kontak ke Lunas', $totalProspek > 0 ? round(($lunas / $totalProspek) * 100, 2) . '%' : '0%', 'Persentase', 'Tingkat konversi prospek menjadi mahasiswa lunas resmi'],
            ['Total Pendapatan Terverifikasi', $revenueValid, 'Rupiah', 'Total pendapatan pendaftaran dan termin 1 valid'],
            ['Periode Analisis', $params['periode'] ?? 'Semua Periode', 'Rentang', 'Rentang tanggal filter data yang diterapkan'],
            ['Tahun Akademik', $params['ta'] ?? 'Semua TA', 'Akademik', 'Tahun akademik yang dievaluasi'],
            ['Waktu Ekspor Dibuat', date('Y-m-d H:i:s'), 'Waktu Server', 'Timestamp data diekstrak dari database'],
        ];

        $rowIdx = 2;
        $currencyCols = [2]; // baris ke-7 (pendapatan) menggunakan format rupiah

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
                    $values = [
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
                    $values = [
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
                $values = [
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
                    $values = [
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

                $values = [
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

        // 7. Sheet Sekolah
        $sekolahSheet = $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndexByName($sekolahSheet->getTitle());
        $this->exportSekolah($spreadsheet, $params);

        // 8. Sheet Target
        $targetSheet = $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndexByName($targetSheet->getTitle());
        $this->exportTarget($spreadsheet, $params);

        // 9. Sheet Follow-Up
        $fuSheet = $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndexByName($fuSheet->getTitle());
        $this->exportFollowUp($spreadsheet, $params);

        // 10. Sheet Timeline
        $timelineSheet = $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndexByName($timelineSheet->getTitle());
        $this->exportTimeline($spreadsheet, $params);

        // 11. Sheet Kualitas Data
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
                    'color'       => ['rgb' => '94A3B8'],
                ],
            ],
        ]);

        $sheet->getRowDimension($headerRow)->setRowHeight(28);
        $freezeRow = $headerRow + 1;
        $sheet->freezePane("A{$freezeRow}");
    }

    /**
     * Helper tulis satu baris data dengan penanganan tipe data yang presisi.
     */
    protected function writeTableRow(Worksheet $sheet, int $row, array $values, array $stringColIndices = [], array $currencyColIndices = []): void
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
                $sheet->getStyle($coord)->getNumberFormat()->setFormatCode('#,##0');
            } elseif (is_numeric($val) && !is_string($val)) {
                // Nilai numerik murni (misal integer hitungan)
                $sheet->setCellValueExplicit($coord, $val, DataType::TYPE_NUMERIC);
            } else {
                $sheet->setCellValueExplicit($coord, (string) ($val ?? '-'), DataType::TYPE_STRING);
            }
        }

        $sheet->getRowDimension($row)->setRowHeight(21);
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
}
