<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengunjung;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class PengunjungController extends Controller
{
    public function index(Request $request)
    {
        $pengunjungs = Pengunjung::with('user')
            ->when($request->search, function ($query, $search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                })->orWhere('no_ktp', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/datamaster/pengunjung/Index', [
            'pengunjungs' => $pengunjungs,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/datamaster/pengunjung/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
            'jenis_kelamin' => 'required|string',
            'no_ktp' => 'required|string|unique:pengunjungs',
            'no_hp' => 'required|string',
            'alamat' => 'required|string',
            'foto_diri' => 'required|image|max:5042',
            'foto_ktp' => 'required|image|max:5042',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Beri role Pengunjung
        $role = Role::firstOrCreate(['name' => 'Pengunjung']);
        $user->assignRole($role);

        $fotoDiri = $request->file('foto_diri')->store('pengunjung/foto_diri', 'public');
        $fotoKtp = $request->file('foto_ktp')->store('pengunjung/foto_ktp', 'public');

        Pengunjung::create([
            'user_id' => $user->id,
            'jenis_kelamin' => $request->jenis_kelamin,
            'no_ktp' => $request->no_ktp,
            'no_hp' => $request->no_hp,
            'alamat' => $request->alamat,
            'foto_diri' => $fotoDiri,
            'foto_ktp' => $fotoKtp,
        ]);

        return redirect()->route('pengunjungs.index')->with('success', 'Data Pengunjung berhasil ditambahkan.');
    }

    public function edit(Pengunjung $pengunjung)
    {
        $pengunjung->load('user');
        return Inertia::render('admin/datamaster/pengunjung/Edit', ['pengunjung' => $pengunjung]);
    }

    public function update(Request $request, Pengunjung $pengunjung)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $pengunjung->user_id,
            'email' => 'required|string|email|max:255',
            'jenis_kelamin' => 'required|string',
            'no_ktp' => 'required|string|unique:pengunjungs,no_ktp,' . $pengunjung->id,
            'no_hp' => 'required|string',
            'alamat' => 'required|string',
            'foto_diri' => 'nullable|image|max:5042',
            'foto_ktp' => 'nullable|image|max:5042',
        ]);

        // Update User
        $pengunjung->user->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
        ]);

        if ($request->password) {
            $pengunjung->user->update(['password' => Hash::make($request->password)]);
        }

        // Update Foto jika ada yang baru
        if ($request->hasFile('foto_diri')) {
            if ($pengunjung->foto_diri) Storage::disk('public')->delete($pengunjung->foto_diri);
            $pengunjung->foto_diri = $request->file('foto_diri')->store('pengunjung/foto_diri', 'public');
        }

        if ($request->hasFile('foto_ktp')) {
            if ($pengunjung->foto_ktp) Storage::disk('public')->delete($pengunjung->foto_ktp);
            $pengunjung->foto_ktp = $request->file('foto_ktp')->store('pengunjung/foto_ktp', 'public');
        }

        $pengunjung->update([
            'jenis_kelamin' => $request->jenis_kelamin,
            'no_ktp' => $request->no_ktp,
            'no_hp' => $request->no_hp,
            'alamat' => $request->alamat,
        ]);

        return redirect()->route('pengunjungs.index')->with('success', 'Data Pengunjung berhasil diperbarui.');
    }

    public function destroy(Pengunjung $pengunjung)
    {
        // Hapus file foto
        if ($pengunjung->foto_diri) Storage::disk('public')->delete($pengunjung->foto_diri);
        if ($pengunjung->foto_ktp) Storage::disk('public')->delete($pengunjung->foto_ktp);

        // Hapus User (otomatis menghapus pengunjung jika cascade, tapi kita hapus user-nya agar bersih)
        $user = $pengunjung->user;
        if ($user) $user->delete();

        return redirect()->route('pengunjungs.index')->with('success', 'Data Pengunjung berhasil dihapus.');
    }
}