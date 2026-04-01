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
            $q->where('waktu_kunjungan', 'LIKE', "%{$waktu}%"); 
        });

        $kunjungans = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('admin/kunjungan/Index', [
            'kunjungans' => $kunjungans,
            'isPengunjung' => $isPengunjung,
            'filters' => $request->only(['search', 'tanggal', 'waktu']) // Kirim filter ke frontend
        ]);
    }

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
            
            fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
            
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

        if ($request->upt_id == 1) { 
            if ($request->waktu_kunjungan !== 'Sesi 1 (09.00 - 11.00)') {
                throw ValidationException::withMessages([
                    'waktu_kunjungan' => 'Lapas Lombok Barat hanya melayani kunjungan pada Sesi 1 Pagi (09.00 - 11.00).'
                ]);
            }
            $jumlahDewasa = $request->pengikut_laki + $request->pengikut_perempuan;
            if ($jumlahDewasa > 3) {
                throw ValidationException::withMessages([
                    'pengikut_laki' => 'Total pengikut Laki-laki dan Perempuan untuk Lapas Lobar maksimal 3 orang.',
                    'pengikut_perempuan' => 'Total pengikut Laki-laki dan Perempuan untuk Lapas Lobar maksimal 3 orang.'
                ]);
            }
        }

        $uptId = $request->upt_id;
        
        if ($uptId == 3) {
            $kuotaMaksimal = 50;
            $jumlahAntrean = Kunjungan::where('upt_id', $uptId)
                ->where('tanggal_kunjungan', $request->tanggal_kunjungan)
                ->where('waktu_kunjungan', $request->waktu_kunjungan) // Dihitung per Sesi
                ->whereNotIn('status', ['Batal', 'Kadaluarsa'])
                ->count();
                
            $pesanError = "Mohon maaf, kuota kunjungan untuk " . $request->waktu_kunjungan . " sudah penuh (Maks: $kuotaMaksimal antrean). Silakan pilih Sesi atau Tanggal lain.";
        } else {
            $kuotaMaksimal = 200;
            $jumlahAntrean = Kunjungan::where('upt_id', $uptId)
                ->where('tanggal_kunjungan', $request->tanggal_kunjungan)
                ->whereNotIn('status', ['Batal', 'Kadaluarsa'])
                ->count();
                
            $pesanError = "Mohon maaf, kuota harian kunjungan untuk tanggal tersebut sudah penuh (Maks: $kuotaMaksimal antrean/hari). Silakan pilih tanggal lain.";
        }

        if ($jumlahAntrean >= $kuotaMaksimal) {
            throw ValidationException::withMessages([
                'tanggal_kunjungan' => $pesanError
            ]);
        }

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

        if ($kunjungan->tanggal_kunjungan !== $request->tanggal_kunjungan || 
            $kunjungan->upt_id !== $request->upt_id || 
            $kunjungan->waktu_kunjungan !== $request->waktu_kunjungan) {
            
            $uptId = $request->upt_id;
            
            if ($uptId == 3) {
                $kuotaMaksimal = 50;
                $jumlahAntrean = Kunjungan::where('upt_id', $uptId)
                    ->where('tanggal_kunjungan', $request->tanggal_kunjungan)
                    ->where('waktu_kunjungan', $request->waktu_kunjungan)
                    ->where('id', '!=', $kunjungan->id)
                    ->whereNotIn('status', ['Batal', 'Kadaluarsa'])
                    ->count();
                $pesanError = "Mohon maaf, kuota kunjungan untuk " . $request->waktu_kunjungan . " sudah penuh (Maks: $kuotaMaksimal antrean).";
            } else {
                $kuotaMaksimal = 200;
                $jumlahAntrean = Kunjungan::where('upt_id', $uptId)
                    ->where('tanggal_kunjungan', $request->tanggal_kunjungan)
                    ->where('id', '!=', $kunjungan->id) // Jangan hitung diri sendiri
                    ->whereNotIn('status', ['Batal', 'Kadaluarsa'])
                    ->count();
                $pesanError = "Mohon maaf, kuota harian kunjungan untuk tanggal tersebut sudah penuh (Maks: $kuotaMaksimal antrean/hari).";
            }

            if ($jumlahAntrean >= $kuotaMaksimal) {
                throw ValidationException::withMessages([
                    'tanggal_kunjungan' => $pesanError
                ]);
            }
        }

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