<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sel extends Model
{
    protected $fillable = ['blok_id', 'nama_sel'];
    public function blok()
    {
        return $this->belongsTo(Blok::class);
    }
}
