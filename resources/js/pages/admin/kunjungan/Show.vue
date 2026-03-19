<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps<{
    kunjungan: any;
    qrCodeImage: string;
}>();

// Fungsi untuk Print Struk / Simpan PDF
const printStruk = () => {
    window.print();
};

// AUTO-DOWNLOAD / AUTO-PRINT SAAT HALAMAN DIBUKA
onMounted(() => {
    // Kita kasih delay setengah detik (500ms) agar gambar QR Code selesai di-render dulu
    setTimeout(() => {
        window.print();
    }, 500);
});
</script>

<template>
    <Head title="Detail Kunjungan" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 bg-background text-foreground">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between no-print mb-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-emerald-600">Berhasil! Bukti Pendaftaran Kunjungan</h1>
                    <p class="text-sm text-muted-foreground">Tunjukkan QR Code ini kepada petugas Lapas saat berkunjung.</p>
                </div>
                <div class="flex gap-2">
                    <Link href="/admin/kunjungans" class="inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground">Kembali ke Daftar</Link>
                    <button @click="printStruk" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors shadow-md">
                        🖨️ Cetak / Simpan Ulang PDF
                    </button>
                </div>
            </div>

            <div class="bg-white text-black p-8 rounded-xl shadow-md max-w-2xl mx-auto w-full border print-area">
                <div class="text-center border-b border-gray-300 pb-6 mb-6">
                    <h2 class="text-xl font-bold uppercase text-gray-900">{{ kunjungan.upt?.name }}</h2>
                    <p class="text-sm text-gray-600 font-semibold mt-1">SURAT IZIN KUNJUNGAN WBP</p>
                </div>

                <div class="flex flex-col md:flex-row gap-8 items-center md:items-start">
                    <div class="flex flex-col items-center justify-center bg-white p-4 rounded-lg border-2 border-gray-200 w-48 shrink-0 shadow-sm">
                        <img :src="qrCodeImage" alt="QR Code" class="w-full h-auto mb-3" />
                        <span class="text-[10px] text-gray-500 font-mono text-center break-all bg-gray-100 px-2 py-1 rounded w-full">{{ kunjungan.qr_code_uuid.split('-')[0] }}</span>
                        <span class="mt-3 text-xs font-bold px-3 py-1 rounded-full bg-yellow-100 text-yellow-800 uppercase border border-yellow-200">{{ kunjungan.status }}</span>
                    </div>

                    <div class="w-full space-y-4 text-sm text-gray-800">
                        <div class="grid grid-cols-3 gap-2 border-b border-gray-100 pb-2">
                            <span class="text-gray-500 font-medium">Waktu Kunjungan</span>
                            <span class="col-span-2 font-bold text-lg text-blue-700">: {{ kunjungan.tanggal_kunjungan }} <br> <span class="text-sm text-gray-600 ml-2">🕒 {{ kunjungan.waktu_kunjungan }}</span></span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-gray-500 font-medium">Nama Pengunjung</span>
                            <span class="col-span-2 font-semibold uppercase">: {{ kunjungan.pengunjung?.user?.name }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-gray-500 font-medium">Nama WBP Tujuan</span>
                            <span class="col-span-2 font-semibold uppercase text-red-600">: {{ kunjungan.wbp?.nama }} ({{ kunjungan.wbp?.no_reg_instansi }})</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-gray-500 font-medium">Lokasi WBP</span>
                            <span class="col-span-2 font-semibold">: Blok {{ kunjungan.wbp?.sel?.blok?.nama_blok }} / Sel {{ kunjungan.wbp?.sel?.nama_sel }}</span>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-2 border-b border-gray-100 pb-3 items-start">
                            <span class="text-gray-500 font-medium">Pengikut</span>
                            <div class="col-span-2 font-semibold flex">
                                <span class="mr-2">:</span>
                                <div class="flex flex-col w-56 space-y-1">
                                    <div class="grid grid-cols-[90px_auto] gap-2">
                                        <span>Laki-laki</span>
                                        <span>: {{ kunjungan.pengikut_laki }} orang</span>
                                    </div>
                                    <div class="grid grid-cols-[90px_auto] gap-2">
                                        <span>Perempuan</span>
                                        <span>: {{ kunjungan.pengikut_perempuan }} orang</span>
                                    </div>
                                    <div class="grid grid-cols-[90px_auto] gap-2">
                                        <span>Anak-anak</span>
                                        <span>: {{ kunjungan.pengikut_anak }} orang</span>
                                    </div>
                                    <div class="grid grid-cols-[90px_auto] gap-2 mt-1 pt-1 border-t border-gray-300 font-bold text-gray-900">
                                        <span>Jumlah Total</span>
                                        <span>: {{ kunjungan.total_pengikut }} orang</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-2">
                            <span class="text-gray-500 font-medium block mb-2">Daftar Barang Titipan:</span>
                            <ul v-if="kunjungan.barang_bawaans.length > 0" class="list-disc list-inside pl-2 text-xs space-y-1.5 font-medium">
                                <li v-for="brg in kunjungan.barang_bawaans" :key="brg.id">
                                    {{ brg.jenis_barang }} ({{ brg.jumlah }}) <span class="text-gray-500 italic font-normal">- {{ brg.keterangan }}</span>
                                </li>
                            </ul>
                            <span v-else class="text-xs text-gray-400 italic">- Tidak membawa barang titipan -</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Pengaturan Kertas Print/PDF */
@media print {
    body * {
        visibility: hidden;
    }
    .print-area, .print-area * {
        visibility: visible;
        color: black !important;
    }
    .print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 20px;
        box-shadow: none !important;
        border: none !important;
    }
    .no-print {
        display: none !important;
    }
}
</style>