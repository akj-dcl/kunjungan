<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset/Bersihkan Cache Spatie (Wajib)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Daftar Komplit Semua Permissions
        $permissions = [
            // Hak Akses Manajemen Role
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            
            // Hak Akses Data Kanwil & UPT
            'kanwils.view', 'kanwils.create', 'kanwils.edit', 'kanwils.delete',
            'upts.view', 'upts.create', 'upts.edit', 'upts.delete',
            
            // Hak Akses Data Blok & Sel
            'bloks.view', 'bloks.create', 'bloks.edit', 'bloks.delete',
            'sels.view', 'sels.create', 'sels.edit', 'sels.delete',
            
            // Hak Akses Data WBP & Jenis Kejahatan
            'wbps.view', 'wbps.create', 'wbps.edit', 'wbps.delete',
            'jenis-kejahatans.view', 'jenis-kejahatans.create', 'jenis-kejahatans.edit', 'jenis-kejahatans.delete',
            
            // Hak Akses Pengunjung & Kunjungan
            'pengunjungs.view', 'pengunjungs.create', 'pengunjungs.edit', 'pengunjungs.delete',
            'kunjungans.view', 'kunjungans.create', 'kunjungans.edit', 'kunjungans.delete',
            
            // Hak Akses Data Akun & Scan QR
            'akun.view', 'akun.create', 'akun.edit', 'akun.delete',
            'scanqr.access',
        ];

        // Masukkan semua permission ke database
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 3. Buat Role Dasar & Berikan Akses Super Admin
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        // Berikan SEMUA permission yang ada di tabel ke Super Admin
        $superAdmin->syncPermissions(Permission::all()); 

        // Role Pengunjung wajib ada karena dipakai di logika Controller & Vue
        $pengunjung = Role::firstOrCreate(['name' => 'Pengunjung']);
        
        // (Opsional) Berikan izin awal ke pengunjung
        $pengunjung->syncPermissions([
            'kunjungans.view', 'kunjungans.create'
        ]);
    }
}