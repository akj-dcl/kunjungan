<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pengunjung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image; // Import library kompresi

class RegisterPengunjungController extends Controller
{
    public function create()
    {
        return Inertia::render('auth/RegisterPengunjung');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'nullable|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'jenis_kelamin' => 'required|string',
            'no_ktp' => 'required|string|unique:pengunjungs',
            'no_hp' => 'required|string',
            'alamat' => 'required|string',
            'foto_diri' => 'required|file|mimes:jpeg,png,jpg,pdf|max:1024',
            'foto_ktp' => 'required|file|mimes:jpeg,png,jpg,pdf|max:1024',
        ], [
            'foto_diri.max' => 'Ukuran foto diri maksimal adalah 1 MB.',
            'foto_diri.mimes' => 'Format foto diri harus berupa JPEG, PNG, JPG, atau PDF.',
            'foto_ktp.max' => 'Ukuran foto KTP maksimal adalah 1 MB.',
            'foto_ktp.mimes' => 'Format foto KTP harus berupa JPEG, PNG, JPG, atau PDF.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // ==========================================
        // LOGIKA KOMPRESI GAMBAR (Intervention Image)
        // ==========================================
        $prosesFile = function ($file, $folder) {
            // Jika file adalah PDF, simpan normal saja tanpa dikompres
            if (strtolower($file->getClientOriginalExtension()) === 'pdf') {
                return $file->store($folder, 'public');
            }

            // Jika Gambar, buat nama file acak baru dan paksa formatnya jadi .jpg
            $namaFile = $folder . '/' . Str::random(40) . '.jpg'; 
            
            // Baca gambar, perkecil ukurannya (maksimal lebar 800px), dan kompres kualitasnya 75%
            $gambar = Image::read($file)
                ->scaleDown(width: 800)
                ->toJpeg(quality: 75);

            // Simpan gambar yang sudah dikompres ke storage public
            Storage::disk('public')->put($namaFile, (string) $gambar);

            return $namaFile;
        };

        // Jalankan fungsi kompresi untuk kedua foto
        $fotoDiriPath = $prosesFile($request->file('foto_diri'), 'pengunjung/foto_diri');
        $fotoKtpPath = $prosesFile($request->file('foto_ktp'), 'pengunjung/foto_ktp');
        // ==========================================

        Pengunjung::create([
            'user_id' => $user->id,
            'jenis_kelamin' => $request->jenis_kelamin,
            'no_ktp' => $request->no_ktp,
            'no_hp' => $request->no_hp,
            'alamat' => $request->alamat,
            'foto_diri' => $fotoDiriPath,
            'foto_ktp' => $fotoKtpPath,
        ]);

        // Berikan role 'Pengunjung'
        $role = Role::firstOrCreate(['name' => 'Pengunjung']);
        $user->assignRole($role);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}