<script setup lang="ts">
import { computed } from 'vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { LetterStatusLog } from '@/types';

const props = withDefaults(
    defineProps<{
        logs?: LetterStatusLog[];
        currentStatus?: string;
        currentPosition?: string;
        createdDate?: string;
        emptyMessage?: string;
    }>(),
    {
        logs: () => [],
        emptyMessage: 'Belum ada catatan riwayat perjalanan untuk berkas ini.',
    }
);

const formatDateTime = (dateStr?: string) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    const date = d.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
    const time = d.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).replace('.', ':') + ' WIB';
    return `${date} • ${time}`;
};

// Urutkan log dari yang terbaru di paling atas
const sortedLogs = computed(() => {
    if (!props.logs || props.logs.length === 0) return [];
    return [...props.logs].sort((a, b) => {
        const timeA = new Date(a.changed_at || (a as any).created_at || 0).getTime();
        const timeB = new Date(b.changed_at || (b as any).created_at || 0).getTime();
        return timeB - timeA;
    });
});

const getStatusColor = (status: string) => {
    const s = (status || '').toLowerCase();
    if (s.includes('selesai') || s.includes('sudah diambil') || s.includes('tuntas')) return '#10B981';
    if (s.includes('revisi')) return '#F59E0B';
    if (s.includes('tolak') || s.includes('kembali')) return '#EF4444';
    return '#2743AF';
};
</script>

<template>
    <div class="status-history-feed">
        <!-- If Logs Exist -->
        <div v-if="sortedLogs.length > 0" class="activity-timeline position-relative py-1">
            <!-- Vertical continuous line -->
            <div class="timeline-vertical-line"></div>

            <div
                v-for="(log, idx) in sortedLogs"
                :key="log.id || idx"
                class="timeline-step-item position-relative mb-3.5"
                :class="{ 'is-last-item': idx === sortedLogs.length - 1 }"
            >
                <!-- Bullet Node Dot -->
                <div
                    class="timeline-node shadow-xs"
                    :style="{
                        borderColor: getStatusColor(log.status),
                        backgroundColor: idx === 0 ? getStatusColor(log.status) : '#ffffff'
                    }"
                >
                    <i v-if="idx === 0" class="bi bi-check-lg text-white" style="font-size: 0.65rem; font-weight: 900;"></i>
                    <span v-else class="node-inner-dot" :style="{ backgroundColor: getStatusColor(log.status) }"></span>
                </div>

                <!-- Main Card Content -->
                <div
                    class="timeline-step-card rounded-3 border bg-white p-3 transition"
                    :class="idx === 0 ? 'border-primary-subtle shadow-sm' : 'border-slate-200 shadow-xs'"
                >
                    <!-- Top Row: Badge & Timestamp -->
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2.5 flex-wrap">
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                            <StatusBadge :status="log.status" />
                            <span v-if="idx === 0" class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold rounded-pill px-2 py-0.5" style="font-size: 0.68rem;">
                                Posisi Terkini
                            </span>
                        </div>
                        <span class="text-muted small fw-medium d-flex align-items-center gap-1" style="font-size: 0.76rem;">
                            <i class="bi bi-clock text-slate-400"></i>
                            {{ formatDateTime(log.changed_at || (log as any).created_at) }}
                        </span>
                    </div>

                    <!-- Meta details: Posisi & Petugas -->
                    <div class="row g-2 mb-2 text-muted small" style="font-size: 0.8rem;">
                        <div v-if="log.position" class="col-sm-6 d-flex align-items-center gap-1.5">
                            <i class="bi bi-geo-alt-fill text-primary opacity-75"></i>
                            <span class="text-slate-500">Posisi:</span>
                            <span class="text-dark fw-semibold">{{ log.position }}</span>
                        </div>
                        <div v-if="log.changed_by" class="col-sm-6 d-flex align-items-center gap-1.5">
                            <i class="bi bi-person-fill text-slate-400"></i>
                            <span class="text-slate-500">Petugas:</span>
                            <span class="text-dark fw-medium">{{ log.changed_by }}</span>
                        </div>
                    </div>

                    <!-- Note Bubble (Clean Soft Quote Style) -->
                    <div v-if="log.note" class="log-note-bubble rounded-2 p-2 px-2.5 small mt-2">
                        <div class="d-flex align-items-start gap-2 text-slate-700">
                            <i class="bi bi-chat-quote-fill text-primary opacity-60 flex-shrink-0 mt-0.5" style="font-size: 0.8rem;"></i>
                            <span class="leading-relaxed" style="font-size: 0.8rem;">{{ log.note }}</span>
                        </div>
                    </div>

                    <!-- Attached File (if any) -->
                    <div v-if="log.attachment_path" class="mt-2.5 pt-2 border-top border-slate-100 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="small text-truncate text-muted d-flex align-items-center gap-1.5" style="max-width: 260px;" :title="log.attachment_name || log.attachment_path.split('/').pop()">
                            <i class="bi bi-paperclip text-primary"></i>
                            <span class="fw-medium text-dark text-truncate">{{ log.attachment_name || log.attachment_path.split('/').pop() }}</span>
                        </div>
                        <a
                            :href="`/lampiran/view/${log.attachment_path}`"
                            target="_blank"
                            class="btn btn-sm btn-light border py-1 px-2.5 rounded-2 text-decoration-none small d-flex align-items-center gap-1 text-primary fw-semibold"
                            style="font-size: 0.75rem;"
                        >
                            <i class="bi bi-download"></i> Unduh Lampiran
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-4 px-3 bg-slate-50 rounded-3 border border-dashed border-slate-200 text-muted">
            <div class="w-10 h-10 rounded-circle bg-white text-primary d-inline-flex align-items-center justify-content-center shadow-xs mb-2">
                <i class="bi bi-clock-history fs-5"></i>
            </div>
            <p class="small mb-1 fw-semibold text-dark">{{ emptyMessage }}</p>
            <p v-if="currentPosition || currentStatus" class="small text-muted mb-0" style="font-size: 0.75rem;">
                Posisi saat ini: <strong class="text-dark">{{ currentPosition || '-' }}</strong> &bull; Status: <strong class="text-dark">{{ currentStatus || '-' }}</strong>
            </p>
        </div>
    </div>
</template>

<style scoped>
.status-history-feed {
    position: relative;
}

.activity-timeline {
    position: relative;
    padding-left: 2.25rem;
}

.timeline-vertical-line {
    position: absolute;
    left: 17px;
    top: 18px;
    bottom: 24px;
    width: 2px;
    background: #E2E8F0;
    border-radius: 2px;
    z-index: 1;
}

.timeline-step-item {
    position: relative;
}

.timeline-step-item.is-last-item {
    margin-bottom: 0 !important;
}

.timeline-node {
    position: absolute;
    left: -2.25rem;
    margin-left: 8px;
    top: 12px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border-width: 2.5px;
    border-style: solid;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    transition: all 0.2s ease;
}

.node-inner-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.timeline-step-card {
    transition: all 0.2s ease;
}

.timeline-step-card:hover {
    border-color: #CBD5E1 !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04) !important;
}

.log-note-bubble {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-left: 3px solid #2743AF;
}
</style>
