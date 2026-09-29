<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'prospek_id',
        'invoice_number',
        'customer_name',
        'customer_phone',
        'amount',
        'due_date',
        'status',
        'notes',
        'created_by_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . date('Ym') . '-';
        $last = self::where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $nextSeq = 1;
        if ($last && preg_match('/-(\d+)$/', $last->invoice_number, $matches)) {
            $nextSeq = ((int) $matches[1]) + 1;
        }

        return $prefix . sprintf('%04d', $nextSeq);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function prospek()
    {
        return $this->belongsTo(Prospek::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class)->orderByDesc('id');
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => 'paid']);
    }
}
