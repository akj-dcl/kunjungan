<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wbp;
use App\Models\Upt;
use App\Models\JenisKejahatan;
use App\Models\Sel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WbpController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user(); // Ambil data user yang login

        $wbps = Wbp::with(['upt', 'jenisKejahatan', 'sel.blok'])
            // JURUS ISOLASI: Jika user terikat dengan 1 UPT, hanya tampilkan WBP dari UPT tersebut
            ->when($user->upt_id, function ($query) use ($user) {
                $query->where('upt_id', $user->upt_id);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('no_reg_instansi', 'like', "%{$search}%");
                });
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/datamaster/wbp/Index', [
            'wbps' => $wbps,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        $user = auth()->user();

        // Jika user punya upt_id, ambil 1 UPT saja. Jika Kanwil/Super Admin, ambil semua UPT.
        $upts = $user->upt_id 
            ? Upt::where('id', $user->upt_id)->get() 
            : Upt::where('is_active', true)->get();

        // Begitu juga untuk pilihan SEL/Kamar, filter hanya sel milik UPT tersebut
        $sels = Sel::with('blok')
            ->when($user->upt_id, function($query) use ($user) {
                $query->whereHas('blok', function($q) use ($user) {
                    $q->where('upt_id', $user->upt_id);
                });
            })->get();

        return Inertia::render('admin/datamaster/wbp/Create', [
            'upts' => $upts,
            'jenis_kejahatans' => JenisKejahatan::all(),
            'sels' => $sels
        ]);
    }

    public function store(Request $request)
    {
        Wbp::create($request->validate([
            'upt_id' => 'required|exists:upts,id',
            'no_reg_instansi' => 'required|string|max:255',
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'jenis_kejahatan_id' => 'required|exists:jenis_kejahatans,id',
            'sel_id' => 'required|exists:sels,id',
            'is_aktif' => 'boolean',
        ]));

        return redirect()->route('wbps.index')->with('success', 'Data WBP berhasil ditambahkan.');
    }

    public function edit(Wbp $wbp)
    {
        $user = auth()->user();

        // Keamanan Ekstra: Tolak jika petugas Lapas A mencoba edit URL WBP dari Lapas B
        if ($user->upt_id && $wbp->upt_id !== $user->upt_id) {
            abort(403, 'Akses Ditolak! Anda tidak dapat mengedit data Lapas lain.');
        }

        // Logic dropdown sama seperti di Create()
        $upts = $user->upt_id 
            ? Upt::where('id', $user->upt_id)->get() 
            : Upt::where('is_active', true)->get();

        $sels = Sel::with('blok')
            ->when($user->upt_id, function($query) use ($user) {
                $query->whereHas('blok', function($q) use ($user) {
                    $q->where('upt_id', $user->upt_id);
                });
            })->get();

        return Inertia::render('admin/datamaster/wbp/Edit', [
            'wbp' => $wbp,
            'upts' => $upts,
            'jenis_kejahatans' => JenisKejahatan::all(),
            'sels' => $sels
        ]);
    }

    public function update(Request $request, Wbp $wbp)
    {
        $wbp->update($request->validate([
            'upt_id' => 'required|exists:upts,id',
            'no_reg_instansi' => 'required|string|max:255',
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'jenis_kejahatan_id' => 'required|exists:jenis_kejahatans,id',
            'sel_id' => 'required|exists:sels,id',
            'is_aktif' => 'required|boolean',
        ]));

        return redirect()->route('wbps.index')->with('success', 'Data WBP berhasil diperbarui.');
    }

    public function destroy(Wbp $wbp)
    {
        $wbp->delete();
        return redirect()->route('wbps.index')->with('success', 'Data WBP berhasil dihapus.');
    }
}