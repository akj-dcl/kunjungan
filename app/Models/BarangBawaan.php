<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BarangBawaan extends Model
{
    protected $fillable = ['kunjungan_id', 'jenis_barang', 'jumlah', 'keterangan'];
    public function kunjungan() { return $this->belongsTo(Kunjungan::class); }
}