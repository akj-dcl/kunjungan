<?php

namespace App\Imports;

use App\Models\Wbp;
use App\Models\JenisKejahatan;
use App\Models\Blok;
use App\Models\Sel;
use App\Models\Upt;
use App\Models\Kanwil;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class WbpImport implements ToCollection, WithStartRow
{
    private $uptId;

    public function __construct()
    {
        // Pastikan Kanwil & UPT terbuat
        $kanwil = Kanwil::firstOrCreate(
            ['name' => 'Kanwil Kemenkumham NTB'],
            ['is_active' => true]
        );

        $upt = Upt::firstOrCreate(
            ['name' => 'Lapas Kelas IIA Lombok Barat'],
            ['kanwil_id' => $kanwil->id, 'is_active' => true]
        );

        $this->uptId = $upt->id;
    }

    // Paksa sistem untuk mengabaikan baris 1 & 2, langsung baca data asli di baris 3
    public function startRow(): int
    {
        return 3;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Index array dari Excel:
            // 0: No, 1: No Reg, 2: Nama, 3: Alamat, 4: Kejahatan, 5: Blok, 6: Sel

            // Cek jika kolom Nama ($row[2]) kosong, lewati baris ini
            if (!isset($row[2]) || trim($row[2]) === '') {
                continue;
            }

            // 1. Buat / Ambil Jenis Kejahatan
            $jenisKejahatan = JenisKejahatan::firstOrCreate([
                'nama_kejahatan' => trim($row[4])
            ]);

            // 2. Buat / Ambil Blok
            $blok = Blok::firstOrCreate([
                'upt_id' => $this->uptId,
                'nama_blok' => trim($row[5])
            ]);

            // 3. Buat / Ambil Sel
            $sel = Sel::firstOrCreate([
                'blok_id' => $blok->id,
                'nama_sel' => trim($row[6])
            ]);

            // 4. Buat / Update WBP (Pakai updateOrCreate agar tidak duplikat kalau dijalankan 2x)
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