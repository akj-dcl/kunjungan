<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { can } from '@/lib/can'

const props = defineProps<{
  kunjungans: {
    data: any[]
    links: any[]
    from: number
    to: number
    total: number
  },
  isPengunjung: boolean // TANGKAP VARIABEL DARI CONTROLLER
}>()

const page = usePage()

function destroyKunjungan(id: number) {
  if (!confirm('Yakin mau hapus data kunjungan ini?')) return
  router.delete(`/admin/kunjungans/${id}`)
}
// import { computed } from 'vue'

// const props = defineProps<{
//   kunjungans: {
//     data: any[]
//     links: any[]
//     from: number
//     to: number
//     total: number
//   }
// }>()

// const page = usePage<any>() 

// const isPengunjung = computed(() => {
//   const roles = page.props.auth?.user?.roles || [];
//   return roles.includes('Pengunjung');
// });

// function destroyKunjungan(id: number) {
//   if (!confirm('Yakin mau hapus data kunjungan ini?')) return
//   router.delete(`/admin/kunjungans/${id}`)
// }
</script>

<template>
  <Head title="Data Kunjungan" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 bg-background text-foreground">
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

      <div class="rounded-xl border border-border bg-card text-card-foreground shadow-sm overflow-hidden mt-4">
        <div class="relative w-full overflow-auto">
          <table class="w-full caption-bottom text-sm">
            <thead class="[&_tr]:border-b border-border">
              <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Waktu Kunjungan</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Nama Pengunjung</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Nama WB </th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Status Kunjungan</th>
                <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">Aksi</th>
              </tr>
            </thead>
            <tbody class="[&_tr:last-child]:border-0">
              <tr v-for="k in kunjungans.data" :key="k.id" class="border-b border-border transition-colors hover:bg-muted/50">
                <td class="p-4">
                  <div class="font-medium text-primary">{{ k.tanggal_kunjungan }}</div>
                  <div class="text-xs text-muted-foreground">Jam: {{ k.waktu_kunjungan }}</div>
                </td>
                <td class="p-4">
                  <div class="font-medium">{{ k.pengunjung?.user?.name ?? 'Tidak diketahui' }}</div>
                  <div class="text-xs text-muted-foreground">Lokasi: {{ k.upt?.name ?? '-' }}</div>
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
                <td colspan="5" class="p-8 text-center text-muted-foreground">Belum ada data riwayat kunjungan.</td>
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
            <Link v-else :href="link.url" class="px-3 py-1 text-sm rounded-md border transition-colors" :class="link.active ? 'bg-primary text-primary-foreground border-primary' : 'border-border bg-background hover:bg-accent hover:text-accent-foreground'" v-html="link.label" />
          </template>
        </div>
      </div>
    </div>
  </AppLayout>
</template>