<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: string;
}>();

const badgeClass = computed(() => {
    switch (props.status) {
        case 'Dokumen Sudah diambil':
        case 'Selesai':
            return 'badge-status-completed';
        case 'Proses Paraf/TTD Sekjen':
        case 'Diajukan ke Sekjen':
        case 'Dalam Tindak Lanjut':
            return 'badge-status-waiting';
        case 'Ditolak':
        case 'Dikembalikan':
            return 'badge-status-cancelled';
        default:
            return 'badge-status-processing';
    }
});
</script>

<template>
    <span :class="['badge-status', badgeClass]">
        <span class="badge-dot animate__animated animate__pulse animate__infinite"></span>
        <span>{{ status }}</span>
    </span>
</template>
