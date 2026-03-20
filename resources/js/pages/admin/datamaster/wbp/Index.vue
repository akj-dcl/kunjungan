<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { debounce } from 'lodash'
import { can } from '@/lib/can'

type Upt = { id: number; name: string }
type JenisKejahatan = { id: number; nama_kejahatan: string }
type Blok = { id: number; nama_blok: string }
type Sel = { id: number; nama_sel: string; blok?: Blok }

type Wbp = {
  id: number
  upt_id: number
  no_reg_instansi: string
  nama: string
  alamat: string | null
  jenis_kejahatan_id: number
  sel_id: number
  is_aktif: boolean
  upt?: Upt
  jenis_kejahatan?: JenisKejahatan
  sel?: Sel
}

type PaginationLink = { url: string | null; label: string; active: boolean }

const props = defineProps<{
  wbps: {
    data: Wbp[]
    links: PaginationLink[]
    from: number
    to: number
    total: number
  }
  filters: { search?: string }
}>()

const page = usePage()
const search = ref(props.filters.search || '')

watch(
  search,
  debounce((newSearch: string) => {
    const params = new URLSearchParams();
    if (newSearch) params.append('search', newSearch);
    
    // Ganti window.location dengan router.get dari Inertia
    router.get(window.location.pathname, Object.fromEntries(params), {
      preserveState: true,
      preserveScroll: true,
      replace: true // Supaya riwayat back button tidak dipenuhi hasil pencarian tiap huruf
    });
  }, 300)
)

function destroyWbp(id: number) {
  if (!confirm('Yakin mau hapus data WBP ini?')) return
  router.delete(`/admin/wbps/${id}`)
}
</script>

<template>
  <Head title="Master WBP" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 bg-background text-foreground">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight">Data WBP</h1>
          <p class="text-sm text-muted-foreground">Kelola data Warga Binaan Pemasyarakatan (WBP).</p>
        </div>

        <Link
          v-if="can('wbps.create')"
          href="/admin/wbps/create"
          class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:opacity-90 transition-colors"
        >
          + Tambah WBP
        </Link>
      </div>

      <div v-if="page.props.flash?.success" class="rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-600 dark:text-emerald-400">
        {{ page.props.flash.success }}
      </div>

      <div class="grid gap-3 md:grid-cols-4">
        <input v-model="search" type="text" placeholder="Cari nama atau No. Registrasi..." class="col-span-1 md:col-span-2 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
      </div>

      <div class="rounded-xl border border-border bg-card text-card-foreground shadow-sm overflow-hidden">
        <div class="relative w-full overflow-auto">
          <table class="w-full caption-bottom text-sm">
            <thead class="[&_tr]:border-b border-border">
              <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">No. Reg / Nama</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">UPT</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Kejahatan</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Lokasi (Blok/Sel)</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Status</th> 
                <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">Aksi</th>
              </tr>
            </thead>
            <tbody class="[&_tr:last-child]:border-0">
              <tr v-for="w in wbps.data" :key="w.id" class="border-b border-border transition-colors hover:bg-muted/50">
                <td class="p-4">
                  <div class="font-bold text-primary">{{ w.no_reg_instansi }}</div>
                  <div class="font-medium mt-1">{{ w.nama }}</div>
                </td>
                <td class="p-4 text-muted-foreground">{{ w.upt?.name ?? '-' }}</td>
                <td class="p-4">
                  <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-400">
                    {{ w.jenis_kejahatan?.nama_kejahatan ?? '-' }}
                  </span>
                </td>
                <td class="p-4">
                  <div class="font-medium">Blok {{ w.sel?.blok?.nama_blok ?? '-' }}</div>
                  <div class="text-xs text-muted-foreground">Sel: {{ w.sel?.nama_sel ?? '-' }}</div>
                </td>
                <td class="p-4">
                  <span v-if="w.is_aktif" class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Aktif</span>
                  <span v-else class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700">Non-Aktif</span>
                </td>
                <td class="p-4 text-right align-middle">
                  <div class="flex justify-end gap-2">
                    <Link v-if="can('wbps.edit')" :href="`/admin/wbps/${w.id}/edit`" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors border border-input bg-transparent shadow-sm hover:bg-accent hover:text-accent-foreground h-8 px-3">Edit</Link>
                    <button v-if="can('wbps.delete')"  type="button" @click="destroyWbp(w.id)" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90 h-8 px-3">Hapus</button>
                  </div>
                </td>
              </tr>
              <tr v-if="!wbps.data.length">
                <td colspan="5" class="p-8 text-center text-muted-foreground">Tidak ada data WBP ditemukan.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="wbps.links.length > 3" class="flex items-center justify-between mt-2">
        <div class="text-sm text-muted-foreground">Menampilkan {{ wbps.from ?? 0 }} sampai {{ wbps.to ?? 0 }} dari {{ wbps.total }} data</div>
        <div class="flex gap-1">
          <template v-for="(link, key) in wbps.links" :key="key">
            <div v-if="link.url === null" class="px-3 py-1 text-sm text-muted-foreground border border-transparent" v-html="link.label" />
            <Link v-else :href="link.url" preserve-state preserve-scroll class="px-3 py-1 text-sm rounded-md border transition-colors" :class="link.active ? 'bg-primary text-primary-foreground border-primary' : 'border-border bg-background hover:bg-accent hover:text-accent-foreground'" v-html="link.label" />
          </template>
        </div>
      </div>
    </div>
  </AppLayout>
</template>