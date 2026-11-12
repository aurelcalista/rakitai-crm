<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'discount_percentage',
        'is_active',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'discount_percentage' => 'float',
        'is_active'           => 'boolean',
        'expires_at'          => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $voucher) {
            $voucher->code = strtoupper(trim((string) $voucher->code));
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return (bool) $this->is_active && !$this->isExpired();
    }

    public function getFormattedPercentageAttribute(): string
    {
        return self::formatPercentage((float) $this->discount_percentage);
    }

    public static function formatPercentage(float $value): string
    {
        $formatted = number_format($value, 2, '.', '');
        return rtrim(rtrim($formatted, '0'), '.');
    }

    public function usages()
    {
        return $this->hasMany(VoucherUsage::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

