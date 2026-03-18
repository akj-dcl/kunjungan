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
    // 1. Menampilkan Halaman Scanner
    public function index()
    {
        return Inertia::render('admin/scanqr/Index');
    }

    // 2. Memproses Hasil Scan QR
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
        // ISOLASI DATA LOKASI LAPAS
        // ==========================================
        $user = auth()->user();
        if ($user->upt_id && $kunjungan->upt_id !== $user->upt_id) {
            return response()->json([
                'success' => false, 
                'message' => 'DITOLAK! Kunjungan ini terdaftar untuk Lapas lain, bukan Lapas Anda.'
            ], 403);
        }
        
        // ==========================================
        // VALIDASI STATUS & TANGGAL (Kadaluarsa & Terlalu Cepat)
        // ==========================================
        $hariIni = Carbon::now()->startOfDay();
        $tanggalKunjungan = Carbon::parse($kunjungan->tanggal_kunjungan)->startOfDay();

        // 1. Cek apakah tanggalnya sudah lewat (Kadaluarsa)
        if ($tanggalKunjungan->lessThan($hariIni)) {
            if ($kunjungan->status !== 'Kadaluarsa') {
                $kunjungan->update(['status' => 'Kadaluarsa']);
            }
            return response()->json([
                'success' => false, 
                'message' => 'QR Code sudah Kadaluarsa! Jadwal kunjungan ini untuk tanggal ' . $tanggalKunjungan->format('d/m/Y')
            ], 400); 
        }

        // 2. Cek apakah datangnya kecepetan (Nyolong Start)
        if ($tanggalKunjungan->greaterThan($hariIni)) {
            return response()->json([
                'success' => false, 
                'message' => 'Belum waktunya berkunjung! Jadwal kunjungan ini untuk besok lusa: ' . $tanggalKunjungan->format('d M Y') . '. Silakan datang pada tanggal tersebut.'
            ], 400); 
        }

        // 3. Cek status lainnya
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
            'message' => 'QR Code berhasil dikonfirmasi!',
            'data' => $kunjungan
        ]);
    }

    // 3. Export Excel (Harian, Mingguan, Bulanan)
    public function export($filter)
    {
        if (!in_array($filter, ['hari', 'minggu', 'bulan', 'semua'])) {
            abort(404);
        }
        
        $namaFile = 'Rekap_Kunjungan_' . ucfirst($filter) . '_' . date('Y-m-d') . '.xlsx';
        return Excel::download(new KunjunganExport($filter), $namaFile);
    }
}