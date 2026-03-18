<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kanwil extends Model
{
    protected $fillable = ['name', 'code', 'address', 'is_active'];

    public function upts()
    {
        return $this->hasMany(Upt::class);
    }
}
