<?php

namespace App\Http\Requests;

use App\Models\Prospek;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use Illuminate\Foundation\Http\FormRequest;

class StoreProspectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authentication handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => 'nullable|string|max:255',
            'no_registrasi' => 'nullable|string|max:100',
            'gelombang'     => 'nullable|string|max:100',
            'type'     => 'required|in:Sekolah,Corporate,Individu',
            'sekolah_id' => 'nullable|exists:sekolahs,id',
            'sekolah_manual' => 'nullable|string|max:255',
            'perusahaan_id' => 'nullable|exists:perusahaans,id',
            'perusahaan_manual' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'pic'      => 'required|string|max:255',
            'pic_phone'=> ['nullable', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: true)],
            'whatsapp' => ['required', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: false)],
            'status'   => 'required|string|max:255',
            'notes'    => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value && str_word_count(strip_tags($value)) > 10) {
                        $fail('Catatan tidak boleh lebih dari 10 kata.');
                    }
                }
            ],
            'source'   => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $allowed = [
                        'Teman/Keluarga/Saudara', 'Sekolah', 'Sosial Media (FB/IG/X)', 'Website CIC', 
                        'Brosur/Poster', 'Sekretariat Kampus (Walk-in)', 'Pameran/Expo/University Day', 
                        'Acara Kampus', 'MGBK/Miniclass', 'Spanduk/Baliho', 'Lainnya',
                        'Brosur', 'Kunjungan Sekolah', 'Instagram', 'Sosial Media (IG/FB/TikTok)',
                        'Website UCIC', 'Brosur / Spanduk', 'Guru BK / Sekolah', 'Teman / Alumni',
                        'Event / Expo Pendidikan', 'Kanvasing / Presentasi', 'Iklan Online (Ads)',
                        'Referensi Mitra / Perusahaan', 'Walk-in (Datang Langsung)', 'Supervisor'
                    ];
                    $masterDataSources = \App\Models\MasterData::where('type', 'sumber_prospek')->pluck('nama')->toArray();
                    $allAllowed = array_unique(array_merge($allowed, $masterDataSources));
                    
                    if (!in_array($value, $allAllowed)) {
                        $fail('The selected source is invalid.');
                    }
                }
            ],
            'prodi_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value === 'lainnya' || $value === null || $value === '') {
                        return;
                    }
                    if (!\App\Models\Prodi::where('id', $value)->exists()) {
                        $fail('The selected prodi id is invalid.');
                    }
                }
            ],
            'prodi_lainnya' => 'nullable|string',
            'wa_ortu' => ['nullable', 'string', 'max:255', new \App\Rules\IndonesianWhatsApp(allowEmpty: true)],
            'asal_kelas' => 'nullable|string|max:100',
            'kelas' => 'nullable|in:Reguler,Karyawan',
            'sekolah_kategori_id' => 'nullable|exists:master_data,id',
            'sekolah_wilayah_id' => 'nullable|exists:wilayahs,id',
            'sekolah_kecamatan' => 'nullable|string|max:100',
            'sekolah_telepon' => 'nullable|string|max:50',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('whatsapp') && $this->filled('whatsapp')) {
            $rawWa = preg_replace('/[^0-9]/', '', (string)$this->whatsapp);
            $cleanWa = preg_replace('/^(62|0)+/', '', $rawWa);
            if (!empty($cleanWa)) {
                $merge['whatsapp'] = '+62' . $cleanWa;
            } else {
                $merge['whatsapp'] = \App\Support\WhatsAppHelper::prepareForValidation($this->whatsapp);
            }
        }
        if ($this->has('wa_ortu') && $this->filled('wa_ortu') && trim((string)$this->wa_ortu) !== '') {
            $rawOrtu = preg_replace('/[^0-9]/', '', (string)$this->wa_ortu);
            $cleanOrtu = preg_replace('/^(62|0)+/', '', $rawOrtu);
            if (!empty($cleanOrtu)) {
                $merge['wa_ortu'] = '+62' . $cleanOrtu;
            } else {
                $merge['wa_ortu'] = null;
            }
        } else {
            $merge['wa_ortu'] = null;
        }
        if ($this->has('pic_phone') && $this->filled('pic_phone') && trim((string)$this->pic_phone) !== '') {
            $rawPhone = preg_replace('/[^0-9]/', '', (string)$this->pic_phone);
            $cleanPhone = preg_replace('/^(62|0)+/', '', $rawPhone);
            if (!empty($cleanPhone)) {
                $merge['pic_phone'] = '+62' . $cleanPhone;
            } else {
                $merge['pic_phone'] = \App\Support\WhatsAppHelper::prepareForValidation($this->pic_phone);
            }
        } else {
            $merge['pic_phone'] = null;
        }

        // Sanitize optional fields
        if ($this->has('no_registrasi') && trim((string)$this->no_registrasi) === '') {
            $merge['no_registrasi'] = null;
        }
        if ($this->has('gelombang') && trim((string)$this->gelombang) === '') {
            $merge['gelombang'] = null;
        }
        if ($this->has('asal_kelas') && trim((string)$this->asal_kelas) === '') {
            $merge['asal_kelas'] = null;
        }
        if ($this->has('notes') && trim((string)$this->notes) === '') {
            $merge['notes'] = null;
        }
        if ($this->has('category') && trim((string)$this->category) === '') {
            $merge['category'] = null;
        }
        if ($this->has('prodi_id') && trim((string)$this->prodi_id) === '') {
            $merge['prodi_id'] = null;
        }
        if ($this->has('prodi_lainnya') && trim((string)$this->prodi_lainnya) === '') {
            $merge['prodi_lainnya'] = null;
        }
        if ($this->has('sekolah_id') && trim((string)$this->sekolah_id) === '') {
            $merge['sekolah_id'] = null;
        }
        if ($this->has('perusahaan_id') && trim((string)$this->perusahaan_id) === '') {
            $merge['perusahaan_id'] = null;
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $validated = $this->all();
            $name = $validated['name'] ?? $validated['pic'] ?? 'Prospek Baru';

            if ($validated['type'] === 'Sekolah') {
                if (!empty($validated['sekolah_id'])) {
                    $sekolah = Sekolah::find($validated['sekolah_id']);
                    if ($sekolah) $name = $sekolah->nama;
                } elseif (!empty($validated['sekolah_manual'])) {
                    $name = trim($validated['sekolah_manual']);
                }
            } elseif ($validated['type'] === 'Corporate') {
                if (!empty($validated['perusahaan_id'])) {
                    $perusahaan = Perusahaan::find($validated['perusahaan_id']);
                    if ($perusahaan) $name = $perusahaan->nama;
                } elseif (!empty($validated['perusahaan_manual'])) {
                    $name = trim($validated['perusahaan_manual']);
                }
            }

            // Duplicate check: hanya cek berdasarkan whatsapp (nomor unik).
            // Untuk Sekolah/Corporate, nama yang sama dari institusi yang berbeda adalah valid.
            $cleanDigits = preg_replace('/[^0-9]/', '', $validated['whatsapp']);
            $dbWaFormat = \App\Support\WhatsAppHelper::formatForDatabase($validated['whatsapp']);
            $duplicate = Prospek::with(['owner', 'sales'])->where(function($q) use ($validated, $dbWaFormat, $cleanDigits) {
                $q->where('whatsapp', $validated['whatsapp']);
                if ($dbWaFormat) {
                    $q->orWhere('whatsapp', $dbWaFormat);
                }
                if (!empty($cleanDigits)) {
                    $q->orWhereRaw("REPLACE(REPLACE(REPLACE(whatsapp, '-', ''), ' ', ''), '+', '') = ?", [$cleanDigits]);
                }
            })->first();

            // Untuk tipe Individu, cek juga duplikat berdasarkan nama
            if (!$duplicate && $validated['type'] === 'Individu') {
                $individuName = $validated['name'] ?? $validated['pic'] ?? null;
                if ($individuName) {
                    $duplicate = Prospek::with(['owner', 'sales'])
                        ->where('type', 'Individu')
                        ->where('name', $individuName)
                        ->first();
                    if ($duplicate) {
                        $errorField = 'name';
                    }
                }
            }

            if ($duplicate) {
                $errorField = $errorField ?? 'whatsapp';
                $ownerName = $duplicate->owner ? $duplicate->owner->name : 'Sistem';
                $salesName = $duplicate->sales ? $duplicate->sales->name : 'Belum Ada Sales';
                
                $errorMessage = "Data prospek sudah ada (Duplicate {$errorField}). "
                              . "Prospek ini dimiliki oleh: {$ownerName}. "
                              . "Sedang ditangani oleh: {$salesName}. "
                              . "Status saat ini: {$duplicate->status}";
                              
                $validator->errors()->add($errorField, $errorMessage);
            }
        });
    }
}
