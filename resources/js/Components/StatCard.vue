<script setup lang="ts">
defineProps<{
    title: string;
    value: number | string;
    icon: string;
    subtitle?: string;
    trend?: string;
    variant?: 'blue' | 'emerald' | 'amber' | 'rose';
}>();
</script>

<template>
    <div class="st-card st-card-interactive stat-card-blue animate-fade-in">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="stat-label text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">{{ title }}</span>
            <div class="stat-icon">
                <i :class="['bi', icon]"></i>
            </div>
        </div>
        <div class="stat-number mb-1">{{ value }}</div>
        <div v-if="subtitle || trend" class="d-flex align-items-center justify-content-between">
            <span v-if="subtitle" class="text-muted small">{{ subtitle }}</span>
            <span v-if="trend" class="badge bg-primary-subtle text-primary fw-semibold">{{ trend }}</span>
        </div>
    </div>
</template>
