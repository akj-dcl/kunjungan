<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Kunjungan extends Model
{
    protected $fillable = [
        'pengunjung_id', 'upt_id', 'wbp_id', 'tanggal_kunjungan', 'waktu_kunjungan',
        'pengikut_laki', 'pengikut_perempuan', 'pengikut_anak', 'total_pengikut',
        'qr_code_uuid', 'status'
    ];

    public function pengunjung() { return $this->belongsTo(Pengunjung::class); }
    public function upt() { return $this->belongsTo(Upt::class); }
    public function wbp() { return $this->belongsTo(Wbp::class); }
    public function barangBawaans() { return $this->hasMany(BarangBawaan::class); }
}