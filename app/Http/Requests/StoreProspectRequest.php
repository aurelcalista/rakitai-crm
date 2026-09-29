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
            'type'     => 'required|in:Sekolah,Corporate,Individu',
            'sekolah_id' => 'nullable|exists:sekolahs,id',
            'sekolah_manual' => 'nullable|string|max:255',
            'perusahaan_id' => 'nullable|exists:perusahaans,id',
            'perusahaan_manual' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'pic'      => 'required|string|max:255',
            'pic_phone'=> 'nullable|string|max:20',
            'whatsapp' => 'required|string|max:20',
            'status'   => 'required|string|max:255',
            'potential'=> 'nullable|string|max:500',
            'ai_training' => 'nullable|string|max:255',
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
            'prodi_id' => 'nullable|exists:prodis,id',
        ];
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
            $duplicate = Prospek::with(['owner', 'sales'])->where('whatsapp', $validated['whatsapp'])->first();

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
