<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sel;
use App\Models\Blok;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SelController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user(); // Ambil user yang login

        // Kita load juga relasi blok dan upt-nya agar bisa ditampilkan di tabel
        $sels = Sel::with('blok.upt')
            // JURUS ISOLASI: Filter sel yang blok-nya berada di UPT milik user
            ->when($user->upt_id, function ($query) use ($user) {
                $query->whereHas('blok', function ($q) use ($user) {
                    $q->where('upt_id', $user->upt_id);
                });
            })
            ->when($request->search, function ($query, $search) {
                $query->where('nama_sel', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/datamaster/sel/Index', [
            'sels' => $sels,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        $user = auth()->user();

        // JIKA USER PUNYA UPT_ID, AMBIL BLOK DARI UPT TERSEBUT SAJA. JIKA TIDAK, AMBIL SEMUA.
        $bloks = Blok::with('upt')
            ->when($user->upt_id, function ($query) use ($user) {
                $query->where('upt_id', $user->upt_id);
            })->get();

        return Inertia::render('admin/datamaster/sel/Create', ['bloks' => $bloks]);
    }

    public function store(Request $request)
    {
        Sel::create($request->validate([
            'blok_id' => 'required|exists:bloks,id',
            'nama_sel' => 'required|string|max:255',
        ]));

        return redirect()->route('sels.index')->with('success', 'Lokasi Sel berhasil ditambahkan.');
    }

    public function edit(Sel $sel)
    {
        $user = auth()->user();

        // Keamanan Ekstra: Cegah petugas edit sel dari Lapas lain lewat URL
        // Karena $sel belum memuat relasi 'blok', kita load dulu
        $sel->load('blok');
        if ($user->upt_id && $sel->blok->upt_id !== $user->upt_id) {
            abort(403, 'Akses Ditolak! Anda tidak dapat mengedit data sel Lapas lain.');
        }

        // Logic dropdown sama seperti di Create()
        $bloks = Blok::with('upt')
            ->when($user->upt_id, function ($query) use ($user) {
                $query->where('upt_id', $user->upt_id);
            })->get();

        return Inertia::render('admin/datamaster/sel/Edit', [
            'sel' => $sel,
            'bloks' => $bloks
        ]);
    }

    public function update(Request $request, Sel $sel)
    {
        $sel->update($request->validate([
            'blok_id' => 'required|exists:bloks,id',
            'nama_sel' => 'required|string|max:255',
        ]));

        return redirect()->route('sels.index')->with('success', 'Lokasi Sel berhasil diperbarui.');
    }

    public function destroy(Sel $sel)
    {
        $sel->delete();
        return redirect()->route('sels.index')->with('success', 'Lokasi Sel berhasil dihapus.');
    }
}