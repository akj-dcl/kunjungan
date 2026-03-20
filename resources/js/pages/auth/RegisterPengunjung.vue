<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    jenis_kelamin: '',
    no_ktp: '',
    no_hp: '',
    alamat: '',
    foto_diri: null as File | null,
    foto_ktp: null as File | null,
});

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

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
    // Inertia akan otomatis mengirim sebagai FormData jika ada file (foto)
    form.post('/register-pengunjung');
};
</script>

<template>
    <Head title="Buat Akun Pengunjung - PUSDAPAS" />

    <div class="min-h-screen flex flex-col bg-gray-50 dark:bg-gray-900 font-sans text-gray-900 dark:text-gray-100 relative overflow-hidden">
        
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-blue-100 via-gray-50 to-white dark:from-blue-900/20 dark:via-gray-900 dark:to-gray-900 -z-10"></div>

        <header class="w-full bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-700 z-10 sticky top-0">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                    <div class="w-8 h-8 bg-blue-700 rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-sm">
                        K
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-md tracking-tight text-blue-900 dark:text-blue-400 leading-tight">E-Kunjungan</span>
                    </div>
                </Link>
                <Link href="/login" class="text-sm font-semibold text-gray-600 hover:text-blue-700 dark:text-gray-300 transition-colors">
                    Sudah punya akun? Masuk
                </Link>
            </div>
        </header>

        <div class="flex-grow flex items-center justify-center p-4 sm:p-8 z-10">
            <div class="w-full max-w-3xl bg-white dark:bg-gray-800 rounded-2xl shadow-xl ring-1 ring-gray-900/5 dark:ring-white/10 overflow-hidden">
                
                <div class="px-6 py-8 sm:p-10 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white text-center">Registrasi Akun Pengunjung</h1>
                    <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400 mt-2 text-center max-w-xl mx-auto">
                        Lengkapi data diri Anda di bawah ini dengan data yang sebenar-benarnya sesuai KTP untuk keperluan validasi kunjungan Lapas.
                    </p>
                </div>

                <form @submit.prevent="submit" class="px-6 py-8 sm:p-10 space-y-8">
                    
                    <div>
                        <h3 class="text-sm font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">1</span> 
                            Informasi Akun Login
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Nama Lengkap *</label>
                                <input v-model="form.name" type="text" class="w-full h-11 rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 shadow-sm" required />
                                <div v-if="form.errors.name" class="text-xs font-medium text-red-500 mt-1">{{ form.errors.name }}</div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Username Login *</label>
                                <input v-model="form.username" type="text" class="w-full h-11 rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 shadow-sm" required />
                                <div v-if="form.errors.username" class="text-xs font-medium text-red-500 mt-1">{{ form.errors.username }}</div>
                            </div>

                            <div class="space-y-1 md:col-span-2">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Email (Opsional)</label>
                                <input v-model="form.email" type="email" class="w-full h-11 rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 shadow-sm" />
                                <div v-if="form.errors.email" class="text-xs font-medium text-red-500 mt-1">{{ form.errors.email }}</div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Password *</label>
                                <div class="relative">
                                    <input v-model="form.password" :type="showPassword ? 'text' : 'password'" class="w-full h-11 rounded-lg border-gray-300 bg-white px-3 pr-10 text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 shadow-sm" required />
                                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                        <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                    </button>
                                </div>
                                <div v-if="form.errors.password" class="text-xs font-medium text-red-500 mt-1">{{ form.errors.password }}</div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Konfirmasi Password *</label>
                                <div class="relative">
                                    <input v-model="form.password_confirmation" :type="showPasswordConfirmation ? 'text' : 'password'" class="w-full h-11 rounded-lg border-gray-300 bg-white px-3 pr-10 text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 shadow-sm" required />
                                    <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                        <svg v-if="showPasswordConfirmation" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                    </button>
                                </div>
                                <div v-if="form.errors.password_confirmation" class="text-xs font-medium text-red-500 mt-1">{{ form.errors.password_confirmation }}</div>
                            </div>
                        </div>
                    </div>

                    <hr class="border-gray-100 dark:border-gray-800" />

                    <div>
                        <h3 class="text-sm font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">2</span> 
                            Data Diri (KTP)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Nomor KTP (NIK) *</label>
                                <input v-model="form.no_ktp" type="text" maxlength="16" @input="form.no_ktp = form.no_ktp.replace(/\D/g, '')" placeholder="16 Digit NIK" class="w-full h-11 rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 shadow-sm" required />
                                <div v-if="form.errors.no_ktp" class="text-xs font-medium text-red-500 mt-1">{{ form.errors.no_ktp }}</div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Nomor HP (WhatsApp) *</label>
                                <input v-model="form.no_hp" type="text" @input="form.no_hp = form.no_hp.replace(/\D/g, '')" placeholder="08123456789" class="w-full h-11 rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 shadow-sm" required />
                                <div v-if="form.errors.no_hp" class="text-xs font-medium text-red-500 mt-1">{{ form.errors.no_hp }}</div>
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Kelamin *</label>
                                <div class="flex items-center space-x-6 h-10 bg-gray-50 dark:bg-gray-900/50 px-4 rounded-lg border border-gray-200 dark:border-gray-700 w-fit">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" v-model="form.jenis_kelamin" value="Laki-laki" class="text-blue-600 border-gray-300 focus:ring-blue-500 w-4 h-4" required>
                                        <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Laki-laki</span>
                                    </label>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" v-model="form.jenis_kelamin" value="Perempuan" class="text-blue-600 border-gray-300 focus:ring-blue-500 w-4 h-4" required>
                                        <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Perempuan</span>
                                    </label>
                                </div>
                                <div v-if="form.errors.jenis_kelamin" class="text-xs font-medium text-red-500 mt-1">{{ form.errors.jenis_kelamin }}</div>
                            </div>

                            <div class="space-y-1 md:col-span-2">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Alamat Lengkap Sesuai KTP *</label>
                                <textarea v-model="form.alamat" rows="3" class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 shadow-sm" required></textarea>
                                <div v-if="form.errors.alamat" class="text-xs font-medium text-red-500 mt-1">{{ form.errors.alamat }}</div>
                            </div>
                        </div>
                    </div>

                    <hr class="border-gray-100 dark:border-gray-800" />

                    <div>
                        <h3 class="text-sm font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">3</span> 
                            Berkas Lampiran
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1 bg-blue-50/50 dark:bg-blue-900/10 p-4 rounded-xl border border-blue-100 dark:border-blue-900/30">
                                <label class="text-sm font-bold text-gray-800 dark:text-gray-200">📷 Upload Foto Diri *</label>
                                <p class="text-xs text-gray-500 mb-2">Pastikan wajah terlihat jelas. (Maks: 1MB)</p>
                                <input type="file" @change="handleFotoDiri" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700 hover:file:cursor-pointer" required />
                                <div v-if="form.errors.foto_diri" class="text-xs font-medium text-red-500 mt-2">{{ form.errors.foto_diri }}</div>
                            </div>

                            <div class="space-y-1 bg-blue-50/50 dark:bg-blue-900/10 p-4 rounded-xl border border-blue-100 dark:border-blue-900/30">
                                <label class="text-sm font-bold text-gray-800 dark:text-gray-200">💳 Upload Foto KTP *</label>
                                <p class="text-xs text-gray-500 mb-2">Foto KTP asli, tulisan harus terbaca. (Maks: 1MB)</p>
                                <input type="file" @change="handleFotoKtp" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700 hover:file:cursor-pointer" required />
                                <div v-if="form.errors.foto_ktp" class="text-xs font-medium text-red-500 mt-2">{{ form.errors.foto_ktp }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <Link href="/" class="text-sm font-medium text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                            ← Batal dan Kembali
                        </Link>
                        <button type="submit" :disabled="form.processing" class="w-full sm:w-auto inline-flex justify-center items-center px-8 py-3 bg-blue-700 rounded-lg font-bold text-sm text-white shadow-lg shadow-blue-700/30 hover:bg-blue-800 focus:ring-4 focus:ring-blue-500/50 transition-all" :class="{ 'opacity-50 cursor-not-allowed': form.processing }">
                            <span v-if="form.processing">Memproses Data...</span>
                            <span v-else>Daftar Akun Sekarang</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
        
        <footer class="py-6 text-center text-xs text-gray-500 dark:text-gray-400 bg-transparent z-10">
            <p>&copy; {{ new Date().getFullYear() }} Kementerian Imigrasi dan Pemasyarakatan RI.</p>
        </footer>
    </div>
</template>