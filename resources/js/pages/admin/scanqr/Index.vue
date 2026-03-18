<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { QrcodeStream } from 'vue-qrcode-reader';

const page = usePage<any>();
const userUptId = page.props.auth?.user?.upt_id || 'pusat'; // Ambil ID UPT petugas yang login
const storageKey = `riwayatScanLapas_${userUptId}`; // Kunci brankasnya jadi unik per Lapas

const isScanning = ref(true);
const scanError = ref('');
const scanSuccess = ref('');
const activeDetail = ref<any>(null);
const historyList = ref<any[]>([]);

// 1. Saat halaman dimuat, cek brankas khusus Lapas ini
onMounted(() => {
    const savedHistory = sessionStorage.getItem(storageKey);
    if (savedHistory) {
        historyList.value = JSON.parse(savedHistory);
    }
});

// 2. Auto-save ke brankas saat ada perubahan
watch(historyList, (newData) => {
    sessionStorage.setItem(storageKey, JSON.stringify(newData));
}, { deep: true });

// 3. Fungsi Reset Riwayat
const bersihkanRiwayat = () => {
    if(confirm('Yakin ingin membersihkan tabel riwayat sesi ini?')) {
        historyList.value = [];
        activeDetail.value = null;
        sessionStorage.removeItem(storageKey);
    }
};

const onDetect = async (detectedCodes: any[]) => {
    if (detectedCodes.length === 0) return;
    
    const qrResult = detectedCodes[0].rawValue;
    isScanning.value = false;
    scanError.value = '';
    scanSuccess.value = '';

    try {
        const response = await axios.post('/admin/api/scan-qr/process', {
            qr_code: qrResult
        });

        if (response.data.success) {
            scanSuccess.value = 'Kunjungan Dikonfirmasi!';
            activeDetail.value = response.data.data;
            
            historyList.value.unshift(response.data.data);
            
            if (historyList.value.length > 50) {
                historyList.value.pop();
            }
            setTimeout(() => { 
                isScanning.value = true; 
                scanSuccess.value = '';
            }, 3000);
        }
    } catch (error: any) {
        scanError.value = error.response?.data?.message || 'Terjadi kesalahan sistem.';
        setTimeout(() => { 
            isScanning.value = true;
            scanError.value = '';
        }, 4000);
    }
};

const printKartu = (id: number) => {
    const oldFrame = document.getElementById('print-frame');
    if (oldFrame) oldFrame.remove();

    const iframe = document.createElement('iframe');
    iframe.id = 'print-frame';
    
    iframe.style.position = 'absolute';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = 'none';
    
    document.body.appendChild(iframe);
    iframe.src = `/admin/scan-qr/print-kartu/${id}`;
};
</script>

<template>
    <Head title="Scan QR Kunjungan" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 bg-background text-foreground no-print">
            
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Scan QR Code</h1>
                    <p class="text-sm text-muted-foreground">Pindai kode QR pengunjung untuk konfirmasi dan cetak kartu.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-2">
                <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden flex flex-col">
                    <div class="p-4 border-b bg-gray-50 dark:bg-gray-800">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">📷 Kamera Scanner</h3>
                    </div>
                    <div class="p-6 flex-1 flex items-center justify-center bg-black relative min-h-[300px]">
                        <qrcode-stream v-if="isScanning" @detect="onDetect" class="w-full h-full"></qrcode-stream>
                        
                        <div v-if="!isScanning && scanSuccess" class="absolute inset-0 flex flex-col items-center justify-center bg-green-900/90 text-white p-6 text-center z-10">
                            <span class="text-5xl mb-4">✅</span>
                            <h2 class="text-xl font-bold">{{ scanSuccess }}</h2>
                            <p class="text-sm mt-2 text-green-200">Memuat ulang kamera...</p>
                        </div>

                        <div v-if="!isScanning && scanError" class="absolute inset-0 flex flex-col items-center justify-center bg-red-900/90 text-white p-6 text-center z-10">
                            <span class="text-5xl mb-4">❌</span>
                            <h2 class="text-xl font-bold text-red-100">{{ scanError }}</h2>
                            <p class="text-sm mt-2 text-red-200">Memuat ulang kamera...</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-border bg-card shadow-sm overflow-hidden flex flex-col">
                    <div class="p-4 border-b bg-gray-50 dark:bg-gray-800">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">📋 Detail Kunjungan Terbaru</h3>
                    </div>
                    <div class="p-6 flex-1 bg-gray-50/50 dark:bg-gray-900/20">
                        <div v-if="activeDetail" class="space-y-4">
                            <div class="flex items-center justify-between border-b pb-2">
                                <span class="text-xs font-bold bg-green-100 text-green-800 px-2 py-1 rounded uppercase">Status: {{ activeDetail.status }}</span>
                                <span class="text-xs text-gray-500 font-mono">{{ activeDetail.tanggal_kunjungan }} | {{ activeDetail.waktu_kunjungan }}</span>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-2 text-sm border-b pb-3 border-dashed">
                                <span class="text-gray-500 font-medium">Pengunjung</span>
                                <span class="col-span-2 font-bold text-blue-700 uppercase">: {{ activeDetail.pengunjung?.user?.name }}</span>
                            </div>

                            <div class="grid grid-cols-3 gap-2 text-sm border-b pb-3 border-dashed">
                                <span class="text-gray-500 font-medium">WBP Tujuan</span>
                                <span class="col-span-2 font-bold text-red-600 uppercase">: {{ activeDetail.wbp?.nama }}</span>
                            </div>

                            <div class="grid grid-cols-3 gap-2 text-sm border-b pb-3 border-dashed">
                                <span class="text-gray-500 font-medium">Lokasi WBP</span>
                                <span class="col-span-2 font-semibold">: Blok {{ activeDetail.wbp?.sel?.blok?.nama_blok }} / Sel {{ activeDetail.wbp?.sel?.nama_sel }}</span>
                            </div>

                            <div class="grid grid-cols-3 gap-2 text-sm">
                                <span class="text-gray-500 font-medium">Pengikut</span>
                                <span class="col-span-2 font-semibold">: {{ activeDetail.total_pengikut }} Orang (L:{{ activeDetail.pengikut_laki }}, P:{{ activeDetail.pengikut_perempuan }}, A:{{ activeDetail.pengikut_anak }})</span>
                            </div>
                        </div>
                        <div v-else class="h-full flex flex-col items-center justify-center text-gray-400">
                            <span class="text-4xl mb-2">🔍</span>
                            <p class="text-sm">Belum ada data. Arahkan QR Code ke kamera.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card text-card-foreground shadow-sm overflow-hidden mt-4">
                <div class="p-4 border-b bg-gray-50 dark:bg-gray-800 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200">Riwayat Scan Sesi Ini</h3>
                    <button @click="bersihkanRiwayat" class="text-xs bg-red-100 text-red-600 hover:bg-red-200 px-3 py-1.5 rounded-md font-semibold transition-colors">
                        🗑️ Bersihkan Riwayat
                    </button>
                </div>
                <div class="relative w-full overflow-auto">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="[&_tr]:border-b border-border">
                            <tr class="border-b transition-colors hover:bg-muted/50">
                                <th class="h-10 px-4 text-left font-medium text-muted-foreground">Waktu</th>
                                <th class="h-10 px-4 text-left font-medium text-muted-foreground">Pengunjung</th>
                                <th class="h-10 px-4 text-left font-medium text-muted-foreground">WBP Tujuan</th>
                                <th class="h-10 px-4 text-left font-medium text-muted-foreground">Lokasi WBP</th>
                                <th class="h-10 px-4 text-right font-medium text-muted-foreground">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in historyList" :key="item.id" class="border-b border-border hover:bg-muted/50">
                                <td class="p-3 text-xs">{{ item.waktu_kunjungan }}</td>
                                <td class="p-3 font-semibold text-blue-700">{{ item.pengunjung?.user?.name }}</td>
                                <td class="p-3 font-semibold text-red-600">{{ item.wbp?.nama }}</td>
                                <td class="p-3 text-xs">Blok {{ item.wbp?.sel?.blok?.nama_blok }} / Sel {{ item.wbp?.sel?.nama_sel }}</td>
                                <td class="p-3 text-right">
                                    <button 
                                        @click="printKartu(item.id)" 
                                        class="bg-blue-100 text-blue-700 px-3 py-1 rounded text-xs font-bold hover:bg-blue-200"
                                    >
                                        🖨️ Cetak Kartu
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="historyList.length === 0">
                                <td colspan="5" class="p-6 text-center text-muted-foreground text-xs italic">Tabel akan terisi saat Anda berhasil melakukan scan QR.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>