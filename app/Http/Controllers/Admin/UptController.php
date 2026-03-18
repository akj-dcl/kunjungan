<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kanwil;
use App\Models\Upt;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UptController extends Controller
{
    public function index(Request $request)
    {
        $upts = Upt::with('kanwil')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/datamaster/upt/Index', [
            'upts' => $upts,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        $kanwils = Kanwil::where('is_active', true)->get();
        return Inertia::render('admin/datamaster/upt/Create', ['kanwils' => $kanwils]);
    }

    public function store(Request $request)
    {
        Upt::create($request->validate([
            'kanwil_id' => 'required|exists:kanwils,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]));

        return redirect()->route('upts.index')->with('success', 'UPT berhasil ditambahkan.');
    }

    public function edit(Upt $upt)
    {
        $kanwils = Kanwil::where('is_active', true)->get();
        return Inertia::render('admin/datamaster/Upt/Edit', [
            'upt' => $upt,
            'kanwils' => $kanwils
        ]);
    }

    public function update(Request $request, Upt $upt)
    {
        $upt->update($request->validate([
            'kanwil_id' => 'required|exists:kanwils,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]));

        return redirect()->route('upts.index')->with('success', 'UPT berhasil diperbarui.');
    }

    public function destroy(Upt $upt)
    {
        $upt->delete();
        return redirect()->route('upts.index')->with('success', 'UPT berhasil dihapus.');
    }
}