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
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $isPengunjung = $user->hasRole('Pengunjung');

        if ($isPengunjung && $user->pengunjung) {
            $query->where('pengunjung_id', $user->pengunjung->id);
        } else {
            $query->when($user->upt_id, function ($q) use ($user) {
                $q->where('upt_id', $user->upt_id);
            });
        }

        // =========================================================
        // LOGIKA FILTERING (Pencarian, Tanggal, Sesi)
        // =========================================================
        $query->when($request->search, function ($q, $search) {
            $q->where(function($query) use ($search) {
                $query->whereHas('pengunjung.user', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })->orWhereHas('wbp', function ($q3) use ($search) {
                    $q3->where('nama', 'like', "%{$search}%");
                });
            });
        });

        $query->when($request->tanggal, function ($q, $tanggal) {
            $q->whereDate('tanggal_kunjungan', $tanggal);
        });

        $query->when($request->waktu, function ($q, $waktu) {
            // Menggunakan LIKE agar kalau ada embel-embel jam tetap terbaca
            $q->where('waktu_kunjungan', 'LIKE', "%{$waktu}%"); 
        });
        // =========================================================

        // Tambahkan withQueryString() agar filter tidak hilang saat ganti halaman pagination
        $kunjungans = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('admin/kunjungan/Index', [
            'kunjungans' => $kunjungans,
            'isPengunjung' => $isPengunjung,
            'filters' => $request->only(['search', 'tanggal', 'waktu']) // Kirim filter ke frontend
        ]);
    }

    // =========================================================
    // METHOD EXPORT EXCEL (CSV Native)
    // =========================================================
    public function export(Request $request)
    {
        $user = auth()->user();
        $query = Kunjungan::with(['upt', 'wbp', 'pengunjung.user']);

        if ($user->hasRole('Pengunjung') && $user->pengunjung) {
            $query->where('pengunjung_id', $user->pengunjung->id);
        } else {
            $query->when($user->upt_id, function ($q) use ($user) {
                $q->where('upt_id', $user->upt_id);
            });
        }

        // Terapkan filter yang sama untuk data yang diexport
        $query->when($request->search, function ($q, $search) {
            $q->where(function($query) use ($search) {
                $query->whereHas('pengunjung.user', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })->orWhereHas('wbp', function ($q3) use ($search) {
                    $q3->where('nama', 'like', "%{$search}%");
                });
            });
        });
        $query->when($request->tanggal, function ($q, $tanggal) {
            $q->whereDate('tanggal_kunjungan', $tanggal);
        });
        $query->when($request->waktu, function ($q, $waktu) {
            $q->where('waktu_kunjungan', $waktu);
        });

        $kunjungans = $query->latest()->get();

        $fileName = 'Data_Kunjungan_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Tanggal', 'Sesi', 'Nama Pengunjung', 'Nama WBP', 'UPT', 'Status'];

        $callback = function() use($kunjungans, $columns) {
            $file = fopen('php://output', 'w');
            
            // Tambahkan BOM agar Excel membaca karakter UTF-8 dengan benar
            fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
            
            // Gunakan separator titik koma (;) yang lebih ramah untuk Microsoft Excel Indonesia
            fputcsv($file, $columns, ';'); 

            foreach ($kunjungans as $k) {
                fputcsv($file, [
                    $k->tanggal_kunjungan,
                    $k->waktu_kunjungan,
                    $k->pengunjung?->user?->name ?? 'Tidak diketahui',
                    $k->wbp?->nama ?? '-',
                    $k->upt?->name ?? '-',
                    $k->status
                ], ';');
            }
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    public function create()
    {
        $user = auth()->user();
        
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

        // ==============================================================
        // CEK KUOTA HARIAN (LIMIT 200 PER UPT PER HARI)
        // ==============================================================
        $kuotaMaksimal = 200;
        $jumlahAntrean = Kunjungan::where('upt_id', $request->upt_id)
            ->where('tanggal_kunjungan', $request->tanggal_kunjungan)
            ->whereNotIn('status', ['Batal', 'Kadaluarsa']) // Abaikan yang batal/kadaluarsa
            ->count();

        if ($jumlahAntrean >= $kuotaMaksimal) {
            throw ValidationException::withMessages([
                'tanggal_kunjungan' => "Mohon maaf, kuota kunjungan untuk tanggal tersebut sudah penuh (Maks: $kuotaMaksimal antrean). Silakan pilih tanggal lain."
            ]);
        }
        // ==============================================================

        $user = auth()->user();
        $pengunjungId = $user->pengunjung ? $user->pengunjung->id : 1; 

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

        return redirect()->route('kunjungans.show', $kunjungan->id)
                         ->with('success', 'Data kunjungan berhasil dibuat!');
    }

    public function show(Kunjungan $kunjungan)
    {
        $kunjungan->load(['upt', 'wbp.sel.blok', 'barangBawaans', 'pengunjung.user']);
        
        $options = new QROptions([
            'version'      => 5,
            'outputType'   => 'svg',
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
        
        if ($user->hasRole('Pengunjung')) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit data kunjungan.');
        }

        if ($user->upt_id && $kunjungan->upt_id !== $user->upt_id) {
            abort(403, 'Akses Ditolak! Anda tidak dapat mengedit data kunjungan dari Lapas lain.');
        }

        $kunjungan->load(['wbp.sel.blok', 'wbp.jenisKejahatan', 'barangBawaans']);
        
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

        // ==============================================================
        // CEK KUOTA HARIAN (Khusus jika mengganti Tanggal atau UPT)
        // ==============================================================
        if ($kunjungan->tanggal_kunjungan !== $request->tanggal_kunjungan || $kunjungan->upt_id !== $request->upt_id) {
            $kuotaMaksimal = 200;
            $jumlahAntrean = Kunjungan::where('upt_id', $request->upt_id)
                ->where('tanggal_kunjungan', $request->tanggal_kunjungan)
                ->where('id', '!=', $kunjungan->id) // Jangan hitung diri sendiri
                ->whereNotIn('status', ['Batal', 'Kadaluarsa'])
                ->count();

            if ($jumlahAntrean >= $kuotaMaksimal) {
                throw ValidationException::withMessages([
                    'tanggal_kunjungan' => "Mohon maaf, kuota kunjungan untuk tanggal tersebut sudah penuh (Maks: $kuotaMaksimal antrean). Silakan pilih tanggal lain."
                ]);
            }
        }
        // ==============================================================

        $kunjungan->update([
            'upt_id' => $request->upt_id,
            'wbp_id' => $request->wbp_id,
            'tanggal_kunjungan' => $request->tanggal_kunjungan,
            'waktu_kunjungan' => $request->waktu_kunjungan,
            'pengikut_laki' => $request->pengikut_laki,
            'pengikut_perempuan' => $request->pengikut_perempuan,
            'pengikut_anak' => $request->pengikut_anak,
            'total_pengikut' => $request->total_pengikut,
            'status' => $request->status,
        ]);

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
        $user = auth()->user();
        if ($user->hasRole('Pengunjung') && $kunjungan->pengunjung_id !== $user->pengunjung->id) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus data ini.');
        }

        $kunjungan->delete();

        return redirect()->route('kunjungans.index')->with('success', 'Data kunjungan berhasil dihapus.');
    }
}