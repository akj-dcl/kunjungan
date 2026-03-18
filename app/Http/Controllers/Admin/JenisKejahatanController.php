<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JenisKejahatan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JenisKejahatanController extends Controller
{
    public function index(Request $request)
    {
        $kejahatans = JenisKejahatan::when($request->search, function ($query, $search) {
                $query->where('nama_kejahatan', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->withQueryString();

        // Pastikan folder di Vue-nya nanti bernama "JenisKejahatan"
        return Inertia::render('admin/datamaster/jeniskejahatan/Index', [
            'kejahatans' => $kejahatans,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/datamaster/jeniskejahatan/Create');
    }

    public function store(Request $request)
    {
        JenisKejahatan::create($request->validate([
            'nama_kejahatan' => 'required|string|max:255',
        ]));

        return redirect()->route('jenis-kejahatans.index')->with('success', 'Jenis Kejahatan berhasil ditambahkan.');
    }

    public function edit(JenisKejahatan $jenis_kejahatan) // Variabel otomatis dari Laravel
    {
        return Inertia::render('admin/datamaster/jeniskejahatan/Edit', ['kejahatan' => $jenis_kejahatan]);
    }

    public function update(Request $request, JenisKejahatan $jenis_kejahatan)
    {
        $jenis_kejahatan->update($request->validate([
            'nama_kejahatan' => 'required|string|max:255',
        ]));

        return redirect()->route('jenis-kejahatans.index')->with('success', 'Jenis Kejahatan berhasil diperbarui.');
    }

    public function destroy(JenisKejahatan $jenis_kejahatan)
    {
        $jenis_kejahatan->delete();
        return redirect()->route('jenis-kejahatans.index')->with('success', 'Jenis Kejahatan berhasil dihapus.');
    }
}