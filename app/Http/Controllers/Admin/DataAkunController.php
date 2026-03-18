<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Upt;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DataAkunController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['roles', 'upt'])
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'Pengunjung');
            })
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('nip', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/datamaster/dataakun/Index', [
            'users' => $users,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        $roles = Role::where('name', '!=', 'Pengunjung')->get();
        
        // Ambil data UPT
        // Catatan: Pastikan kolomnya benar is_active. Kalau error, ganti jadi Upt::all();
        $upts = Upt::where('is_active', true)->get(); 

        // INI YANG BIKIN ERROR KEMARIN: 'upts' lupa dimasukkan ke dalam array!
        return Inertia::render('admin/datamaster/dataakun/Create', [
            'roles' => $roles,
            'upts' => $upts 
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'required|string|unique:users,nip',
            'jabatan' => 'required|string',
            'role' => 'required|exists:roles,name',
            'password' => 'required|string|min:8',
            'upt_id' => 'nullable|exists:upts,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'nip' => $request->nip,
            'username' => $request->nip, 
            'jabatan' => $request->jabatan,
            'upt_id' => $request->upt_id,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        return redirect()->route('data-akun.index')->with('success', 'Akun pegawai berhasil dibuat.');
    }

    // ... fungsi index, create, store biarkan saja ...

    // 1. UBAH FUNGSI EDIT
    public function edit($id) // Ubah parameter jadi $id
    {
        $user = User::findOrFail($id); // Cari data manual berdasarkan ID

        $user->load('roles');
        $roles = Role::where('name', '!=', 'Pengunjung')->get();
        $upts = Upt::where('is_active', true)->get();

        return Inertia::render('admin/datamaster/dataakun/Edit', [
            'user' => $user,
            'roles' => $roles,
            'upts' => $upts,
            'userRole' => $user->roles->first()?->name
        ]);
    }

    // 2. UBAH FUNGSI UPDATE
    public function update(Request $request, $id) // Ubah parameter jadi $id
    {
        $user = User::findOrFail($id); // Cari data manual berdasarkan ID

        $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'required|string|unique:users,nip,' . $user->id,
            'jabatan' => 'required|string',
            'role' => 'required|exists:roles,name',
            'upt_id' => 'nullable|exists:upts,id',
        ]);

        $user->update([
            'name' => $request->name,
            'nip' => $request->nip,
            'username' => $request->nip, 
            'jabatan' => $request->jabatan,
            'upt_id' => $request->upt_id,
        ]);

        if ($request->password) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->syncRoles($request->role);

        return redirect()->route('data-akun.index')->with('success', 'Akun pegawai berhasil diperbarui.');
    }

    // 3. UBAH FUNGSI DESTROY
    public function destroy($id) // Ubah parameter jadi $id
    {
        $user = User::findOrFail($id); // Cari data manual berdasarkan ID
        
        $user->delete();
        return redirect()->route('data-akun.index')->with('success', 'Akun berhasil dihapus.');
    }
}