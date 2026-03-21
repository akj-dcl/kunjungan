<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wbp;
use App\Models\JenisKejahatan;
use App\Models\Blok;
use App\Models\Sel;
use App\Models\Upt;

class ImportWbpData extends Command
{
    // Tambahkan parameter {nama_file} dan {upt_id} agar dinamis
    protected $signature = 'app:import-wbp-data {nama_file} {upt_id}';
    protected $description = 'Import Master Data dan WBP. Contoh penggunaan: php artisan app:import-wbp-data data_lpp.csv 3';

    private function bersihkanTeks($teks) 
    {
        if (empty($teks)) return '';
        $teks = mb_convert_encoding($teks, 'UTF-8', 'UTF-8');
        $teks = preg_replace('/[^\p{L}\p{N}\s\.\,\-\/\@\(\)\_]/u', '', $teks);
        return trim($teks);
    }

    public function handle()
    {
        $fileName = $this->argument('nama_file');
        $uptId = $this->argument('upt_id');

        $this->info("Mencari file {$fileName} untuk diimport ke UPT ID {$uptId}...");
        
        $file = storage_path('app/' . $fileName);

        if (!file_exists($file)) {
            $this->error("File tidak ditemukan! Pastikan file ada di folder storage/app/{$fileName}");
            return;
        }

        // Cek apakah UPT nya ada di database
        $upt = Upt::find($uptId);
        if (!$upt) {
            $this->error("UPT dengan ID {$uptId} tidak ditemukan di database!");
            return;
        }

        $handle = fopen($file, "r");
        $firstLine = fgets($handle);
        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
        rewind($handle);

        $rowCounter = 0;
        $inserted = 0;

        $this->info("Sedang memproses data untuk UPT: {$upt->name}... mohon tunggu.");

        while (($data = fgetcsv($handle, 10000, $delimiter)) !== FALSE) {
            $rowCounter++;

            if ($rowCounter <= 1) continue;
            if (count($data) < 7 || !isset($data[2]) || trim($data[2]) === '') continue;

            try {
                $noRegInstansi = substr($this->bersihkanTeks($data[1]), 0, 255);
                $nama = substr($this->bersihkanTeks($data[2]), 0, 255);
                $alamat = $this->bersihkanTeks($data[3]);
                
                // Mencegah duplikat karena perbedaan huruf besar/kecil (misal: "Narkotika" dan "NARKOTIKA" jadi sama)
                $teksKejahatan = $this->bersihkanTeks($data[4]) ?: 'TIDAK DIKETAHUI';
                $namaKejahatan = ucwords(strtolower($teksKejahatan)); 
                
                $namaBlok = substr($this->bersihkanTeks($data[5]), 0, 255);
                $namaSel = substr($this->bersihkanTeks($data[6]), 0, 255);

                // 1. Otomatis skip buat baru kalau sudah ada
                $jenisKejahatan = JenisKejahatan::firstOrCreate([
                    'nama_kejahatan' => $namaKejahatan
                ]);

                // 2. Blok akan dibuat per UPT, jadi Blok 1 Lobar dan Blok 1 LPP Mataram tidak bentrok
                $blok = Blok::firstOrCreate([
                    'upt_id' => $uptId,
                    'nama_blok' => $namaBlok ?: 'TIDAK DIKETAHUI'
                ]);

                // 3. Sel per blok
                $sel = Sel::firstOrCreate([
                    'blok_id' => $blok->id,
                    'nama_sel' => $namaSel ?: 'TIDAK DIKETAHUI'
                ]);

                Wbp::updateOrCreate(
                    [
                        'no_reg_instansi' => $noRegInstansi ?: 'REG-' . $rowCounter, 
                        'upt_id' => $uptId,
                    ],
                    [
                        'nama' => $nama,
                        'alamat' => $alamat,
                        'jenis_kejahatan_id' => $jenisKejahatan->id,
                        'sel_id' => $sel->id,
                    ]
                );

                $inserted++;
            } catch (\Exception $e) {
                $this->error("Gagal pada baris {$rowCounter}: " . $e->getMessage());
                continue; 
            }
        }

        fclose($handle);
        $this->info("Import Selesai! Berhasil memproses {$inserted} data WBP ke {$upt->name}.");
    }
}