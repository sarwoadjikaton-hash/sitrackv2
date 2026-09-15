<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const signatureSteps = [
    { num: 1, title: 'Pengajuan Berhasil', desc: 'Permohonan paraf diajukan oleh unit pengusul, menerima Nomor Agenda dan Kode Resi Tracking.', role: 'Unit Pengusul' },
    { num: 2, title: 'Diperiksa Arsiparis', desc: 'Verifikasi kelengkapan berkas, format naskah dinas, dan arsip pendukung.', role: 'Arsiparis' },
    { num: 3, title: 'Paraf Pengendalian Administrasi', desc: 'Pemeriksaan keabsahan redaksional dan paraf oleh Ktus / Subkoordinator.', role: 'Kasubbag / Ktus' },
    { num: 4, title: 'Proses Paraf/TTD Sekjen', desc: 'Penyampaian berkas ke meja pimpinan untuk penandatanganan resmi.', role: 'Sekretaris Jenderal' },
    { num: 5, title: 'Surat Selesai di Paraf/TTD', desc: 'Naskah telah ditandatangani dan siap diambil oleh unit pengolah.', role: 'Tata Usaha' },
    { num: 6, title: 'Dokumen Sudah Diambil', desc: 'Fisik surat diserahterimakan kepada staf unit dan proses tuntas.', role: 'Unit Pengolah' },
];

const dispositionSteps = [
    { num: 1, title: 'Surat Diterima TU', desc: 'Surat masuk eksternal/internal dicatat dan diagendakan ke lajur disposisi.', role: 'Admin Operator' },
    { num: 2, title: 'Diajukan ke Sekjen', desc: 'Dokumen masuk disampaikan ke pimpinan untuk mendapatkan arahan tertulis.', role: 'TU Pimpinan' },
    { num: 3, title: 'Didisposisikan', desc: 'Pimpinan menerbitkan instruksi disposisi kepada unit kerja atau pejabat terkait.', role: 'Sekretaris Jenderal' },
    { num: 4, title: 'Diteruskan ke Unit', desc: 'Lembar disposisi diteruskan kepada unit kerja pelaksana.', role: 'Unit Kerja' },
    { num: 5, title: 'Dalam Tindak Lanjut', desc: 'Unit kerja memproses instruksi dan mengunggah catatan perkembangan.', role: 'Pejabat Unit' },
    { num: 6, title: 'Selesai', desc: 'Seluruh butir arahan disposisi telah tuntas ditindaklanjuti.', role: 'Sistem' },
];
</script>

<template>
    <AppLayout title="Panduan Alur Status Persuratan">
        <Head title="Alur Status Persuratan" />

        <div class="mb-4">
            <span class="text-uppercase fw-bold small text-primary">SOP & Alur Kerja</span>
            <h2 class="fw-bold mb-1 text-dark">Panduan Alur Status Persuratan</h2>
            <p class="text-muted mb-0 small">Visualisasi diagram alur kerja untuk kedua lajur operasional persuratan di lingkungan Sekretariat Jenderal.</p>
        </div>

        <!-- Lajur 1: Tindak Lanjut / Penandatanganan -->
        <div class="st-card mb-4">
            <div class="d-flex align-items-center gap-2 mb-4 border-bottom pb-3">
                <span class="badge-lane-signature fs-6 px-3 py-2">
                    <i class="bi bi-pen-fill me-1"></i> Lajur Pertama
                </span>
                <h4 class="fw-bold text-dark mb-0">Alur Tindak Lanjut & Penandatanganan Pimpinan</h4>
            </div>

            <div class="row g-3">
                <div v-for="step in signatureSteps" :key="step.num" class="col-md-6 col-lg-4">
                    <div class="p-3 rounded-3 border bg-light h-100 position-relative">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary text-white rounded-circle d-grid place-items-center" style="width: 28px; height: 28px;">
                                {{ step.num }}
                            </span>
                            <h6 class="fw-bold text-dark mb-0">{{ step.title }}</h6>
                        </div>
                        <p class="small text-muted mb-2">{{ step.desc }}</p>
                        <span class="badge bg-white text-primary border small">
                            <i class="bi bi-person me-1"></i>{{ step.role }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lajur 2: Disposisi -->
        <div class="st-card">
            <div class="d-flex align-items-center gap-2 mb-4 border-bottom pb-3">
                <span class="badge-lane-disposition fs-6 px-3 py-2">
                    <i class="bi bi-diagram-3-fill me-1"></i> Lajur Kedua
                </span>
                <h4 class="fw-bold text-dark mb-0">Alur Disposisi & Arahan Bertingkat</h4>
            </div>

            <div class="row g-3">
                <div v-for="step in dispositionSteps" :key="step.num" class="col-md-6 col-lg-4">
                    <div class="p-3 rounded-3 border bg-light h-100 position-relative">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary text-white rounded-circle d-grid place-items-center" style="width: 28px; height: 28px;">
                                {{ step.num }}
                            </span>
                            <h6 class="fw-bold text-dark mb-0">{{ step.title }}</h6>
                        </div>
                        <p class="small text-muted mb-2">{{ step.desc }}</p>
                        <span class="badge bg-white text-primary border small">
                            <i class="bi bi-person me-1"></i>{{ step.role }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
