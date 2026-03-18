<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  code: '',
  address: '',
  is_active: true,
})

function submit() {
  form.post('/admin/kanwils')
}
</script>

<template>
  <Head title="Tambah Kanwil" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 bg-background text-foreground">
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight">Tambah Kanwil</h1>
          <p class="text-sm text-muted-foreground">Isi data Kantor Wilayah baru.</p>
        </div>

        <Link
          href="/admin/kanwils"
          class="inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
        >
          Kembali
        </Link>
      </div>

      <div class="rounded-xl border border-border bg-card text-card-foreground shadow-sm">
        <form @submit.prevent="submit" class="p-6 space-y-6">
          <div class="grid gap-6 md:grid-cols-2">
            <div class="md:col-span-2 space-y-2">
              <label class="text-sm font-medium leading-none">Nama Kanwil</label>
              <input
                v-model="form.name"
                type="text"
                placeholder="Contoh: Kanwil Kemenkumham NTB"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50"
              />
              <div v-if="form.errors.name" class="text-sm font-medium text-destructive">{{ form.errors.name }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Kode (Opsional)</label>
              <input
                v-model="form.code"
                type="text"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50"
              />
              <div v-if="form.errors.code" class="text-sm font-medium text-destructive">{{ form.errors.code }}</div>
            </div>

            <div class="flex items-end pb-2">
              <div class="flex items-center space-x-2">
                <input
                  id="is_active"
                  type="checkbox"
                  v-model="form.is_active"
                  class="h-4 w-4 rounded border-primary text-primary shadow focus:ring-primary"
                />
                <label for="is_active" class="text-sm font-medium leading-none cursor-pointer">Status Aktif</label>
              </div>
            </div>

            <div class="md:col-span-2 space-y-2">
              <label class="text-sm font-medium leading-none">Alamat</label>
              <textarea
                v-model="form.address"
                rows="3"
                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50"
              ></textarea>
              <div v-if="form.errors.address" class="text-sm font-medium text-destructive">{{ form.errors.address }}</div>
            </div>
          </div>

          <div class="flex justify-end pt-4 border-t mt-6">
            <button
              type="submit"
              :disabled="form.processing"
              class="inline-flex items-center justify-center rounded-md bg-primary px-8 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 disabled:opacity-50"
            >
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>