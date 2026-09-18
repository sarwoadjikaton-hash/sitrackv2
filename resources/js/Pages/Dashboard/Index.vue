<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Letter, Disposition, LetterNumberType } from '@/types';

defineProps<{
    stats: {
        signature: { total: number; in_progress: number; completed: number };
        disposition: { total: number; in_progress: number; completed: number };
        stock: { year: number; used: number; available: number; reserved: number; total: number };
    };
    recentLetters: Letter[];
    recentDispositions: Disposition[];
    typesSummary: LetterNumberType[];
}>();
</script>

<template>
    <AppLayout title="Dashboard Persuratan">

        <Head title="Dashboard" />

        <!-- Welcome Banner -->
        <div class="dsh-welcome mb-4">
            <div class="dsh-welcome-blob dsh-welcome-blob-a"></div>
            <div class="dsh-welcome-blob dsh-welcome-blob-b"></div>
            <div class="row align-items-center position-relative">
                <div class="col-lg-8">
                    <span class="dsh-welcome-badge d-inline-flex align-items-center gap-2">
                        <img src="/images/sitrack_logo.svg" alt="SiTrack" width="16" height="16" />
                        <span>SiTrack</span>
                        <span class="opacity-50">|</span>
                        <img src="/images/kemnaker_logo.png" alt="Kemnaker" width="14" height="14" style="filter: brightness(0) invert(1);" />
                        <span>Sekretariat Jenderal Kemnaker RI</span>
                    </span>
                    <h2 class="fw-bold mb-1 text-white">Selamat Datang di Sistem Tracking Persuratan</h2>
                    <p class="mb-0 text-white-50 small">
                        Kelola seluruh alur penomoran surat naskah dinas, lembar tindak lanjut tanda tangan, dan arahan
                        disposisi pimpinan secara realtime dan transparan.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <div class="d-flex gap-2 justify-content-lg-end">
                        <Link href="/tindak-lanjut/create" class="dsh-btn-pill dsh-btn-pill-light">
                            <i class="bi bi-plus-circle-fill me-1"></i> Surat Baru
                        </Link>
                        <Link href="/disposisi/create" class="dsh-btn-pill dsh-btn-pill-outline">
                            <i class="bi bi-arrow-right-circle me-1"></i> Disposisi
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="dsh-stat-card">
                    <span class="dsh-stat-icon dsh-icon-primary"><i class="bi bi-pen-fill"></i></span>
                    <div class="dsh-stat-value">{{ stats.signature.total }}</div>
                    <div class="dsh-stat-title">Tindak Lanjut / TTD</div>
                    <div class="dsh-stat-sub">{{ stats.signature.in_progress }} Dalam Proses &bull; {{
                        stats.signature.completed }} Selesai</div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="dsh-stat-card">
                    <span class="dsh-stat-icon dsh-icon-accent"><i class="bi bi-diagram-3-fill"></i></span>
                    <div class="dsh-stat-value">{{ stats.disposition.total }}</div>
                    <div class="dsh-stat-title">Lajur Disposisi</div>
                    <div class="dsh-stat-sub">{{ stats.disposition.in_progress }} Proses &bull; {{
                        stats.disposition.completed }} Tuntas</div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="dsh-stat-card">
                    <span class="dsh-stat-icon dsh-icon-success"><i class="bi bi-check2-circle"></i></span>
                    <div class="dsh-stat-value">{{ stats.stock.available }}</div>
                    <div class="dsh-stat-title">Nomor Tersedia</div>
                    <div class="dsh-stat-sub">Tahun {{ stats.stock.year }} &bull; {{ stats.stock.reserved }} Direservasi
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="dsh-stat-card">
                    <span class="dsh-stat-icon dsh-icon-info"><i class="bi bi-file-earmark-check"></i></span>
                    <div class="dsh-stat-value">{{ stats.stock.used }}</div>
                    <div class="dsh-stat-title">Nomor Digunakan</div>
                    <div class="dsh-stat-sub">Total slot terdaftar: {{ stats.stock.total }}</div>
                </div>
            </div>
        </div>

        <!-- Recent Documents & Dispositions -->
        <div class="row g-4 mb-4">
            <!-- Recent Letters -->
            <div class="col-lg-7">
                <div class="dsh-panel h-100">
                    <div class="dsh-panel-head">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Surat Terkini</h5>
                            <small class="text-muted">Dokumen yang baru dicatat atau diperbarui</small>
                        </div>
                        <Link href="/tindak-lanjut" class="dsh-link">
                            Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                        </Link>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <div v-for="letter in recentLetters" :key="letter.id" class="dsh-row-item">
                            <div class="flex-grow-1" style="min-width: 0;">
                                <div class="fw-bold text-primary small">{{ letter.agenda_number || '-' }}</div>
                                <div class="fw-semibold text-dark text-truncate small">{{ letter.subject }}</div>
                                <small class="text-muted text-truncate d-block">{{ letter.sender_unit ||
                                    letter.sender_name }} &bull; <span class="font-monospace">{{ letter.tracking_code
                                        }}</span></small>
                            </div>
                            <StatusBadge :status="letter.status" />
                        </div>
                        <div v-if="recentLetters.length === 0" class="text-center py-4 text-muted small">Belum ada data
                            surat.</div>
                    </div>
                </div>
            </div>

            <!-- Recent Dispositions -->
            <div class="col-lg-5">
                <div class="dsh-panel h-100">
                    <div class="dsh-panel-head">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Arahan Disposisi</h5>
                            <small class="text-muted">Instruksi disposisi pimpinan terkini</small>
                        </div>
                        <Link href="/disposisi" class="dsh-link">
                            Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                        </Link>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <div v-for="disp in recentDispositions" :key="disp.id" class="dsh-disp-item">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold text-primary small">
                                    <i class="bi bi-person-fill me-1"></i>{{ disp.from_name }} &rarr; {{
                                        disp.to_unit?.unit_name || disp.to_name || 'Unit' }}
                                </span>
                                <span class="dsh-mini-badge">{{ disp.status }}</span>
                            </div>
                            <p class="mb-1 text-dark small fw-medium text-truncate-2">{{ disp.instruction }}</p>
                            <small class="text-muted d-block">Terkait: <span class="fw-semibold">{{ disp.letter?.subject
                                || '-' }}</span></small>
                        </div>
                        <div v-if="recentDispositions.length === 0" class="text-center py-4 text-muted small">Belum ada
                            instruksi disposisi.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Workbook Types Stock Summary -->
        <div class="dsh-panel">
            <div class="dsh-panel-head">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Distribusi Stok Nomor per Jenis Naskah</h5>
                    <small class="text-muted">Status ketersediaan nomor naskah dinas tahun {{ stats.stock.year
                        }}</small>
                </div>
                <Link href="/ketersediaan-nomor" class="dsh-link">
                    Kelola Ketersediaan Nomor <i class="bi bi-arrow-right ms-1"></i>
                </Link>
            </div>

            <div class="row g-3">
                <div v-for="type in typesSummary" :key="type.id" class="col-md-6 col-lg-3">
                    <div class="dsh-type-card">
                        <div class="fw-bold text-dark text-truncate mb-2" :title="type.type_name">{{ type.workbook_name
                            }}</div>
                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span>Tersedia: <strong style="color: var(--st-success);">{{ type.available_slots || 0
                                    }}</strong></span>
                            <span>Terpakai: <strong style="color: var(--st-primary);">{{ type.used_slots || 0
                                    }}</strong></span>
                        </div>
                        <div class="dsh-progress-track">
                            <div class="dsh-progress-fill"
                                :style="{ width: type.total_slots && type.total_slots > 0 ? `${((type.used_slots || 0) / type.total_slots) * 100}%` : '0%' }">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
