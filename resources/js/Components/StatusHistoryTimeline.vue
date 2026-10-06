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

const formatDate = (dateStr?: string) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

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
    return `${date} pukul ${time}`;
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

const getStatusType = (status: string) => {
    const s = (status || '').toLowerCase();
    if (s.includes('selesai') || s.includes('sudah diambil') || s.includes('tuntas')) return 'success';
    if (s.includes('revisi')) return 'warning';
    if (s.includes('tolak') || s.includes('kembali')) return 'danger';
    return 'primary';
};
</script>

<template>
    <div class="status-history-wrap">
        <!-- If Logs Exist -->
        <div v-if="sortedLogs.length > 0" class="timeline-container">
            <div
                v-for="(log, idx) in sortedLogs"
                :key="log.id || idx"
                class="timeline-entry"
                :class="{ 'is-latest': idx === 0 }"
            >
                <!-- Line & Marker -->
                <div class="timeline-indicator-col">
                    <div
                        class="timeline-marker shadow-xs"
                        :class="[
                            `marker-${getStatusType(log.status)}`,
                            { 'is-pulse': idx === 0 }
                        ]"
                    >
                        <i v-if="getStatusType(log.status) === 'success'" class="bi bi-check2"></i>
                        <i v-else-if="getStatusType(log.status) === 'warning'" class="bi bi-exclamation"></i>
                        <i v-else-if="getStatusType(log.status) === 'danger'" class="bi bi-x"></i>
                        <i v-else class="bi bi-clock-history"></i>
                    </div>
                    <div v-if="idx < sortedLogs.length - 1" class="timeline-track"></div>
                </div>

                <!-- Content Card -->
                <div class="timeline-card rounded-3 border p-3 bg-white mb-3 shadow-xs">
                    <!-- Top Info: Status Badge, Latest Tag, Timestamp -->
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2 flex-wrap">
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                            <StatusBadge :status="log.status" />
                            <span v-if="idx === 0" class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold" style="font-size: 0.68rem;">
                                Terkini
                            </span>
                        </div>
                        <span class="text-muted small fw-medium" style="font-size: 0.76rem;">
                            <i class="bi bi-calendar3 me-1"></i>
                            {{ formatDateTime(log.changed_at || (log as any).created_at) }}
                        </span>
                    </div>

                    <!-- Position & Changed By -->
                    <div class="d-flex align-items-center gap-3 text-muted small mb-2 flex-wrap" style="font-size: 0.8rem;">
                        <span v-if="log.position" class="d-flex align-items-center gap-1 text-dark">
                            <i class="bi bi-geo-alt-fill text-primary"></i>
                            <span>Posisi: <strong>{{ log.position }}</strong></span>
                        </span>
                        <span v-if="log.changed_by" class="d-flex align-items-center gap-1 text-muted">
                            <i class="bi bi-person-circle"></i>
                            <span>Oleh: <strong class="text-dark">{{ log.changed_by }}</strong></span>
                        </span>
                    </div>

                    <!-- Note / Remarks -->
                    <div v-if="log.note" class="timeline-note p-2 rounded-2 bg-light border-start border-3 border-primary text-secondary small">
                        <i class="bi bi-chat-left-text me-1 text-muted"></i>
                        <span>{{ log.note }}</span>
                    </div>

                    <!-- Attached File (if any) -->
                    <div v-if="log.attachment_path" class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between">
                        <div class="small text-truncate text-muted" style="max-width: 220px;" :title="log.attachment_name || log.attachment_path.split('/').pop()">
                            <i class="bi bi-paperclip me-1 text-primary"></i>
                            <span>{{ log.attachment_name || log.attachment_path.split('/').pop() }}</span>
                        </div>
                        <a
                            :href="`/lampiran/view/${log.attachment_path}`"
                            target="_blank"
                            class="btn btn-xs btn-outline-primary py-0.5 px-2 rounded-2 text-decoration-none small"
                            style="font-size: 0.72rem;"
                        >
                            <i class="bi bi-download me-0.5"></i> Unduh
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-4 px-3 bg-light rounded-3 border border-dashed text-muted">
            <div class="w-10 h-10 rounded-circle bg-white text-muted d-inline-flex align-items-center justify-content-center shadow-xs mb-2">
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
.status-history-wrap {
    position: relative;
}

.timeline-container {
    position: relative;
}

.timeline-entry {
    display: flex;
    gap: 12px;
    position: relative;
}

.timeline-indicator-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
    width: 28px;
    padding-top: 4px;
}

.timeline-marker {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: bold;
    z-index: 2;
    flex-shrink: 0;
}

.marker-primary {
    background: #2743AF;
    color: #ffffff;
}

.marker-success {
    background: #10B981;
    color: #ffffff;
}

.marker-warning {
    background: #F59E0B;
    color: #ffffff;
}

.marker-danger {
    background: #EF4444;
    color: #ffffff;
}

.is-pulse {
    box-shadow: 0 0 0 4px rgba(39, 67, 175, 0.2);
}

.timeline-track {
    width: 2px;
    background: #E2E8F0;
    flex-grow: 1;
    margin: 4px 0;
}

.timeline-card {
    flex-grow: 1;
    transition: all 0.2s ease;
}

.timeline-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.timeline-note {
    background: #f8fafc;
    line-height: 1.4;
}
</style>
