<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upt extends Model
{
    protected $fillable = ['kanwil_id', 'name', 'address', 'is_active'];

    public function kanwil()
    {
        return $this->belongsTo(Kanwil::class);
    }
}
