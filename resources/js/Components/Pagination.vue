<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: { url?: string; label: string; active: boolean }[];
}>();
</script>

<template>
    <nav v-if="links.length > 3" aria-label="Navigasi halaman">
        <ul class="pagination pagination-sm justify-content-end mb-0 gap-1">
            <li
                v-for="(link, key) in links"
                :key="key"
                class="page-item"
                :class="{ active: link.active, disabled: !link.url }"
            >
                <Link
                    v-if="link.url"
                    class="page-link rounded-2 px-3 py-2 fw-semibold"
                    :href="link.url"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="page-link rounded-2 px-3 py-2 text-muted"
                    v-html="link.label"
                />
            </li>
        </ul>
    </nav>
</template>
