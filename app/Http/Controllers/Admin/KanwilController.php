<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kanwil;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KanwilController extends Controller
{
    public function index(Request $request)
    {
        $kanwils = Kanwil::when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/datamaster/kanwil/Index', [
            'kanwils' => $kanwils,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/datamaster/kanwil/Create');
    }

    public function store(Request $request)
    {
        Kanwil::create($request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]));

        return redirect()->route('kanwils.index')->with('success', 'Kanwil berhasil ditambahkan.');
    }

    public function edit(Kanwil $kanwil)
    {
        return Inertia::render('admin/datamaster/kanwil/Edit', ['kanwil' => $kanwil]);
    }

    public function update(Request $request, Kanwil $kanwil)
    {
        $kanwil->update($request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]));

        return redirect()->route('kanwils.index')->with('success', 'Kanwil berhasil diperbarui.');
    }

    public function destroy(Kanwil $kanwil)
    {
        $kanwil->delete();
        return redirect()->route('kanwils.index')->with('success', 'Kanwil berhasil dihapus.');
    }
}
