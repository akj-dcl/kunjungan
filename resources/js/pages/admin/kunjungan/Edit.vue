<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { debounce } from 'lodash';

const props = defineProps<{
    kunjungan: any;
    upts: { id: number, name: string }[]
}>();

// Form Data dengan Pre-fill Data Kunjungan
const form = useForm({
    _method: 'PUT', // Untuk method spoofing Laravel
    upt_id: props.kunjungan.upt_id,
    wbp_id: props.kunjungan.wbp_id,
    tanggal_kunjungan: props.kunjungan.tanggal_kunjungan,
    waktu_kunjungan: props.kunjungan.waktu_kunjungan,
    pengikut_laki: props.kunjungan.pengikut_laki,
    pengikut_perempuan: props.kunjungan.pengikut_perempuan,
    pengikut_anak: props.kunjungan.pengikut_anak,
    total_pengikut: props.kunjungan.total_pengikut,
    status: props.kunjungan.status,
    // Format barang bawaan yang lama agar sesuai form
    barang_bawaan: props.kunjungan.barang_bawaans.map((b: any) => ({
        jenis_barang: b.jenis_barang,
        jumlah: b.jumlah,
        keterangan: b.keterangan === '-' ? '' : b.keterangan
    }))
});

const totalPengikut = computed(() => {
    const jumlahBawaan = Number(form.pengikut_laki) + Number(form.pengikut_perempuan) + Number(form.pengikut_anak);
    const total = jumlahBawaan + 1; 
    form.total_pengikut = total;
    return total;
});

const tambahBarang = () => form.barang_bawaan.push({ jenis_barang: '', jumlah: 1, keterangan: '' });
const hapusBarang = (index: number | string) => form.barang_bawaan.splice(Number(index), 1);

// Logika Pencarian WBP
const searchQuery = ref('');
const searchResults = ref<any[]>([]);
const isSearching = ref(false);
const selectedWbpDetail = ref<any>(props.kunjungan.wbp); // Isi dengan data WBP asal

// Set nilai awal pencarian dengan nama WBP saat ini
onMounted(() => {
    if (props.kunjungan.wbp) {
        searchQuery.value = `${props.kunjungan.wbp.nama} (${props.kunjungan.wbp.no_reg_instansi})`;
    }
});

watch(
    searchQuery,
    debounce(async (newQuery: string) => {
        if (selectedWbpDetail.value && searchQuery.value.includes(selectedWbpDetail.value.nama)) return;
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
            console.error('Error:', error);
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
    if (!form.wbp_id) {
        alert('Harap pilih WBP tujuan dari daftar pencarian terlebih dahulu!');
        return;
    }
    // Ganti path post ke rute update kunjungan id
    form.post(`/admin/kunjungans/${props.kunjungan.id}`);
};
</script>

<template>
    <Head title="Edit Data Kunjungan" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 bg-background text-foreground">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between mb-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Edit Kunjungan</h1>
                    <p class="text-sm text-muted-foreground">Koreksi data kunjungan atau status antrean.</p>
                </div>
                <Link href="/admin/kunjungans" class="inline-flex px-4 py-2 border rounded hover:bg-gray-100">Batal & Kembali</Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="rounded-xl border border-border bg-card shadow-sm p-6 space-y-6">
                    <h3 class="text-lg font-semibold border-b pb-2">1. Data Tujuan & Waktu</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-red-600">Pilih UPT Lapas *</label>
                            <select v-model="form.upt_id" :disabled="upts.length === 1" class="flex h-10 w-full rounded-md border px-3" required>
                                <option v-for="upt in upts" :key="upt.id" :value="upt.id">{{ upt.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-red-600">Status Kunjungan *</label>
                            <select v-model="form.status" class="flex h-10 w-full rounded-md border px-3 font-bold" required>
                                <option value="Menunggu Kedatangan Kunjungan">Menunggu Kedatangan Kunjungan</option>
                                <option value="Selesai">Selesai</option>
                                <option value="Batal">Batal</option>
                                <option value="Kadaluarsa">Kadaluarsa</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-red-600">Tanggal Kunjungan *</label>
                            <input v-model="form.tanggal_kunjungan" type="date" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                            <div v-if="form.errors.tanggal_kunjungan" class="text-xs font-medium text-red-500 mt-1">{{ form.errors.tanggal_kunjungan }}</div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-red-600">Pilih Sesi Kunjungan *</label>
                            <select v-model="form.waktu_kunjungan" class="flex h-10 w-full rounded-md border px-3" required>
                                <option value="" disabled>-- Pilih Sesi --</option>
                                <option value="Sesi 1 (09.00 - 11.00)">Sesi 1 Pagi (09.00 - 11.00 WITA)</option>
                                <option value="Sesi 2 (13.00 - 15.00)">Sesi 2 Siang (13.00 - 15.00 WITA)</option>
                            </select>
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold border-b pb-2 mt-8">2. Data Warga Binaan (WBP)</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="relative space-y-2">
                            <label class="text-sm font-medium text-red-600">Ubah WBP Tujuan *</label>
                            <input v-model="searchQuery" type="text" placeholder="Ketik nama WBP untuk mengganti..." class="flex h-10 w-full rounded-md border px-3" required>
                            
                            <ul v-if="searchResults.length > 0" class="absolute z-10 w-full bg-white border mt-1 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                <li v-for="res in searchResults" :key="res.id" @click="pilihWbp(res)" class="px-4 py-3 hover:bg-gray-100 cursor-pointer border-b flex flex-col">
                                    <span class="font-bold text-sm text-gray-900">{{ res.nama }}</span>
                                    <span class="text-xs text-gray-500">{{ res.no_reg_instansi }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="space-y-2 bg-gray-50 p-4 rounded-md border">
                            <label class="text-sm font-bold text-gray-700">Data WBP Terpilih:</label>
                            <div class="text-sm text-gray-600 mt-2 space-y-1">
                                <p><strong>Jenis Kejahatan:</strong> {{ selectedWbpDetail?.jenis_kejahatan?.nama_kejahatan || '-' }}</p>
                                <p><strong>Lokasi Blok/Sel:</strong> Blok {{ selectedWbpDetail?.sel?.blok?.nama_blok || '-' }} / Sel {{ selectedWbpDetail?.sel?.nama_sel || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold border-b pb-2 mt-8">3. Jumlah Pengikut</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="space-y-2"><label class="text-sm font-medium">Laki-laki</label><input v-model="form.pengikut_laki" type="number" min="0" class="flex h-10 w-full rounded-md border px-3" required></div>
                        <div class="space-y-2"><label class="text-sm font-medium">Perempuan</label><input v-model="form.pengikut_perempuan" type="number" min="0" class="flex h-10 w-full rounded-md border px-3" required></div>
                        <div class="space-y-2"><label class="text-sm font-medium">Anak-anak</label><input v-model="form.pengikut_anak" type="number" min="0" class="flex h-10 w-full rounded-md border px-3" required></div>
                        <div class="space-y-2 bg-blue-50 border p-2 text-center rounded-md"><label class="text-xs font-bold text-blue-700">TOTAL</label><div class="text-2xl font-black text-blue-700">{{ totalPengikut }}</div></div>
                    </div>

                    <h3 class="text-lg font-semibold border-b pb-2 mt-8 flex justify-between items-center">
                        <span>4. Barang Bawaan (Titipan)</span>
                        <button type="button" @click="tambahBarang" class="text-sm bg-gray-200 px-3 py-1 rounded hover:bg-gray-300">+ Tambah Barang</button>
                    </h3>
                    
                    <div class="space-y-3">
                        <div v-if="form.barang_bawaan.length === 0" class="text-sm text-gray-500 italic text-center p-4 border border-dashed rounded">Tidak ada barang bawaan.</div>
                        <div v-for="(barang, index) in form.barang_bawaan" :key="index" class="flex flex-col md:flex-row gap-3 border p-3 rounded-md bg-gray-50">
                            <div class="w-full md:w-1/3"><label class="text-xs font-medium text-red-600 block">Jenis *</label><input v-model="barang.jenis_barang" type="text" class="w-full rounded border px-3 py-1.5 text-sm" required></div>
                            <div class="w-full md:w-1/6"><label class="text-xs font-medium text-red-600 block">Jml *</label><input v-model="barang.jumlah" type="number" min="1" class="w-full rounded border px-3 py-1.5 text-sm" required></div>
                            <div class="w-full md:w-1/3"><label class="text-xs font-medium block">Keterangan</label><input v-model="barang.keterangan" type="text" class="w-full rounded border px-3 py-1.5 text-sm"></div>
                            <div class="w-full md:w-1/6"><button type="button" @click="hapusBarang(index)" class="w-full bg-red-100 text-red-600 px-3 py-1.5 rounded text-sm font-semibold hover:bg-red-200 mt-5">Hapus</button></div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end mt-6">
                    <button type="submit" :disabled="form.processing" class="bg-amber-600 text-white px-8 py-3 rounded-md font-bold hover:bg-amber-700 shadow transition-all">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>