<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blok extends Model
{
    protected $fillable = ['upt_id', 'nama_blok'];
    public function upt()
    {
        return $this->belongsTo(Upt::class);
    }
}
