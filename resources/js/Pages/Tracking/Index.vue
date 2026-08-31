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
                                        <div class="meta-box mb-4">
                                            <label>Kode Tracking</label>
                                            <p class="fw-bold text-teal font-monospace fs-5">{{ letter.tracking_code }}
                                            </p>
                                        </div>
                                        <div class="meta-box mb-4">
                                            <label>Posisi Sekarang</label>
                                            <p class="fw-bold text-dark"><i
                                                    class="bi bi-geo-alt-fill text-danger me-1"></i>{{
                                                        letter.current_position }}</p>
                                        </div>
                                        <div class="meta-box">
                                            <label>Pengirim</label>
                                            <p class="text-dark">{{ letter.sender_unit || letter.sender_name }}</p>
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
    background: #f1f5f9;
    min-height: 100%;
}

/* ============ Layout Cards ============ */

.glass-result-card {
    border-radius: 2.5rem;
}

.text-teal {
    color: #14b8a6;
}

.bg-teal {
    background-color: #14b8a6;
}

.bg-teal-subtle {
    background-color: rgba(20, 184, 166, 0.1);
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
    background: #14b8a6;
    border: 4px solid #fff;
    border-radius: 50%;
    box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.15);
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
        background: var(--st-primary-dark);
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