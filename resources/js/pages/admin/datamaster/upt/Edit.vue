<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3'

type Kanwil = { id: number; name: string }
type Upt = {
  id: number
  kanwil_id: number
  name: string
  address: string | null
  is_active: boolean
}

const props = defineProps<{
  upt: Upt
  kanwils: Kanwil[]
}>()

const form = useForm({
  kanwil_id: String(props.upt.kanwil_id ?? ''),
  name: props.upt.name ?? '',
  address: props.upt.address ?? '',
  is_active: !!props.upt.is_active,
})

function submit() {
  form.transform((data) => ({
    ...data,
    kanwil_id: Number(data.kanwil_id),
  })).put(`/admin/upts/${props.upt.id}`)
}
</script>

<template>
  <Head title="Edit UPT" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 bg-background text-foreground">
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight">Edit UPT</h1>
          <p class="text-sm text-muted-foreground">Perbarui data Unit Pelaksana Teknis.</p>
        </div>

        <Link
          href="/admin/upts"
          class="inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
        >
          Kembali
        </Link>
      </div>

      <div class="rounded-xl border border-border bg-card text-card-foreground shadow-sm">
        <form @submit.prevent="submit" class="p-6 space-y-6">
          <div class="grid gap-6 md:grid-cols-2">
            
            <div class="md:col-span-2 space-y-2">
              <label class="text-sm font-medium leading-none">Kanwil</label>
              <select
                v-model="form.kanwil_id"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50"
              >
                <option v-for="k in kanwils" :key="k.id" :value="String(k.id)">{{ k.name }}</option>
              </select>
              <div v-if="form.errors.kanwil_id" class="text-sm font-medium text-destructive">{{ form.errors.kanwil_id }}</div>
            </div>

            <div class="md:col-span-2 space-y-2">
              <label class="text-sm font-medium leading-none">Nama UPT</label>
              <input
                v-model="form.name"
                type="text"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50"
              />
              <div v-if="form.errors.name" class="text-sm font-medium text-destructive">{{ form.errors.name }}</div>
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
            
          </div>

          <div class="flex justify-end pt-4 border-t mt-6">
            <button
              type="submit"
              :disabled="form.processing"
              class="inline-flex items-center justify-center rounded-md bg-primary px-8 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 disabled:opacity-50"
            >
              Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>