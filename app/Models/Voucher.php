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
        'discount_amount',
        'quota',
        'used_quota',
        'discount_percentage',
        'is_active',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'discount_amount'     => 'integer',
        'quota'               => 'integer',
        'used_quota'          => 'integer',
        'discount_percentage' => 'float',
        'is_active'           => 'boolean',
        'expires_at'          => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $voucher) {
            $voucher->code = strtoupper(trim((string) $voucher->code));
            if ($voucher->discount_amount === null) {
                $voucher->discount_amount = 0;
            }
            if ($voucher->quota === null) {
                $voucher->quota = 0;
            }
            if ($voucher->used_quota === null) {
                $voucher->used_quota = 0;
            }
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function getRemainingQuotaAttribute(): int
    {
        $used = $this->used_quota ?? 0;
        return max(0, (int) $this->quota - (int) $used);
    }

    public function hasRemainingQuota(): bool
    {
        return $this->remaining_quota > 0;
    }

    public function isUsable(): bool
    {
        return (bool) $this->is_active && !$this->isExpired() && $this->hasRemainingQuota();
    }

    public function getFormattedDiscountAttribute(): string
    {
        return self::formatRupiah((int) $this->discount_amount);
    }

    public static function formatRupiah(int $value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }

    public function refreshUsedQuota(): int
    {
        $count = $this->usages()->where('status', VoucherUsage::STATUS_USED)->count();
        $this->update(['used_quota' => $count]);
        return $count;
    }

    public function getFormattedPercentageAttribute(): string
    {
        return self::formatPercentage((float) ($this->discount_percentage ?? 0));
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
