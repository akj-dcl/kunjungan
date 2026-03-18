<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Wbp extends Model
{
    protected $fillable = ['upt_id', 'no_reg_instansi', 'nama', 'alamat', 'jenis_kejahatan_id', 'sel_id', 'is_aktif'];

    // Pastikan Laravel tahu kalau ini boolean (true/false)
    protected $casts = [
        'is_aktif' => 'boolean',
    ];

    public function upt() { return $this->belongsTo(Upt::class); }
    public function jenisKejahatan() { return $this->belongsTo(JenisKejahatan::class); }
    public function sel() { return $this->belongsTo(Sel::class); }
}