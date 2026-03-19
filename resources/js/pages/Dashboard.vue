<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';

// Import Chart.js
import { Line } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

const props = defineProps<{
    isKanwil: boolean;
    upts: { id: number, name: string }[];
    // Tambahkan sesi di filters
    filters: { start_date: string, end_date: string, upt_id: string | null, sesi: string | null };
    ringkasan: { total_orang: number, total_wbp: number };
    grafik: { tanggal: string, total_orang: number, total_wbp: number }[];
}>();

// Form State
const filterForm = ref({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
    upt_id: props.filters.upt_id || '',
    sesi: props.filters.sesi || '' // Tambahkan ini
});

// Watcher untuk Filter (Otomatis reload data tanpa refresh halaman)
watch(filterForm, debounce((newVal) => {
    router.get('/dashboard', newVal, { preserveState: true, replace: true, preserveScroll: true });
}, 500), { deep: true });

const exportExcel = () => {
    const params = new URLSearchParams(filterForm.value as any).toString();
    window.location.href = `/dashboard/export?${params}`;
};

// Setup Data Grafik
const chartData = computed(() => {
    return {
        labels: props.grafik.map(g => g.tanggal),
        datasets: [
            {
                label: 'Jumlah Orang Membesuk',
                backgroundColor: '#3b82f6',
                borderColor: '#3b82f6',
                data: props.grafik.map(g => g.total_orang),
                tension: 0.3
            },
            {
                label: 'Jumlah WBP Dibesuk',
                backgroundColor: '#ef4444',
                borderColor: '#ef4444',
                data: props.grafik.map(g => g.total_wbp),
                tension: 0.3
            }
        ]
    }
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' as const }
    }
};
</script>

<template>
    <Head title="Dashboard Analytics" />

    <AppLayout>
        <div class="flex flex-1 flex-col gap-6 p-6 bg-background text-foreground">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Dashboard Kunjungan</h1>
                    <p class="text-muted-foreground mt-1">Pantau statistik kunjungan secara real-time.</p>
                </div>
                <button @click="exportExcel" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md font-medium shadow-sm flex items-center gap-2 transition-colors">
                    📥 Tarik Excel
                </button>
            </div>

            <div class="bg-card border border-border rounded-xl p-4 shadow-sm flex flex-wrap gap-4 items-end">
                <div class="space-y-1">
                    <label class="text-sm font-medium">Dari Tanggal</label>
                    <input v-model="filterForm.start_date" type="date" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary" />
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium">Sampai Tanggal</label>
                    <input v-model="filterForm.end_date" type="date" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary" />
                </div>

                <div class="space-y-1 min-w-[150px]">
                    <label class="text-sm font-medium">Pilih Sesi</label>
                    <select v-model="filterForm.sesi" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                        <option value="">Semua Sesi</option>
                        <option value="Sesi 1">Sesi 1 Pagi</option>
                        <option value="Sesi 2">Sesi 2 Siang</option>
                    </select>
                </div>
                <div v-if="isKanwil" class="space-y-1 flex-1 min-w-[200px]">
                    <label class="text-sm font-medium">Pilih UPT (Kanwil Akses)</label>
                    <select v-model="filterForm.upt_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                        <option value="">-- Semua UPT --</option>
                        <option v-for="upt in upts" :key="upt.id" :value="upt.id">{{ upt.name }}</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Orang Membesuk</p>
                        <h3 class="text-4xl font-black text-blue-800 dark:text-blue-300 mt-2">{{ ringkasan.total_orang }} <span class="text-lg font-medium text-blue-500">Orang</span></h3>
                    </div>
                    <div class="text-blue-300 text-6xl opacity-50">👥</div>
                </div>
                
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-6 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-red-600 dark:text-red-400">Total WBP Dibesuk</p>
                        <h3 class="text-4xl font-black text-red-800 dark:text-red-300 mt-2">{{ ringkasan.total_wbp }} <span class="text-lg font-medium text-red-500">WBP</span></h3>
                    </div>
                    <div class="text-red-300 text-6xl opacity-50">🔒</div>
                </div>
            </div>

            <div class="bg-card border border-border rounded-xl p-6 shadow-sm">
                <h3 class="font-semibold text-lg mb-6">Grafik Tren Kunjungan Harian</h3>
                <div class="h-[400px] w-full">
                    <Line v-if="grafik.length > 0" :data="chartData" :options="chartOptions" />
                    <div v-else class="h-full flex items-center justify-center text-muted-foreground border-2 border-dashed rounded-lg">
                        Tidak ada data kunjungan pada rentang tanggal ini.
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>