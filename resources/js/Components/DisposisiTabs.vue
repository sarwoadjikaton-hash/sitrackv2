<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentUrl = computed(() => page.url);

const tabs = [
    { id: 'disposisi', label: 'Lajur Disposisi', icon: 'bi-send-fill', href: '/disposisi', exact: true },
    { id: 'create-disposisi', label: 'Input Surat Disposisi', icon: 'bi-plus-circle', href: '/disposisi/create', exact: true },
];

const isActive = (tab: { href: string; exact?: boolean }) => {
    if (tab.exact) {
        return currentUrl.value === tab.href || currentUrl.value.split('?')[0] === tab.href;
    }
    return currentUrl.value.startsWith(tab.href);
};
</script>

<template>
    <div class="flex gap-2 flex-wrap mb-4 pb-1">
        <Link
            v-for="tab in tabs"
            :key="tab.id"
            :href="tab.href"
            class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-semibold transition-all text-decoration-none"
            :class="isActive(tab)
                ? 'text-white shadow-sm'
                : 'text-slate-600 bg-white hover:bg-slate-50 border border-slate-200'"
            :style="isActive(tab) ? 'background: #2743AF;' : ''"
        >
            <i class="bi" :class="tab.icon"></i>
            <span>{{ tab.label }}</span>
        </Link>
    </div>
</template>
