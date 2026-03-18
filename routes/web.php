<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\KanwilController;
use App\Http\Controllers\Admin\UptController;
use App\Http\Controllers\Admin\JenisKejahatanController;
use App\Http\Controllers\Admin\BlokController;
use App\Http\Controllers\Admin\SelController;
use App\Http\Controllers\Admin\WbpController;
use App\Http\Controllers\Admin\PengunjungController;
use App\Http\Controllers\Admin\KunjunganController;
use App\Http\Controllers\Admin\DataAkunController;
use App\Models\Wbp;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\ScanQRController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Middleware\RoleRedirectMiddleware;

use App\Http\Controllers\Auth\RegisterPengunjungController;


Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Tambahkan RoleRedirectMiddleware di sini
    Route::middleware(RoleRedirectMiddleware::class)->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');
    });
});

Route::get('register-pengunjung', [RegisterPengunjungController::class, 'create'])->name('register-pengunjung.create');
Route::post('register-pengunjung', [RegisterPengunjungController::class, 'store'])->name('register-pengunjung.store');

Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::resource('roles', RoleController::class);
    Route::resource('kanwils', KanwilController::class);
    Route::resource('upts', UptController::class);
    Route::resource('jenis-kejahatans', JenisKejahatanController::class);
    Route::resource('bloks', BlokController::class);
    Route::resource('sels', SelController::class);
    Route::resource('wbps', WbpController::class);
    Route::resource('wbps', WbpController::class);
    Route::resource('data-akun', DataAkunController::class)->names('data-akun');
    Route::resource('pengunjungs', PengunjungController::class);
    Route::resource('kunjungans', KunjunganController::class);
    Route::get('/datamaster/DataAkun', function () {
        return Inertia::render('admin/datamaster/DataAkun');
    });
    Route::get('/api/search-wbp', function (Request $request) {
        if (!$request->q) return response()->json([]);
        
        $user = auth()->user();
        
        // Ambil UPT yang dipilih di form (frontend)
        $uptId = $request->upt_id;

        // JURUS ISOLASI: Jika user adalah petugas cabang (punya upt_id),
        // PAKSA pencarian hanya di UPT miliknya (mencegah manipulasi data).
        if ($user->upt_id) {
            $uptId = $user->upt_id;
        }
        
        $wbps = Wbp::with(['sel.blok', 'jenisKejahatan'])
            ->where('is_aktif', true)
            // Filter berdasarkan UPT yang valid
            ->when($uptId, function ($query) use ($uptId) {
                $query->where('upt_id', $uptId);
            })
            ->where(function($q) use ($request) {
                $q->where('nama', 'like', "%{$request->q}%")
                  ->orWhere('no_reg_instansi', 'like', "%{$request->q}%");
            })
            ->limit(15)
            ->get();
            
        return response()->json($wbps);
    });
    // ROUTE SCAN QR & EXPORT
    Route::get('/scan-qr', [ScanQRController::class, 'index'])->name('scan-qr.index');
    Route::post('/api/scan-qr/process', [ScanQRController::class, 'process']);
    Route::get('/scan-qr/export/{filter}', [ScanQRController::class, 'export']);
});

Route::get('/admin/scan-qr/print-kartu/{id}', function ($id) {
    $kunjungan = \App\Models\Kunjungan::with(['upt', 'wbp.sel.blok', 'pengunjung.user', 'barangBawaans'])->findOrFail($id);
    return view('print.kartu', compact('kunjungan'));
})->name('scan-qr.print');

require __DIR__ . '/settings.php';