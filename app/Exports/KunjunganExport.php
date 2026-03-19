<?php

namespace App\Exports;

use App\Models\Kunjungan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class KunjunganExport implements FromCollection, WithHeadings, WithMapping
{
    protected $startDate, $endDate, $uptId, $sesi;

    // Tangkap semua parameter filter dari Controller
    public function __construct($startDate, $endDate, $uptId, $sesi = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->uptId = $uptId;
        $this->sesi = $sesi;
    }

    public function collection()
    {
        $query = Kunjungan::with(['pengunjung.user', 'wbp.sel.blok', 'upt'])
            ->where('status', 'Selesai')
            ->whereBetween('tanggal_kunjungan', [$this->startDate, $this->endDate]);

        // Filter UPT jika ada
        if ($this->uptId) {
            $query->where('upt_id', $this->uptId);
        }

        // Filter Sesi jika dipilih (Sesi 1 atau Sesi 2)
        if ($this->sesi) {
            // Pakai 'like' agar cocok dengan string "Sesi 1 (09.00 - 11.00)" di DB
            $query->where('waktu_kunjungan', 'like', $this->sesi . '%');
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal', 'Sesi / Waktu', 'UPT Lapas', 'Nama Pengunjung', 'NIK Pengunjung', 
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