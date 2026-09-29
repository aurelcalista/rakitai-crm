<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'invoice_id',
        'transaction_id',
        'payment_gateway',
        'payment_method',
        'amount',
        'status',
        'paid_at',
        'expired_at',
        'payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
        'payload' => 'array',
    ];

    public static function generateTransactionId(): string
    {
        $prefix = 'SIM-' . date('Ymd') . '-';
        $random = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $txId = $prefix . $random;

        while (self::where('transaction_id', $txId)->exists()) {
            $random = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $txId = $prefix . $random;
        }

        return $txId;
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        if ($this->status === self::STATUS_PENDING && $this->expired_at && Carbon::now()->greaterThanOrEqualTo($this->expired_at)) {
            $this->update(['status' => self::STATUS_EXPIRED]);
            return true;
        }

        return false;
    }

    /**
     * Check if payment can be paid/processed.
     */
    public function canBePaid(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        if ($this->expired_at && Carbon::now()->greaterThanOrEqualTo($this->expired_at)) {
            $this->update(['status' => self::STATUS_EXPIRED]);
            return false;
        }

        return true;
    }

    /**
     * Remaining seconds until expired_at. Returns 0 if already expired.
     */
    public function getRemainingSecondsAttribute(): int
    {
        if ($this->status !== self::STATUS_PENDING || !$this->expired_at) {
            return 0;
        }

        $diff = Carbon::now()->diffInSeconds($this->expired_at, false);
        return max(0, (int) $diff);
    }
}
