<?php

namespace App\Services;

use App\Models\MasterData;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use App\Models\Sekolah;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Support\WhatsAppHelper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProspectImportService
{
    /**
     * Recognized CRM Target Fields.
     */
    public const CRM_FIELDS = [
        'name'          => 'Nama Lengkap / PIC (Wajib)',
        'whatsapp'      => 'Nomor WhatsApp Prospek (Wajib)',
        'wa_ortu'       => 'Nomor WhatsApp Orang Tua',
        'type'          => 'Tipe Prospek (Individu/Sekolah/Corporate)',
        'sekolah_name'  => 'Asal Sekolah / Kampus',
        'asal_kelas'    => 'Kelas / Jurusan / Angkatan',
        'prodi_name'    => 'Program Studi / Minat Studi',
        'category'      => 'Kategori / Program (S1/S2/B2C)',
        'kelas'         => 'Kelas Perkuliahan (Reguler/Karyawan)',
        'source'        => 'Sumber Prospek (Leads Source)',
        'status'        => 'Status Prospek Awal',
        'notes'         => 'Catatan Khusus (Notes)',
        'extra_to_notes'=> 'Simpan ke Catatan Tambahan',
        'ignore'        => 'Abaikan Kolom Ini',
    ];

    /**
     * Clean header names for matching.
     */
    protected function normalizeHeader(string $header): string
    {
        // Strip BOM, numbers with dots like "1. ", trim, lowercase
        $h = preg_replace('/^\xEF\xBB\xBF/', '', $header);
        $h = preg_replace('/^[0-9]+[\.\)]\s*/', '', $h);
        $h = trim(preg_replace('/\s+/', ' ', $h));
        return strtolower($h);
    }

    /**
     * Heuristic auto-mapping from raw column header to CRM field.
     */
    public function detectFieldMapping(string $header): string
    {
        $clean = $this->normalizeHeader($header);

        // Name
        if (preg_match('/^(nama\s*leads|nama\s*lengkap|nama\s*siswa|nama\s*mhs|nama\s*calon|full\s*name|nama\s*pic|nama)$/i', $clean) ||
            in_array($clean, ['nama leads', 'nama lengkap', 'nama siswa', 'nama', 'pic', 'leads', 'full name'])) {
            return 'name';
        }

        // Parent WA
        if (preg_match('/(wa\s*ortu|orang\s*tua|wali|ayah|ibu)/i', $clean) && preg_match('/(wa|hp|telepon|whatsapp|phone|nomor|no)/i', $clean)) {
            return 'wa_ortu';
        }

        // WhatsApp / Phone
        if (preg_match('/^(phone|no\s*wa|nomor\s*whatsapp|whatsapp|no\s*hp|nomorhpaktif|telepon|handphone|kontak|no\s*telepon|whatsapp\s*link|wa\.me|no\s*telp)$/i', $clean) ||
            preg_match('/(whatsapp|wa\.me|no\s*wa|nomor\s*wa|no\s*hp|nomor\s*hp|phone)/i', $clean)) {
            return 'whatsapp';
        }

        // Asal Sekolah
        if (preg_match('/(asal\s*sekolah|nama\s*sekolah|sekolah|school|universitas|kampus|perguruan\s*tinggi|institusi|instansi)/i', $clean) && !preg_match('/(tgl|tanggal|tahun|blast|url|link)/i', $clean)) {
            return 'sekolah_name';
        }

        // Kelas / Jurusan / Angkatan (Asal Kelas)
        if (preg_match('/(asal\s*kelas|kelas|jurusan|tingkat|angkatan|alumni\s*angkatan|dulu\s*kuliah\s*jurusan)/i', $clean) && !preg_match('/(minat|tujuan|rencana|kuliah\s*jurusan\s*apa)/i', $clean)) {
            return 'asal_kelas';
        }

        // Minat Studi / Program Studi
        if (preg_match('/(minat\s*studi|minat\s*prodi|program\s*studi\s*yang\s*diminati|program\s*studi|prodi|lulusan\s*program\s*studi|mini\s*boothcamp\s*pilihanmu|pilihan\s*prodi|konsentrasi)/i', $clean)) {
            return 'prodi_name';
        }

        // Sumber Leads / Info
        if (preg_match('/(sumber\s*leads|sumber\s*informasi|sumber|darimana\s*mendapat|dari\s*mana\s*kamu\s*tahu|mengetahui\s*acara\s*ini|info\s*dari)/i', $clean)) {
            return 'source';
        }

        // Status Prospek
        if (preg_match('/^(prospek|status|status\s*diri|status\s*fu|status\s*pendaftaran)$/i', $clean)) {
            return 'status';
        }

        // Notes / Catatan
        if (preg_match('/^(notes|catatan|keterangan|column\s*1)$/i', $clean)) {
            return 'notes';
        }

        // Default: unmapped extra columns should be preserved into notes
        return 'extra_to_notes';
    }

    /**
     * Clean phone number extracted from string (supports wa.me links, raw strings, etc.)
     */
    public function cleanPhoneNumber(?string $raw): ?string
    {
        if (empty($raw) || trim($raw) === '' || trim($raw) === '-') {
            return null;
        }

        $str = trim((string) $raw);

        // Check if wa.me link contains phone
        if (preg_match('/wa\.me\/(?:\+?62|0)?([0-9]{8,15})/i', $str, $matches)) {
            $digits = $matches[1];
            return '+62' . ltrim($digits, '0');
        }

        // Extract digits
        $digits = preg_replace('/[^0-9]/', '', $str);
        if (empty($digits)) {
            return null;
        }

        // Remove country code if present
        if (str_starts_with($digits, '62')) {
            $digits = substr($digits, 2);
        }
        $digits = ltrim($digits, '0');

        // Indonesian mobile subscriber number must start with 8 and have 8 to 12 digits
        if (str_starts_with($digits, '8') && strlen($digits) >= 8 && strlen($digits) <= 13) {
            return '+62' . $digits;
        }

        // Fallback standard database formatting
        $formatted = WhatsAppHelper::formatForDatabase($str);
        return $formatted;
    }

    /**
     * Parse uploaded file and generate preview structure.
     */
    public function parseFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'xls', 'csv', 'txt'])) {
            throw new \InvalidArgumentException('Format berkas tidak didukung. Harap unggah berkas Excel (.xlsx, .xls) atau CSV (.csv).');
        }

        $token = 'import_' . date('Ymd_His') . '_' . Str::random(10);
        $fileName = $token . '.' . $extension;
        $file->storeAs('temp_imports', $fileName, 'local');
        $rawPath = Storage::disk('local')->path('temp_imports/' . $fileName);
        $tempFilePath = realpath($rawPath) ?: str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rawPath);

        $rawRows = $this->readRowsFromFile($tempFilePath, $extension);
        if (empty($rawRows)) {
            Storage::disk('local')->delete('temp_imports/' . $fileName);
            throw new \RuntimeException('Berkas yang diunggah kosong atau tidak memiliki data.');
        }

        // Detect header row (first non-empty row)
        $headerIndex = null;
        foreach ($rawRows as $idx => $r) {
            $filledCount = count(array_filter($r, fn($v) => !is_null($v) && trim((string)$v) !== ''));
            if ($filledCount >= 2) {
                $headerIndex = $idx;
                break;
            }
        }

        if ($headerIndex === null) {
            File::delete($tempFilePath);
            throw new \RuntimeException('Tidak dapat mendeteksi baris judul/header pada berkas spreadsheet.');
        }

        $rawHeaders = array_map(fn($v) => trim((string)$v), $rawRows[$headerIndex]);
        // Remove trailing empty headers
        while (!empty($rawHeaders) && end($rawHeaders) === '') {
            array_pop($rawHeaders);
        }

        // Detect mappings
        $detectedMapping = [];
        $unmappedCount = 0;
        foreach ($rawHeaders as $idx => $h) {
            $title = $h !== '' ? $h : 'Kolom ' . ($idx + 1);
            $mappedField = $this->detectFieldMapping($title);
            $detectedMapping[$idx] = [
                'index'        => $idx,
                'header'       => $title,
                'target_field' => $mappedField,
            ];
            if ($mappedField === 'extra_to_notes') {
                $unmappedCount++;
            }
        }

        // Extract data rows
        $dataRows = [];
        $seenPhonesInFile = [];
        $existingPhonesInDb = Prospek::whereNotNull('whatsapp')->pluck('whatsapp')->map(fn($w) => trim($w))->flip()->toArray();

        $validCount = 0;
        $duplicateCount = 0;
        $invalidCount = 0;
        $nameOnlyCount = 0;

        for ($i = $headerIndex + 1; $i < count($rawRows); $i++) {
            $row = $rawRows[$i];
            // Skip entirely empty row
            $isRowEmpty = true;
            foreach ($row as $val) {
                if (!is_null($val) && trim((string)$val) !== '') {
                    $isRowEmpty = false;
                    break;
                }
            }
            if ($isRowEmpty) {
                continue;
            }

            // Extract mapped fields for this row
            $rowValues = [];
            foreach ($detectedMapping as $idx => $map) {
                $val = isset($row[$idx]) ? trim((string)$row[$idx]) : '';
                $rowValues[$idx] = $val;
            }

            // Test validity
            $nameVal = '';
            $phoneVal = '';
            $schoolVal = '';
            foreach ($detectedMapping as $idx => $map) {
                if ($map['target_field'] === 'name' && empty($nameVal)) {
                    $nameVal = $rowValues[$idx] ?? '';
                } elseif ($map['target_field'] === 'whatsapp' && empty($phoneVal)) {
                    $phoneVal = $rowValues[$idx] ?? '';
                } elseif ($map['target_field'] === 'sekolah_name' && empty($schoolVal)) {
                    $schoolVal = $rowValues[$idx] ?? '';
                }
            }

            if (empty($schoolVal)) {
                // Heuristic: check if column mapped to 'source' actually holds school names (e.g. 'Sumber Leads' = SMKN 1 KEDAWUNG)
                foreach ($detectedMapping as $idx => $map) {
                    if ($map['target_field'] === 'source' && !empty($rowValues[$idx])) {
                        $cand = trim((string)$rowValues[$idx]);
                        if (preg_match('/(smk|sma|smp|man|mts|sekolah|school|universitas|kampus)/i', $cand)) {
                            $schoolVal = $cand;
                            break;
                        }
                    }
                }
            }

            $cleanPhone = $this->cleanPhoneNumber($phoneVal);
            $cleanName = trim($nameVal);

            $status = 'valid';
            $statusReason = 'Siap diimpor';

            if (empty($cleanName) && empty($cleanPhone)) {
                $status = 'invalid';
                $statusReason = 'Nama dan Nomor WhatsApp kosong';
                $invalidCount++;
            } elseif (empty($cleanPhone)) {
                $status = 'invalid';
                $statusReason = 'Nomor WhatsApp tidak ada / kosong (' . ($phoneVal ?: 'kosong') . ')';
                $invalidCount++;
                if (!empty($cleanName)) {
                    $nameOnlyCount++;
                }
            } elseif (empty($cleanName)) {
                $status = 'invalid';
                $statusReason = 'Nama prospek kosong';
                $invalidCount++;
            } elseif (isset($existingPhonesInDb[$cleanPhone])) {
                $status = 'duplicate';
                $statusReason = 'Nomor WhatsApp sudah terdaftar di CRM';
                $duplicateCount++;
            } elseif (isset($seenPhonesInFile[$cleanPhone])) {
                $status = 'duplicate_file';
                $statusReason = 'Duplikat dalam file dengan baris ' . $seenPhonesInFile[$cleanPhone];
                $duplicateCount++;
            } else {
                $seenPhonesInFile[$cleanPhone] = count($dataRows) + 1;
                $validCount++;
            }

            $dataRows[] = [
                'row_num'       => count($dataRows) + 1,
                'values'        => $rowValues,
                'clean_name'    => $cleanName,
                'clean_phone'   => $cleanPhone,
                'school_name'   => trim($schoolVal),
                'status'        => $status,
                'status_reason' => $statusReason,
            ];
        }

        // Cache parsed session data for the execute step (valid for 2 hours)
        $metaData = [
            'token'           => $token,
            'file_name'       => $file->getClientOriginalName(),
            'temp_file'       => 'temp_imports/' . $fileName,
            'headers'         => array_values($detectedMapping),
            'total_rows'      => count($dataRows),
            'data_rows'       => $dataRows,
        ];

        Storage::disk('local')->put('temp_imports/' . $token . '.meta.json', json_encode($metaData, JSON_PRETTY_PRINT));

        return [
            'token'           => $token,
            'file_name'       => $file->getClientOriginalName(),
            'total_rows'      => count($dataRows),
            'valid_count'     => $validCount,
            'duplicate_count' => $duplicateCount,
            'invalid_count'   => $invalidCount,
            'name_only_count' => $nameOnlyCount,
            'unmapped_count'  => $unmappedCount,
            'headers'         => array_values($detectedMapping),
            'crm_fields'      => self::CRM_FIELDS,
            'sample_preview'  => array_slice($dataRows, 0, 15),
        ];
    }

    /**
     * Read all rows from Excel or CSV.
     */
    protected function readRowsFromFile(string $filePath, string $extension): array
    {
        if (in_array($extension, ['csv', 'txt'])) {
            $rows = [];
            if (($handle = fopen($filePath, 'r')) !== false) {
                // Read first line to detect separator (; or , or \t)
                $firstLine = fgets($handle);
                rewind($handle);
                $sep = ',';
                if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
                    $sep = ';';
                } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
                    $sep = "\t";
                }

                while (($data = fgetcsv($handle, 0, $sep)) !== false) {
                    $rows[] = $data;
                }
                fclose($handle);
            }
            return $rows;
        }

        // Read using PhpSpreadsheet
        try {
            if ($extension === 'xlsx') {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            } elseif ($extension === 'xls') {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
            } else {
                $reader = IOFactory::createReaderForFile($filePath);
            }

            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            return $sheet->toArray(null, true, false, false);
        } catch (\Throwable $e) {
            Log::error('PhpSpreadsheet read error: ' . $e->getMessage());
            throw new \RuntimeException('Gagal membaca berkas Excel: ' . $e->getMessage());
        }
    }

    /**
     * Cache collection of all Sekolah for high-performance matching during bulk import.
     */
    protected ?\Illuminate\Support\Collection $cachedSchools = null;

    /**
     * Resolve or find existing Sekolah ID.
     */
    public function resolveSekolahId(?string $schoolName): ?int
    {
        if (empty($schoolName) || trim($schoolName) === '' || trim($schoolName) === '-') {
            return null;
        }

        $name = trim(preg_replace('/\s+/', ' ', $schoolName));
        $clean = strtolower($name);

        if ($this->cachedSchools === null) {
            $this->cachedSchools = Sekolah::all();
        }

        // 1. Exact case-insensitive match
        $matched = $this->cachedSchools->first(function ($s) use ($clean) {
            return strtolower(trim((string)$s->nama)) === $clean;
        });
        if ($matched) {
            return $matched->id;
        }

        // 2. Normalized abbreviations: SMAN <-> SMA NEGERI, SMKN <-> SMK NEGERI, etc.
        $normalizeSchool = function (string $text) {
            $text = strtolower(trim($text));
            $text = str_replace(
                ['sma negeri ', 'smk negeri ', 'smp negeri ', 'ma negeri ', 'mts negeri '],
                ['sman ', 'smkn ', 'smpn ', 'man ', 'mtsn '],
                $text
            );
            $text = preg_replace('/\s+/', ' ', $text);
            return $text;
        };

        $normalizedClean = $normalizeSchool($clean);

        $matchedNorm = $this->cachedSchools->first(function ($s) use ($normalizeSchool, $normalizedClean) {
            return $normalizeSchool((string)$s->nama) === $normalizedClean;
        });
        if ($matchedNorm) {
            return $matchedNorm->id;
        }

        // 3. Substring matching: e.g. "SMK WAHIDIN CIREBON" vs "SMK WAHIDIN"
        $loose = $this->cachedSchools->first(function ($s) use ($normalizeSchool, $normalizedClean) {
            $normDb = $normalizeSchool((string)$s->nama);
            if (empty($normDb) || empty($normalizedClean)) return false;
            return str_contains($normalizedClean, $normDb) || str_contains($normDb, $normalizedClean);
        });
        if ($loose) {
            return $loose->id;
        }

        // 4. Auto-create new master Sekolah with active status
        try {
            $newSchool = Sekolah::create([
                'kode'   => 'SCH-' . strtoupper(Str::random(8)),
                'nama'   => $name,
                'status' => 'Aktif',
            ]);
            $this->cachedSchools->push($newSchool);
            return $newSchool->id;
        } catch (\Throwable $e) {
            Log::warning('Gagal auto-create sekolah: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Resolve or match existing Prodi ID based on lead text.
     */
    public function resolveProdi(?string $prodiText): array
    {
        if (empty($prodiText) || trim($prodiText) === '' || trim($prodiText) === '-') {
            return ['prodi_id' => null, 'category' => null, 'prodi_lainnya' => null];
        }

        $text = trim($prodiText);
        $lower = strtolower($text);

        $prodis = Prodi::where('status', 'Aktif')->get();

        // 1. Direct match with S2 Manajemen
        if (preg_match('/(s2|magister|pascasarjana)/i', $lower)) {
            $s2 = $prodis->first(fn($p) => str_contains(strtolower($p->nama), 's2') || $p->jenjang === 'S2');
            return [
                'prodi_id'      => $s2?->id,
                'category'      => 'S2 Magister Manajemen',
                'prodi_lainnya' => $s2 ? null : $text,
            ];
        }

        // 2. Teknik Informatika / TI
        if (preg_match('/(teknik\s*informatika|informatika|\bti\b|it\b|coding|iot|robot)/i', $lower)) {
            $ti = $prodis->first(fn($p) => str_contains(strtolower($p->nama), 'informatika') || $p->kode === 'TI');
            return ['prodi_id' => $ti?->id, 'category' => 'S1 Teknik Informatika', 'prodi_lainnya' => null];
        }

        // 3. DKV / Desain
        if (preg_match('/(dkv|desain\s*komunikasi\s*visual|desain|multimedia|animasi|komik)/i', $lower)) {
            $dkv = $prodis->first(fn($p) => str_contains(strtolower($p->nama), 'dkv') || $p->kode === 'DKV');
            return ['prodi_id' => $dkv?->id, 'category' => 'S1 DKV', 'prodi_lainnya' => null];
        }

        // 4. Bisnis Digital / Startup
        if (preg_match('/(bisnis\s*digital|bisdig|startup|digital\s*business)/i', $lower)) {
            $bd = $prodis->first(fn($p) => str_contains(strtolower($p->nama), 'bisnis digital') || $p->kode === 'BD');
            return ['prodi_id' => $bd?->id, 'category' => 'S1 Bisnis Digital', 'prodi_lainnya' => null];
        }

        // 5. Akuntansi / Keuangan
        if (preg_match('/(akuntansi|keuangan|accounting|akt|fintech)/i', $lower)) {
            $akt = $prodis->first(fn($p) => str_contains(strtolower($p->nama), 'akuntansi') || $p->kode === 'AKT');
            return ['prodi_id' => $akt?->id, 'category' => 'S1 Akuntansi', 'prodi_lainnya' => null];
        }

        // 6. Sistem Informasi
        if (preg_match('/(sistem\s*informasi|\bsi\b|sika)/i', $lower)) {
            $si = $prodis->first(fn($p) => str_contains(strtolower($p->nama), 'sistem informasi') || $p->kode === 'SI');
            return ['prodi_id' => $si?->id, 'category' => 'S1 Sistem Informasi', 'prodi_lainnya' => null];
        }

        // 7. PKOR / Olahraga
        if (preg_match('/(pkor|olahraga|kebugaran|pelatih)/i', $lower)) {
            $pkor = $prodis->first(fn($p) => str_contains(strtolower($p->nama), 'pkor') || $p->kode === 'PKOR');
            return ['prodi_id' => $pkor?->id, 'category' => 'S1 PKOR', 'prodi_lainnya' => null];
        }

        // 8. Manajemen (S1 / D3)
        if (preg_match('/(manajemen|management)/i', $lower)) {
            if (preg_match('/(d3|diploma)/i', $lower)) {
                $mbD3 = $prodis->first(fn($p) => $p->kode === 'MB-D3');
                return ['prodi_id' => $mbD3?->id, 'category' => 'D3 Manajemen Bisnis', 'prodi_lainnya' => null];
            }
            $mnj = $prodis->first(fn($p) => $p->kode === 'MNJ' || strtolower($p->nama) === 'manajemen');
            return ['prodi_id' => $mnj?->id, 'category' => 'S1 Manajemen', 'prodi_lainnya' => null];
        }

        // Exact name matching fallback
        foreach ($prodis as $p) {
            if (str_contains($lower, strtolower($p->nama)) || strtolower($p->kode) === $lower) {
                return ['prodi_id' => $p->id, 'category' => $p->nama, 'prodi_lainnya' => null];
            }
        }

        return ['prodi_id' => null, 'category' => $text, 'prodi_lainnya' => $text];
    }

    /**
     * Execute Mass Import based on user confirmed mappings and parameters.
     */
    public function executeImport(array $payload, User $user): array
    {
        $token = $payload['token'] ?? '';
        $metaRelative = 'temp_imports/' . $token . '.meta.json';

        if (!Storage::disk('local')->exists($metaRelative)) {
            throw new \RuntimeException('Sesi berkas import telah kedaluwarsa. Silakan unggah ulang berkas spreadsheet.');
        }

        $meta = json_decode(Storage::disk('local')->get($metaRelative), true);
        if (!$meta || empty($meta['data_rows'])) {
            throw new \RuntimeException('Data berkas import tidak valid atau kosong.');
        }

        $customMapping    = $payload['mapping'] ?? []; // ['col_idx' => 'target_field']
        $onDuplicate      = $payload['on_duplicate'] ?? 'skip'; // 'skip', 'update', or 'import_all'
        $importIncomplete = ($payload['import_incomplete'] ?? 'skip') === 'import_valid_name'; // true: import rows with name even if phone empty
        $defaultSource    = trim($payload['default_source'] ?? 'Google Sheets Import') ?: 'Google Sheets Import';
        $defaultStatus    = strtoupper(trim($payload['default_status'] ?? 'BARU')) ?: 'BARU';
        $defaultType      = in_array($payload['default_type'] ?? '', ['Individu', 'Sekolah', 'Corporate']) ? $payload['default_type'] : 'Individu';

        // Stage number
        $stageNumber = Prospek::STAGES[$defaultStatus] ?? 1;

        $taAktif = TahunAkademik::getAktif();
        $academicYearId = $taAktif?->id;
        $tahunAkademik  = $taAktif?->nama ?? '2026/2027';

        $importedCount = 0;
        $skippedCount  = 0;
        $updatedCount  = 0;
        $invalidCount  = 0;
        $errors        = [];

        $existingProspeksMap = Prospek::whereNotNull('whatsapp')->get(['id', 'whatsapp', 'notes'])->keyBy('whatsapp');

        DB::transaction(function () use (
            $meta, $customMapping, $onDuplicate, $importIncomplete, $defaultSource, $defaultStatus,
            $defaultType, $stageNumber, $academicYearId, $tahunAkademik,
            $user, &$importedCount, &$skippedCount, &$updatedCount, &$invalidCount, &$errors, $existingProspeksMap
        ) {
            $seenInBatch = [];

            foreach ($meta['data_rows'] as $rowItem) {
                $rowValues = $rowItem['values'];

                // 1. Map values according to confirmed mapping
                $mapped = [
                    'name'         => null,
                    'whatsapp'     => null,
                    'wa_ortu'      => null,
                    'type'         => $defaultType,
                    'sekolah_name' => null,
                    'asal_kelas'   => null,
                    'prodi_name'   => null,
                    'category'     => null,
                    'kelas'        => 'Reguler',
                    'source'       => $defaultSource,
                    'status'       => $defaultStatus,
                    'notes'        => null,
                ];

                $unmappedDetails = [];

                foreach ($meta['headers'] as $col) {
                    $idx = $col['index'];
                    $headerTitle = $col['header'];
                    $val = isset($rowValues[$idx]) ? trim((string)$rowValues[$idx]) : '';
                    $targetField = $customMapping[$idx] ?? $col['target_field'];

                    if ($targetField === 'ignore' || $val === '') {
                        continue;
                    }

                    if ($targetField === 'extra_to_notes') {
                        $unmappedDetails[] = "{$headerTitle}: {$val}";
                    } elseif (array_key_exists($targetField, $mapped)) {
                        $mapped[$targetField] = $val;
                    } else {
                        $unmappedDetails[] = "{$headerTitle}: {$val}";
                    }
                }

                // 2. Resolve Name & Phone
                $cleanName = trim($mapped['name'] ?? $rowItem['clean_name'] ?? '');
                $cleanPhone = $this->cleanPhoneNumber($mapped['whatsapp'] ?? $rowItem['clean_phone'] ?? '');

                if (empty($cleanName) && empty($cleanPhone)) {
                    $invalidCount++;
                    continue;
                }

                if (empty($cleanName)) {
                    $cleanName = 'Prospek ' . $cleanPhone;
                }

                if (empty($cleanPhone)) {
                    if ($importIncomplete) {
                        $cleanPhone = null; // Allowed: import prospect with name only
                    } else {
                        $invalidCount++;
                        continue;
                    }
                }

                // 3. Handle Duplicate
                if (!empty($cleanPhone)) {
                    $existing = $existingProspeksMap->get($cleanPhone);
                    if ($existing || isset($seenInBatch[$cleanPhone])) {
                        if ($onDuplicate === 'import_all') {
                            // Proceed: User explicitly wants to import all duplicates as new prospect records
                        } elseif ($onDuplicate === 'update' && $existing) {
                            $updateData = [];

                            // Check school resolution for updating existing prospect
                            $dupSchoolName = !empty($mapped['sekolah_name']) ? trim($mapped['sekolah_name']) : null;
                            if (empty($dupSchoolName) && !empty($mapped['source']) && preg_match('/(smk|sma|smp|man|mts|sekolah|school|universitas|kampus)/i', $mapped['source'])) {
                                $dupSchoolName = trim($mapped['source']);
                            }
                            if ($dupSchoolName && empty($existing->sekolah_id)) {
                                $resolvedSchId = $this->resolveSekolahId($dupSchoolName);
                                if ($resolvedSchId) {
                                    $updateData['sekolah_id'] = $resolvedSchId;
                                    $schModel = $this->cachedSchools?->firstWhere('id', $resolvedSchId) ?? Sekolah::find($resolvedSchId);
                                    if (empty($existing->wilayah_id) && $schModel?->wilayah_id) {
                                        $updateData['wilayah_id'] = $schModel->wilayah_id;
                                    }
                                }
                            }

                            // Append new notes to existing prospect
                            $extraNote = !empty($unmappedDetails) ? "\n[Import Massal " . date('d/m/Y') . "]: " . implode(' | ', $unmappedDetails) : '';
                            if ($extraNote) {
                                $updateData['notes'] = trim(($existing->notes ?? '') . $extraNote);
                            }

                            if (!empty($updateData)) {
                                $existing->update($updateData);
                                ProspekTimeline::create([
                                    'prospek_id'   => $existing->id,
                                    'user_id'      => $user->id,
                                    'title'        => 'Update Import Spreadsheet',
                                    'notes'        => 'Data catatan/sekolah diperbarui via import massal Google Sheets oleh CS ' . $user->name,
                                    'status_after' => $existing->status,
                                    'time'         => now(),
                                ]);
                                $updatedCount++;
                            } else {
                                $skippedCount++;
                            }
                            continue;
                        } else {
                            $skippedCount++;
                            continue;
                        }
                    }
                    $seenInBatch[$cleanPhone] = true;
                }

                // 4. Resolve Relations & Fields
                $schoolDisplayName = !empty($mapped['sekolah_name']) ? trim($mapped['sekolah_name']) : null;
                if (empty($schoolDisplayName) && !empty($mapped['source']) && preg_match('/(smk|sma|smp|man|mts|sekolah|school|universitas|kampus)/i', $mapped['source'])) {
                    $schoolDisplayName = trim($mapped['source']);
                }
                $sekolahId = !empty($schoolDisplayName) ? $this->resolveSekolahId($schoolDisplayName) : null;
                $sekolahModel = $sekolahId ? ($this->cachedSchools?->firstWhere('id', $sekolahId) ?? Sekolah::find($sekolahId)) : null;
                $prodiResolution = $this->resolveProdi($mapped['prodi_name']);

                // Type inference
                $prospectType = $mapped['type'] ?: $defaultType;
                if ($sekolahId || !empty($schoolDisplayName)) {
                    $prospectType = 'Sekolah';
                }

                $wilayahId = $sekolahModel?->wilayah_id ?? $user->wilayah_id;

                // Compile Notes: keep manual notes + all extra spreadsheet columns
                $compiledNotesList = [];
                if (!empty($mapped['notes'])) {
                    $compiledNotesList[] = $mapped['notes'];
                }
                if (!empty($unmappedDetails)) {
                    $compiledNotesList[] = "[Info Tambahan Spreadsheet]:\n" . implode("\n", array_map(fn($d) => "• " . $d, $unmappedDetails));
                }
                $finalNotes = !empty($compiledNotesList) ? implode("\n\n", $compiledNotesList) : 'Data diimpor massal dari Google Sheets / Excel.';

                // WhatsApp Ortu
                $cleanWaOrtu = !empty($mapped['wa_ortu']) ? $this->cleanPhoneNumber($mapped['wa_ortu']) : null;

                // Create Prospect
                $prospek = Prospek::create([
                    'name'             => $cleanName,
                    'pic'              => $cleanName,
                    'pic_phone'        => $cleanPhone,
                    'whatsapp'         => $cleanPhone,
                    'wa_ortu'          => $cleanWaOrtu,
                    'type'             => $prospectType,
                    'status'           => $defaultStatus,
                    'stage_number'     => $stageNumber,
                    'source'           => $mapped['source'] ?: $defaultSource,
                    'kelas'            => $mapped['kelas'] ?: 'Reguler',
                    'sekolah_id'       => $sekolahId,
                    'asal_kelas'       => $mapped['asal_kelas'] ?: null,
                    'prodi_id'         => $prodiResolution['prodi_id'],
                    'prodi_lainnya'    => $prodiResolution['prodi_lainnya'],
                    'category'         => $mapped['category'] ?: $prodiResolution['category'] ?: ($prospectType === 'Sekolah' ? 'B2B/Sekolah' : 'B2C/Individu'),
                    'notes'            => $finalNotes,
                    'cs_id'            => $user->id,
                    'owner_id'         => $user->id,
                    'wilayah_id'       => $wilayahId,
                    'status_takeover'  => 'Sudah Di-takeover',
                    'takeover_by_id'   => $user->id,
                    'takeover_at'      => now(),
                    'academic_year_id' => $academicYearId,
                    'tahun_akademik'   => $tahunAkademik,
                ]);

                // Create Initial Timeline Event
                ProspekTimeline::create([
                    'prospek_id'   => $prospek->id,
                    'user_id'      => $user->id,
                    'title'        => 'Import Massal Spreadsheet',
                    'notes'        => "Prospek berhasil diimpor massal dari spreadsheet oleh CS {$user->name}. Sumber: {$prospek->source}.",
                    'status_after' => $prospek->status,
                    'time'         => now(),
                ]);

                $importedCount++;
            }
        });

        // Cleanup temporary files
        try {
            if (Storage::disk('local')->exists($metaRelative)) {
                Storage::disk('local')->delete($metaRelative);
            }
            if (!empty($meta['temp_file']) && Storage::disk('local')->exists($meta['temp_file'])) {
                Storage::disk('local')->delete($meta['temp_file']);
            }
        } catch (\Throwable $e) {
            // Ignore cleanup errors
        }

        return [
            'success'        => true,
            'imported_count' => $importedCount,
            'skipped_count'  => $skippedCount,
            'updated_count'  => $updatedCount,
            'invalid_count'  => $invalidCount,
            'message'        => "Berhasil mengimpor {$importedCount} data prospek ke sistem CRM.",
        ];
    }
}
