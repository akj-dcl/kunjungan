<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Kunjungan;
use App\Exports\KunjunganExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ScanQRController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/scanqr/Index');
    }

    // public function process(Request $request)
    // {
    //     $request->validate(['qr_code' => 'required|string']);

    //     $kunjungan = Kunjungan::with(['pengunjung.user', 'wbp.sel.blok', 'barangBawaans'])
    //         ->where('qr_code_uuid', $request->qr_code)
    //         ->first();

    //     if (!$kunjungan) {
    //         return response()->json(['success' => false, 'message' => 'QR Code tidak ditemukan atau tidak valid!'], 404);
    //     }

    //     // ==========================================
    //     // 1. ISOLASI DATA LOKASI LAPAS
    //     // ==========================================
    //     $user = auth()->user();
    //     if ($user->upt_id && $kunjungan->upt_id !== $user->upt_id) {
    //         return response()->json([
    //             'success' => false, 
    //             'message' => 'DITOLAK! Kunjungan ini terdaftar untuk Lapas lain, bukan Lapas Anda.'
    //         ], 403);
    //     }
        
    //     // ==========================================
    //     // 2. VALIDASI HARI (KADALUARSA / KECEPETAN)
    //     // ==========================================
    //     $hariIni = Carbon::now()->startOfDay();
    //     $tanggalKunjungan = Carbon::parse($kunjungan->tanggal_kunjungan)->startOfDay();

    //     if ($tanggalKunjungan->lessThan($hariIni)) {
    //         if ($kunjungan->status !== 'Kadaluarsa') {
    //             $kunjungan->update(['status' => 'Kadaluarsa']);
    //         }
    //         return response()->json([
    //             'success' => false, 
    //             'message' => 'QR Code sudah Kadaluarsa! Jadwal kunjungan ini untuk tanggal ' . $tanggalKunjungan->format('d/m/Y')
    //         ], 400); 
    //     }

    //     if ($tanggalKunjungan->greaterThan($hariIni)) {
    //         return response()->json([
    //             'success' => false, 
    //             'message' => 'Belum waktunya berkunjung! Jadwal kunjungan ini untuk besok lusa: ' . $tanggalKunjungan->format('d M Y') . '.'
    //         ], 400); 
    //     }

    //     // ==========================================
    //     // 3. VALIDASI JAM SESI (HARI H)
    //     // ==========================================
    //     // String dari DB, misal: "Sesi 1 (09.00 - 11.00)"
    //     $waktuSesi = $kunjungan->waktu_kunjungan; 
        
    //     // Jam server saat ini (contoh: 10:45)
    //     $waktuSekarang = Carbon::now()->format('H:i'); 
        
    //     // Ekstrak rentang jam dari string. Pakai Regex buat ambil angka "09.00 - 11.00"
    //     if (preg_match('/\((\d{2}\.\d{2})\s*-\s*(\d{2}\.\d{2})\)/', $waktuSesi, $matches)) {
    //         // Ubah format titik jadi titik dua biar bisa dibandingin (09.00 -> 09:00)
    //         $jamBuka = str_replace('.', ':', $matches[1]);
    //         $jamTutup = str_replace('.', ':', $matches[2]);

    //         // Cek apakah pengunjung datang SEBELUM jam buka
    //         if ($waktuSekarang < $jamBuka) {
    //             return response()->json([
    //                 'success' => false, 
    //                 'message' => "SESI BELUM DIMULAI! Anda terdaftar untuk $waktuSesi. Silakan tunggu di ruang tunggu hingga jam $jamBuka."
    //             ], 400); 
    //         }

    //         // Cek apakah pengunjung datang SETELAH jam tutup
    //         if ($waktuSekarang > $jamTutup) {
    //             return response()->json([
    //                 'success' => false, 
    //                 'message' => "SESI SUDAH BERAKHIR! Anda terdaftar untuk $waktuSesi. Waktu Anda sudah lewat."
    //             ], 400); 
    //         }
    //     }

    //     // ==========================================
    //     // 4. CEK STATUS LAINNYA
    //     // ==========================================
    //     if ($kunjungan->status === 'Selesai') {
    //         return response()->json([
    //             'success' => false, 
    //             'message' => 'QR Code ini sudah pernah di-scan dan digunakan sebelumnya!'
    //         ], 400);
    //     }

    //     if ($kunjungan->status === 'Kadaluarsa') {
    //         return response()->json([
    //             'success' => false, 
    //             'message' => 'QR Code sudah Kadaluarsa! Silakan buat jadwal kunjungan yang baru.'
    //         ], 400);
    //     }

    //     // Jika lolos semua validasi di atas, Ubah status jadi Selesai
    //     $kunjungan->update(['status' => 'Selesai']);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Kunjungan Dikonfirmasi! Silakan masuk.',
    //         'data' => $kunjungan
    //     ]);
    // }

    public function process(Request $request)
    {
        $request->validate(['qr_code' => 'required|string']);

        $kunjungan = Kunjungan::with(['pengunjung.user', 'wbp.sel.blok', 'barangBawaans'])
            ->where('qr_code_uuid', $request->qr_code)
            ->first();

        if (!$kunjungan) {
            return response()->json(['success' => false, 'message' => 'QR Code tidak ditemukan atau tidak valid!'], 404);
        }

        // ==========================================
        // 1. ISOLASI DATA LOKASI LAPAS
        // ==========================================
        $user = auth()->user();
        if ($user->upt_id && $kunjungan->upt_id !== $user->upt_id) {
            return response()->json([
                'success' => false, 
                'message' => 'DITOLAK! Kunjungan ini terdaftar untuk Lapas lain, bukan Lapas Anda.'
            ], 403);
        }
        
        // ==========================================
        // 2. VALIDASI HARI (KADALUARSA / KECEPETAN)
        // ==========================================
        $hariIni = Carbon::now()->startOfDay();
        $tanggalKunjungan = Carbon::parse($kunjungan->tanggal_kunjungan)->startOfDay();

        if ($tanggalKunjungan->lessThan($hariIni)) {
            if ($kunjungan->status !== 'Kadaluarsa') {
                $kunjungan->update(['status' => 'Kadaluarsa']);
            }
            return response()->json([
                'success' => false, 
                'message' => 'QR Code sudah Kadaluarsa! Jadwal kunjungan ini untuk tanggal ' . $tanggalKunjungan->format('d/m/Y')
            ], 400); 
        }

        if ($tanggalKunjungan->greaterThan($hariIni)) {
            return response()->json([
                'success' => false, 
                'message' => 'Belum waktunya berkunjung! Jadwal kunjungan ini untuk: ' . $tanggalKunjungan->format('d M Y') . '.'
            ], 400); 
        }

        // ==========================================
        // 3. CEK STATUS LAINNYA
        // (Validasi Jam Sesi sudah dihapus, jadi bebas scan kapan saja di hari yang sama)
        // ==========================================
        if ($kunjungan->status === 'Selesai') {
            return response()->json([
                'success' => false, 
                'message' => 'QR Code ini sudah pernah di-scan dan digunakan sebelumnya!'
            ], 400);
        }

        if ($kunjungan->status === 'Kadaluarsa') {
            return response()->json([
                'success' => false, 
                'message' => 'QR Code sudah Kadaluarsa! Silakan buat jadwal kunjungan yang baru.'
            ], 400);
        }

        // Jika lolos semua validasi di atas, Ubah status jadi Selesai
        $kunjungan->update(['status' => 'Selesai']);

        return response()->json([
            'success' => true,
            'message' => 'Kunjungan Dikonfirmasi! Silakan masuk.',
            'data' => $kunjungan
        ]);
    }

    public function export($filter)
    {
        if (!in_array($filter, ['hari', 'minggu', 'bulan', 'semua'])) {
            abort(404);
        }
        
        $namaFile = 'Rekap_Kunjungan_' . ucfirst($filter) . '_' . date('Y-m-d') . '.xlsx';
        return Excel::download(new KunjunganExport($filter), $namaFile);
    }
}