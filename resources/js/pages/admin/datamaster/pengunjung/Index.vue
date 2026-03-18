<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { debounce } from 'lodash'
import { can } from '@/lib/can'

type User = { id: number; name: string; email: string; username: string; }
type Pengunjung = {
  id: number
  user_id: number
  no_ktp: string
  no_hp: string
  jenis_kelamin: string
  foto_diri: string | null
  foto_ktp: string | null // Tambahkan ini agar TypeScript tidak protes
  user?: User
}

type PaginationLink = { url: string | null; label: string; active: boolean }

const props = defineProps<{
  pengunjungs: {
    data: Pengunjung[]
    links: PaginationLink[]
    from: number
    to: number
    total: number
  }
  filters: { search?: string }
}>()

const page = usePage()
const search = ref(props.filters.search || '')

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

watch(
  search,
  debounce((newSearch: string) => {
    const params = new URLSearchParams();
    if (newSearch) params.append('search', newSearch);
    window.location.href = window.location.pathname + '?' + params.toString();
  }, 300)
)

function destroyPengunjung(id: number) {
  if (!confirm('Yakin mau hapus pengunjung ini? Akun login mereka juga akan terhapus!')) return
  router.delete(`/admin/pengunjungs/${id}`)
}
</script>

<template>
  <Head title="Data Pengunjung" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 bg-background text-foreground relative">
      
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight">Data Pengunjung</h1>
          <p class="text-sm text-muted-foreground">Kelola data pengunjung Lapas dan verifikasi berkas KTP.</p>
        </div>

        <Link
          v-if="can('pengunjungs.create')"
          href="/admin/pengunjungs/create"
          class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:opacity-90 transition-colors"
        >
          + Tambah Pengunjung
        </Link>
      </div>

      <div v-if="page.props.flash?.success" class="rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-600 dark:text-emerald-400">
        {{ page.props.flash.success }}
      </div>

      <div class="grid gap-3 md:grid-cols-4">
        <input v-model="search" type="text" placeholder="Cari nama, email, atau NIK..." class="col-span-1 md:col-span-2 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
      </div>

      <div class="rounded-xl border border-border bg-card text-card-foreground shadow-sm overflow-hidden">
        <div class="relative w-full overflow-auto">
          <table class="w-full caption-bottom text-sm">
            <thead class="[&_tr]:border-b border-border bg-muted/30">
              <tr class="border-b transition-colors">
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Berkas Foto</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Info Akun</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Data Diri</th>
                <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">Aksi</th>
              </tr>
            </thead>
            <tbody class="[&_tr:last-child]:border-0">
              <tr v-for="p in pengunjungs.data" :key="p.id" class="border-b border-border transition-colors hover:bg-muted/50">
                
                <td class="p-4">
                  <div class="flex gap-2">
                    <div 
                      v-if="p.foto_diri" 
                      @click="openModal(`/storage/${p.foto_diri}`)"
                      class="cursor-pointer group relative h-12 w-12 rounded-full overflow-hidden border border-gray-300 shadow-sm"
                      title="Lihat Foto Diri"
                    >
                      <img :src="`/storage/${p.foto_diri}`" alt="Foto Diri" class="h-full w-full object-cover transition-transform group-hover:scale-110" />
                    </div>
                    
                    <div 
                      v-if="p.foto_ktp" 
                      @click="openModal(`/storage/${p.foto_ktp}`)"
                      class="cursor-pointer group relative h-12 w-16 rounded-md overflow-hidden border border-gray-300 shadow-sm"
                      title="Lihat KTP"
                    >
                      <img :src="`/storage/${p.foto_ktp}`" alt="Foto KTP" class="h-full w-full object-cover transition-transform group-hover:scale-110" />
                    </div>
                  </div>
                </td>
                
                <td class="p-4">
                  <div class="font-medium text-primary">{{ p.user?.name ?? '-' }}</div>
                  <div class="text-xs text-muted-foreground mt-0.5">User: {{ p.user?.username ?? '-' }}</div>
                  <div class="text-xs text-muted-foreground">{{ p.user?.email ?? '-' }}</div>
                </td>
                
                <td class="p-4">
                  <div class="font-medium font-mono text-xs bg-muted px-2 py-1 rounded inline-block">NIK: {{ p.no_ktp }}</div>
                  <div class="text-xs text-muted-foreground mt-1">HP: {{ p.no_hp }} ({{ p.jenis_kelamin }})</div>
                </td>
                
                <td class="p-4 text-right align-middle">
                  <div class="flex justify-end gap-2">
                    <Link v-if="can('pengunjungs.edit')" :href="`/admin/pengunjungs/${p.id}/edit`" class="inline-flex items-center justify-center rounded-md text-xs font-medium transition-colors border border-input bg-transparent shadow-sm hover:bg-accent hover:text-accent-foreground h-8 px-3">Edit</Link>
                    <button v-if="can('pengunjungs.delete')" type="button" @click="destroyPengunjung(p.id)" class="inline-flex items-center justify-center rounded-md text-xs font-medium transition-colors bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90 h-8 px-3">Hapus</button>
                  </div>
                </td>

              </tr>
              <tr v-if="!pengunjungs.data.length">
                <td colspan="4" class="p-8 text-center text-muted-foreground">Tidak ada data pengunjung.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="pengunjungs.links.length > 3" class="flex items-center justify-between mt-2">
        <div class="text-sm text-muted-foreground">Menampilkan {{ pengunjungs.from ?? 0 }} sampai {{ pengunjungs.to ?? 0 }} dari {{ pengunjungs.total }} data</div>
        <div class="flex gap-1">
          <template v-for="(link, key) in pengunjungs.links" :key="key">
            <div v-if="link.url === null" class="px-3 py-1 text-sm text-muted-foreground border border-transparent" v-html="link.label" />
            <Link v-else :href="link.url" class="px-3 py-1 text-sm rounded-md border transition-colors" :class="link.active ? 'bg-primary text-primary-foreground border-primary' : 'border-border bg-background hover:bg-accent hover:text-accent-foreground'" v-html="link.label" />
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