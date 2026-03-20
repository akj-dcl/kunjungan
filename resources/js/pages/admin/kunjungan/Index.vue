<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { can } from '@/lib/can'
import { ref, watch } from 'vue'
import { debounce } from 'lodash'

const props = defineProps<{
  kunjungans: {
    data: any[]
    links: any[]
    from: number
    to: number
    total: number
  },
  isPengunjung: boolean,
  filters: { search?: string, tanggal?: string, waktu?: string } // Tangkap filter
}>()

const page = usePage()

// State untuk filter
const filterForm = ref({
  search: props.filters.search ?? '',
  tanggal: props.filters.tanggal ?? '',
  waktu: props.filters.waktu ?? '',
})

// ==========================================
// STATE UNTUK POPUP FOTO (MODAL)
// ==========================================
const isModalOpen = ref(false);
const selectedImage = ref('');

const openModal = (imageUrl: string) => {
  selectedImage.value = imageUrl;
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  selectedImage.value = '';
};
// ==========================================

// Watcher untuk nge-trigger pencarian tanpa refresh page (anti kedip)
watch(
  filterForm,
  debounce((newVal) => {
    // Bersihkan parameter yang kosong agar URL rapi
    const params = Object.fromEntries(Object.entries(newVal).filter(([_, v]) => v != ''));
    
    router.get('/admin/kunjungans', params, {
      preserveState: true,
      preserveScroll: true,
      replace: true
    })
  }, 300),
  { deep: true }
)

// Fungsi Download Excel
const exportExcel = () => {
  const params = new URLSearchParams();
  if (filterForm.value.search) params.append('search', filterForm.value.search);
  if (filterForm.value.tanggal) params.append('tanggal', filterForm.value.tanggal);
  if (filterForm.value.waktu) params.append('waktu', filterForm.value.waktu);

  // Akses route export
  window.location.href = `/admin/kunjungans/export?${params.toString()}`;
}

function destroyKunjungan(id: number) {
  if (!confirm('Yakin mau hapus data kunjungan ini?')) return
  router.delete(`/admin/kunjungans/${id}`)
}
</script>

<template>
  <Head title="Data Kunjungan" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 bg-background text-foreground relative">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight">Data Kunjungan</h1>
          <p class="text-sm text-muted-foreground">Riwayat dan jadwal kunjungan ke Warga Binaan Pemasyarakatan.</p>
        </div>

        <Link
          v-if="can('kunjungans.create')"
          href="/admin/kunjungans/create"
          class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:opacity-90 transition-colors"
        >
          + Buat Kunjungan Baru
        </Link>
      </div>

      <div v-if="page.props.flash?.success" class="rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-600 dark:text-emerald-400">
        {{ page.props.flash.success }}
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 mt-2">
        <input 
          v-model="filterForm.search" 
          type="text" 
          placeholder="Cari Pengunjung / WBP..." 
          class="md:col-span-2 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" 
        />
        
        <input 
          v-model="filterForm.tanggal" 
          type="date" 
          class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" 
        />
        
        <select 
          v-model="filterForm.waktu" 
          class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
        >
          <option value="">Semua Sesi</option>
          <option value="Sesi 1">Sesi 1</option>
          <option value="Sesi 2">Sesi 2</option>
        </select>

        <button 
          @click="exportExcel" 
          class="inline-flex items-center justify-center h-10 rounded-md bg-emerald-600 px-4 text-sm font-medium text-white shadow hover:bg-emerald-700 transition-colors"
        >
          Tarik Excel
        </button>
      </div>

      <div class="rounded-xl border border-border bg-card text-card-foreground shadow-sm overflow-hidden mt-4">
        <div class="relative w-full overflow-auto">
          <table class="w-full caption-bottom text-sm">
            <thead class="[&_tr]:border-b border-border">
              <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                <th v-if="!isPengunjung" class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Foto & KTP</th>
                
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Waktu Kunjungan</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Data Pengunjung</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Nama WB </th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Status Kunjungan</th>
                <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">Aksi</th>
              </tr>
            </thead>
            <tbody class="[&_tr:last-child]:border-0">
              <tr v-for="k in kunjungans.data" :key="k.id" class="border-b border-border transition-colors hover:bg-muted/50">
                
                <td v-if="!isPengunjung" class="p-4">
                  <div class="flex gap-2" v-if="k.pengunjung">
                    <div 
                      v-if="k.pengunjung.foto_diri" 
                      @click="openModal(`/storage/${k.pengunjung.foto_diri}`)"
                      class="cursor-pointer group relative h-10 w-10 rounded-full overflow-hidden border border-gray-300 shadow-sm shrink-0"
                      title="Lihat Foto Diri"
                    >
                      <img :src="`/storage/${k.pengunjung.foto_diri}`" alt="Foto Diri" class="h-full w-full object-cover transition-transform group-hover:scale-110" />
                    </div>
                    
                    <div 
                      v-if="k.pengunjung.foto_ktp" 
                      @click="openModal(`/storage/${k.pengunjung.foto_ktp}`)"
                      class="cursor-pointer group relative h-10 w-14 rounded-md overflow-hidden border border-gray-300 shadow-sm shrink-0"
                      title="Lihat KTP"
                    >
                      <img :src="`/storage/${k.pengunjung.foto_ktp}`" alt="Foto KTP" class="h-full w-full object-cover transition-transform group-hover:scale-110" />
                    </div>
                  </div>
                  <div v-else class="text-xs text-muted-foreground italic">-</div>
                </td>

                <td class="p-4">
                  <div class="font-medium text-primary">{{ k.tanggal_kunjungan }}</div>
                  <div class="text-xs text-muted-foreground font-semibold mt-1">{{ k.waktu_kunjungan }}</div>
                </td>
                
                <td class="p-4">
                  <div class="font-medium">{{ k.pengunjung?.user?.name ?? 'Tidak diketahui' }}</div>
                  <div class="font-mono text-xs bg-muted px-2 py-0.5 rounded inline-block mt-1 border">NIK: {{ k.pengunjung?.no_ktp ?? '-' }}</div>
                  <div class="text-xs text-muted-foreground mt-1">Lokasi: {{ k.upt?.name ?? '-' }}</div>
                </td>
                
                <td class="p-4">
                  <div class="font-medium">{{ k.wbp?.nama ?? '-' }}</div>
                  <div class="text-xs text-muted-foreground">{{ k.wbp?.no_reg_instansi ?? '-' }}</div>
                </td>
                <td class="p-4">
                  <span
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors"
                    :class="{
                      'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': k.status === 'Menunggu Kedatangan Kunjungan',
                      'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': k.status === 'Selesai',
                      'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': k.status === 'Batal',
                      'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-400': k.status === 'Kadaluarsa'
                    }"
                  >
                    {{ k.status }}
                  </span>
                </td>
                <td class="p-4 text-right align-middle">
                  <div class="flex justify-end gap-2">
                    <Link :href="`/admin/kunjungans/${k.id}`" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-400 h-8 px-3">
                      Lihat / QR
                    </Link>
                    <Link v-if="can('kunjungans.edit')" :href="`/admin/kunjungans/${k.id}/edit`" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 h-8 px-3">
                      Edit
                    </Link>
                    <button v-if="can('kunjungans.delete')" type="button" @click="destroyKunjungan(k.id)" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90 h-8 px-3">
                      Hapus
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!kunjungans.data.length">
                <td :colspan="isPengunjung ? 5 : 6" class="p-8 text-center text-muted-foreground">Belum ada data riwayat kunjungan.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="kunjungans.links && kunjungans.links.length > 3" class="flex items-center justify-between mt-2">
        <div class="text-sm text-muted-foreground">Menampilkan {{ kunjungans.from ?? 0 }} sampai {{ kunjungans.to ?? 0 }} dari {{ kunjungans.total }} data</div>
        <div class="flex gap-1">
          <template v-for="(link, key) in kunjungans.links" :key="key">
            <div v-if="link.url === null" class="px-3 py-1 text-sm text-muted-foreground border border-transparent" v-html="link.label" />
            <Link 
              v-else 
              :href="link.url" 
              preserve-state 
              preserve-scroll
              class="px-3 py-1 text-sm rounded-md border transition-colors" 
              :class="link.active ? 'bg-primary text-primary-foreground border-primary' : 'border-border bg-background hover:bg-accent hover:text-accent-foreground'" 
              v-html="link.label" 
            />
          </template>
        </div>
      </div>
    </div>

    <div 
      v-if="isModalOpen" 
      class="fixed inset-0 z-[999] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
      @click="closeModal"
    >
      <div class="relative max-w-4xl w-full flex justify-center items-center flex-col" @click.stop>
        
        <button 
          @click="closeModal" 
          class="absolute -top-12 right-0 md:right-4 text-white hover:text-red-500 font-bold flex items-center gap-2 px-3 py-1 bg-white/10 rounded-full transition-colors"
        >
          <span class="text-2xl leading-none">&times;</span> Tutup
        </button>

        <div class="bg-black/50 p-2 rounded-xl shadow-2xl">
          <img 
            :src="selectedImage" 
            alt="Preview Dokumen" 
            class="max-w-full max-h-[85vh] object-contain rounded-lg border border-gray-700" 
          />
        </div>
        
      </div>
    </div>

  </AppLayout>
</template>