<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prospek extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'category', 'pic', 'pic_phone', 'whatsapp',
        'status', 'stage_number', 'potential', 'ai_training', 'notes',
        'wilayah_id', 'sales_id', 'cs_id', 'owner_id', 'source',
        'sekolah_id', 'perusahaan_id',
        'lost_reason', 'lost_note',
    ];

    /**
     * Stage number map for pipeline transitions.
     */
    public const STAGES = [
        'Cold Lead'           => 1,
        'Interested'          => 2,
        'Follow Up'           => 3,
        'Beli Formulir'       => 4,
        'Pembayaran Termin 1' => 5,
        'Closing'             => 6,
        'Lost'                => 0,
    ];

    public const LOST_REASONS = [
        'Tidak tertarik',
        'Tidak dapat dihubungi',
        'Membatalkan',
        'Memilih kampus lain',
        'Lainnya',
    ];

    /**
     * Check if the given user is the active Sales handler of this prospect.
     */
    public function isHandledBySales(\App\Models\User $user): bool
    {
        return $this->sales_id === $user->id;
    }

    /**
     * Check if the given user is the active CS handler of this prospect.
     */
    public function isHandledByCs(\App\Models\User $user): bool
    {
        return $this->cs_id === $user->id;
    }

    /**
     * Determine the current active handler role label.
     */
    public function activeHandlerLabel(): string
    {
        // If handed over to CS, CS is the active handler
        if ($this->cs_id && $this->cs) {
            return 'CS — ' . $this->cs->name;
        }
        if ($this->sales_id && $this->sales) {
            return 'Sales — ' . $this->sales->name;
        }
        return 'Belum Ada';
    }

    /**
     * Check if the user is the currently active handler.
     */
    public function isActiveHandler(\App\Models\User $user): bool
    {
        if ($this->cs_id) {
            return $this->isHandledByCs($user);
        }
        return $this->isHandledBySales($user);
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class);
    }

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function cs()
    {
        return $this->belongsTo(User::class, 'cs_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }

    public function timelines()
    {
        return $this->hasMany(ProspekTimeline::class);
    }

    /**
     * Scope to filter prospects belonging to a specific sales user.
     */
    public function scopeForSales($query, int $salesId)
    {
        return $query->where('sales_id', $salesId);
    }

    /**
     * Scope: active prospects (not Lost or Closing).
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['Lost', 'Closing']);
    }
}
