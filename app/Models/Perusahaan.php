<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perusahaan extends Model
{
    protected $fillable = [
        'kode', 'nama', 'kategori_id', 'wilayah_id', 'kecamatan', 
        'alamat', 'telepon', 'email', 'website', 'pic_name', 'pic_jabatan', 'pic_phone', 'status',
        'lat', 'lng'
    ];

    public function kategori()
    {
        return $this->belongsTo(MasterData::class, 'kategori_id');
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'wilayah_id');
    }

    public function kunjungans()
    {
        return $this->hasMany(Kunjungan::class, 'tujuan_id')->where('jenis', 'Perusahaan');
    }

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    /**
     * Get all active companies, auto-syncing any missing companies added by EO (Events) or Sales (Kunjungan).
     */
    public static function getDynamicPerusahaans()
    {
        try {
            $eventCorps = \Illuminate\Support\Facades\DB::table('events')
                ->whereNotNull('nama_institusi')
                ->where('nama_institusi', '!=', '')
                ->where('jenis_institusi', 'Perusahaan')
                ->select('nama_institusi', 'alamat', 'pic_name', 'pic_whatsapp')
                ->get();

            foreach ($eventCorps as $item) {
                $name = trim($item->nama_institusi);
                if ($name !== '') {
                    self::firstOrCreate(
                        ['nama' => $name],
                        [
                            'kode' => 'CORP-' . strtoupper(\Illuminate\Support\Str::random(6)),
                            'alamat' => $item->alamat,
                            'pic_name' => $item->pic_name,
                            'pic_phone' => $item->pic_whatsapp,
                            'status' => 'Aktif'
                        ]
                    );
                }
            }

            $visitCorps = \Illuminate\Support\Facades\DB::table('kunjungans')
                ->whereIn('jenis', ['Perusahaan', 'Corporate'])
                ->whereNotNull('nama_institusi')
                ->where('nama_institusi', '!=', '')
                ->select('nama_institusi', 'alamat', 'pic_name', 'pic_whatsapp')
                ->get();

            foreach ($visitCorps as $item) {
                $name = trim($item->nama_institusi);
                if ($name !== '') {
                    self::firstOrCreate(
                        ['nama' => $name],
                        [
                            'kode' => 'CORP-' . strtoupper(\Illuminate\Support\Str::random(6)),
                            'alamat' => $item->alamat,
                            'pic_name' => $item->pic_name,
                            'pic_phone' => $item->pic_whatsapp,
                            'status' => 'Aktif'
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            // Log or ignore safely
        }

        return self::where('status', 'Aktif')->orderBy('nama')->get();
    }
}
