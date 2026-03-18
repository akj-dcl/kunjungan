<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Selamat Datang - PUSDAPAS" />
    
    <div class="flex min-h-screen flex-col bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 font-sans">
        
        <header class="w-full bg-white dark:bg-gray-800 shadow-sm z-10 sticky top-0">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-700 rounded-lg flex items-center justify-center text-white font-bold text-xl shadow-md">
                        K
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-lg tracking-tight text-blue-900 dark:text-blue-400 leading-tight">E-Kunjungan</span>
                        <span class="text-[10px] text-gray-500 font-medium">Kanwil NTB</span>
                    </div>
                </div>
                <nav class="flex items-center gap-4">
                    <Link v-if="$page.props.auth.user" :href="dashboard()" class="text-sm font-semibold text-gray-700 hover:text-blue-700 dark:text-gray-300 dark:hover:text-blue-400 transition-colors">
                        Masuk Dashboard
                    </Link>
                    <template v-else>
                        <Link :href="login()" class="text-sm font-semibold text-gray-700 hover:text-blue-700 dark:text-gray-300 dark:hover:text-blue-400 transition-colors">
                            Login Pegawai
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="flex-grow flex items-center justify-center p-6 relative overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-blue-100 via-gray-50 to-white dark:from-blue-900/20 dark:via-gray-900 dark:to-gray-900 -z-10"></div>

            <div class="max-w-6xl w-full grid grid-cols-1 lg:grid-cols-2 gap-12 items-center z-10">
                
                <div class="space-y-6 text-center lg:text-left">
                    <div class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold text-blue-800 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 ring-1 ring-inset ring-blue-600/20 uppercase tracking-wider">
                        Layanan Publik Digital Terpadu
                    </div>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-gray-900 dark:text-white leading-tight">
                        Sistem Layanan <br/>
                        <span class="text-blue-700 dark:text-blue-500">Kunjungan Lapas</span>
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto lg:mx-0">
                        E-Kunjungan mempermudah masyarakat dalam melakukan pendaftaran kunjungan dan penitipan barang untuk Warga Binaan secara cepat, aman, dan transparan.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start pt-4">
                        <Link v-if="!$page.props.auth.user" href="/register-pengunjung" class="inline-flex justify-center items-center rounded-lg bg-blue-700 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-700/30 hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 transition-all">
                            📝 Buat Akun Pengunjung
                        </Link>
                        <Link v-if="!$page.props.auth.user" :href="login()" class="inline-flex justify-center items-center rounded-lg bg-white dark:bg-gray-800 px-6 py-3 text-sm font-bold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
                            Masuk (Log in)
                        </Link>
                        <Link v-if="$page.props.auth.user" :href="dashboard()" class="inline-flex justify-center items-center rounded-lg bg-blue-700 px-8 py-3 text-sm font-bold text-white shadow-lg shadow-blue-700/30 hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 transition-all">
                            Buka Dashboard Sistem
                        </Link>
                    </div>
                </div>

                <div class="hidden lg:block relative">
                    <div class="absolute -inset-1 rounded-2xl bg-gradient-to-br from-blue-600 to-cyan-400 opacity-20 blur-xl dark:opacity-30"></div>
                    <div class="relative rounded-2xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-gray-900/5 p-8">
                        <div class="flex items-center justify-center border-b border-gray-100 dark:border-gray-700 pb-6 mb-6">
                            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100">Informasi Layanan Kami</h3>
                        </div>
                        <ul class="space-y-5 text-sm text-gray-600 dark:text-gray-300">
                            <li class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold">✓</span>
                                <span class="mt-0.5">Pendaftaran Kunjungan Dapat Dilakukan secara Online.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold">✓</span>
                                <span class="mt-0.5">Pendaftaran Titipan Barang dan Makanan untuk Warga Binaan.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold">✓</span>
                                <span class="mt-0.5">Cetak Tiket QR Code untuk kemudahan dan kecepatan verifikasi di loket.</span>
                            </li>
                            <li class="flex items-start gap-3 mt-6 pt-6 border-t border-gray-100 dark:border-gray-700 bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                                <span class="text-red-500 text-xl leading-none font-bold">!</span>
                                <span class="font-semibold text-red-700 dark:text-red-400 leading-tight">PERHATIAN: Seluruh layanan Pemasyarakatan ini 100% GRATIS dan tidak dipungut biaya apapun.</span>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </main>

        <footer class="py-6 text-center text-sm text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
            <p class="font-medium">&copy; {{ new Date().getFullYear() }} Kementerian Imigrasi dan Pemasyarakatan RI.</p>
            <p class="text-xs mt-1">Kantor Wilayah Nusa Tenggara Barat - Dikembangkan oleh Tim IT Kanwil NTB.</p>
        </footer>
        
    </div>
</template>