<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { debounce } from 'lodash'
import { can } from '@/lib/can' // Pastikan file helper can.js/ts sudah dibuat

type Permission = { id: number; name: string }
type Role = {
  id: number
  name: string
  permissions: Permission[]
}
type PaginationLink = { url: string | null; label: string; active: boolean }

const props = defineProps<{
  roles: {
    data: Role[]
    links: PaginationLink[]
    from: number
    to: number
    total: number
  }
  filters: {
    search?: string
  }
}>()

const page = usePage()
const search = ref(props.filters.search || '')

// Watcher untuk fitur pencarian
watch(
  search,
  debounce((newSearch: string) => {
    const params = new URLSearchParams()
    if (newSearch) params.append('search', newSearch)
    window.location.href = window.location.pathname + '?' + params.toString()
  }, 300)
)

function destroyRole(id: number) {
  if (!confirm('Yakin mau hapus Role ini?')) return
  router.delete(`/admin/roles/${id}`)
}
</script>

<template>
  <Head title="Master Role" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 bg-background text-foreground">

      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight">Master Role & Akses</h1>
          <p class="text-sm text-muted-foreground">Kelola hak akses pengguna dalam sistem Lapas.</p>
        </div>

        <Link
          v-if="can('roles.create')"
          href="/admin/roles/create"
          class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:opacity-90 transition-colors"
        >
          + Tambah Role
        </Link>
      </div>

      <div class="grid gap-3 md:grid-cols-4">
        <input
          v-model="search"
          type="text"
          placeholder="Cari nama role..."
          class="col-span-1 md:col-span-2 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
        />
      </div>

      <div class="rounded-xl border border-border bg-card text-card-foreground shadow-sm overflow-hidden">
        <div class="relative w-full overflow-auto">
          <table class="w-full caption-bottom text-sm">
            <thead class="[&_tr]:border-b border-border">
              <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Nama Role</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Hak Akses (Permissions)</th>
                <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">Aksi</th>
              </tr>
            </thead>
            <tbody class="[&_tr:last-child]:border-0">
              <tr
                v-for="role in roles.data"
                :key="role.id"
                class="border-b border-border transition-colors hover:bg-muted/50"
              >
                <td class="p-4 font-medium">{{ role.name }}</td>
                <td class="p-4">
                  <div class="flex flex-wrap gap-1">
                    <span
                      v-for="perm in role.permissions"
                      :key="perm.id"
                      class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                    >
                      {{ perm.name }}
                    </span>
                    <span v-if="!role.permissions.length" class="text-muted-foreground text-xs italic">- Belum ada akses -</span>
                  </div>
                </td>
                <td class="p-4 text-right align-middle">
                  <div class="flex justify-end gap-2">
                    <Link
                      v-if="can('roles.edit')"
                      :href="`/admin/roles/${role.id}/edit`"
                      class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors border border-input bg-transparent shadow-sm hover:bg-accent hover:text-accent-foreground h-8 px-3"
                    >
                      Edit
                    </Link>
                    <button
                      v-if="can('roles.delete')"
                      type="button"
                      @click="destroyRole(role.id)"
                      class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90 h-8 px-3"
                    >
                      Hapus
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!roles.data.length">
                <td colspan="3" class="p-8 text-center text-muted-foreground">
                  Tidak ada data Role ditemukan.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="roles.links.length > 3" class="flex items-center justify-between mt-2">
        <div class="text-sm text-muted-foreground">
          Menampilkan {{ roles.from ?? 0 }} sampai {{ roles.to ?? 0 }} dari {{ roles.total }} data
        </div>
        <div class="flex gap-1">
          <template v-for="(link, key) in roles.links" :key="key">
            <div
              v-if="link.url === null"
              class="px-3 py-1 text-sm text-muted-foreground border border-transparent"
              v-html="link.label"
            />
            <Link
              v-else
              :href="link.url"
              class="px-3 py-1 text-sm rounded-md border transition-colors"
              :class="link.active
                ? 'bg-primary text-primary-foreground border-primary'
                : 'border-border bg-background hover:bg-accent hover:text-accent-foreground'"
              v-html="link.label"
            />
          </template>
        </div>
      </div>

    </div>
  </AppLayout>
</template>