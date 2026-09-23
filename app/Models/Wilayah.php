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

    public function children()
    {
        return $this->hasMany(Wilayah::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Wilayah::class, 'parent_id');
    }

    protected $fillable = ['kode', 'nama', 'level', 'parent_id', 'status'];

    public function sekolahs()
    {
        return $this->hasMany(Sekolah::class, 'wilayah_id');
    }

    public function perusahaans()
    {
        return $this->hasMany(Perusahaan::class, 'wilayah_id');
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
