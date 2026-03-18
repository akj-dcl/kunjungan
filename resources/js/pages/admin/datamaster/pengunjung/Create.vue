<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  username:'',
  email: '',
  password: '',
  jenis_kelamin: 'Laki-laki',
  no_ktp: '',
  no_hp: '',
  alamat: '',
  foto_diri: null as File | null,
  foto_ktp: null as File | null,
})

function submit() {
  form.post('/admin/pengunjungs')
}
</script>

<template>
  <Head title="Tambah Pengunjung" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 bg-background text-foreground">
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight">Tambah Pengunjung</h1>
          <p class="text-sm text-muted-foreground">Input data pengunjung secara manual.</p>
        </div>
        <Link href="/admin/pengunjungs" class="inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground">Kembali</Link>
      </div>

      <div class="rounded-xl border border-border bg-card text-card-foreground shadow-sm">
        <form @submit.prevent="submit" class="p-6 space-y-6">
          <div class="grid gap-6 md:grid-cols-2">
            
            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Nama Lengkap</label>
              <input v-model="form.name" type="text" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:opacity-50" />
              <div v-if="form.errors.name" class="text-sm font-medium text-destructive">{{ form.errors.name }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Username Login *</label>
              <input v-model="form.username" type="text" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:opacity-50" required />
              <div v-if="form.errors.username" class="text-sm font-medium text-destructive">{{ form.errors.username }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Email Akun (Opsional)</label>
              <input v-model="form.email" type="email" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:opacity-50" />
              <div v-if="form.errors.email" class="text-sm font-medium text-destructive">{{ form.errors.email }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Password Login</label>
              <input v-model="form.password" type="password" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:opacity-50" />
              <div v-if="form.errors.password" class="text-sm font-medium text-destructive">{{ form.errors.password }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Jenis Kelamin</label>
              <select v-model="form.jenis_kelamin" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:opacity-50">
                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>
              </select>
              <div v-if="form.errors.jenis_kelamin" class="text-sm font-medium text-destructive">{{ form.errors.jenis_kelamin }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Nomor KTP (NIK)</label>
              <input v-model="form.no_ktp" type="text" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:opacity-50" />
              <div v-if="form.errors.no_ktp" class="text-sm font-medium text-destructive">{{ form.errors.no_ktp }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Nomor HP</label>
              <input v-model="form.no_hp" type="text" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:opacity-50" />
              <div v-if="form.errors.no_hp" class="text-sm font-medium text-destructive">{{ form.errors.no_hp }}</div>
            </div>

            <div class="md:col-span-2 space-y-2">
              <label class="text-sm font-medium leading-none">Alamat Lengkap</label>
              <textarea v-model="form.alamat" rows="3" class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:opacity-50"></textarea>
              <div v-if="form.errors.alamat" class="text-sm font-medium text-destructive">{{ form.errors.alamat }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Foto Diri</label>
              <input type="file" @input="(e) => form.foto_diri = (e.target as HTMLInputElement).files?.[0] || null" accept="image/*" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-muted-foreground file:border-0 file:bg-transparent file:text-sm file:font-medium" />
              <div v-if="form.errors.foto_diri" class="text-sm font-medium text-destructive">{{ form.errors.foto_diri }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Foto KTP</label>
              <input type="file" @input="(e) => form.foto_ktp = (e.target as HTMLInputElement).files?.[0] || null" accept="image/*" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-muted-foreground file:border-0 file:bg-transparent file:text-sm file:font-medium" />
              <div v-if="form.errors.foto_ktp" class="text-sm font-medium text-destructive">{{ form.errors.foto_ktp }}</div>
            </div>

          </div>
          <div class="flex justify-end pt-4 border-t mt-6">
            <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center rounded-md bg-primary px-8 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 disabled:opacity-50">Simpan Pengunjung</button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>