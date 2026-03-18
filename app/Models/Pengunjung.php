<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengunjung extends Model
{
    protected $fillable = [
        'user_id',
        'jenis_kelamin',
        'no_ktp',
        'no_hp',
        'alamat',
        'foto_diri',
        'foto_ktp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}