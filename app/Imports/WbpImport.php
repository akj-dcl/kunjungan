<?php

namespace App\Imports;

use App\Models\Wbp;
use App\Models\JenisKejahatan;
use App\Models\Blok;
use App\Models\Sel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class WbpImport implements ToCollection, WithStartRow
{
    private $uptId;

    // Tambahkan parameter uptId di sini
    public function __construct($uptId)
    {
        $this->uptId = $uptId;
    }

    public function startRow(): int
    {
        return 3;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            if (!isset($row[2]) || trim($row[2]) === '') {
                continue;
            }

            // Seragamkan Huruf Kapital (Narkotika == NARKOTIKA)
            $teksKejahatan = trim($row[4]) ?: 'TIDAK DIKETAHUI';
            $namaKejahatan = ucwords(strtolower($teksKejahatan));

            // 1. Buat / Ambil Jenis Kejahatan (Aman dari Duplikat)
            $jenisKejahatan = JenisKejahatan::firstOrCreate([
                'nama_kejahatan' => $namaKejahatan
            ]);

            // 2. Buat / Ambil Blok (Spesifik milik UPT masing-masing)
            $blok = Blok::firstOrCreate([
                'upt_id' => $this->uptId,
                'nama_blok' => trim($row[5]) ?: 'TIDAK DIKETAHUI'
            ]);

            // 3. Buat / Ambil Sel
            $sel = Sel::firstOrCreate([
                'blok_id' => $blok->id,
                'nama_sel' => trim($row[6]) ?: 'TIDAK DIKETAHUI'
            ]);

            // 4. Buat / Update WBP
            Wbp::updateOrCreate(
                [
                    'no_reg_instansi' => trim($row[1]), 
                    'upt_id' => $this->uptId,
                ],
                [
                    'nama' => trim($row[2]),
                    'alamat' => trim($row[3]),
                    'jenis_kejahatan_id' => $jenisKejahatan->id,
                    'sel_id' => $sel->id,
                ]
            );
        }
    }
}