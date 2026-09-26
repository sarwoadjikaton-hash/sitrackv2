<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Letter, Disposition, LetterNumberType, PageProps } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    stats: {
        signature: { total: number; in_progress: number; completed: number };
        disposition: { total: number; in_progress: number; completed: number };
        stock: { year: number; used: number; available: number; reserved: number; total: number };
    };
    recentLetters: Letter[];
    recentDispositions: Disposition[];
    typesSummary: LetterNumberType[];
}>();

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 11) return 'Selamat pagi';
    if (hour < 15) return 'Selamat siang';
    if (hour < 18) return 'Selamat sore';
    return 'Selamat malam';
});

const todayDateFormatted = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(new Date());
});

const getStatusBadgeStyle = (status: string) => {
    const s = (status || '').toLowerCase();
    if (s.includes('selesai') || s.includes('siap') || s.includes('diambil')) {
        return {
            bg: 'rgba(16, 185, 129, 0.12)',
            color: '#059669',
            dot: '#10b981'
        };
    }
    if (s.includes('sekjen')) {
        return {
            bg: 'rgba(245, 158, 11, 0.12)',
            color: '#d97706',
            dot: '#f59e0b'
        };
    }
    if (s.includes('kasubbag') || s.includes('kasubag')) {
        return {
            bg: 'rgba(139, 92, 246, 0.12)',
            color: '#7c3aed',
            dot: '#8b5cf6'
        };
    }
    return {
        bg: 'rgba(37, 99, 235, 0.12)',
        color: '#2563eb',
        dot: '#3b82f6'
    };
};
</script>

<template>
    <AppLayout title="Dashboard">
        <Head title="Dashboard" />

        <!-- Header Greeting & Actions (Figma Model: Clean on page canvas) -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pt-1">
            <div>
                <h2 class="fw-bold mb-1 text-dark" style="font-size: 1.65rem; letter-spacing: -0.02em;">
                    {{ greeting }}, {{ user?.name || 'Dyah Permatasari' }} 👋
                </h2>
                <div class="d-flex align-items-center gap-2 text-muted small">
                    <span>{{ todayDateFormatted }}</span>
                    <span>&bull;</span>
                    <span class="d-inline-flex align-items-center gap-1 text-success">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981;"></span>
                        Sistem berjalan normal
                    </span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <Link href="/tindak-lanjut/create" class="fg-btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    <span>Surat Baru</span>
                </Link>
                <Link href="/disposisi/create" class="fg-btn-secondary">
                    <i class="bi bi-send"></i>
                    <span>Input Disposisi</span>
                </Link>
                <Link href="/scan-status" class="fg-btn-secondary">
                    <i class="bi bi-qr-code-scan"></i>
                    <span>Scan QR</span>
                </Link>
            </div>
        </div>

        <!-- 4 Stat Cards Grid (Figma Model) -->
        <div class="row g-3 mb-4">
            <!-- Card 1: Tindak Lanjut / TTD -->
            <div class="col-md-6 col-xl-3">
                <div class="fg-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="fg-icon-box fg-icon-blue">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </div>
                        <i class="bi bi-arrow-up-right fg-arrow-corner"></i>
                    </div>
                    <div class="fg-stat-num">{{ stats.signature.total }}</div>
                    <div class="fg-stat-label">Tindak Lanjut / TTD</div>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top small">
                        <span class="text-warning-emphasis fw-medium">{{ stats.signature.in_progress }} Dalam Proses</span>
                        <span class="text-success fw-medium">{{ stats.signature.completed }} Selesai</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Lajur Disposisi -->
            <div class="col-md-6 col-xl-3">
                <div class="fg-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="fg-icon-box fg-icon-purple">
                            <i class="bi bi-send-fill"></i>
                        </div>
                        <i class="bi bi-arrow-up-right fg-arrow-corner"></i>
                    </div>
                    <div class="fg-stat-num">{{ stats.disposition.total }}</div>
                    <div class="fg-stat-label">Lajur Disposisi</div>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top small">
                        <span class="text-warning-emphasis fw-medium">{{ stats.disposition.in_progress }} Dalam Proses</span>
                        <span class="text-success fw-medium">{{ stats.disposition.completed }} Tuntas</span>
                    </div>
                </div>
            </div>

            <!-- Card 3: Nomor Tersedia -->
            <div class="col-md-6 col-xl-3">
                <div class="fg-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="fg-icon-box fg-icon-teal">
                            <i class="bi bi-hash"></i>
                        </div>
                        <i class="bi bi-arrow-up-right fg-arrow-corner"></i>
                    </div>
                    <div class="fg-stat-num" style="color: var(--st-teal, #0d9488);">{{ stats.stock.available }}</div>
                    <div class="fg-stat-label">Nomor Tersedia</div>
                    <div class="mt-3 pt-2 border-top small text-muted">
                        <span>Seluruh workbook</span>
                    </div>
                </div>
            </div>

            <!-- Card 4: Nomor Terpakai -->
            <div class="col-md-6 col-xl-3">
                <div class="fg-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="fg-icon-box fg-icon-amber">
                            <i class="bi bi-file-earmark-check-fill"></i>
                        </div>
                        <i class="bi bi-arrow-up-right fg-arrow-corner"></i>
                    </div>
                    <div class="fg-stat-num">{{ stats.stock.used }}</div>
                    <div class="fg-stat-label">Nomor Terpakai</div>
                    <div class="mt-3 pt-2 border-top small text-muted">
                        <span>{{ stats.stock.used }} Bulan ini</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Two Panels Grid (Figma Model: Tindak Lanjut Terbaru & Disposisi Masuk) -->
        <div class="row g-4 mb-4">
            <!-- Left: Tindak Lanjut Terbaru -->
            <div class="col-lg-7">
                <div class="fg-panel h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Tindak Lanjut Terbaru</h5>
                            <small class="text-muted">5 naskah terakhir masuk</small>
                        </div>
                        <Link href="/tindak-lanjut" class="fg-link">
                            Lihat semua <i class="bi bi-arrow-right ms-1"></i>
                        </Link>
                    </div>

                    <div class="d-flex flex-column">
                        <div v-for="(letter, idx) in recentLetters" :key="letter.id" 
                            class="fg-tl-row" :class="{ 'border-bottom': idx < recentLetters.length - 1 }">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="font-monospace text-muted small">{{ letter.tracking_code || letter.agenda_number || '-' }}</span>
                                    <span v-if="(letter.priority || '').toLowerCase() === 'urgent' || (letter.priority || '').toLowerCase() === 'segera'" 
                                        class="fg-priority-badge">SEGERA</span>
                                </div>
                                <span class="fg-pill-badge" :style="{ backgroundColor: getStatusBadgeStyle(letter.status).bg, color: getStatusBadgeStyle(letter.status).color }">
                                    <span class="fg-pill-dot" :style="{ backgroundColor: getStatusBadgeStyle(letter.status).dot }"></span>
                                    <span>{{ letter.status }}</span>
                                </span>
                            </div>
                            <div class="fw-semibold text-dark small mb-1 text-truncate" :title="letter.subject">
                                {{ letter.subject }}
                            </div>
                            <div class="text-muted" style="font-size: 0.78rem;">
                                <span>{{ letter.sender_unit || letter.sender_name || 'Unit Pengirim' }}</span>
                                <span class="mx-1">&bull;</span>
                                <span>{{ letter.letter_date || '-' }}</span>
                            </div>
                        </div>

                        <div v-if="recentLetters.length === 0" class="text-center py-4 text-muted small">
                            Belum ada data naskah tindak lanjut.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Disposisi Masuk -->
            <div class="col-lg-5">
                <div class="fg-panel h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Disposisi Masuk</h5>
                        </div>
                        <Link href="/disposisi" class="fg-link">
                            Semua <i class="bi bi-arrow-right ms-1"></i>
                        </Link>
                    </div>

                    <div class="d-flex flex-column">
                        <div v-for="(disp, idx) in recentDispositions" :key="disp.id" 
                            class="fg-disp-row" :class="{ 'border-bottom': idx < recentDispositions.length - 1 }">
                            <div class="fg-disp-icon">
                                <i class="bi bi-send-fill"></i>
                            </div>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <div class="fw-semibold text-dark small text-truncate-2 mb-1" :title="disp.instruction || disp.letter?.subject">
                                    {{ disp.letter?.subject || disp.instruction }}
                                </div>
                                <div class="text-muted" style="font-size: 0.78rem;">
                                    {{ disp.from_name || disp.to_name || 'Instansi Terkait' }}
                                </div>
                            </div>
                        </div>

                        <div v-if="recentDispositions.length === 0" class="text-center py-4 text-muted small">
                            Belum ada instruksi disposisi.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Workbook Types Stock Summary -->
        <div class="fg-panel">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Distribusi Stok Nomor per Jenis Naskah</h5>
                    <small class="text-muted">Status ketersediaan nomor naskah dinas tahun {{ stats.stock.year }}</small>
                </div>
                <Link href="/ketersediaan-nomor" class="fg-link">
                    Kelola Ketersediaan Nomor <i class="bi bi-arrow-right ms-1"></i>
                </Link>
            </div>

            <div class="row g-3">
                <div v-for="type in typesSummary" :key="type.id" class="col-md-6 col-lg-3">
                    <div class="p-3 rounded-3 border bg-white h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-bold text-dark text-truncate small" :title="type.type_name">{{ type.workbook_name }}</div>
                            <span class="badge bg-light text-secondary border" style="font-size: 0.65rem;">{{ type.type_code }}</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-2" style="font-size: 0.75rem;">
                            <span>Tersedia: <strong style="color: var(--st-success, #10b981);">{{ type.available_slots || 0 }}</strong></span>
                            <span>Terpakai: <strong style="color: var(--st-danger, #ef4444);">{{ type.used_slots || 0 }}</strong></span>
                        </div>
                        <div style="height: 6px; border-radius: 999px; background: #e2e8f0; overflow: hidden;">
                            <div style="height: 100%; border-radius: 999px; background: var(--st-teal, #0d9488);"
                                :style="{ width: type.total_slots && type.total_slots > 0 ? `${((type.used_slots || 0) / type.total_slots) * 100}%` : '0%' }">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
