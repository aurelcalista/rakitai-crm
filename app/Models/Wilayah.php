<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wilayah extends Model
{
    use HasFactory;

    public function users()
    {
        return $this->hasMany(User::class, 'wilayah_id');
    }

    public function prospeks()
    {
        return $this->hasMany(Prospek::class, 'wilayah_id');
    }

    public function children()
    {
        return $this->hasMany(Wilayah::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Wilayah::class, 'parent_id');
    }

    protected $fillable = ['kode', 'nama', 'level', 'parent_id', 'status'];

    /**
     * Auto-generate Kecamatan code for a parent Kota/Kabupaten.
     * Format: KODE_KOTA - KODE_KECAMATAN - NOMOR_SEQUENCE (e.g. CRB-KSB-01)
     */
    public static function generateKecamatanKode($parent, string $kecamatanNama): string
    {
        if (!($parent instanceof Wilayah)) {
            $parent = Wilayah::findOrFail($parent);
        }

        $kotaNameClean = preg_replace('/^(Kota|Kabupaten|Kab\.)\s+/i', '', $parent->nama);
        $cleanWords = array_values(array_filter(explode(' ', trim($kotaNameClean))));
        if (count($cleanWords) >= 3) {
            $kotaPrefix = strtoupper(substr($cleanWords[0], 0, 1) . substr($cleanWords[1], 0, 1) . substr($cleanWords[2], 0, 1));
        } elseif (count($cleanWords) == 2) {
            $kotaPrefix = strtoupper(substr($cleanWords[0], 0, 2) . substr($cleanWords[1], 0, 1));
        } else {
            $cleanName = preg_replace('/[^A-Z]/', '', strtoupper($kotaNameClean));
            $consonants = preg_replace('/[AEIOU]/', '', $cleanName);
            $kotaPrefix = strlen($consonants) >= 3 ? substr($consonants, 0, 3) : str_pad(substr($cleanName, 0, 3), 3, 'X');
        }

        $abbreviationMap = [
            'KESAMBI'    => 'KSB',
            'KEJAKSAN'   => 'KJS',
            'HARJAMUKTI' => 'HMJ',
            'PEKALIPAN'  => 'PLP',
            'LEMAHWUNGI' => 'LMW',
        ];
        $upperName = strtoupper(trim($kecamatanNama));
        if (isset($abbreviationMap[$upperName])) {
            $kecAbbr = $abbreviationMap[$upperName];
        } else {
            $words = array_values(array_filter(explode(' ', trim($kecamatanNama))));
            if (count($words) >= 3) {
                $kecAbbr = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1) . substr($words[2], 0, 1));
            } elseif (count($words) == 2) {
                $kecAbbr = strtoupper(substr($words[0], 0, 2) . substr($words[1], 0, 1));
            } else {
                $clean = preg_replace('/[^A-Z]/', '', $upperName);
                $consonants = preg_replace('/[AEIOU]/', '', $clean);
                if (strlen($consonants) >= 3) {
                    $kecAbbr = substr($consonants, 0, 3);
                } else {
                    $kecAbbr = str_pad(substr($clean, 0, 3), 3, 'X');
                }
            }
        }

        $existingChildren = Wilayah::where('parent_id', $parent->id)->get();
        $maxSeq = 0;
        foreach ($existingChildren as $child) {
            if (preg_match('/-(\d+)$/', $child->kode, $m)) {
                $maxSeq = max($maxSeq, (int)$m[1]);
            }
        }
        if ($maxSeq === 0 && $existingChildren->count() > 0) {
            $maxSeq = $existingChildren->count();
        }
        $nextSeq = sprintf('%02d', $maxSeq + 1);

        return "{$kotaPrefix}-{$kecAbbr}-{$nextSeq}";
    }

    public function sekolahs()
    {
        return $this->hasMany(Sekolah::class, 'wilayah_id');
    }

    public function perusahaans()
    {
        return $this->hasMany(Perusahaan::class, 'wilayah_id');
    }

    /**
     * Users assigned to this Wilayah via user_wilayah pivot table.
     */
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'user_wilayah', 'wilayah_id', 'user_id')
            ->withPivot(['role', 'is_active', 'assigned_at', 'deactivated_at'])
            ->withTimestamps();
    }

    /**
     * Currently active Sales user assigned to this Wilayah.
     */
    public function activeSalesUser(): ?User
    {
        return $this->belongsToMany(User::class, 'user_wilayah', 'wilayah_id', 'user_id')
            ->wherePivot('role', 'Sales')
            ->wherePivot('is_active', true)
            ->first();
    }

    /**
     * Currently active CS user assigned to this Wilayah.
     */
    public function activeCsUser(): ?User
    {
        return $this->belongsToMany(User::class, 'user_wilayah', 'wilayah_id', 'user_id')
            ->wherePivot('role', 'CS')
            ->wherePivot('is_active', true)
            ->first();
    }

    /**
     * Check recursively if this Wilayah is equal to or a descendant of the given parent Wilayah.
     */
    public function isDescendantOf(Wilayah|int|null $parentWilayah): bool
    {
        if (!$parentWilayah) return true;
        $parentId = $parentWilayah instanceof Wilayah ? $parentWilayah->id : $parentWilayah;

        if ($this->id == $parentId) {
            return true;
        }

        $current = $this;
        while ($current && $current->parent_id) {
            if ($current->parent_id == $parentId) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }

    /**
     * Get active SPVs assigned to this Wilayah or its parent/children.
     */
    public function getActiveSpvs()
    {
        $descendantIds = $this->getDescendantIds();
        $kotaId = $this->parent_id ?: $this->id;

        return User::where('role', 'SPV')
            ->where('status', 'Aktif')
            ->where(function ($q) use ($descendantIds, $kotaId) {
                $q->whereIn('wilayah_id', array_merge($descendantIds, [$kotaId]))
                  ->orWhereHas('activeWilayahes', function ($wq) use ($descendantIds, $kotaId) {
                      $wq->whereIn('wilayah_id', array_merge($descendantIds, [$kotaId]));
                  });
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Get active child Kecamatan areas for this Kota/Kabupaten.
     */
    public function getActiveAreas()
    {
        if ($this->level === 'Kecamatan' && $this->parent_id) {
            return Wilayah::where('parent_id', $this->parent_id)->where('status', 'Aktif')->orderBy('nama')->get();
        }

        return Wilayah::where('parent_id', $this->id)->where('status', 'Aktif')->orderBy('nama')->get();
    }

    /**
     * Get active Sales and CS users for this Wilayah (or area/SPV).
     */
    public function getActiveSalesAndCs(?int $spvId = null)
    {
        $descendantIds = $this->getDescendantIds();
        $kotaId = $this->parent_id ?: $this->id;
        $searchIds = array_unique(array_merge($descendantIds, [$kotaId]));

        return User::whereIn('role', ['Sales', 'CS'])
            ->where('status', 'Aktif')
            ->where(function ($q) use ($searchIds, $spvId) {
                $q->whereIn('wilayah_id', $searchIds)
                  ->orWhereHas('activeWilayahes', function ($wq) use ($searchIds) {
                      $wq->whereIn('wilayah_id', $searchIds);
                  });

                if ($spvId) {
                    $q->orWhere('supervisor_id', $spvId);
                }
            })
            ->orderBy('role')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all descendant Wilayah IDs (including self ID) recursively.
     */
    public function getDescendantIds(): array
    {
        $ids = [$this->id];
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->getDescendantIds());
        }
        return array_values(array_unique($ids));
    }
}

