<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentUrl = computed(() => page.url);

const tabs = [
    { id: 'units', label: 'Unit Kerja', icon: 'bi-building', href: '/master/units' },
    { id: 'users', label: 'User & Akses', icon: 'bi-people', href: '/master/users' },
    { id: 'types', label: 'Jenis Naskah', icon: 'bi-tag', href: '/master/number-types' },
    { id: 'categories', label: 'Kategori Surat', icon: 'bi-folder', href: '/master/categories' },
    { id: 'alur', label: 'Alur Status', icon: 'bi-bezier2', href: '/alur-status' },
    { id: 'rekap', label: 'Rekap Master', icon: 'bi-bar-chart-line', href: '/master/rekap' },
];

const isActive = (href: string) => currentUrl.value.startsWith(href);
</script>

<template>
    <div class="flex gap-2 flex-wrap mb-4 pb-1">
        <Link
            v-for="tab in tabs"
            :key="tab.id"
            :href="tab.href"
            class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-semibold transition-all text-decoration-none"
            :class="isActive(tab.href)
                ? 'text-white shadow-sm'
                : 'text-slate-600 bg-white hover:bg-slate-50 border border-slate-200'"
            :style="isActive(tab.href) ? 'background: #2743AF;' : ''"
        >
            <i class="bi" :class="tab.icon"></i>
            <span>{{ tab.label }}</span>
        </Link>
    </div>
</template>
