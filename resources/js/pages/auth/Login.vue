<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <Head title="Masuk (Login) - PUSDAPAS" />

    <div class="min-h-screen flex flex-col bg-gray-50 dark:bg-gray-900 font-sans text-gray-900 dark:text-gray-100 relative overflow-hidden">
        
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-blue-100 via-gray-50 to-white dark:from-blue-900/20 dark:via-gray-900 dark:to-gray-900 -z-10"></div>

        <header class="w-full bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-700 z-10 sticky top-0">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                    <div class="w-8 h-8 bg-blue-700 rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-sm">
                        K
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-md tracking-tight text-blue-900 dark:text-blue-400 leading-tight">E-Kunjungan</span>
                    </div>
                </Link>
                <Link href="/" class="text-sm font-semibold text-gray-600 hover:text-blue-700 dark:text-gray-300 transition-colors">
                    ← Kembali ke Beranda
                </Link>
            </div>
        </header>

        <div class="flex-grow flex items-center justify-center p-4 sm:p-8 z-10">
            <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-xl ring-1 ring-gray-900/5 dark:ring-white/10 overflow-hidden">
                
                <div class="px-6 py-8 sm:p-10 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 text-center">
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">LOGIN</h1>
                    <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400 mt-2">
                        Masukkan Username beserta Password Anda.
                    </p>
                </div>

                <div v-if="status" class="px-6 pt-6 text-center text-sm font-medium text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 py-3 border-b border-emerald-100 dark:border-emerald-800">
                    {{ status }}
                </div>

                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="px-6 py-8 sm:p-10 space-y-6"
                >
                    <div class="grid gap-6">
                        <div class="grid gap-2">
                            <Label for="username" class="text-sm font-medium text-gray-700 dark:text-gray-300">Username</Label>
                            <Input
                                id="username"
                                type="text"
                                name="username"
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="username"
                                placeholder="Masukkan Username atau NIP"
                                class="h-11 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm"
                            />
                            <InputError :message="errors.username" />
                        </div>

                        <div class="grid gap-2">
                            <div class="flex items-center justify-between">
                                <Label for="password" class="text-sm font-medium text-gray-700 dark:text-gray-300">Password</Label>
                                <TextLink
                                    v-if="canResetPassword"
                                    :href="request()"
                                    class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400"
                                    :tabindex="5"
                                >
                                </TextLink>
                            </div>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                :tabindex="2"
                                autocomplete="current-password"
                                placeholder="Password"
                                class="h-11 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="flex items-center justify-between mt-2">
                            <Label for="remember" class="flex items-center space-x-3 cursor-pointer">
                                <Checkbox id="remember" name="remember" :tabindex="3" class="border-gray-300 text-blue-600 focus:ring-blue-500 rounded" />
                                <span class="text-sm font-normal text-gray-600 dark:text-gray-400">Ingat Saya</span>
                            </Label>
                        </div>

                        <Button
                            type="submit"
                            class="mt-4 w-full h-11 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded-lg shadow-lg shadow-blue-700/30 transition-all"
                            :tabindex="4"
                            :disabled="processing"
                            data-test="login-button"
                        >
                            <Spinner v-if="processing" class="mr-2" />
                            Log in Sekarang
                        </Button>
                    </div>

                    <div class="text-center text-sm text-gray-500 mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                        Belum punya akun Pengunjung?
                        <Link href="/register-pengunjung" class="text-blue-600 hover:text-blue-800 font-semibold dark:text-blue-400 hover:underline transition-colors" :tabindex="5">
                            Daftar di sini
                        </Link>
                    </div>
                </Form>
            </div>
        </div>

        <footer class="py-6 text-center text-xs text-gray-500 dark:text-gray-400 bg-transparent z-10">
            <p>&copy; {{ new Date().getFullYear() }} Kementerian Imigrasi dan Pemasyarakatan RI.</p>
        </footer>
    </div>
</template>