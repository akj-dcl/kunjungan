<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Kunjungan;
use App\Models\Upt;
use Illuminate\Support\Facades\DB;
use App\Exports\KunjunganExport;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isKanwil = $user->hasRole(['Super Admin', 'Admin Kanwil', 'Supervisor Kanwil']) || empty($user->upt_id);
        $upts = $isKanwil ? Upt::select('id', 'name')->where('is_active', true)->get() : [];

        // Parameter Filter
        $startDate = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? now()->endOfMonth()->format('Y-m-d');
        $uptId = $request->upt_id;
        $sesi = $request->sesi; // Tangkap filter sesi

        $query = Kunjungan::where('status', 'Selesai')
            ->whereBetween('tanggal_kunjungan', [$startDate, $endDate]);

        // Isolasi UPT
        if ($isKanwil) {
            if ($uptId) $query->where('upt_id', $uptId);
        } else {
            $query->where('upt_id', $user->upt_id);
        }

        // TAMBAHAN: Filter berdasarkan Sesi
        if ($sesi) {
            $query->where('waktu_kunjungan', 'like', $sesi . '%');
        }

        $ringkasan = (clone $query)
            ->selectRaw('SUM(1 + total_pengikut) as total_orang_membesuk')
            ->selectRaw('COUNT(DISTINCT wbp_id) as total_wbp_dibesuk')
            ->first();

        $grafikData = (clone $query)
            ->select(
                'tanggal_kunjungan as tanggal',
                DB::raw('SUM(1 + total_pengikut) as total_orang'),
                DB::raw('COUNT(DISTINCT wbp_id) as total_wbp')
            )
            ->groupBy('tanggal_kunjungan')
            ->orderBy('tanggal_kunjungan', 'asc')
            ->get();

        return Inertia::render('Dashboard', [
            'isKanwil' => $isKanwil,
            'upts' => $upts,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'upt_id' => $uptId,
                'sesi' => $sesi, // Lempar ke Vue
            ],
            'ringkasan' => [
                'total_orang' => $ringkasan->total_orang_membesuk ?? 0,
                'total_wbp' => $ringkasan->total_wbp_dibesuk ?? 0,
            ],
            'grafik' => $grafikData
        ]);
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $isKanwil = $user->hasRole(['Super Admin', 'Admin Kanwil', 'Supervisor Kanwil']) || empty($user->upt_id);
        
        $uptId = $request->upt_id;
        $sesi = $request->sesi; // Tangkap filter sesi

        if (!$isKanwil) {
            $uptId = $user->upt_id; 
        }

        $namaFile = 'Laporan_Kunjungan_' . date('Ymd_His') . '.xlsx';
        
        // Lempar parameter $sesi ke Export class
        return Excel::download(new KunjunganExport($request->start_date, $request->end_date, $uptId, $sesi), $namaFile);
    }
}