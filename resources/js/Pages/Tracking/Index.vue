<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TrackingHero from '@/Components/TrackingHero.vue';
import { Letter, LetterStatusLog, Disposition } from '@/types';

const props = defineProps<{
    searchCode: string;
    letter?: Letter | null;
    logs: LetterStatusLog[];
    dispositions: Disposition[];
    progress: number;
}>();

const inputCode = ref(props.searchCode || '');

const handleSearch = () => {
    if (!inputCode.value) return;
    router.get('/tracking', { code: inputCode.value }, { preserveState: true });
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
                                <StatusBadge :status="letter.status" class="fs-5 px-4 py-2" />
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
                                        <!-- Lembar Pendamping Resmi (Lajur Tindak Lanjut / Semua Naskah Dinas) -->
                                        <div v-if="letter.process_lane === 'signature' || !letter.process_lane" class="meta-box mb-3 p-3 rounded-3 border" style="background: #f0fdf4; border-color: #bbf7d0 !important;">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <label class="d-flex align-items-center gap-1 text-success fw-bold mb-0">
                                                    <i class="bi bi-file-earmark-check fs-6"></i> Lembar Pendamping Resmi
                                                </label>
                                                <span v-if="letter.status === 'Dokumen Sudah diambil' || (letter.attachment_path && letter.attachment_path.includes('sig_'))" class="badge bg-success text-white small" style="font-size: 10px;">
                                                    <i class="bi bi-check2-circle me-1"></i>Sudah Bertanda Tangan
                                                </span>
                                            </div>
                                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2">
                                                <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                    <i class="bi bi-file-earmark-pdf text-success fs-3"></i>
                                                    <div>
                                                        <div class="small fw-bold text-dark">Format Lembar Pendamping</div>
                                                        <small class="text-muted">Agenda: {{ letter.agenda_number || letter.tracking_code }}</small>
                                                    </div>
                                                </div>
                                                <a :href="`/cetak/pendamping/${letter.id}`" target="_blank"
                                                    class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 px-3 py-1 text-nowrap fw-semibold">
                                                    <i class="bi bi-file-earmark-check"></i> Buka Lembar Pendamping
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Lampiran Berkas Naskah Digital (Bila ada dokumen asli yang diupload, BUKAN file tanda tangan) -->
                                        <div v-if="letter.attachment_path && !letter.attachment_path.includes('signatures/') && !letter.attachment_path.includes('sig_')" class="meta-box mb-3 p-3 rounded-3 border" style="background: #f8fafc;">
                                            <label class="d-flex align-items-center gap-1 text-primary fw-bold mb-2">
                                                <i class="bi bi-paperclip fs-6"></i> Lampiran Berkas Naskah
                                            </label>
                                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2">
                                                <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                    <i class="bi bi-file-earmark-text text-primary fs-4"></i>
                                                    <span class="small text-truncate fw-semibold text-dark" style="max-width: 170px;" :title="letter.attachment_path.split('/').pop()">
                                                        {{ letter.attachment_path.split('/').pop() }}
                                                    </span>
                                                </div>
                                                <a :href="`/storage/${letter.attachment_path}`" target="_blank"
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
                                                    class="d-flex flex-column flex-sm-row justify-content-between gap-1">
                                                    <h6 class="fw-bold mb-1 text-dark">{{ log.status }}</h6>
                                                    <small class="text-muted">{{ new
                                                         Date(log.changed_at).toLocaleDateString('id-ID') }}</small>
                                                </div>
                                                <div class="small text-teal fw-semibold mb-1">{{ log.position }}</div>
                                                <p class="small text-muted mb-0">{{ log.note }}</p>
                                                <!-- Lampiran Dokumen Asli (Hanya jika BUKAN file raw tanda tangan) -->
                                                <div v-if="log.attachment_path && !log.attachment_path.includes('signatures/') && !log.attachment_path.includes('sig_')" class="mt-2 p-2 rounded-2 bg-light border d-flex align-items-center justify-content-between gap-2">
                                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                        <i class="bi bi-paperclip text-primary fs-5"></i>
                                                        <span class="small text-truncate fw-semibold text-dark" style="max-width: 220px;" :title="log.attachment_name || log.attachment_path.split('/').pop()">
                                                            {{ log.attachment_name || log.attachment_path.split('/').pop() }}
                                                        </span>
                                                    </div>
                                                    <a :href="`/storage/${log.attachment_path}`" target="_blank"
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
    --ocean-primary: #002b4c;
    --ocean-primary-dark: #001a2e;
    --ocean-primary-light: #5b96b8;
    --ocean-accent: #f59e71;

    background: #f1f5f9;
    min-height: 100%;
}

/* ============ Layout Cards ============ */

.glass-result-card {
    border-radius: 2.5rem;
}

.text-teal {
    color: var(--ocean-primary);
}

.bg-teal {
    background-color: var(--ocean-accent);
}

.bg-teal-subtle {
    background-color: rgba(0, 43, 76, 0.08);
}

.ls-2 {
    letter-spacing: 2px;
}

.meta-box label {
    display: block;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 5px;
}

.meta-box p {
    font-size: 1.1rem;
    color: #1e293b;
    margin-bottom: 0;
}

.custom-timeline {
    border-left: 2px solid #e2e8f0;
    padding-left: 30px;
    position: relative;
}

.timeline-marker {
    position: absolute;
    left: -39px;
    top: 5px;
    width: 16px;
    height: 16px;
    background: var(--ocean-accent);
    border: 4px solid #fff;
    border-radius: 50%;
    box-shadow: 0 0 0 4px rgba(245, 158, 113, 0.2);
}

.timeline-item-v2 {
    position: relative;
    padding-bottom: 40px;
}

.animate-fade-up {
    animation: fadeUp 0.6s ease-out forwards;
}

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(30px);
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

    .tracking-viewport {
        background: var(--ocean-primary-dark);
    }

    /* Result tidak terlalu menabrak Hero */
    #results {
        margin-top: -1.5rem !important;
        padding-top: 2rem !important;
        padding-bottom: 2rem !important;
        background: #f1f5f9;
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
        font-size: 1rem;
    }

    /* Header hasil */
    .glass-result-card>.d-flex {
        margin-bottom: 2rem !important;
    }

    /* Kolom hasil */
    .glass-result-card .row.g-5 {
        --bs-gutter-y: 2rem;
    }

    /* Progress */
    .progress-section {
        margin-bottom: 2rem !important;
    }

    /* Metadata */
    .meta-box {
        margin-bottom: 1.25rem !important;
    }

    .meta-box p {
        font-size: 0.95rem;
    }

    /* Timeline */
    .custom-timeline {
        padding-left: 22px;
    }

    .timeline-marker {
        left: -31px;
        width: 14px;
        height: 14px;
        border-width: 3px;
    }

    .timeline-item-v2 {
        padding-bottom: 28px;
    }

    .timeline-content h6 {
        font-size: 0.9rem;
    }

    .timeline-content small,
    .timeline-content p,
    .timeline-content .small {
        font-size: 0.78rem !important;
    }

    /* Not found card */
    .glass-result-card.p-5 {
        padding: 2rem 1.25rem !important;
        width: calc(100vw - 2rem);
        max-width: none !important;
    }
}
</style>