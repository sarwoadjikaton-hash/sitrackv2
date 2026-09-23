<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const signatureSteps = [
    { 
        num: 1, 
        title: 'Diregistrasi', 
        desc: 'Permohonan diajukan oleh unit pengusul via portal publik atau loket, menerima Nomor Agenda dan Kode Resi Tracking.', 
        role: 'Unit Pengusul',
        icon: 'bi-file-earmark-plus'
    },
    { 
        num: 2, 
        title: 'Diterima', 
        desc: 'Fisik naskah dinas diterima di loket Tata Usaha untuk diverifikasi kelengkapan berkasnya.', 
        role: 'Loket / Tata Usaha',
        icon: 'bi-inbox'
    },
    { 
        num: 3, 
        title: 'Diperiksa Oleh TU Sekjen', 
        desc: 'Verifikasi kelengkapan format, substansi naskah dinas, dan arsip pendukung oleh staf TU Sekjen.', 
        role: 'Staf TU Sekjen',
        icon: 'bi-search'
    },
    { 
        num: 4, 
        title: 'Diperiksa Oleh Kasubag TU Sekjen', 
        desc: 'Pemeriksaan keabsahan redaksional, penyesuaian akhir, dan pemberian paraf kendali oleh Kasubag TU.', 
        role: 'Kasubag TU Sekjen',
        icon: 'bi-check2-circle'
    },
    { 
        num: 5, 
        title: 'Diperiksa Oleh Sekjen', 
        desc: 'Penyampaian berkas kepada Sekretaris Jenderal untuk proses penandatanganan / paraf resmi.', 
        role: 'Sekretaris Jenderal',
        icon: 'bi-pen'
    },
    { 
        num: 6, 
        title: 'Selesai dan Siap Untuk diambil', 
        desc: 'Naskah telah selesai ditandatangani/diparaf dan siap diambil kembali oleh staf unit pengusul.', 
        role: 'Tata Usaha',
        icon: 'bi-bell'
    },
    { 
        num: 7, 
        title: 'Dokumen Sudah diambil', 
        desc: 'Fisik surat resmi telah diserahterimakan kepada staf unit dan proses persuratan dinyatakan tuntas.', 
        role: 'Unit Pengolah',
        icon: 'bi-check-all'
    },
];

const signatureExceptions = [
    {
        title: 'Revisi',
        desc: 'Dokumen dikembalikan sementara ke unit pengusul untuk perbaikan redaksional/kelengkapan sebelum diajukan kembali.',
        badge: 'badge-status-processing'
    },
    {
        title: 'Ditolak',
        desc: 'Pengajuan ditolak oleh pemeriksa/pimpinan karena tidak memenuhi ketentuan perundang-undangan atau kebijakan.',
        badge: 'badge-status-cancelled'
    }
];

const dispositionSteps = [
    { 
        num: 1, 
        title: 'Surat Diterima TU', 
        desc: 'Surat masuk eksternal/internal diterima, dicatat, dan diagendakan ke lajur disposisi pimpinan.', 
        role: 'Admin Operator',
        icon: 'bi-envelope'
    },
    { 
        num: 2, 
        title: 'Diajukan ke Sekjen', 
        desc: 'Dokumen masuk disampaikan kepada pimpinan untuk mendapatkan telaah dan arahan tertulis.', 
        role: 'TU Pimpinan',
        icon: 'bi-send'
    },
    { 
        num: 3, 
        title: 'Didisposisikan', 
        desc: 'Pimpinan menerbitkan instruksi lembar disposisi resmi kepada unit kerja atau pejabat penanggung jawab.', 
        role: 'Sekretaris Jenderal',
        icon: 'bi-diagram-3'
    },
    { 
        num: 4, 
        title: 'Diteruskan ke Unit', 
        desc: 'Lembar disposisi fisik/digital diteruskan kepada unit kerja pelaksana untuk segera ditindaklanjuti.', 
        role: 'Tata Usaha / Unit',
        icon: 'bi-arrow-right-circle'
    },
    { 
        num: 5, 
        title: 'Dalam Tindak Lanjut', 
        desc: 'Unit kerja memproses butir arahan instruksi disposisi dan mencatat perkembangan tindak lanjut.', 
        role: 'Pejabat Unit Kerja',
        icon: 'bi-hourglass-split'
    },
    { 
        num: 6, 
        title: 'Selesai', 
        desc: 'Seluruh butir arahan instruksi disposisi telah tuntas ditindaklanjuti dan dilaporkan kembali.', 
        role: 'Sistem / Pimpinan',
        icon: 'bi-check-circle-fill'
    },
];

const dispositionExceptions = [
    {
        title: 'Dikembalikan',
        desc: 'Surat masuk dibatalkan atau dikembalikan ke instansi pengirim karena salah alamat atau berkas tidak valid.',
        badge: 'badge-status-cancelled'
    }
];
</script>

<template>
    <AppLayout title="Panduan Alur Status Persuratan">
        <Head title="Alur Status Persuratan" />

        <div class="mb-4">
            <span class="text-uppercase fw-bold small text-primary tracking-wide">SOP & Alur Operasional</span>
            <h2 class="fw-bold mb-1 text-dark">Panduan Alur Status Persuratan</h2>
            <p class="text-muted mb-0 small">Visualisasi diagram dan urutan status resmi untuk kedua lajur operasional persuratan di lingkungan Sekretariat Jenderal.</p>
        </div>

        <!-- Lajur 1: Tindak Lanjut / Penandatanganan -->
        <div class="st-card mb-4 shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-lane-signature fs-6 px-3 py-2">
                        <i class="bi bi-pen-fill me-1"></i> Lajur Pertama
                    </span>
                    <h4 class="fw-bold text-dark mb-0">Alur Tindak Lanjut & Penandatanganan Pimpinan</h4>
                </div>
                <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill small">
                    <i class="bi bi-shield-check text-primary me-1"></i> 7 Tahap Utama
                </span>
            </div>

            <!-- Steps Grid -->
            <div class="row g-3 mb-4">
                <div v-for="step in signatureSteps" :key="step.num" class="col-md-6 col-lg-4 col-xl-3">
                    <div class="p-3 rounded-3 border bg-white h-100 position-relative shadow-xs d-flex flex-column justify-content-between hover-lift transition"
                        :class="{ 
                            'border-warning-subtle bg-warning-subtle bg-opacity-10': step.num === 6,
                            'border-success-subtle bg-success-subtle bg-opacity-10': step.num === 7 
                        }">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge rounded-circle d-grid place-items-center fw-bold"
                                    :class="step.num === 7 ? 'bg-success text-white' : (step.num === 6 ? 'bg-warning text-dark' : 'bg-primary text-white')"
                                    style="width: 28px; height: 28px; font-size: 0.85rem;">
                                    {{ step.num }}
                                </span>
                                <i :class="['bi', step.icon, step.num === 7 ? 'text-success' : (step.num === 6 ? 'text-warning' : 'text-primary'), 'fs-5']"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">{{ step.title }}</h6>
                            <p class="small text-muted mb-3" style="font-size: 0.82rem; line-height: 1.45;">{{ step.desc }}</p>
                        </div>
                        <div class="pt-2 border-top">
                            <span class="badge small fw-medium border"
                                :class="step.num === 7 ? 'bg-success-subtle text-success border-success-subtle' : (step.num === 6 ? 'bg-warning-subtle text-warning-emphasis border-warning-subtle' : 'bg-light text-primary')">
                                <i class="bi bi-person me-1"></i>{{ step.role }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exceptions / Status Khusus -->
            <div class="p-3 rounded-3 bg-light border">
                <h6 class="fw-bold text-secondary mb-2 small text-uppercase">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Status Kondisional / Pengecualian:
                </h6>
                <div class="row g-2">
                    <div v-for="exc in signatureExceptions" :key="exc.title" class="col-md-6">
                        <div class="d-flex align-items-start gap-2 bg-white p-2.5 rounded-2 border">
                            <StatusBadge :status="exc.title" class="flex-shrink-0" />
                            <span class="small text-muted" style="font-size: 0.8rem;">{{ exc.desc }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lajur 2: Disposisi -->
        <div class="st-card shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-lane-disposition fs-6 px-3 py-2">
                        <i class="bi bi-diagram-3-fill me-1"></i> Lajur Kedua
                    </span>
                    <h4 class="fw-bold text-dark mb-0">Alur Disposisi & Arahan Bertingkat</h4>
                </div>
                <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill small">
                    <i class="bi bi-shield-check text-info me-1"></i> 6 Tahap Utama
                </span>
            </div>

            <!-- Steps Grid -->
            <div class="row g-3 mb-4">
                <div v-for="step in dispositionSteps" :key="step.num" class="col-md-6 col-lg-4">
                    <div class="p-3 rounded-3 border bg-white h-100 position-relative shadow-xs d-flex flex-column justify-content-between hover-lift transition"
                        :class="{ 'border-success-subtle bg-success-subtle bg-opacity-10': step.num === 6 }">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge rounded-circle d-grid place-items-center fw-bold"
                                    :class="step.num === 6 ? 'bg-success text-white' : 'bg-info text-white'"
                                    style="width: 28px; height: 28px; font-size: 0.85rem;">
                                    {{ step.num }}
                                </span>
                                <i :class="['bi', step.icon, step.num === 6 ? 'text-success' : 'text-info', 'fs-5']"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">{{ step.title }}</h6>
                            <p class="small text-muted mb-3" style="font-size: 0.82rem; line-height: 1.45;">{{ step.desc }}</p>
                        </div>
                        <div class="pt-2 border-top">
                            <span class="badge small fw-medium border"
                                :class="step.num === 6 ? 'bg-success-subtle text-success border-success-subtle' : 'bg-light text-info'">
                                <i class="bi bi-person me-1"></i>{{ step.role }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exceptions / Status Khusus -->
            <div class="p-3 rounded-3 bg-light border">
                <h6 class="fw-bold text-secondary mb-2 small text-uppercase">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Status Kondisional / Pengecualian:
                </h6>
                <div class="row g-2">
                    <div v-for="exc in dispositionExceptions" :key="exc.title" class="col-md-6">
                        <div class="d-flex align-items-start gap-2 bg-white p-2.5 rounded-2 border">
                            <StatusBadge :status="exc.title" class="flex-shrink-0" />
                            <span class="small text-muted" style="font-size: 0.8rem;">{{ exc.desc }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

