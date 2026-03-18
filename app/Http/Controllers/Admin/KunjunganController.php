<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kunjungan;
use App\Models\Upt;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Carbon\Carbon;
// use chillerlan\QRCode\Output\QROutputInterface;

class KunjunganController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $hariIni = Carbon::now()->startOfDay();

        // Auto Kadaluarsa
        Kunjungan::where('status', 'Menunggu Kedatangan Kunjungan')
                 ->whereDate('tanggal_kunjungan', '<', $hariIni->toDateString())
                 ->update(['status' => 'Kadaluarsa']);

        $query = Kunjungan::with(['upt', 'wbp', 'pengunjung.user']);

        // CEK ROLE DI BACKEND SAJA (Lebih Akurat)
        $isPengunjung = $user->hasRole('Pengunjung');

        // Jika login sebagai pengunjung, hanya tampilkan kunjungan miliknya
        if ($isPengunjung && $user->pengunjung) {
            $query->where('pengunjung_id', $user->pengunjung->id);
        } else {
            // ==============================================================
            // JURUS ISOLASI DATA (KHUSUS PETUGAS/ADMIN LAPAS)
            // ==============================================================
            // Jika user punya upt_id (berarti dia petugas cabang, bukan Kanwil), 
            // maka hanya tampilkan Kunjungan yang terjadi di UPT tersebut.
            $query->when($user->upt_id, function ($q) use ($user) {
                $q->where('upt_id', $user->upt_id);
            });
        }

        $kunjungans = $query->latest()->paginate(10);

        return Inertia::render('admin/kunjungan/Index', [
            'kunjungans' => $kunjungans,
            'isPengunjung' => $isPengunjung, 
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        
        // JIKA USER PUNYA UPT_ID, AMBIL 1 UPT SAJA. JIKA KANWIL, AMBIL SEMUA.
        $upts = $user->upt_id 
            ? Upt::where('id', $user->upt_id)->get() 
            : Upt::where('is_active', true)->get();

        return Inertia::render('admin/kunjungan/Create', ['upts' => $upts]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'upt_id' => 'required|exists:upts,id',
            'wbp_id' => 'required|exists:wbps,id',
            'tanggal_kunjungan' => 'required|date',
            'waktu_kunjungan' => 'required',
        ]);

        $user = auth()->user();
        // Ambil ID pengunjung. Jika Super Admin yang sedang testing, kita pasang null atau ID 1 sementara
        $pengunjungId = $user->pengunjung ? $user->pengunjung->id : 1; 

        // 1. Simpan Data Kunjungan & Generate UUID untuk QR Code
        $kunjungan = Kunjungan::create([
            'pengunjung_id' => $pengunjungId,
            'upt_id' => $request->upt_id,
            'wbp_id' => $request->wbp_id,
            'tanggal_kunjungan' => $request->tanggal_kunjungan,
            'waktu_kunjungan' => $request->waktu_kunjungan,
            'pengikut_laki' => $request->pengikut_laki,
            'pengikut_perempuan' => $request->pengikut_perempuan,
            'pengikut_anak' => $request->pengikut_anak,
            'total_pengikut' => $request->total_pengikut,
            'qr_code_uuid' => Str::uuid()->toString(),
            'status' => 'Menunggu Kedatangan Kunjungan'
        ]);

        // 2. Simpan Data Barang Bawaan (Titipan)
        if ($request->has('barang_bawaan') && is_array($request->barang_bawaan)) {
            foreach ($request->barang_bawaan as $barang) {
                if (!empty($barang['jenis_barang'])) {
                    $kunjungan->barangBawaans()->create([
                        'jenis_barang' => $barang['jenis_barang'],
                        'jumlah' => $barang['jumlah'],
                        'keterangan' => $barang['keterangan'] ?? '-',
                    ]);
                }
            }
        }

        // Redirect ke halaman detail (menampilkan QR Code)
        return redirect()->route('kunjungans.show', $kunjungan->id)
                         ->with('success', 'Data kunjungan berhasil dibuat!');
    }

    public function show(Kunjungan $kunjungan)
    {
        $kunjungan->load(['upt', 'wbp.sel.blok', 'barangBawaans', 'pengunjung.user']);
        
        // Gunakan string langsung 'svg' (ini cara paling aman dan kompatibel dengan semua versi)
        $options = new QROptions([
            'version'      => 5,
            'outputType'   => 'svg', // UBAH BAGIAN INI
            'imageBase64'  => true,
        ]);
        
        $qrCodeImage = (new QRCode($options))->render($kunjungan->qr_code_uuid);

        return Inertia::render('admin/kunjungan/Show', [
            'kunjungan' => $kunjungan,
            'qrCodeImage' => $qrCodeImage
        ]);
    }

    public function edit(Kunjungan $kunjungan)
    {
        $user = auth()->user();
        
        // Cegah pengunjung mengakses halaman edit
        if ($user->hasRole('Pengunjung')) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit data kunjungan.');
        }

        // Keamanan Ekstra: Cegah petugas mengedit data Kunjungan milik Lapas lain
        if ($user->upt_id && $kunjungan->upt_id !== $user->upt_id) {
            abort(403, 'Akses Ditolak! Anda tidak dapat mengedit data kunjungan dari Lapas lain.');
        }

        $kunjungan->load(['wbp.sel.blok', 'wbp.jenisKejahatan', 'barangBawaans']);
        
        // Logic dropdown sama seperti di Create()
        $upts = $user->upt_id 
            ? Upt::where('id', $user->upt_id)->get() 
            : Upt::where('is_active', true)->get();

        return Inertia::render('admin/kunjungan/Edit', [
            'kunjungan' => $kunjungan,
            'upts' => $upts
        ]);
    }

    public function update(Request $request, Kunjungan $kunjungan)
    {
        $user = auth()->user();
        if ($user->hasRole('Pengunjung')) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'upt_id' => 'required|exists:upts,id',
            'wbp_id' => 'required|exists:wbps,id',
            'tanggal_kunjungan' => 'required|date',
            'waktu_kunjungan' => 'required',
        ]);

        // 1. Update Data Kunjungan Utama
        $kunjungan->update([
            'upt_id' => $request->upt_id,
            'wbp_id' => $request->wbp_id,
            'tanggal_kunjungan' => $request->tanggal_kunjungan,
            'waktu_kunjungan' => $request->waktu_kunjungan,
            'pengikut_laki' => $request->pengikut_laki,
            'pengikut_perempuan' => $request->pengikut_perempuan,
            'pengikut_anak' => $request->pengikut_anak,
            'total_pengikut' => $request->total_pengikut,
            'status' => $request->status, // Memungkinkan admin merubah status manual
        ]);

        // 2. Hapus semua barang bawaan lama, lalu insert yang baru (Cara paling aman)
        $kunjungan->barangBawaans()->delete();

        if ($request->has('barang_bawaan') && is_array($request->barang_bawaan)) {
            foreach ($request->barang_bawaan as $barang) {
                if (!empty($barang['jenis_barang'])) {
                    $kunjungan->barangBawaans()->create([
                        'jenis_barang' => $barang['jenis_barang'],
                        'jumlah' => $barang['jumlah'],
                        'keterangan' => $barang['keterangan'] ?? '-',
                    ]);
                }
            }
        }

        return redirect()->route('kunjungans.index')->with('success', 'Data kunjungan berhasil diperbarui!');
    }

    public function destroy(Kunjungan $kunjungan)
    {
        // Pastikan hanya Super Admin atau pengunjung yang bersangkutan yang bisa menghapus
        $user = auth()->user();
        if ($user->hasRole('Pengunjung') && $kunjungan->pengunjung_id !== $user->pengunjung->id) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus data ini.');
        }

        // Karena di tabel barang_bawaan kita pakai cascadeOnDelete,
        // kita cukup hapus data kunjungannya saja, barang otomatis terhapus
        $kunjungan->delete();

        return redirect()->route('kunjungans.index')->with('success', 'Data kunjungan berhasil dihapus.');
    }
}