<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'kode',
    'name',
    'username',
    'email',
    'phone',
    'avatar',
    'role',
    'jabatan',
    'status',
    'last_login_at',
    'password',
    'wilayah_id',
    'supervisor_id',
    'lokasi_penugasan',
    'google_access_token',
    'google_refresh_token',
    'google_token_expires_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function getJabatanAttribute($value): string
    {
        return $value ?: ($this->role ?? 'Staff');
    }

    protected static function booted(): void
    {
        static::creating(function ($user) {
            if (empty($user->kode) && !empty($user->role) && strtolower($user->status ?? 'aktif') !== 'pending') {
                $user->kode = self::generateUserCode($user->role);
            }
        });
    }

    /**
     * Auto-generate unique user code for users (Sales, CS, SPV, HM, Admin, EO, etc.).
     * Format: YYMM + ROLE_LETTER + 3 DIGITS (e.g. 2609S001 for Sales, 2609A001 for Admin, 2609V001 for SPV).
     * Sequence resets per role every calendar year.
     * Safe against concurrent creations using lockForUpdate.
     */
    public static function generateUserCode(string|null $roleOrYearMonth = null, ?string $joinedYearMonth = null): string
    {
        $role = 'Sales';
        $ym = null;

        if ($roleOrYearMonth !== null) {
            if (preg_match('/^\d{4}$/', $roleOrYearMonth)) {
                $ym = $roleOrYearMonth;
                if ($joinedYearMonth !== null && !preg_match('/^\d{4}$/', $joinedYearMonth)) {
                    $role = $joinedYearMonth;
                }
            } else {
                $role = $roleOrYearMonth;
                $ym = $joinedYearMonth;
            }
        }

        $prefixYM = $ym ?: date('ym');

        $roleLetter = match (strtolower($role)) {
            'cs'    => 'C',
            'sales' => 'S',
            'spv'   => 'V',
            'hm'    => 'H',
            'admin' => 'A',
            'eo'    => 'E',
            default => strtoupper(substr($role, 0, 1)),
        };

        return \Illuminate\Support\Facades\DB::transaction(function () use ($prefixYM, $roleLetter, $role) {
            // Find all matching codes for this role and year-month prefix
            $matchingCodes = self::where('kode', 'like', "{$prefixYM}{$roleLetter}%")
                ->lockForUpdate()
                ->pluck('kode');

            $maxSeq = 0;
            foreach ($matchingCodes as $code) {
                if (preg_match('/(\d{3})$/', $code, $matches)) {
                    $seq = (int) $matches[1];
                    if ($seq > $maxSeq) {
                        $maxSeq = $seq;
                    }
                }
            }

            $nextSeq = sprintf('%03d', $maxSeq + 1);

            return "{$prefixYM}{$roleLetter}{$nextSeq}";
        });
    }

    /**
     * Get avatar public URL if set.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? asset('storage/' . $this->avatar) : null;
    }

    /**
     * Get user initials for avatar fallback.
     */
    public function getInitialsAttribute(): string
    {
        $words = array_values(array_filter(explode(' ', trim($this->name))));
        return strtoupper(substr($words[0] ?? 'A', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function transaksis()
    {
        return $this->hasMany(Transaksi::class);
    }
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function subordinates()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    /**
     * Sales subordinates under this Supervisor.
     */
    public function salesSubordinates()
    {
        return $this->hasMany(User::class, 'supervisor_id')->where('role', 'Sales');
    }

    /**
     * Check if a given Sales user (or ID) is a subordinate of this Supervisor.
     */
    public function isSupervisorOf(User|int|null $sales): bool
    {
        if (!$sales) return false;
        $salesId = $sales instanceof User ? $sales->id : $sales;
        return $this->subordinates()->where('id', $salesId)->exists();
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function targets()
    {
        return $this->hasMany(Target::class, 'sales_id');
    }

    /**
     * Active Wilayahs assigned to this user via user_wilayah pivot table.
     */
    public function activeWilayahes()
    {
        return $this->belongsToMany(Wilayah::class, 'user_wilayah', 'user_id', 'wilayah_id')
            ->wherePivot('is_active', true)
            ->withPivot(['role', 'is_active', 'assigned_at', 'deactivated_at'])
            ->withTimestamps();
    }

    /**
     * All Wilayahs ever assigned to this user.
     */
    public function allWilayahes()
    {
        return $this->belongsToMany(Wilayah::class, 'user_wilayah', 'user_id', 'wilayah_id')
            ->withPivot(['role', 'is_active', 'assigned_at', 'deactivated_at'])
            ->withTimestamps();
    }

    /**
     * Helper to get list of active wilayah IDs (including legacy wilayah_id if set).
     */
    public function activeWilayahIds(): array
    {
        $pivotIds = $this->activeWilayahes()->pluck('wilayahs.id')->toArray();
        if ($this->wilayah_id && !in_array($this->wilayah_id, $pivotIds)) {
            $pivotIds[] = $this->wilayah_id;
        }
        return array_values(array_unique($pivotIds));
    }

    /**
     * Prospects where this user is the Sales handler.
     */
    public function prospeks()
    {
        return $this->hasMany(Prospek::class, 'sales_id');
    }

    /**
     * Prospects where this user is the owner (creator).
     */
    public function ownedProspeks()
    {
        return $this->hasMany(Prospek::class, 'owner_id');
    }

    /**
     * Follow-ups recorded by this user.
     */
    public function followUps()
    {
        return $this->hasMany(FollowUp::class, 'user_id');
    }

    /**
     * Field visits by this Sales user.
     */
    public function kunjungans()
    {
        return $this->hasMany(Kunjungan::class, 'sales_id');
    }

    /**
     * Helper: check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return strtolower($this->role) === strtolower($role);
    }

    /**
     * Check if this user's assigned area/wilayah (or a given target Wilayah) is within a main Wilayah scope recursively.
     */
    public function isWithinWilayahScope(Wilayah|int|null $mainWilayah): bool
    {
        if (strtolower($this->role) === 'admin') {
            return true; // Admin HAS GLOBAL ACCESS
        }

        if (!$mainWilayah) {
            return true;
        }

        $mainWilayahId = $mainWilayah instanceof Wilayah ? $mainWilayah->id : $mainWilayah;

        if (!$this->wilayah_id) {
            return true; // Unassigned / global HM / SPV fallback
        }

        if ($this->wilayah_id == $mainWilayahId) {
            return true;
        }

        $userWilayah = $this->wilayah ?? Wilayah::find($this->wilayah_id);
        if ($userWilayah) {
            return $userWilayah->isDescendantOf($mainWilayahId);
        }

        return false;
    }

    /**
     * Get team member IDs for an SPV (subordinates with supervisor_id or Sales in SPV's descendant Wilayahs).
     * Excludes CS as CS is managed directly by HM.
     */
    public function teamMemberIds(): array
    {
        $subordinateIds = $this->subordinates()->where('role', 'Sales')->pluck('id')->toArray();

        if ($this->wilayah_id) {
            $mainWilayah = $this->wilayah ?? Wilayah::find($this->wilayah_id);
            $descendantWilayahIds = $mainWilayah ? $mainWilayah->getDescendantIds() : [$this->wilayah_id];

            $wilayahMemberIds = User::where('role', 'Sales')
                ->where(function ($q) use ($descendantWilayahIds) {
                    $q->whereIn('wilayah_id', $descendantWilayahIds)
                      ->orWhereHas('activeWilayahes', function($wq) use ($descendantWilayahIds) {
                          $wq->whereIn('wilayah_id', $descendantWilayahIds);
                      });
                })
                ->where(function ($q) {
                    $q->whereNull('supervisor_id')
                      ->orWhere('supervisor_id', $this->id);
                })
                ->pluck('id')
                ->toArray();

            return array_values(array_unique(array_merge($subordinateIds, $wilayahMemberIds)));
        }

        return array_values(array_unique(array_merge(
            $subordinateIds,
            User::where('role', 'Sales')->pluck('id')->toArray()
        )));
    }

    /**
     * Get user IDs belonging to an HM's wilayah (including all descendant wilayahs).
     */
    public function hmMemberIds(): array
    {
        if (strtolower($this->role) === 'admin') {
            return User::pluck('id')->toArray(); // Admin = global
        }

        if ($this->wilayah_id) {
            $mainWilayah = $this->wilayah ?? Wilayah::find($this->wilayah_id);
            $descendantWilayahIds = $mainWilayah ? $mainWilayah->getDescendantIds() : [$this->wilayah_id];

            return User::where(function($q) use ($descendantWilayahIds) {
                $q->whereIn('wilayah_id', $descendantWilayahIds)
                  ->orWhereHas('activeWilayahes', function($wq) use ($descendantWilayahIds) {
                      $wq->whereIn('wilayah_id', $descendantWilayahIds);
                  });
            })->pluck('id')->toArray();
        }

        return User::pluck('id')->toArray();
    }

    /**
     * Get Sales subordinates for SPV.
     */
    public function teamSales()
    {
        $memberIds = $this->teamMemberIds();
        return User::whereIn('id', $memberIds)->where('role', 'Sales');
    }

    /**
     * Get CS subordinates for SPV.
     */
    public function teamCs()
    {
        $memberIds = $this->teamMemberIds();
        return User::whereIn('id', $memberIds)->where('role', 'CS');
    }

    /**
     * Get query for prospects belonging to this SPV's team.
     */
    public function teamProspeks()
    {
        $memberIds = $this->teamMemberIds();
        $mainWilayah = $this->wilayah ?? ($this->wilayah_id ? Wilayah::find($this->wilayah_id) : null);
        $descendantWilayahIds = $mainWilayah ? $mainWilayah->getDescendantIds() : [];

        return Prospek::where(function ($q) use ($memberIds, $descendantWilayahIds) {
            $q->whereIn('sales_id', $memberIds)
              ->orWhereIn('owner_id', $memberIds)
              ->orWhereIn('cs_id', $memberIds);

            if (!empty($descendantWilayahIds)) {
                $q->orWhereIn('wilayah_id', $descendantWilayahIds);
            }
        });
    }

    /**
     * Get query for field visits by this SPV's team.
     */
    public function teamKunjungans()
    {
        $memberIds = $this->teamMemberIds();
        return Kunjungan::whereIn('sales_id', $memberIds);
    }

    /**
     * Events managed by this user (EO).
     */
    public function managedEvents()
    {
        return $this->hasMany(Event::class, 'eo_id');
    }

    /**
     * Events assigned to this user as SPV.
     */
    public function assignedEventsSpv()
    {
        return $this->belongsToMany(Event::class, 'event_spv', 'spv_id', 'event_id')->withTimestamps();
    }

    /**
     * Events assigned to this user as Sales.
     */
    public function assignedEventsSales()
    {
        return $this->belongsToMany(Event::class, 'event_sales', 'sales_id', 'event_id')
            ->withPivot('assigned_by_spv_id')
            ->withTimestamps();
    }
}

