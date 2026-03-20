<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3'

type User = { name: string; email: string; username: string; }
type Pengunjung = {
  id: number
  jenis_kelamin: string
  no_ktp: string
  no_hp: string
  alamat: string
  foto_diri: string | null
  foto_ktp: string | null
  user: User
}

const props = defineProps<{ pengunjung: Pengunjung }>()

const form = useForm({
  _method: 'PUT',
  name: props.pengunjung.user?.name ?? '',
  username: props.pengunjung.user?.username ?? '',
  email: props.pengunjung.user?.email ?? '',
  password: '',
  jenis_kelamin: props.pengunjung.jenis_kelamin ?? 'Laki-laki',
  no_ktp: props.pengunjung.no_ktp ?? '',
  no_hp: props.pengunjung.no_hp ?? '', // Cukup begini saja
  alamat: props.pengunjung.alamat ?? '',
  foto_diri: null as File | null,
  foto_ktp: null as File | null,
})

// Fungsi helper untuk validasi file di sisi frontend
const validateFile = (file: File, fieldName: 'foto_diri' | 'foto_ktp') => {
    // Cek Ukuran (1MB = 1048576 bytes)
    if (file.size > 5048576) {
        form.errors[fieldName] = `Ukuran file maksimal adalah 5 MB.`;
        return false;
    }
    // Cek Tipe File
    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    if (!validTypes.includes(file.type)) {
        form.errors[fieldName] = `Format file harus JPEG, PNG, atau PDF.`;
        return false;
    }
    
    // Jika lolos, bersihkan pesan error (kalau ada)
    form.errors[fieldName] = '';
    return true;
};

const handleNoHpInput = (e: Event) => {
    let val = (e.target as HTMLInputElement).value;
    
    // Ambil hanya angka dari inputan
    let digits = val.replace(/\D/g, '');

    // Hilangkan 62 atau 0 di depan jika ada
    if (digits.startsWith('62')) {
        digits = digits.substring(2);
    } else if (digits.startsWith('0')) {
        digits = digits.substring(1);
    }

    // Gabungkan dengan +62 dan Spasi
    form.no_hp = digits.length > 0 ? '+62 ' + digits : '+62 ';
};

// Set nilai default awal
if (!form.no_hp || form.no_hp === '') {
    form.no_hp = '+62 ';
}

// ==========================================
// 2. FUNGSI KOMPRESI GAMBAR DI FRONTEND (ANTI LEMOT)
// ==========================================
const compressImage = (file: File, maxWidth = 800): Promise<File> => {
    return new Promise((resolve) => {
        // Kalau file PDF, jangan dikompres
        if (file.type === 'application/pdf') return resolve(file);

        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target?.result as string;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;

                // Perkecil dimensi gambar
                if (width > maxWidth) {
                    height = Math.round((height * maxWidth) / width);
                    width = maxWidth;
                }

                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx?.drawImage(img, 0, 0, width, height);

                // Ubah kembali jadi file dengan kualitas 75%
                canvas.toBlob((blob) => {
                    if (blob) {
                        const compressedFile = new File([blob], file.name, {
                            type: 'image/jpeg',
                            lastModified: Date.now(),
                        });
                        resolve(compressedFile);
                    } else {
                        resolve(file); // Fallback
                    }
                }, 'image/jpeg', 0.75);
            };
        };
    });
};

// Update handle foto untuk menjalankan kompresi dulu!
const handleFotoDiri = async (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        let file = target.files[0];
        if (validateFile(file, 'foto_diri')) {
            form.foto_diri = await compressImage(file); // Proses kompresi berjalan di sini
        } else {
            target.value = ''; form.foto_diri = null;
        }
    }
};

const handleFotoKtp = async (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        let file = target.files[0];
        if (validateFile(file, 'foto_ktp')) {
            form.foto_ktp = await compressImage(file); // Proses kompresi berjalan di sini
        } else {
            target.value = ''; form.foto_ktp = null;
        }
    }
};
function submit() {
  form.post(`/admin/pengunjungs/${props.pengunjung.id}`)
}
</script>

<template>
  <Head title="Edit Pengunjung" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 bg-background text-foreground">
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight">Edit Pengunjung</h1>
          <p class="text-sm text-muted-foreground">Perbarui data pengunjung.</p>
        </div>
        <Link href="/admin/pengunjungs" class="inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground">Kembali</Link>
      </div>

      <div class="rounded-xl border border-border bg-card text-card-foreground shadow-sm">
        <form @submit.prevent="submit" class="p-6 space-y-6">
          <div class="grid gap-6 md:grid-cols-2">
            
            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Nama Lengkap</label>
              <input v-model="form.name" type="text" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:opacity-50" />
              <div v-if="form.errors.name" class="text-sm font-medium text-destructive">{{ form.errors.name }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Username Login *</label>
              <input v-model="form.username" type="text" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:opacity-50" required />
              <div v-if="form.errors.username" class="text-sm font-medium text-destructive">{{ form.errors.username }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Email Akun (Opsional)</label>
              <input v-model="form.email" type="email" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:opacity-50" />
              <div v-if="form.errors.email" class="text-sm font-medium text-destructive">{{ form.errors.email }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Password Baru (Opsional)</label>
              <input v-model="form.password" type="password" placeholder="Kosongkan jika tidak ingin mengubah password" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:opacity-50" />
              <div v-if="form.errors.password" class="text-sm font-medium text-destructive">{{ form.errors.password }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Jenis Kelamin</label>
              <select v-model="form.jenis_kelamin" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:opacity-50">
                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>
              </select>
              <div v-if="form.errors.jenis_kelamin" class="text-sm font-medium text-destructive">{{ form.errors.jenis_kelamin }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Nomor KTP (NIK)</label>
              <input v-model="form.no_ktp" type="text" maxlength="16" @input="form.no_ktp = form.no_ktp.replace(/\D/g, '')" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:opacity-50" />
              <div v-if="form.errors.no_ktp" class="text-sm font-medium text-destructive">{{ form.errors.no_ktp }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Nomor HP</label>
              <input v-model="form.no_hp" type="text" @input="form.no_hp = form.no_hp.replace(/\D/g, '')" placeholder="08123456789" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:opacity-50" />
              <div v-if="form.errors.no_hp" class="text-sm font-medium text-destructive">{{ form.errors.no_hp }}</div>
            </div>

            <div class="md:col-span-2 space-y-2">
              <label class="text-sm font-medium leading-none">Alamat Lengkap</label>
              <textarea v-model="form.alamat" rows="3" class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:opacity-50"></textarea>
              <div v-if="form.errors.alamat" class="text-sm font-medium text-destructive">{{ form.errors.alamat }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Foto Diri Baru (Opsional)</label>
              <div class="flex items-center gap-4">
                <img v-if="pengunjung.foto_diri" :src="`/storage/${pengunjung.foto_diri}`" class="h-16 w-16 rounded object-cover border" />
                <input type="file" @change="handleFotoDiri" accept="image/*" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-muted-foreground file:border-0 file:bg-transparent file:text-sm file:font-medium" />
              </div>
              <div v-if="form.errors.foto_diri" class="text-sm font-medium text-destructive">{{ form.errors.foto_diri }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Foto KTP Baru (Opsional)</label>
              <div class="flex items-center gap-4">
                <img v-if="pengunjung.foto_ktp" :src="`/storage/${pengunjung.foto_ktp}`" class="h-16 w-24 rounded object-cover border" />
                <input type="file" @change="handleFotoKtp" accept="image/*" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-muted-foreground file:border-0 file:bg-transparent file:text-sm file:font-medium" />
              </div>
              <div v-if="form.errors.foto_ktp" class="text-sm font-medium text-destructive">{{ form.errors.foto_ktp }}</div>
            </div>

          </div>
          <div class="flex justify-end pt-4 border-t mt-6">
            <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center rounded-md bg-primary px-8 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 disabled:opacity-50">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>