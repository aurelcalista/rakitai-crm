<?php

namespace App\Http\Requests;

use App\Models\Prospek;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProspectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            'perusahaan_id' => 'nullable|exists:perusahaans,id',
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
            'source'   => 'required|in:Teman/Keluarga/Saudara,Sekolah,Sosial Media (FB/IG/X),Website CIC,Brosur/Poster,Sekretariat Kampus (Walk-in),Pameran/Expo/University Day,Acara Kampus,MGBK/Miniclass,Spanduk/Baliho,Lainnya',
            'prodi_id' => 'required|exists:prodis,id',
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
            $prospekId = $this->route('prospek') ? $this->route('prospek')->id : $this->route('id');

            if ($validated['type'] === 'Sekolah' && !empty($validated['sekolah_id'])) {
                $sekolah = Sekolah::find($validated['sekolah_id']);
                if ($sekolah) $name = $sekolah->nama;
            } elseif ($validated['type'] === 'Corporate' && !empty($validated['perusahaan_id'])) {
                $perusahaan = Perusahaan::find($validated['perusahaan_id']);
                if ($perusahaan) $name = $perusahaan->nama;
            }

            $duplicate = Prospek::with(['owner', 'sales'])->where(function($query) use ($validated, $name) {
                $query->where('whatsapp', $validated['whatsapp'])
                      ->orWhere('name', $name);
            })
            ->where('id', '!=', $prospekId) // Ignore itself
            ->first();

            if ($duplicate) {
                $errorField = $duplicate->whatsapp === $validated['whatsapp'] ? 'whatsapp' : 'name';
                $ownerName = $duplicate->owner ? $duplicate->owner->name : 'Sistem';
                $salesName = $duplicate->sales ? $duplicate->sales->name : 'Belum Ada Sales';
                
                $errorMessage = "Data prospek sudah ada (Duplicate {$errorField}).\n"
                              . "Prospek ini dimiliki oleh: {$ownerName}\n"
                              . "Sedang ditangani oleh: {$salesName}\n"
                              . "Status saat ini: {$duplicate->status}";
                              
                $validator->errors()->add($errorField, $errorMessage);
            }
        });
    }
}
