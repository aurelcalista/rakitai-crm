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
    'name',
    'username',
    'email',
    'phone',
    'role',
    'status',
    'last_login_at',
    'password',
    'wilayah_id',
    'supervisor_id',
    'google_access_token',
    'google_refresh_token',
    'google_token_expires_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
     * Get team member IDs for an SPV (subordinates with supervisor_id or Sales/CS in SPV's descendant Wilayahs).
     */
    public function teamMemberIds(): array
    {
        $subordinateIds = $this->subordinates()->pluck('id')->toArray();

        if ($this->wilayah_id) {
            $mainWilayah = $this->wilayah ?? Wilayah::find($this->wilayah_id);
            $descendantWilayahIds = $mainWilayah ? $mainWilayah->getDescendantIds() : [$this->wilayah_id];

            $wilayahMemberIds = User::whereIn('wilayah_id', $descendantWilayahIds)
                ->whereIn('role', ['Sales', 'CS'])
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
            User::whereIn('role', ['Sales', 'CS'])->pluck('id')->toArray()
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

            return User::whereIn('wilayah_id', $descendantWilayahIds)->pluck('id')->toArray();
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
        return Prospek::where(function ($q) use ($memberIds) {
            $q->whereIn('sales_id', $memberIds)
              ->orWhereIn('owner_id', $memberIds)
              ->orWhereIn('cs_id', $memberIds);
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

