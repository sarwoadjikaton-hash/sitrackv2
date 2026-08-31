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
        <div class="st-card mb-4 bg-primary text-white p-4" style="background: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #38bdf8 100%) !important;">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-white text-primary fw-bold mb-2">SiTrack</span>
                    <h2 class="fw-bold mb-1">Selamat Datang di Sistem Tracking Persuratan</h2>
                    <p class="mb-0 opacity-90 small">
                        Kelola seluruh alur penomoran surat naskah dinas, lembar tindak lanjut tanda tangan, dan arahan disposisi pimpinan secara realtime dan transparan.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <div class="d-flex gap-2 justify-content-lg-end">
                        <Link href="/tindak-lanjut/create" class="btn btn-light fw-bold text-primary shadow-sm">
                            <i class="bi bi-plus-circle-fill me-1"></i> Surat Baru
                        </Link>
                        <Link href="/disposisi/create" class="btn btn-outline-light fw-bold shadow-sm">
                            <i class="bi bi-arrow-right-circle me-1"></i> Disposisi
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-xl-3">
                <StatCard
                    title="Tindak Lanjut / TTD"
                    :value="stats.signature.total"
                    icon="bi-pen-fill"
                    :subtitle="`${stats.signature.in_progress} Dalam Proses &bull; ${stats.signature.completed} Selesai`"
                />
            </div>
            <div class="col-md-6 col-xl-3">
                <StatCard
                    title="Lajur Disposisi"
                    :value="stats.disposition.total"
                    icon="bi-diagram-3-fill"
                    :subtitle="`${stats.disposition.in_progress} Proses &bull; ${stats.disposition.completed} Tuntas`"
                />
            </div>
            <div class="col-md-6 col-xl-3">
                <StatCard
                    title="Nomor Tersedia"
                    :value="stats.stock.available"
                    icon="bi-check2-circle"
                    :subtitle="`Tahun ${stats.stock.year} &bull; ${stats.stock.reserved} Direservasi`"
                />
            </div>
            <div class="col-md-6 col-xl-3">
                <StatCard
                    title="Nomor Digunakan"
                    :value="stats.stock.used"
                    icon="bi-file-earmark-check"
                    :subtitle="`Total slot terdaftar: ${stats.stock.total}`"
                />
            </div>
        </div>

        <!-- Recent Documents & Dispositions -->
        <div class="row g-4 mb-4">
            <!-- Recent Letters -->
            <div class="col-lg-7">
                <div class="st-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Surat Terkini</h5>
                            <small class="text-muted">Dokumen yang baru dicatat atau diperbarui</small>
                        </div>
                        <Link href="/tindak-lanjut" class="btn btn-sm btn-outline-blue">
                            Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                        </Link>
                    </div>

                    <div class="table-responsive">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>Agenda & Resi</th>
                                    <th>Perihal & Pengirim</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="letter in recentLetters" :key="letter.id">
                                    <td>
                                        <div class="fw-bold text-primary">{{ letter.agenda_number || '-' }}</div>
                                        <small class="text-muted font-monospace">{{ letter.tracking_code }}</small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark text-truncate" style="max-width: 260px;">
                                            {{ letter.subject }}
                                        </div>
                                        <small class="text-muted">{{ letter.sender_unit || letter.sender_name }}</small>
                                    </td>
                                    <td>
                                        <StatusBadge :status="letter.status" />
                                    </td>
                                </tr>
                                <tr v-if="recentLetters.length === 0">
                                    <td colspan="3" class="text-center py-4 text-muted">Belum ada data surat.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Dispositions -->
            <div class="col-lg-5">
                <div class="st-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Arahan Disposisi</h5>
                            <small class="text-muted">Instruksi disposisi pimpinan terkini</small>
                        </div>
                        <Link href="/disposisi" class="btn btn-sm btn-outline-blue">
                            Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                        </Link>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        <div
                            v-for="disp in recentDispositions"
                            :key="disp.id"
                            class="p-3 rounded-3 border bg-light"
                        >
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold text-primary small">
                                    <i class="bi bi-person-fill me-1"></i>{{ disp.from_name }} &rarr; {{ disp.to_unit?.unit_name || disp.to_name || 'Unit' }}
                                </span>
                                <span class="badge bg-secondary-subtle text-secondary small">{{ disp.status }}</span>
                            </div>
                            <p class="mb-1 text-dark small fw-medium text-truncate-2">{{ disp.instruction }}</p>
                            <small class="text-muted d-block">
                                Terkait: <span class="fw-semibold">{{ disp.letter?.subject || '-' }}</span>
                            </small>
                        </div>
                        <div v-if="recentDispositions.length === 0" class="text-center py-4 text-muted">
                            Belum ada instruksi disposisi.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Workbook Types Stock Summary -->
        <div class="st-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Distribusi Stok Nomor per Jenis Naskah</h5>
                    <small class="text-muted">Status ketersediaan nomor naskah dinas tahun {{ stats.stock.year }}</small>
                </div>
                <Link href="/ketersediaan-nomor" class="btn btn-sm btn-outline-blue">
                    Kelola Ketersediaan Nomor <i class="bi bi-arrow-right ms-1"></i>
                </Link>
            </div>

            <div class="row g-3">
                <div
                    v-for="type in typesSummary"
                    :key="type.id"
                    class="col-md-6 col-lg-3"
                >
                    <div class="p-3 border rounded-3 bg-white h-100 shadow-xs">
                        <div class="fw-bold text-dark text-truncate mb-1" :title="type.type_name">
                            {{ type.workbook_name }}
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span>Tersedia: <strong class="text-success">{{ type.available_slots || 0 }}</strong></span>
                            <span>Terpakai: <strong class="text-primary">{{ type.used_slots || 0 }}</strong></span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div
                                class="progress-bar bg-primary"
                                :style="{
                                    width: type.total_slots && type.total_slots > 0
                                        ? `${((type.used_slots || 0) / type.total_slots) * 100}%`
                                        : '0%'
                                }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
