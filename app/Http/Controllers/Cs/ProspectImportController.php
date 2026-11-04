<?php

namespace App\Http\Controllers\Cs;

use App\Http\Controllers\Controller;
use App\Models\MasterData;
use App\Models\Prospek;
use App\Services\ProspectImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProspectImportController extends Controller
{
    public function __construct(
        protected ProspectImportService $importService
    ) {}

    /**
     * Preview uploaded spreadsheet file and suggest automatic column mappings.
     */
    public function preview(Request $request): JsonResponse
    {
        $role = strtolower(auth()->user()->role ?? '');
        if (!in_array($role, ['cs', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya akun role CS dan Admin yang berwenang menggunakan fitur Import Prospek.',
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|max:15360', // 15MB max
        ], [
            'file.required' => 'Silakan pilih berkas spreadsheet Excel (.xlsx, .xls) atau CSV (.csv) untuk diunggah.',
            'file.max'      => 'Ukuran berkas tidak boleh melebihi 15MB.',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'])) {
            return response()->json([
                'success' => false,
                'message' => 'Format berkas tidak valid. Harap gunakan berkas dengan ekstensi .xlsx, .xls, atau .csv.',
            ], 422);
        }

        try {
            $previewData = $this->importService->parseFile($file);

            return response()->json([
                'success' => true,
                'data'    => $previewData,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Process confirmed mass import into CRM database.
     */
    public function process(Request $request): JsonResponse
    {
        $role = strtolower(auth()->user()->role ?? '');
        if (!in_array($role, ['cs', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya akun role CS dan Admin yang berwenang menggunakan fitur Import Prospek.',
            ], 403);
        }

        $request->validate([
            'token'             => 'required|string',
            'mapping'           => 'nullable|array',
            'on_duplicate'      => 'nullable|in:skip,update,import_all',
            'import_incomplete' => 'nullable|in:skip,import_valid_name',
            'default_source'    => 'nullable|string|max:100',
            'default_status'    => 'nullable|string|max:50',
            'default_type'      => 'nullable|string|in:Individu,Sekolah,Corporate',
        ]);

        try {
            $result = $this->importService->executeImport($request->all(), auth()->user());

            return response()->json([
                'success' => true,
                'data'    => $result,
                'message' => $result['message'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses import data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download sample Excel template for Leads / Google Sheets Import.
     */
    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Prospek');

        $headers = [
            'Nama Leads',
            'Phone (No. WhatsApp)',
            'Minat Studi',
            'Asal Sekolah',
            'Jurusan / Kelas',
            'Sumber Leads',
            'No WA Ortu',
            'Status Prospek',
            'Notes (Catatan Tambahan)',
        ];

        $sheet->fromArray([$headers], null, 'A1');

        // Sample Rows
        $sampleData = [
            [
                'Asep Hidayat',
                '0895324355090',
                'S2 Magister Manajemen',
                'Universitas CIC',
                'Alumni 2020',
                'Alumni',
                '081234567890',
                'BARU',
                'Minat studi lanjut S2 program eksekutif akhir pekan',
            ],
            [
                'Riyanthie Amalia Putri',
                '083838927288',
                'DKV',
                'SMKN 1 KEDAWUNG',
                'XI DKV 1',
                'Kunjungan Sekolah',
                '08561898202',
                'BARU',
                'Tertarik desain grafis dan animasi digital',
            ],
            [
                'Farhan Muhammad Faiq',
                '088970787595',
                'Teknik Informatika',
                'SMKS WAHIDIN',
                'XII RPL 1',
                'MGBK/Miniclass',
                '085323054757',
                'BARU',
                'Mengikuti miniclass pembuatan robot AI & IoT',
            ],
        ];

        $sheet->fromArray($sampleData, null, 'A2');

        // Styling
        $headerRange = 'A1:I1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E40AF'], // Blue 800
            ],
        ]);

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'Template_Import_Prospek_CRM_' . date('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }
}

