<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    user: any;
    roles: { id: number, name: string }[];
    upts: { id: number, name: string }[];
    userRole: string;
}>();

const form = useForm({
    name: props.user.name,
    nip: props.user.nip,
    jabatan: props.user.jabatan,
    role: props.userRole,
    upt_id: props.user.upt_id ? props.user.upt_id : '',
    password: '', // Kosongkan password secara default
});

const submit = () => {
    form.put(`/admin/data-akun/${props.user.id}`);
};
</script>

<template>
    <Head title="Edit Akun Pegawai" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 bg-background text-foreground">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Edit Akun: <span class="text-primary">{{ user.name }}</span></h1>
                    <p class="text-sm text-muted-foreground">Perbarui informasi jabatan, penempatan, atau hak akses pegawai.</p>
                </div>
                <Link href="/admin/data-akun" class="inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground">Kembali</Link>
            </div>

            <div class="rounded-xl border border-border bg-card text-card-foreground shadow-sm">
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <div class="grid gap-6 md:grid-cols-2">
                        
                        <div class="space-y-2">
                            <label class="text-sm font-medium leading-none">Nama Lengkap Pegawai *</label>
                            <input v-model="form.name" type="text" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50" required />
                            <div v-if="form.errors.name" class="text-sm font-medium text-destructive">{{ form.errors.name }}</div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium leading-none">NIP / Username *</label>
                            <input v-model="form.nip" type="text" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50" required />
                            <div v-if="form.errors.nip" class="text-sm font-medium text-destructive">{{ form.errors.nip }}</div>
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label class="text-sm font-medium leading-none">Jabatan *</label>
                            <input v-model="form.jabatan" type="text" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50" required />
                            <div v-if="form.errors.jabatan" class="text-sm font-medium text-destructive">{{ form.errors.jabatan }}</div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium leading-none">Hak Akses (Role) *</label>
                            <select v-model="form.role" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50" required>
                                <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                            </select>
                            <div v-if="form.errors.role" class="text-sm font-medium text-destructive">{{ form.errors.role }}</div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium leading-none">Penempatan UPT (Lapas)</label>
                            <select v-model="form.upt_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50">
                                <option value="">-- Kantor Wilayah / Bebas UPT --</option>
                                <option v-for="upt in upts" :key="upt.id" :value="upt.id">{{ upt.name }}</option>
                            </select>
                            <div v-if="form.errors.upt_id" class="text-sm font-medium text-destructive">{{ form.errors.upt_id }}</div>
                            <p class="text-xs text-muted-foreground mt-1">Biarkan kosong jika ini akun Kanwil/Super Admin.</p>
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label class="text-sm font-medium leading-none">Password Baru (Opsional)</label>
                            <input v-model="form.password" type="password" placeholder="Isi hanya jika ingin mengubah password login" class="flex h-10 w-full md:w-1/2 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50" />
                            <div v-if="form.errors.password" class="text-sm font-medium text-destructive">{{ form.errors.password }}</div>
                        </div>

                    </div>
                    <div class="flex justify-end pt-4 border-t mt-6">
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center rounded-md bg-primary px-8 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 disabled:opacity-50">
                            Update Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>