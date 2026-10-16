<?php

namespace App\Http\Controllers;

use App\Models\Kunjungan;
use App\Models\Prospek;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

class ExportController extends Controller
{
    /**
     * Setup professional UCIC navy blue header.
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
                'startColor' => ['rgb' => '0F172A'], // Sleek Dark Slate / Navy Blue
            ],
            'alignment' => [
                'vertical'   => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '334155'],
                ],
            ],
        ]);

        $sheet->getRowDimension($headerRow)->setRowHeight(28);
        $freezeRow = $headerRow + 1;
        $sheet->freezePane("A{$freezeRow}");
    }

    /**
     * Write single row with data types and currency formatting.
     */
    protected function writeTableRow(Worksheet $sheet, int $row, array $values, array $stringColIndices = [], array $currencyColIndices = []): void
    {
        foreach ($values as $idx => $val) {
            $colIdx = $idx + 1;
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $coord = $colLetter . $row;

            if (in_array($colIdx, $stringColIndices, true)) {
                $sheet->setCellValueExplicit($coord, (string) ($val ?? '-'), DataType::TYPE_STRING);
                $sheet->getStyle($coord)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
            } elseif (in_array($colIdx, $currencyColIndices, true) && is_numeric($val)) {
                $sheet->setCellValueExplicit($coord, (float) $val, DataType::TYPE_NUMERIC);
                $sheet->getStyle($coord)->getNumberFormat()->setFormatCode('#,##0');
            } elseif (is_numeric($val) && !is_string($val)) {
                $sheet->setCellValueExplicit($coord, $val, DataType::TYPE_NUMERIC);
            } else {
                $sheet->setCellValueExplicit($coord, (string) ($val ?? '-'), DataType::TYPE_STRING);
            }
        }

        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    /**
     * Finalize table styling, borders and autosize.
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
     * Stream download spreadsheet as response.
     */
    protected function streamSpreadsheet(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }

    /**
     * 1. Export Data Prospek (.xlsx)
     */
    public function exportProspek(Request $request): StreamedResponse
    {
        $user = auth()->user();
        $role = strtoupper($user->role ?? '');

        $query = Prospek::with(['sales', 'cs', 'owner', 'prodi', 'wilayah', 'sekolah', 'perusahaan']);

        // Scope filter: all, own, others, presensi
        $scope = $request->query('scope', 'all');

        if ($scope === 'presensi') {
            // Khusus tab Presensi Siswa: hanya data yang berasal dari form presensi siswa
            $query->where(function ($q) {
                $q->where('source', Prospek::SOURCE_PRESENSI_SISWA)
                  ->orWhere('source', 'Presensi Siswa')
                  ->orWhere('source', 'like', '%presensi%');
            });
        } elseif ($scope === 'own') {
            // Khusus tab Prospek Saya
            if ($role === 'CS') {
                $query->where(function ($q) use ($user) {
                    $q->where('cs_id', $user->id)
                      ->orWhere('owner_id', $user->id)
                      ->orWhere('takeover_by_id', $user->id);
                });
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('sales_id', $user->id)
                      ->orWhere('owner_id', $user->id)
                      ->orWhere('takeover_by_id', $user->id);
                });
            }
        } elseif ($scope === 'others') {
            // Khusus tab Prospek User Lain
            if ($role === 'CS') {
                $query->where(function ($q) use ($user) {
                    $q->where(function ($s) use ($user) {
                        $s->whereNull('cs_id')->orWhere('cs_id', '!=', $user->id);
                    })->where(function ($s) use ($user) {
                        $s->whereNull('owner_id')->orWhere('owner_id', '!=', $user->id);
                    })->where(function ($s) use ($user) {
                        $s->whereNull('takeover_by_id')->orWhere('takeover_by_id', '!=', $user->id);
                    });
                });
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where(function ($s) use ($user) {
                        $s->whereNull('sales_id')->orWhere('sales_id', '!=', $user->id);
                    })->where(function ($s) use ($user) {
                        $s->whereNull('owner_id')->orWhere('owner_id', '!=', $user->id);
                    })->where(function ($s) use ($user) {
                        $s->whereNull('takeover_by_id')->orWhere('takeover_by_id', '!=', $user->id);
                    });
                });
            }
        } else {
            // Default role scoping untuk tab 'Semua Prospek' (all)
            if ($role === 'SALES') {
                $query->where(function ($q) use ($user) {
                    $q->where('sales_id', $user->id)
                      ->orWhere('owner_id', $user->id)
                      ->orWhere('takeover_by_id', $user->id)
                      ->orWhereNotNull('cs_id')
                      ->orWhere('source', Prospek::SOURCE_PRESENSI_SISWA);
                });
            } elseif ($role === 'SPV') {
                $teamSalesIds = array_unique(array_merge([$user->id], $user->teamMemberIds()));
                $query->where(function ($q) use ($teamSalesIds, $user) {
                    $q->whereIn('sales_id', $teamSalesIds)
                      ->orWhereIn('owner_id', $teamSalesIds)
                      ->orWhere('takeover_by_id', $user->id)
                      ->orWhere('source', Prospek::SOURCE_PRESENSI_SISWA);
                    if ($user->wilayah_id) {
                        $q->orWhere('wilayah_id', $user->wilayah_id);
                    }
                });
            }
        }

        // Filters
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        if ($request->filled('gelombang') && $request->gelombang !== 'all') {
            $query->where('gelombang', $request->gelombang);
        }
        if ($request->filled('sales_id') && $request->sales_id !== 'all') {
            $query->where('sales_id', $request->sales_id);
        }
        if ($request->filled('cs_id') && $request->cs_id !== 'all') {
            $query->where('cs_id', $request->cs_id);
        }
        if ($request->filled('takeover') && $request->takeover !== 'all') {
            if ($request->takeover === 'sales') {
                $query->whereNotNull('takeover_by_id')->whereHas('takeoverBy', fn($sq) => $sq->where('role', 'Sales'));
            } elseif ($request->takeover === 'cs') {
                $query->whereNotNull('takeover_by_id')->whereHas('takeoverBy', fn($sq) => $sq->where('role', 'CS'));
            } elseif ($request->takeover === 'taken') {
                $query->whereNotNull('takeover_by_id');
            } elseif ($request->takeover === 'untaken') {
                $query->whereNull('takeover_by_id');
            }
        }
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('pic', 'like', "%{$s}%")
                  ->orWhere('whatsapp', 'like', "%{$s}%")
                  ->orWhere('no_registrasi', 'like', "%{$s}%")
                  ->orWhere('gelombang', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%")
                  ->orWhereHas('sekolah', fn($sq) => $sq->where('nama', 'like', "%{$s}%"))
                  ->orWhereHas('perusahaan', fn($pq) => $pq->where('nama', 'like', "%{$s}%"))
                  ->orWhereHas('wilayah', fn($wq) => $wq->where('nama', 'like', "%{$s}%"))
                  ->orWhereHas('sales', fn($sq) => $sq->where('name', 'like', "%{$s}%"))
                  ->orWhereHas('cs', fn($cq) => $cq->where('name', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $query->orderBy('created_at', 'desc');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle('Data Prospek CRM UCIC');
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Prospek');

        $headers = [
            'No',
            'ID Prospek',
            'No. Registrasi',
            'Nama Calon Mahasiswa',
            'No. WhatsApp',
            'Tipe',
            'Status Pipeline',
            'Jurusan / Prodi Pilihan',
            'Kelas',
            'Asal Sekolah / Perusahaan',
            'Wilayah',
            'Petugas Sales',
            'Petugas CS',
            'Sumber Lead',
            'Gelombang',
            'Tanggal Dibuat',
            'Terakhir Diperbarui'
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

        $row = 2;
        $no = 1;
        $stringCols = [2, 3, 5]; // ID, No Registrasi, WhatsApp

        $query->chunk(250, function ($prospeks) use ($sheet, &$row, &$no, $stringCols) {
            foreach ($prospeks as $p) {
                $values = [
                    $no++,
                    $p->id,
                    $p->no_registrasi ?: '-',
                    $p->name,
                    $p->whatsapp ?: '-',
                    $p->type,
                    $p->status,
                    $p->prodi?->nama ?: ($p->prodi_lainnya ?: '-'),
                    $p->kelas ?: 'Reguler',
                    $p->sekolah?->nama ?: ($p->perusahaan?->nama ?: '-'),
                    $p->wilayah?->nama ?: '-',
                    $p->sales?->name ?: ($p->owner?->name ?: '-'),
                    $p->cs?->name ?: '-',
                    $p->source ?: '-',
                    $p->gelombang ?: '-',
                    $p->created_at ? $p->created_at->format('d/m/Y H:i') : '-',
                    $p->updated_at ? $p->updated_at->format('d/m/Y H:i') : '-',
                ];
                $this->writeTableRow($sheet, $row, $values, $stringCols);
                $row++;
            }
        });

        $this->finalizeTable($sheet, count($headers), max($row - 1, 1), 1);

        $filename = 'UCIC_CRM_Prospek_' . date('Ymd_His') . '.xlsx';
        return $this->streamSpreadsheet($spreadsheet, $filename);
    }

    /**
     * 2. Export Data Kunjungan (.xlsx)
     */
    public function exportKunjungan(Request $request): StreamedResponse
    {
        $user = auth()->user();
        $role = strtoupper($user->role ?? '');

        $query = Kunjungan::with(['sales', 'dosen', 'sekolah', 'perusahaan', 'event']);

        // Role scoping
        if ($role === 'SALES') {
            $query->where('sales_id', $user->id);
        } elseif ($role === 'SPV') {
            $teamSalesIds = array_unique(array_merge([$user->id], $user->teamMemberIds()));
            $query->whereIn('sales_id', $teamSalesIds);
        }

        // Filter Sales jika dipilih
        if ($request->filled('sales_id') && $request->sales_id !== 'all') {
            $query->where('sales_id', $request->sales_id);
        }

        // Filter Tab Verifikasi SPV
        if ($request->boolean('verifikasi_only') || $request->input('verifikasi_only') === '1') {
            $query->where(function ($q) {
                $q->where('status_verifikasi', 'Perlu Verifikasi')
                  ->orWhere('is_outside_radius', true);
            });
        }

        // Filter Jenis / Kategori
        if ($request->filled('jenis') && $request->jenis !== 'all') {
            $jenis = ucfirst(strtolower($request->jenis));
            if (in_array($jenis, ['Corporate', 'Perusahaan'])) {
                $query->whereIn('jenis', ['Perusahaan', 'Corporate']);
            } elseif ($jenis === 'Sekolah') {
                $query->where('jenis', 'Sekolah');
            }
        }

        // Filter Search
        if ($request->filled('search') || $request->filled('q')) {
            $s = trim($request->search ?: $request->q);
            $query->where(function ($q) use ($s) {
                $q->where('nomor', 'like', "%{$s}%")
                  ->orWhere('nama_institusi', 'like', "%{$s}%")
                  ->orWhere('tujuan_kunjungan', 'like', "%{$s}%")
                  ->orWhere('pic_name', 'like', "%{$s}%")
                  ->orWhere('pic_whatsapp', 'like', "%{$s}%")
                  ->orWhere('dosen_pemateri', 'like', "%{$s}%")
                  ->orWhereHas('sales', fn($sq) => $sq->where('name', 'like', "%{$s}%"))
                  ->orWhereHas('sekolah', fn($sq) => $sq->where('nama', 'like', "%{$s}%"))
                  ->orWhereHas('perusahaan', fn($pq) => $pq->where('nama', 'like', "%{$s}%"));
            });
        }

        // Filter Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('tanggal', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('tanggal', '<=', $request->end_date);
        }

        $query->orderBy('tanggal', 'desc');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle('Data Kunjungan CRM UCIC');
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Kunjungan');

        $headers = [
            'No',
            'Nomor Kunjungan',
            'Tanggal',
            'Waktu',
            'Petugas Sales',
            'Jenis Kunjungan',
            'Nama Institusi / Sekolah / Corporate',
            'PIC / Kontak',
            'No. WhatsApp PIC',
            'Alamat',
            'Dosen Pemateri / Narasumber',
            'Potensi Kemitraan / Mahasiswa',
            'Catatan / Hasil Kunjungan',
            'Status Verifikasi Radius',
            'Status Kunjungan'
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

        $row = 2;
        $no = 1;
        $stringCols = [2, 9]; // Nomor Kunjungan, WhatsApp

        $query->chunk(250, function ($kunjungans) use ($sheet, &$row, &$no, $stringCols) {
            foreach ($kunjungans as $k) {
                $dosenName = $k->dosen ? $k->dosen->name : ($k->dosen_pemateri ?: '-');
                $instName = $k->nama_institusi ?: ($k->sekolah?->nama ?: ($k->perusahaan?->nama ?: ($k->tujuan_kunjungan ?: '-')));
                
                $potential = $k->jenis === 'Sekolah' 
                    ? ($k->potensi_mahasiswa ?: ($k->detail_potensi_mahasiswa ?: '-'))
                    : ($k->potensi_s1 || $k->potensi_s2 || $k->potensi_csr 
                        ? trim("S1: {$k->potensi_s1} | S2: {$k->potensi_s2} | CSR: {$k->potensi_csr}") 
                        : ($k->bidang_usaha ?: '-'));

                $values = [
                    $no++,
                    $k->nomor ?: "KNJ-{$k->id}",
                    $k->tanggal ? Carbon::parse($k->tanggal)->format('d/m/Y') : '-',
                    $k->waktu ? Carbon::parse($k->waktu)->format('H:i') . ' WIB' : '-',
                    $k->sales?->name ?: '-',
                    $k->jenis ?: '-',
                    $instName,
                    $k->pic_name ?: '-',
                    $k->pic_whatsapp ?: '-',
                    $k->alamat ?: '-',
                    $dosenName,
                    $potential,
                    $k->catatan ?: ($k->hasil ?: '-'),
                    $k->is_verified ? 'Terverifikasi' : ($k->is_outside_radius ? 'Di Luar Radius (Perlu Verifikasi)' : 'Valid Radius'),
                    $k->status ?: 'Selesai'
                ];

                $this->writeTableRow($sheet, $row, $values, $stringCols);
                $row++;
            }
        });

        $this->finalizeTable($sheet, count($headers), max($row - 1, 1), 1);

        $filename = 'UCIC_CRM_Kunjungan_' . date('Ymd_His') . '.xlsx';
        return $this->streamSpreadsheet($spreadsheet, $filename);
    }

    /**
     * 3. Export Verifikasi Pembayaran (.xlsx) untuk CS, Admin, HM
     */
    public function exportVerifikasi(Request $request): StreamedResponse
    {
        $userRole = strtoupper(auth()->user()->role ?? '');
        abort_if(!in_array($userRole, ['CS', 'ADMIN', 'HM', 'HEAD MARKETING']), 403);

        $query = Transaksi::with([
            'prospek.sekolah',
            'prospek.perusahaan',
            'prospek.prodi',
            'prospek.sales',
            'prospek.cs',
            'user',
            'verifier'
        ]);

        $activeTab = $request->query('tab', 'pending');
        if ($activeTab === 'pending') {
            $query->where('payment_status', Transaksi::STATUS_PENDING);
        } else {
            $statusFilter = $request->query('status');
            if ($statusFilter && in_array($statusFilter, [Transaksi::STATUS_VERIFIED, Transaksi::STATUS_REJECTED])) {
                $query->where('payment_status', $statusFilter);
            } else {
                $query->whereIn('payment_status', [Transaksi::STATUS_VERIFIED, Transaksi::STATUS_REJECTED]);
            }
        }

        // Apply filters
        if ($request->filled('kategori') && $request->kategori !== 'all') {
            $query->where('kategori', $request->kategori);
        }
        if ($request->filled('prodi_id') && $request->prodi_id !== 'all') {
            $query->whereHas('prospek', fn($p) => $p->where('prodi_id', $request->prodi_id));
        }
        if ($request->filled('kelas') && $request->kelas !== 'all') {
            $query->whereHas('prospek', fn($p) => $p->where('kelas', $request->kelas));
        }
        if ($request->filled('start_date')) {
            $query->whereDate('tanggal', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('tanggal', '<=', $request->end_date);
        }
        if ($request->filled('q')) {
            $s = trim($request->q);
            $query->where(function ($sq) use ($s) {
                $sq->whereHas('prospek', fn($p) => $p->where('name', 'like', "%{$s}%")->orWhere('whatsapp', 'like', "%{$s}%"))
                   ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$s}%"))
                   ->orWhere('notes', 'like', "%{$s}%")
                   ->orWhere('rejection_reason', 'like', "%{$s}%");
            });
        }

        $query->orderBy('tanggal', 'desc');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle('Verifikasi Pembayaran CRM UCIC');
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Verifikasi Pembayaran');

        $headers = [
            'No',
            'ID Transaksi',
            'No. Registrasi',
            'Nama Calon Mahasiswa',
            'Asal Sekolah / Mitra',
            'Petugas Sales',
            'Petugas CS',
            'Program Studi Pilihan',
            'Kelas',
            'Kategori Transaksi',
            'Metode Pembayaran',
            'Tanggal Pembayaran',
            'Nominal Pembayaran (Rp)',
            'Status Verifikasi',
            'Diverifikasi Oleh',
            'Tanggal Verifikasi',
            'Catatan / Alasan'
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

        $row = 2;
        $no = 1;
        $stringCols = [2, 3];
        $currencyCols = [13];

        $query->chunk(250, function ($transaksis) use ($sheet, &$row, &$no, $stringCols, $currencyCols) {
            foreach ($transaksis as $t) {
                $p = $t->prospek;
                $values = [
                    $no++,
                    $t->id,
                    $p?->no_registrasi ?: '-',
                    $p?->name ?: '-',
                    $p?->sekolah?->nama ?: ($p?->perusahaan?->nama ?: '-'),
                    $p?->sales?->name ?: ($t->user?->name ?: '-'),
                    $p?->cs?->name ?: '-',
                    $p?->prodi?->nama ?: ($p?->prodi_lainnya ?: '-'),
                    $p?->kelas ?: 'Reguler',
                    $t->kategori ?: 'DP Daftar',
                    ucwords(str_replace('_', ' ', $t->metode_pembayaran ?: 'bank_transfer')),
                    $t->tanggal ? Carbon::parse($t->tanggal)->format('d/m/Y') : '-',
                    (float) $t->nominal,
                    strtoupper($t->payment_status ?: 'PENDING'),
                    $t->verifier?->name ?: '-',
                    $t->verified_at ? Carbon::parse($t->verified_at)->format('d/m/Y H:i') : '-',
                    $t->rejection_reason ?: ($t->notes ?: '-')
                ];

                $this->writeTableRow($sheet, $row, $values, $stringCols, $currencyCols);
                $row++;
            }
        });

        $this->finalizeTable($sheet, count($headers), max($row - 1, 1), 1);

        $filename = 'UCIC_CRM_Verifikasi_Pembayaran_' . date('Ymd_His') . '.xlsx';
        return $this->streamSpreadsheet($spreadsheet, $filename);
    }

    /**
     * 4. Export Riwayat Pembayaran (.xlsx) untuk Sales, SPV, CS, Admin, HM
     */
    public function exportPembayaran(Request $request): StreamedResponse
    {
        $user = auth()->user();
        $role = $user->role ?? 'Sales';

        $query = Transaksi::with([
            'prospek.sekolah',
            'prospek.perusahaan',
            'prospek.prodi',
            'prospek.sales',
            'prospek.cs',
            'user',
            'verifier'
        ]);

        // Scoping
        if ($role === 'Sales') {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('prospek', function ($qp) use ($user) {
                      $qp->where('sales_id', $user->id)
                         ->orWhere('owner_id', $user->id);
                  });
            });
        } elseif ($role === 'SPV') {
            $teamSalesIds = array_unique(array_merge([$user->id], $user->teamMemberIds()));
            $query->where(function ($q) use ($teamSalesIds) {
                $q->whereIn('user_id', $teamSalesIds)
                  ->orWhereHas('prospek', function ($qp) use ($teamSalesIds) {
                      $qp->whereIn('sales_id', $teamSalesIds)
                         ->orWhereIn('owner_id', $teamSalesIds);
                  });
            });
        }

        // Filters
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('payment_status', $request->status);
        }
        if ($request->filled('jenis') && $request->jenis !== 'all') {
            $query->where('jenis', $request->jenis);
        }
        if ($request->filled('kategori') && $request->kategori !== 'all') {
            $query->where('kategori', $request->kategori);
        }
        if ($request->filled('metode') && $request->metode !== 'all') {
            $query->where('metode_pembayaran', $request->metode);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('tanggal', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('tanggal', '<=', $request->end_date);
        }
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('id', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%")
                  ->orWhereHas('prospek', fn($qp) => $qp->where('name', 'like', "%{$s}%")->orWhere('whatsapp', 'like', "%{$s}%"));
            });
        }

        $query->orderBy('tanggal', 'desc');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle('Riwayat Pembayaran CRM UCIC');
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Riwayat Pembayaran');

        $headers = [
            'No',
            'ID Transaksi',
            'Tanggal Transaksi',
            'Nama Calon Mahasiswa',
            'WhatsApp',
            'Petugas Sales',
            'Petugas CS',
            'Jenis Transaksi',
            'Kategori Transaksi',
            'Metode Pembayaran',
            'Nominal Pembayaran (Rp)',
            'Status Pembayaran',
            'Diverifikasi Oleh',
            'Catatan'
        ];

        $this->setupSheetHeader($sheet, $headers, 1);

        $row = 2;
        $no = 1;
        $stringCols = [2, 5];
        $currencyCols = [11];

        $query->chunk(250, function ($transaksis) use ($sheet, &$row, &$no, $stringCols, $currencyCols) {
            foreach ($transaksis as $t) {
                $p = $t->prospek;
                $values = [
                    $no++,
                    $t->id,
                    $t->tanggal ? Carbon::parse($t->tanggal)->format('d/m/Y') : '-',
                    $p?->name ?: '-',
                    $p?->whatsapp ?: '-',
                    $p?->sales?->name ?: ($t->user?->name ?: '-'),
                    $p?->cs?->name ?: '-',
                    $t->jenis ?: 'Pendaftaran',
                    $t->kategori ?: 'DP Daftar',
                    ucwords(str_replace('_', ' ', $t->metode_pembayaran ?: 'Transfer Bank')),
                    (float) $t->nominal,
                    $t->payment_status === Transaksi::STATUS_VERIFIED ? 'LUNAS (Verified)' : ($t->payment_status === Transaksi::STATUS_REJECTED ? 'DITOLAK' : 'MENUNGGU CS'),
                    $t->verifier?->name ?: '-',
                    $t->notes ?: '-'
                ];

                $this->writeTableRow($sheet, $row, $values, $stringCols, $currencyCols);
                $row++;
            }
        });

        $this->finalizeTable($sheet, count($headers), max($row - 1, 1), 1);

        $filename = 'UCIC_CRM_Riwayat_Pembayaran_' . date('Ymd_His') . '.xlsx';
        return $this->streamSpreadsheet($spreadsheet, $filename);
    }
}
