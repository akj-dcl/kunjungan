<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3'

type Blok = { id: number; nama_blok: string; upt?: { name: string } }
type Sel = {
  id: number
  blok_id: number
  nama_sel: string
}

const props = defineProps<{
  sel: Sel
  bloks: Blok[]
}>()

const form = useForm({
  blok_id: String(props.sel.blok_id ?? ''),
  nama_sel: props.sel.nama_sel ?? '',
})

function submit() {
  form.transform((data) => ({
    ...data,
    blok_id: Number(data.blok_id),
  })).put(`/admin/sels/${props.sel.id}`)
}
</script>

<template>
  <Head title="Edit Sel" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 bg-background text-foreground">
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight">Edit Sel</h1>
          <p class="text-sm text-muted-foreground">Perbarui data lokasi sel.</p>
        </div>
        <Link href="/admin/sels" class="inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground">Kembali</Link>
      </div>

      <div class="rounded-xl border border-border bg-card text-card-foreground shadow-sm">
        <form @submit.prevent="submit" class="p-6 space-y-6">
          <div class="grid gap-6 md:grid-cols-2">
            
            <div class="md:col-span-2 space-y-2">
              <label class="text-sm font-medium leading-none">Lokasi Blok</label>
              <select v-model="form.blok_id" :disabled="bloks.length === 1" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50">
                <option v-for="b in bloks" :key="b.id" :value="String(b.id)">
                  {{ b.upt?.name ?? 'Tanpa UPT' }} - Blok {{ b.nama_blok }}
                </option>
              </select>
              <div v-if="form.errors.blok_id" class="text-sm font-medium text-destructive">{{ form.errors.blok_id }}</div>
            </div>

            <div class="md:col-span-2 space-y-2">
              <label class="text-sm font-medium leading-none">Nama Sel / Kamar</label>
              <input v-model="form.nama_sel" type="text" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50" />
              <div v-if="form.errors.nama_sel" class="text-sm font-medium text-destructive">{{ form.errors.nama_sel }}</div>
            </div>
            
          </div>
          <div class="flex justify-end pt-4 border-t mt-6">
            <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center rounded-md bg-primary px-8 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 disabled:opacity-50">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>