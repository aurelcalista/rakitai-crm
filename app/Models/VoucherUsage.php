<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherUsage extends Model
{
    use HasFactory;

    public const STATUS_APPLIED   = 'applied';
    public const STATUS_USED      = 'used';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'voucher_id',
        'prospek_id',
        'transaksi_id',
        'voucher_code',
        'voucher_name',
        'discount_percentage',
        'dpp_before_discount',
        'discount_amount',
        'dpp_after_discount',
        'status',
        'used_at',
    ];

    protected $casts = [
        'discount_percentage' => 'float',
        'dpp_before_discount' => 'integer',
        'discount_amount'     => 'integer',
        'dpp_after_discount'  => 'integer',
        'used_at'             => 'datetime',
    ];

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_USED      => 'Sudah Digunakan',
            self::STATUS_APPLIED   => 'Diterapkan (Menunggu Pembayaran)',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default                => 'Belum Menggunakan Voucher',
        };
    }

    public function getFormattedPercentageAttribute(): string
    {
        return Voucher::formatPercentage((float) $this->discount_percentage);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function prospek()
    {
        return $this->belongsTo(Prospek::class);
    }

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }
}

