<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterData extends Model
{
    protected $fillable = ['type', 'kode', 'nama', 'deskripsi', 'status'];
}
