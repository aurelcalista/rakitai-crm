<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
    'lokasi_penugasan',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
     * Get team member IDs for an SPV (subordinates or same wilayah Sales/CS).
     */
    public function teamMemberIds(): array
    {
        $subordinateIds = $this->subordinates()->pluck('id')->toArray();
        if (!empty($subordinateIds)) {
            return $subordinateIds;
        }

        if ($this->wilayah_id) {
            return User::where('wilayah_id', $this->wilayah_id)
                ->whereIn('role', ['Sales', 'CS'])
                ->pluck('id')
                ->toArray();
        }

        return User::whereIn('role', ['Sales', 'CS'])->pluck('id')->toArray();
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
            if ($this->wilayah_id) {
                $q->orWhere('wilayah_id', $this->wilayah_id);
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

