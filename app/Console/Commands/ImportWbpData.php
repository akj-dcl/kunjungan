<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wbp;
use App\Models\JenisKejahatan;
use App\Models\Blok;
use App\Models\Sel;
use App\Models\Upt;
use App\Models\Kanwil;

class ImportWbpData extends Command
{
    protected $signature = 'app:import-wbp-data';
    protected $description = 'Import Master Data dan WBP dari file CSV murni';

    // Fungsi sapu jagat untuk membuang karakter gaib/binary
    private function bersihkanTeks($teks) 
    {
        if (empty($teks)) return '';
        
        // Paksa konversi ke UTF-8 dan abaikan byte yang rusak
        $teks = mb_convert_encoding($teks, 'UTF-8', 'UTF-8');
        
        // Gunakan Regex: Hanya izinkan huruf, angka, spasi, dan tanda baca umum (titik, koma, strip, garis miring, dll)
        // Karakter aneh seperti \xFF akan otomatis terhapus
        $teks = preg_replace('/[^\p{L}\p{N}\s\.\,\-\/\@\(\)\_]/u', '', $teks);
        
        return trim($teks);
    }

    public function handle()
    {
        $this->info('Memulai membaca file CSV...');
        
        $file = storage_path('app/data_wbp.csv');

        if (!file_exists($file)) {
            $this->error("File tidak ditemukan! Pastikan file ada di: " . $file);
            return;
        }

        // Setup Data UPT & Kanwil
        $kanwil = Kanwil::firstOrCreate(
            ['name' => 'Kanwil Kemenkumham NTB'],
            ['is_active' => true]
        );

        $upt = Upt::firstOrCreate(
            ['name' => 'Lapas Kelas IIA Lombok Barat'],
            ['kanwil_id' => $kanwil->id, 'is_active' => true]
        );

        $uptId = $upt->id;

        // Buka file
        $handle = fopen($file, "r");
        
        // Deteksi Delimiter (koma atau titik koma)
        $firstLine = fgets($handle);
        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
        rewind($handle); // Kembali ke awal file

        $rowCounter = 0;
        $inserted = 0;

        $this->info("Sedang memproses data dengan delimiter '{$delimiter}'... mohon tunggu.");

        while (($data = fgetcsv($handle, 10000, $delimiter)) !== FALSE) {
            $rowCounter++;

            if ($rowCounter <= 1) {
                continue;
            }

            if (count($data) < 7 || !isset($data[2]) || trim($data[2]) === '') {
                continue;
            }

            try {
                // Bersihkan teks dari karakter aneh menggunakan fungsi helper di atas
                $noRegInstansi = substr($this->bersihkanTeks($data[1]), 0, 255);
                $nama = substr($this->bersihkanTeks($data[2]), 0, 255);
                $alamat = $this->bersihkanTeks($data[3]);
                $namaKejahatan = substr($this->bersihkanTeks($data[4]), 0, 255);
                $namaBlok = substr($this->bersihkanTeks($data[5]), 0, 255);
                $namaSel = substr($this->bersihkanTeks($data[6]), 0, 255);

                $jenisKejahatan = JenisKejahatan::firstOrCreate([
                    'nama_kejahatan' => $namaKejahatan ?: 'TIDAK DIKETAHUI'
                ]);

                $blok = Blok::firstOrCreate([
                    'upt_id' => $uptId,
                    'nama_blok' => $namaBlok ?: 'TIDAK DIKETAHUI'
                ]);

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

        $this->info("Import Selesai! Berhasil memproses {$inserted} data WBP ke database.");
    }
}