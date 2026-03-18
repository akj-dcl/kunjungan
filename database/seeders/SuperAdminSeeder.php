<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil akun user kamu (Asumsinya akun kamu adalah user pertama dengan ID 1)
        $user = User::find(1); 

        if ($user) {
            // 2. Buat Role Super Admin
            $role = Role::firstOrCreate(['name' => 'Super Admin']);

            // 3. Berikan semua permissions yang ada di database ke role ini
            $permissions = Permission::pluck('id', 'id')->all();
            $role->syncPermissions($permissions);

            // 4. Jadikan akun kamu sebagai Super Admin
            $user->assignRole([$role->id]);
            
            $this->command->info('User pertama berhasil jadi Super Admin!');
        } else {
            $this->command->error('User dengan ID 1 tidak ditemukan. Bikin akun dulu di menu register!');
        }
    }
}