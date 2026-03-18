<?php

namespace App\Exports;

use App\Models\Kunjungan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class KunjunganExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filter;

    public function __construct($filter)
    {
        $this->filter = $filter;
    }

    public function collection()
    {
        $query = Kunjungan::with(['pengunjung.user', 'wbp.sel.blok', 'upt'])->where('status', 'Selesai');

        $now = Carbon::now();
        if ($this->filter === 'hari') {
            $query->whereDate('tanggal_kunjungan', $now->toDateString());
        } elseif ($this->filter === 'minggu') {
            $query->whereBetween('tanggal_kunjungan', [$now->startOfWeek()->toDateString(), $now->endOfWeek()->toDateString()]);
        } elseif ($this->filter === 'bulan') {
            $query->whereMonth('tanggal_kunjungan', $now->month)->whereYear('tanggal_kunjungan', $now->year);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal', 'Waktu', 'UPT Lapas', 'Nama Pengunjung', 'NIK Pengunjung', 
            'Nama WBP', 'Blok / Sel', 'Laki-laki', 'Perempuan', 'Anak', 'Total Pengikut', 'Status'
        ];
    }

    public function map($kunjungan): array
    {
        return [
            $kunjungan->tanggal_kunjungan,
            $kunjungan->waktu_kunjungan,
            $kunjungan->upt->name ?? '-',
            $kunjungan->pengunjung->user->name ?? '-',
            $kunjungan->pengunjung->no_ktp ?? '-',
            $kunjungan->wbp->nama ?? '-',
            'Blok ' . ($kunjungan->wbp->sel->blok->nama_blok ?? '') . ' / Sel ' . ($kunjungan->wbp->sel->nama_sel ?? ''),
            $kunjungan->pengikut_laki,
            $kunjungan->pengikut_perempuan,
            $kunjungan->pengikut_anak,
            $kunjungan->total_pengikut,
            $kunjungan->status,
        ];
    }
}