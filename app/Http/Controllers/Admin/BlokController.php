<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blok;
use App\Models\Upt;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlokController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user(); // Ambil user yang login

        $bloks = Blok::with('upt')
            // JURUS ISOLASI: Filter blok hanya milik UPT user yang login
            ->when($user->upt_id, function ($query) use ($user) {
                $query->where('upt_id', $user->upt_id);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('nama_blok', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/datamaster/blok/Index', [
            'bloks' => $bloks,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        $user = auth()->user();

        // JIKA USER PUNYA UPT_ID, AMBIL 1 UPT SAJA. JIKA TIDAK, AMBIL SEMUA.
        $upts = $user->upt_id 
            ? Upt::where('id', $user->upt_id)->get() 
            : Upt::where('is_active', true)->get();

        return Inertia::render('admin/datamaster/blok/Create', ['upts' => $upts]);
    }

    public function store(Request $request)
    {
        Blok::create($request->validate([
            'upt_id' => 'required|exists:upts,id',
            'nama_blok' => 'required|string|max:255',
        ]));

        return redirect()->route('bloks.index')->with('success', 'Blok berhasil ditambahkan.');
    }

    public function edit(Blok $blok)
    {
        $user = auth()->user();

        // Keamanan Ekstra: Cegah petugas edit blok dari Lapas lain lewat URL
        if ($user->upt_id && $blok->upt_id !== $user->upt_id) {
            abort(403, 'Akses Ditolak! Anda tidak dapat mengedit data blok Lapas lain.');
        }

        // Logic dropdown sama seperti di Create()
        $upts = $user->upt_id 
            ? Upt::where('id', $user->upt_id)->get() 
            : Upt::where('is_active', true)->get();

        return Inertia::render('admin/datamaster/blok/Edit', [
            'blok' => $blok,
            'upts' => $upts
        ]);
    }

    public function update(Request $request, Blok $blok)
    {
        $blok->update($request->validate([
            'upt_id' => 'required|exists:upts,id',
            'nama_blok' => 'required|string|max:255',
        ]));

        return redirect()->route('bloks.index')->with('success', 'Blok berhasil diperbarui.');
    }

    public function destroy(Blok $blok)
    {
        $blok->delete();
        return redirect()->route('bloks.index')->with('success', 'Blok berhasil dihapus.');
    }
}