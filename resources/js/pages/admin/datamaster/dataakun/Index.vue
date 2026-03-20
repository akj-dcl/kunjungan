<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { debounce } from 'lodash'
import { can } from '@/lib/can'

type User = {
  id: number
  name: string
  username: string
  nip: string
  jabatan: string
  upt_id: number | null
  roles: { id: number; name: string }[]
  upt?: { id: number; name: string } // Tambahan relasi UPT
}

type PaginationLink = { url: string | null; label: string; active: boolean }

const props = defineProps<{
  users: {
    data: User[]
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

const deleteUser = (id: number) => {
  if (confirm('Yakin mau hapus akun ini?')) router.delete(`/admin/data-akun/${id}`)
}
</script>

<template>
  <Head title="Data Akun Pegawai" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 bg-background text-foreground">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight">Data Akun Pegawai</h1>
          <p class="text-sm text-muted-foreground">Kelola akun akses sistem untuk pegawai.</p>
        </div>

        <Link
          v-if="can('akun.create')"
          href="/admin/data-akun/create"
          class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:opacity-90 transition-colors"
        >
          + Tambah Akun
        </Link>
      </div>

      <div v-if="page.props.flash?.success" class="rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-600 dark:text-emerald-400">
        {{ page.props.flash.success }}
      </div>

      <div class="grid gap-3 md:grid-cols-4">
        <input v-model="search" type="text" placeholder="Cari nama atau NIP..." class="col-span-1 md:col-span-2 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
      </div>

      <div class="rounded-xl border border-border bg-card text-card-foreground shadow-sm overflow-hidden">
        <div class="relative w-full overflow-auto">
          <table class="w-full caption-bottom text-sm">
            <thead class="[&_tr]:border-b border-border">
              <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Nama / Jabatan</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">NIP (Username)</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Hak Akses (Role)</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Penempatan UPT</th>
                <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">Aksi</th>
              </tr>
            </thead>
            <tbody class="[&_tr:last-child]:border-0">
              <tr v-for="u in users.data" :key="u.id" class="border-b border-border transition-colors hover:bg-muted/50">
                <td class="p-4">
                  <div class="font-bold text-primary">{{ u.name }}</div>
                  <div class="font-medium mt-1 text-xs text-muted-foreground">{{ u.jabatan }}</div>
                </td>
                <td class="p-4 font-mono font-medium">{{ u.nip }}</td>
                <td class="p-4">
                  <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 uppercase">
                    {{ u.roles[0]?.name ?? '-' }}
                  </span>
                </td>
                <td class="p-4">
                  <span v-if="u.upt" class="font-medium text-emerald-700">{{ u.upt.name }}</span>
                  <span v-else class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">Kanwil / Pusat</span>
                </td>
                <td class="p-4 text-right align-middle">
                  <div class="flex justify-end gap-2">
                    <Link v-if="can('akun.edit')" :href="`/admin/data-akun/${u.id}/edit`" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors border border-input bg-transparent shadow-sm hover:bg-accent hover:text-accent-foreground h-8 px-3">Edit</Link>
                    <button v-if="can('akun.delete')" type="button" @click="deleteUser(u.id)" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90 h-8 px-3">Hapus</button>
                  </div>
                </td>
              </tr>
              <tr v-if="!users.data.length">
                <td colspan="5" class="p-8 text-center text-muted-foreground">Tidak ada data akun ditemukan.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="users.links?.length > 3" class="flex items-center justify-between mt-2">
        <div class="text-sm text-muted-foreground">Menampilkan {{ users.from ?? 0 }} sampai {{ users.to ?? 0 }} dari {{ users.total }} data</div>
        <div class="flex gap-1">
          <template v-for="(link, key) in users.links" :key="key">
            <div v-if="link.url === null" class="px-3 py-1 text-sm text-muted-foreground border border-transparent" v-html="link.label" />
            <Link v-else :href="link.url" preserve-state preserve-scroll class="px-3 py-1 text-sm rounded-md border transition-colors" :class="link.active ? 'bg-primary text-primary-foreground border-primary' : 'border-border bg-background hover:bg-accent hover:text-accent-foreground'" v-html="link.label" />
          </template>
        </div>
      </div>
    </div>
  </AppLayout>
</template>