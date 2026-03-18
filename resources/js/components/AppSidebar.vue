<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Album, BookOpen, Database, FolderGit2, LayoutGrid, ScanQrCode, Shield } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

// Ambil data roles dari halaman
const page = usePage<any>();
const userRoles = computed(() => page.props.auth?.user?.roles || []);

// Helper untuk mengecek role
const hasRole = (role: string) => userRoles.value.includes(role);

// Filter Menu Berdasarkan Role
const mainNavItems = computed<NavItem[]>(() => {
    let items: NavItem[] = [];

    // 1. PENGUNJUNG: Hanya melihat menu Data Kunjungan
    if (hasRole('Pengunjung')) {
        return [
            { title: 'Data Kunjungan', icon: Album, href: '/admin/kunjungans' }
        ];
    }

    // 2. OPERATOR LAPAS: Hanya melihat menu Scan QR
    if (hasRole('Operator Lapas')) {
        return [
            { title: 'Scan QR', icon: ScanQrCode, href: '/admin/scan-qr' }
        ];
    }

    // 3. SUPER ADMIN & ROLE LAINNYA: Tampilkan berdasarkan hak akses
    
    items.push({ title: 'Dashboard', href: '/dashboard', icon: LayoutGrid });

    // Menu Manajemen Role (Hanya untuk Super Admin atau Admin Kanwil)
    if (hasRole('Super Admin') || hasRole('Admin Kanwil')) {
        items.push({ title: 'Manajemen Akses (Role)', icon: Shield, href: '/admin/roles' });
    }

    // Menu Data Master (Dinamis berdasarkan Role)
    if (!hasRole('Operator Lapas')) { 
        // Menu dasar yang bisa dilihat semua role (Super Admin, Kanwil, Lapas)
        let masterChildren = [
            { title: 'Data WBP', href: '/admin/wbps' },
            { title: 'Lokasi Blok', href: '/admin/bloks' },
            { title: 'Lokasi Sel', href: '/admin/sels' },
            { title: 'Jenis kejahatan', href: '/admin/jenis-kejahatans' },
        ];

        // Tambahkan Data Pengunjung & Data UPT (Hanya untuk Kanwil & Super Admin)
        // Admin Lapas & Supervisor Lapas TIDAK BISA lihat ini
        if (hasRole('Super Admin') || hasRole('Admin Kanwil') || hasRole('Supervisor Kanwil')) {
            masterChildren.push({ title: 'Data Pengunjung', href: '/admin/pengunjungs' });
            masterChildren.push({ title: 'Data UPT', href: '/admin/upts' });
        }

        // Tambahkan Data Kanwil & Data Akun (HANYA untuk Super Admin)
        // Admin/Supervisor Kanwil & Lapas TIDAK BISA lihat ini
        if (hasRole('Super Admin')) {
            masterChildren.push({ title: 'Data Kanwil', href: '/admin/kanwils' });
            masterChildren.push({ title: 'Data Akun', href: '/admin/data-akun' });
        }

        items.push({
            title: 'Data Master',
            icon: Database,
            children: masterChildren
        });
    }

    // Menu Scan QR
    if (hasRole('Super Admin') || hasRole('Admin Lapas') || hasRole('Operator Lapas')) {
        items.push({ title: 'Scan QR', icon: ScanQrCode, href: '/admin/scan-qr' });
    }

    // Menu Data Kunjungan
    items.push({ title: 'Data Kunjungan', icon: Album, href: '/admin/kunjungans' });

    return items;
});

const footerNavItems: NavItem[] = [
    // { title: 'Github Repo', href: 'https://github.com/laravel/vue-starter-kit', icon: FolderGit2 },
    // { title: 'Documentation', href: 'https://laravel.com/docs/starter-kits#vue', icon: BookOpen },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>