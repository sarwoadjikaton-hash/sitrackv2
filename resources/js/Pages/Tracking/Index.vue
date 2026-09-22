<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TrackingHero from '@/Components/TrackingHero.vue';
import { Letter, LetterStatusLog, Disposition, PageProps } from '@/types';

const props = defineProps<{
    searchCode: string;
    letter?: Letter | null;
    logs: LetterStatusLog[];
    dispositions: Disposition[];
    progress: number;
}>();

const page = usePage<PageProps>();
const user = computed(() => page.props.auth?.user);

const inputCode = ref(props.searchCode || '');

const handleSearch = () => {
    if (!inputCode.value) return;
    router.get('/tracking', { code: inputCode.value }, { preserveState: true });
};

const formatDate = (dateStr: string) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const formatTime = (dateStr: string) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).replace('.', ':') + ' WIB';
};

const isReadyForPickup = computed(() => {
    if (!props.letter) return false;
    const s = props.letter.status;
    return s === 'Selesai dan Siap Untuk diambil' || s === 'Dokumen Sudah diambil' || s === 'Selesai' || s === 'Surat Selesai di Paraf/TTD dan bisa diambil';
});

const isValidAttachment = (path: any) => {
    if (!path || typeof path !== 'string') return false;
    const p = path.trim().toLowerCase();
    if (p === '0' || p === 'null' || p === 'undefined' || p === '') return false;
    if (p.includes('signatures/') || p.includes('sig_')) return false;
    return p.includes('.') && p.length >= 4;
};
</script>

<template>
    <PublicLayout>

        <Head title="Lacak Surat & Naskah Dinas" />

        <div class="tracking-viewport" :class="{ 'has-results': letter || searchCode }">
            <TrackingHero v-model="inputCode" @search="handleSearch" />

            <!-- ============ Result Area ============ -->
            <div v-if="letter || searchCode" id="results" class="container py-5 mt-n5 position-relative"
                style="z-index: 20;">

                <!-- If Found -->
                <div v-if="letter" class="row justify-content-center animate-fade-up">
                    <div class="col-lg-11">
                        <div class="glass-result-card p-4 p-md-5 shadow-lg border-0 bg-white">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-5">
                                <div>
                                    <span class="badge bg-teal-subtle text-teal text-uppercase ls-2 mb-2 px-3">Hasil
                                        Pelacakan</span>
                                    <h2 class="fw-bold text-dark mb-0">{{ letter.subject }}</h2>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <Link v-if="user && (letter.process_lane === 'signature' || !letter.process_lane)"
                                        :href="`/scan-status?tracking=${letter.tracking_code}`"
                                        class="btn btn-sm btn-primary-blue shadow-sm d-inline-flex align-items-center gap-1 px-3 py-2 rounded-pill">
                                        <i class="bi bi-pencil-square"></i> Update Status (Admin)
                                    </Link>
                                    <StatusBadge :status="letter.status" class="fs-5 px-4 py-2" />
                                </div>
                            </div>

                            <div class="row g-5">
                                <div class="col-md-5">
                                    <div class="progress-section mb-5">
                                        <div class="d-flex justify-content-between mb-2 small fw-bold text-muted">
                                            <span>Progres Penanganan</span>
                                            <span class="text-teal fw-bold">{{ progress }}%</span>
                                        </div>
                                        <div class="progress shadow-sm" style="height: 8px; border-radius: 10px;">
                                            <div class="progress-bar bg-teal" :style="{ width: `${progress}%` }"></div>
                                        </div>
                                    </div>

                                    <div class="meta-info-grid">
                                        <div class="meta-box mb-3">
                                            <label>Kode Tracking</label>
                                            <p class="fw-bold text-teal font-monospace fs-5">{{ letter.tracking_code }}</p>
                                        </div>
                                        <div class="meta-box mb-3">
                                            <label>Posisi Berkas Sekarang</label>
                                            <p class="fw-bold text-dark"><i
                                                    class="bi bi-geo-alt-fill text-danger me-1"></i>{{
                                                        letter.current_position }}</p>
                                        </div>
                                        <div class="meta-box mb-3">
                                            <label>Unit Pengusul / Pengirim</label>
                                            <p class="text-dark fw-semibold mb-0">{{ letter.sender_unit || letter.sender_name }}</p>
                                            <small v-if="letter.sender_unit && letter.sender_name" class="text-muted">Oleh: {{ letter.sender_name }}</small>
                                        </div>
                                        <div v-if="letter.destination || letter.recipient_unit" class="meta-box mb-3">
                                            <label>Unit Tujuan</label>
                                            <p class="text-dark mb-0">{{ letter.destination || letter.recipient_unit?.unit_name }}</p>
                                        </div>
                                        <div class="meta-box mb-3">
                                            <label>Nomor Surat</label>
                                            <p v-if="letter.letter_number" class="fw-bold text-dark font-monospace mb-0">{{ letter.letter_number }}</p>
                                            <p v-else class="text-muted fst-italic small mb-0"><i class="bi bi-hourglass-split me-1 text-warning"></i>Menunggu input Admin/TU</p>
                                        </div>
                                        <div v-if="letter.priority" class="meta-box mb-3">
                                            <label>Sifat Naskah</label>
                                            <p class="text-dark mb-0"><span class="badge bg-light text-dark border">{{ letter.priority }}</span></p>
                                        </div>
                                        <!-- Lembar Pendamping (Hanya muncul jika status sudah Selesai dan Siap Untuk diambil / Dokumen Sudah diambil) -->
                                        <div v-if="(letter.process_lane === 'signature' || !letter.process_lane) && isReadyForPickup" class="meta-box mb-3 p-3 rounded-3 border" style="background: #f0fdf4; border-color: #bbf7d0 !important;">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <label class="d-flex align-items-center gap-1 text-success fw-bold mb-0">
                                                    <i class="bi bi-file-earmark-check fs-6"></i> Lembar Pendamping
                                                </label>
                                                <span class="badge bg-success text-white small" style="font-size: 10px;">
                                                    <i class="bi bi-check2-circle me-1"></i>Siap Diambil / Selesai
                                                </span>
                                            </div>
                                            <p class="small text-muted mb-3">
                                                Lembar kontrol fisik persuratan dengan barcode pelacakan dan riwayat paraf pimpinan.
                                            </p>
                                            <a :href="`/cetak/pendamping/${letter.id}`" target="_blank"
                                                class="btn btn-sm btn-success w-100 fw-bold d-inline-flex align-items-center justify-content-center gap-1 shadow-sm py-2">
                                                <i class="bi bi-printer-fill"></i> Buka / Cetak Lembar Pendamping
                                            </a>
                                        </div>

                                        <div v-if="letter && isValidAttachment(letter.attachment_path)" class="meta-box p-3 rounded-3 border bg-light">
                                            <label class="d-flex align-items-center gap-1 text-primary fw-bold mb-2">
                                                <i class="bi bi-paperclip fs-6"></i> Lampiran Berkas Naskah
                                            </label>
                                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2">
                                                <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                    <i class="bi bi-file-earmark-text text-primary fs-4"></i>
                                                    <span class="small text-truncate fw-semibold text-dark" style="max-width: 170px;" :title="letter.attachment_path?.split('/').pop() || 'Lampiran'">
                                                        {{ letter.attachment_path?.split('/').pop() || 'Lampiran' }}
                                                    </span>
                                                </div>
                                                <a :href="`/lampiran/view/${letter.attachment_path}`" target="_blank"
                                                    class="btn btn-sm btn-primary-blue d-inline-flex align-items-center gap-1 px-3 py-1 text-nowrap">
                                                    <i class="bi bi-eye"></i> Lihat / Unduh
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-7">
                                    <h5 class="fw-bold text-dark mb-4">Riwayat Perjalanan Dokumen</h5>
                                    <div class="custom-timeline">
                                        <div v-for="log in logs" :key="log.id" class="timeline-item-v2">
                                            <div class="timeline-marker"></div>
                                            <div class="timeline-content">
                                                <div
                                                    class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-1">
                                                    <h6 class="fw-bold mb-1 text-dark">{{ log.status }}</h6>
                                                    <div class="text-sm-end mb-1">
                                                        <div class="small fw-semibold text-dark">{{ formatDate(log.changed_at) }}</div>
                                                        <small class="text-muted d-block" style="font-size: 0.78rem;">
                                                             <i class="bi bi-clock me-1 text-primary"></i>{{ formatTime(log.changed_at) }}
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="small text-teal fw-semibold mb-1">{{ log.position }}</div>
                                                <p class="small text-muted mb-0">{{ log.note }}</p>
                                                <!-- Lampiran Dokumen Asli (Hanya jika ada file lampiran valid) -->
                                                <div v-if="isValidAttachment(log.attachment_path)" class="mt-2 p-2 rounded-2 bg-light border d-flex align-items-center justify-content-between gap-2">
                                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                        <i class="bi bi-paperclip text-primary fs-5"></i>
                                                        <span class="small text-truncate fw-semibold text-dark" style="max-width: 220px;" :title="log.attachment_name || log.attachment_path?.split('/').pop() || 'Lampiran'">
                                                            {{ log.attachment_name || log.attachment_path?.split('/').pop() || 'Lampiran' }}
                                                        </span>
                                                    </div>
                                                    <a :href="`/lampiran/view/${log.attachment_path}`" target="_blank"
                                                        class="btn btn-sm btn-outline-primary py-1 px-2 text-nowrap d-inline-flex align-items-center gap-1 small">
                                                        <i class="bi bi-eye"></i> Buka Lampiran
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Not Found State -->
                <div v-else-if="searchCode" class="text-center py-5 animate-fade-up">
                    <div class="glass-result-card p-5 d-inline-block mx-auto border-0 shadow-lg bg-white"
                        style="max-width: 500px;">
                        <i class="bi bi-search text-muted display-4 mb-3 d-block opacity-20"></i>
                        <h4 class="fw-bold text-dark">Data Tidak Ditemukan</h4>
                        <p class="text-muted small mb-0">Kode <strong>"{{ searchCode }}"</strong> tidak terdaftar.</p>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>


<style scoped>
.tracking-viewport {
    --ocean-primary: #1C386F;
    --ocean-primary-dark: #03205A;
    --ocean-primary-light: #395ba0;
    --ocean-accent: #167992;

    background: #03205A;
    min-height: 100%;
}

.tracking-viewport.has-results {
    background: #03205A;
}

/* ============ Layout Cards ============ */

.glass-result-card {
    border-radius: 2rem;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25) !important;
}

.text-teal {
    color: var(--ocean-accent);
}

.bg-teal {
    background-color: var(--ocean-accent);
}

.bg-teal-subtle {
    background-color: #E4F5F9;
}

.ls-2 {
    letter-spacing: 1.5px;
}

.meta-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 0.85rem 1rem;
    border-radius: 0.75rem;
}

.meta-box label {
    display: block;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 4px;
    letter-spacing: 0.5px;
}

.meta-box p {
    font-size: 1.05rem;
    color: #1e293b;
    margin-bottom: 0;
}

.custom-timeline {
    border-left: 2px solid #e2e8f0;
    padding-left: 28px;
    position: relative;
}

.timeline-marker {
    position: absolute;
    left: -37px;
    top: 4px;
    width: 16px;
    height: 16px;
    background: var(--ocean-accent);
    border: 4px solid #fff;
    border-radius: 50%;
    box-shadow: 0 0 0 4px rgba(22, 121, 146, 0.25);
}

.timeline-item-v2 {
    position: relative;
    padding-bottom: 32px;
}

.timeline-item-v2:last-child {
    padding-bottom: 0;
}

.animate-fade-up {
    animation: fadeUp 0.5s ease-out forwards;
}

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(24px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 767.98px) {
    #results {
        margin-top: -1rem !important;
        padding-top: 1.5rem !important;
        padding-bottom: 2.5rem !important;
    }

    /* Card hasil */
    .glass-result-card {
        border-radius: 1.5rem;
        padding: 1.25rem !important;
    }

    /* Heading hasil */
    .glass-result-card h2 {
        font-size: 1.25rem;
        line-height: 1.35;
    }

    .glass-result-card h5 {
        font-size: 1.05rem;
    }

    /* Header hasil */
    .glass-result-card > .d-flex {
        margin-bottom: 1.5rem !important;
    }

    /* Kolom hasil */
    .glass-result-card .row.g-5 {
        --bs-gutter-y: 1.75rem;
    }

    /* Progress */
    .progress-section {
        margin-bottom: 1.5rem !important;
    }

    /* Metadata */
    .meta-box {
        margin-bottom: 0.75rem !important;
        padding: 0.75rem;
    }

    .meta-box p {
        font-size: 0.95rem;
    }

    /* Timeline */
    .custom-timeline {
        padding-left: 20px;
    }

    .timeline-marker {
        left: -29px;
        width: 14px;
        height: 14px;
        border-width: 3px;
    }

    .timeline-item-v2 {
        padding-bottom: 24px;
    }

    .timeline-content h6 {
        font-size: 0.92rem;
    }

    .timeline-content small,
    .timeline-content p,
    .timeline-content .small {
        font-size: 0.8rem !important;
    }

    /* Not found card */
    .glass-result-card.p-5 {
        padding: 2rem 1.25rem !important;
        width: 100%;
        max-width: none !important;
    }
}
</style>