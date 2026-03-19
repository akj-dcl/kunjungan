<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { debounce } from 'lodash';

// Data Props dari Controller
const props = defineProps<{
    upts: { id: number, name: string }[]
}>();

// Form Data Utama (Beri default 0 bukan string kosong untuk angka)
const form = useForm({
    upt_id: props.upts?.[0]?.id ? String(props.upts[0].id) : '',
    wbp_id: '',
    tanggal_kunjungan: '',
    waktu_kunjungan: '',
    pengikut_laki: 0,
    pengikut_perempuan: 0,
    pengikut_anak: 0,
    total_pengikut: 1, // Default 1 (dirinya sendiri)
    barang_bawaan: [] as Array<{ jenis_barang: string, jumlah: number, keterangan: string }>
});

// Otomatis Hitung Total Pengikut (+1 karena Pendaftar dihitung)
const totalPengikut = computed(() => {
    // Hitung pengikut yang dibawa
    const jumlahBawaan = Number(form.pengikut_laki) + Number(form.pengikut_perempuan) + Number(form.pengikut_anak);
    // Tambah 1 (si pengunjung yang daftar form)
    const total = jumlahBawaan + 1; 
    
    form.total_pengikut = total; // update form
    return total;
});

// Fungsi Dinamis Barang Bawaan
const tambahBarang = () => {
    form.barang_bawaan.push({ jenis_barang: '', jumlah: 1, keterangan: '' });
};
const hapusBarang = (index: number) => {
    form.barang_bawaan.splice(index, 1);
};

// ==========================================
// LOGIKA PENCARIAN WBP (DIPERBARUI)
// ==========================================
const searchQuery = ref('');
const searchResults = ref<any[]>([]);
const isSearching = ref(false);
const selectedWbpDetail = ref<any>(null);

// Gunakan Watch + Debounce (Lebih stabil dari @input)
watch(
    searchQuery,
    debounce(async (newQuery: string) => {
        if (selectedWbpDetail.value && searchQuery.value.includes(selectedWbpDetail.value.nama)) {
            return;
        }
        if (newQuery.length < 2) {
            searchResults.value = [];
            isSearching.value = false;
            return;
        }
        if (!form.upt_id) {
            alert('Silakan pilih UPT Lapas terlebih dahulu sebelum mencari WBP!');
            searchResults.value = [];
            isSearching.value = false;
            return;
        }
        isSearching.value = true;
        try {
            const response = await axios.get(`/admin/api/search-wbp?q=${newQuery}&upt_id=${form.upt_id}`);
            searchResults.value = response.data;
        } catch (error) {
            console.error('Gagal mengambil data WBP:', error);
        } finally {
            isSearching.value = false;
        }
    }, 500)
);

const pilihWbp = (wbp: any) => {
    form.wbp_id = wbp.id;
    searchQuery.value = `${wbp.nama} (${wbp.no_reg_instansi})`;
    selectedWbpDetail.value = wbp;
    searchResults.value = [];
};

const submit = () => {
    // Validasi wbp_id di frontend sebelum post
    if (!form.wbp_id) {
        alert('Harap pilih WBP tujuan dari daftar pencarian terlebih dahulu!');
        return;
    }
    form.post('/admin/kunjungans');
};
</script>

<template>
    <Head title="Buat Data Kunjungan" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 bg-background text-foreground">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between mb-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Formulir Kunjungan Lapas</h1>
                    <p class="text-sm text-muted-foreground">Isi data kunjungan dan barang titipan.</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="rounded-xl border border-border bg-card shadow-sm p-6 space-y-6">
                    <h3 class="text-lg font-semibold border-b pb-2">1. Data Tujuan & Waktu</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-red-600">Pilih UPT Lapas *</label>
                            <select v-model="form.upt_id" :disabled="upts.length === 1" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                                <option value="" disabled>-- Pilih Lapas --</option>
                                <option v-for="upt in upts" :key="upt.id" :value="upt.id">{{ upt.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-red-600">Tanggal Kunjungan *</label>
                            <input v-model="form.tanggal_kunjungan" type="date" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                            <div v-if="form.errors.tanggal_kunjungan" class="text-xs font-medium text-red-500 mt-1">{{ form.errors.tanggal_kunjungan }}</div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-red-600">Pilih Sesi Kunjungan *</label>
                            <select v-model="form.waktu_kunjungan" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                                <option value="" disabled>-- Pilih Sesi --</option>
                                <option value="Sesi 1 (09.00 - 11.00)">Sesi 1 Pagi (09.00 - 11.00 WITA)</option>
                                <option value="Sesi 2 (13.00 - 15.00)">Sesi 2 Siang (13.00 - 15.00 WITA)</option>
                            </select>
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold border-b pb-2 mt-8">2. Data Warga Binaan (WBP)</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="relative space-y-2">
                            <label class="text-sm font-medium text-red-600">Cari & Pilih Nama / No. Reg WBP *</label>
                            <input 
                                v-model="searchQuery" 
                                type="text" 
                                placeholder="Ketik minimal 2 huruf..." 
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                                required
                            >
                            <div v-if="!form.wbp_id && searchQuery.length > 2 && !isSearching" class="text-xs text-red-500 mt-1 font-bold">
                                WBP belum terpilih! Klik nama WBP dari dropdown di bawah.
                            </div>
                            
                            <div v-if="isSearching" class="text-xs text-blue-600 mt-1 flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Sedang mencari di database...
                            </div>
                            
                            <ul v-if="searchResults.length > 0" class="absolute z-10 w-full bg-white border border-gray-200 mt-1 rounded-md shadow-lg max-h-60 overflow-y-auto dark:bg-gray-800 dark:border-gray-700">
                                <li 
                                    v-for="res in searchResults" 
                                    :key="res.id"
                                    @click="pilihWbp(res)"
                                    class="px-4 py-3 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-0 dark:hover:bg-gray-700 dark:border-gray-700 flex flex-col"
                                >
                                    <span class="font-bold text-sm text-gray-900 dark:text-white">{{ res.nama }}</span>
                                    <span class="text-xs text-gray-500">{{ res.no_reg_instansi }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="space-y-2 bg-gray-50 p-4 rounded-md border border-gray-200 dark:bg-gray-900/50 dark:border-gray-800">
                            <label class="text-sm font-bold text-gray-700 dark:text-gray-300">Data WBP Otomatis:</label>
                            <div class="text-sm text-gray-600 mt-2 space-y-1">
                                <p><strong>Jenis Kejahatan:</strong> {{ selectedWbpDetail?.jenis_kejahatan?.nama_kejahatan || '-' }}</p>
                                <p><strong>Lokasi Blok:</strong> {{ selectedWbpDetail?.sel?.blok?.nama_blok || '-' }}</p>
                                <p><strong>Lokasi Sel/Kamar:</strong> {{ selectedWbpDetail?.sel?.nama_sel || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold border-b pb-2 mt-8">3. Jumlah Pengikut (Yang Dibawa)</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Laki-laki</label>
                            <input v-model="form.pengikut_laki" type="number" min="0" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Perempuan</label>
                            <input v-model="form.pengikut_perempuan" type="number" min="0" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Anak-anak</label>
                            <input v-model="form.pengikut_anak" type="number" min="0" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div class="space-y-2 bg-blue-50 border border-blue-200 rounded-md p-2 text-center dark:bg-blue-900/20 dark:border-blue-800">
                            <label class="text-xs font-bold text-blue-700 dark:text-blue-300">TOTAL (+ Diri Sendiri)</label>
                            <div class="text-2xl font-black text-blue-700 dark:text-blue-300">{{ totalPengikut }}</div>
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold border-b pb-2 mt-8 flex justify-between items-center">
                        <span>4. Barang Bawaan (Titipan)</span>
                        <button type="button" @click="tambahBarang" class="text-sm bg-gray-200 text-gray-800 px-3 py-1 rounded hover:bg-gray-300 dark:bg-gray-700 dark:text-white">+ Tambah Barang</button>
                    </h3>
                    
                    <div class="space-y-3">
                        <div v-if="form.barang_bawaan.length === 0" class="text-sm text-gray-500 italic text-center p-4 border border-dashed rounded-md">
                            Tidak ada barang bawaan. Klik tombol "+ Tambah Barang" jika ada.
                        </div>
                        
                        <div v-for="(barang, index) in form.barang_bawaan" :key="index" class="flex flex-col md:flex-row gap-3 items-end border p-3 rounded-md bg-gray-50 dark:bg-gray-900/30">
                            <div class="w-full md:w-1/3">
                                <label class="text-xs font-medium mb-1 block text-red-600">Jenis Barang *</label>
                                <input v-model="barang.jenis_barang" type="text" placeholder="Misal: Makanan, Pakaian" class="w-full rounded border-gray-300 px-3 py-1.5 text-sm" required>
                            </div>
                            <div class="w-full md:w-1/6">
                                <label class="text-xs font-medium mb-1 block text-red-600">Jumlah *</label>
                                <input v-model="barang.jumlah" type="number" min="1" class="w-full rounded border-gray-300 px-3 py-1.5 text-sm" required>
                            </div>
                            <div class="w-full md:w-1/3">
                                <label class="text-xs font-medium mb-1 block">Keterangan</label>
                                <input v-model="barang.keterangan" type="text" placeholder="Misal: Warna merah, dll" class="w-full rounded border-gray-300 px-3 py-1.5 text-sm">
                            </div>
                            <div class="w-full md:w-1/6">
                                <button type="button" @click="hapusBarang(index)" class="w-full bg-red-100 text-red-600 px-3 py-1.5 rounded text-sm font-semibold hover:bg-red-200">Hapus</button>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" :disabled="form.processing" class="bg-blue-600 text-white px-8 py-3 rounded-md font-bold hover:bg-blue-700 shadow-md transition-all">
                        Simpan Data Kunjungan
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>