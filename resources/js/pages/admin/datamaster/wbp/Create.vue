<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3'

type Upt = { id: number; name: string }
type JenisKejahatan = { id: number; nama_kejahatan: string }
type Blok = { nama_blok: string }
type Sel = { id: number; nama_sel: string; blok?: Blok }

const props = defineProps<{
  upts: Upt[]
  jenis_kejahatans: JenisKejahatan[]
  sels: Sel[]
}>()

const form = useForm({
  upt_id: props.upts?.[0]?.id ? String(props.upts[0].id) : '',
  no_reg_instansi: '',
  nama: '',
  alamat: '',
  jenis_kejahatan_id: props.jenis_kejahatans?.[0]?.id ? String(props.jenis_kejahatans[0].id) : '',
  sel_id: props.sels?.[0]?.id ? String(props.sels[0].id) : '',
  is_aktif: true,
})

function submit() {
  form.transform((data) => ({
    ...data,
    upt_id: Number(data.upt_id),
    jenis_kejahatan_id: Number(data.jenis_kejahatan_id),
    sel_id: Number(data.sel_id),
  })).post('/admin/wbps')
}
</script>

<template>
  <Head title="Tambah WBP" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 bg-background text-foreground">
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight">Tambah WBP</h1>
          <p class="text-sm text-muted-foreground">Input data Warga Binaan Pemasyarakatan baru.</p>
        </div>
        <Link href="/admin/wbps" class="inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground">Kembali</Link>
      </div>

      <div class="rounded-xl border border-border bg-card text-card-foreground shadow-sm">
        <form @submit.prevent="submit" class="p-6 space-y-6">
          <div class="grid gap-6 md:grid-cols-2">
            
            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Lokasi UPT (Lapas)</label>
              <select v-model="form.upt_id" :disabled="upts.length === 1" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50">
                <option v-for="u in upts" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
              </select>
              <div v-if="form.errors.upt_id" class="text-sm font-medium text-destructive">{{ form.errors.upt_id }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Nomor Registrasi Instansi</label>
              <input v-model="form.no_reg_instansi" type="text" placeholder="Contoh: BI. 123/2026" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50" />
              <div v-if="form.errors.no_reg_instansi" class="text-sm font-medium text-destructive">{{ form.errors.no_reg_instansi }}</div>
            </div>

            <div class="md:col-span-2 space-y-2">
              <label class="text-sm font-medium leading-none">Nama Lengkap WBP</label>
              <input v-model="form.nama" type="text" placeholder="Nama Lengkap / Alias" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50" />
              <div v-if="form.errors.nama" class="text-sm font-medium text-destructive">{{ form.errors.nama }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Jenis Kejahatan</label>
              <select v-model="form.jenis_kejahatan_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50">
                <option v-for="jk in jenis_kejahatans" :key="jk.id" :value="String(jk.id)">{{ jk.nama_kejahatan }}</option>
              </select>
              <div v-if="form.errors.jenis_kejahatan_id" class="text-sm font-medium text-destructive">{{ form.errors.jenis_kejahatan_id }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Lokasi Sel / Kamar</label>
              <select v-model="form.sel_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50">
                <option v-for="s in sels" :key="s.id" :value="String(s.id)">
                  Blok {{ s.blok?.nama_blok ?? '?' }} - Sel {{ s.nama_sel }}
                </option>
              </select>
              <div v-if="form.errors.sel_id" class="text-sm font-medium text-destructive">{{ form.errors.sel_id }}</div>
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Status WBP</label>
              <select v-model="form.is_aktif" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50">
                <option :value="true">Aktif (Berada di Lapas)</option>
                <option :value="false">Non-Aktif (Bebas / Pindah / Meninggal)</option>
              </select>
            </div>
            <div class="md:col-span-2 space-y-2">
              <label class="text-sm font-medium leading-none">Alamat Lengkap (Opsional)</label>
              <textarea v-model="form.alamat" rows="3" class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50"></textarea>
              <div v-if="form.errors.alamat" class="text-sm font-medium text-destructive">{{ form.errors.alamat }}</div>
            </div>

          </div>
          <div class="flex justify-end pt-4 border-t mt-6">
            <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center rounded-md bg-primary px-8 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 disabled:opacity-50">Simpan WBP</button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>