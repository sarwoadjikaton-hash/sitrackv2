<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: string;
}>();

const badgeClass = computed(() => {
    switch (props.status) {
        case 'Dokumen Sudah diambil':
        case 'Dokumen Sudah Diambil':
        case 'Sudah Diambil':
        case 'Sudah diambil':
        case 'Selesai dan Siap Untuk diambil':
        case 'Selesai dan Siap Untuk Diambil':
        case 'Selesai':
        case 'Tuntas':
        case 'Surat Selesai di Paraf/TTD dan bisa diambil':
        case 'completed':
            return 'badge-status-completed';
        case 'Proses Paraf/TTD Sekjen':
        case 'Diajukan ke Sekjen':
        case 'Dalam Tindak Lanjut':
        case 'in_progress':
            return 'badge-status-waiting';
        case 'Ditolak':
        case 'Dikembalikan':
        case 'Revisi':
        case 'cancelled':
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
