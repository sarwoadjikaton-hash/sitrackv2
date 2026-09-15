<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Letter } from '@/types';

defineProps<{
    letter: Letter;
}>();
</script>

<template>
    <PublicLayout>

        <Head title="Pengajuan Berhasil" />

        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="st-card text-center p-4 p-md-5 shadow-lg border-0">
                        <div class="stat-icon mx-auto mb-3"
                            style="background: #ecfdf5; color: #10b981; width: 64px; height: 64px; font-size: 2rem;">
                            <i class="bi bi-check2-circle"></i>
                        </div>

                        <h2 class="fw-bold text-dark mb-1">Pengajuan Surat Berhasil!</h2>
                        <p class="text-muted small mb-4">
                            Permohonan paraf naskah dinas Anda telah berhasil diajukan dan terdaftar dalam sistem SiTrack. Simpan Nomor Resi / Kode Tracking di bawah ini.
                        </p>

                        <!-- Tracking Code Box -->
                        <div class="p-4 rounded-3 border mb-4" style="background: #eff6ff;">
                            <small class="text-muted text-uppercase fw-bold d-block mb-1">Kode Tracking Resmi</small>
                            <h2 class="fw-extrabold font-monospace text-primary mb-2">{{ letter.tracking_code }}</h2>
                            <div class="small text-dark fw-semibold">
                                Nomor Agenda: <span class="font-monospace">{{ letter.agenda_number }}</span>
                            </div>
                            <div class="small text-muted mt-2 pt-2 border-top">
                                <span v-if="letter.letter_number">
                                    Nomor Surat: <span class="font-monospace fw-bold text-dark">{{ letter.letter_number }}</span>
                                </span>
                                <span v-else class="text-secondary fst-italic">
                                    <i class="bi bi-hourglass-split me-1 text-warning"></i>Nomor surat belum diterbitkan (menunggu verifikasi & input Admin/TU).
                                </span>
                            </div>
                        </div>

                        <!-- Details Summary -->
                        <dl class="row small text-start border-bottom pb-3 mb-4">
                            <dt class="col-5 text-muted">Pengirim / Pemohon</dt>
                            <dd class="col-7 fw-bold text-dark">{{ letter.sender_name }}</dd>

                            <dt class="col-5 text-muted">Unit Pengusul</dt>
                            <dd class="col-7 fw-semibold text-dark">{{ letter.sender_unit || '-' }}</dd>

                            <dt class="col-5 text-muted">Unit Tujuan</dt>
                            <dd class="col-7 text-dark">{{ letter.destination || (letter.recipient_unit?.unit_name || '-') }}</dd>

                            <dt class="col-5 text-muted">Sifat Naskah</dt>
                            <dd class="col-7"><span class="badge bg-light text-dark border">{{ letter.priority || 'Biasa' }}</span></dd>

                            <dt class="col-5 text-muted">Perihal</dt>
                            <dd class="col-7 text-truncate" :title="letter.subject">{{ letter.subject }}</dd>

                            <dt class="col-5 text-muted">Posisi Berkas Awal</dt>
                            <dd class="col-7 fw-semibold text-primary"><i class="bi bi-geo-alt-fill me-1"></i>{{ letter.current_position }}</dd>

                            <dt class="col-5 text-muted">Status</dt>
                            <dd class="col-7 fw-bold text-success">{{ letter.status }}</dd>
                        </dl>

                        <!-- Actions -->
                        <div class="d-flex justify-content-center">
                            <Link :href="`/tracking?code=${letter.tracking_code}`" class="btn btn-primary-blue">
                                <i class="bi bi-search me-1"></i> Pantau Status Surat
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>