<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { LetterCategory, Unit } from '@/types';

defineProps<{
    categories: LetterCategory[];
    units: Unit[];
    allowedStatuses: string[];
}>();

const form = useForm({
    letter_number: '',
    sender_unit: '',
    sender_name: '',
    sender_phone: '',
    category_id: null as number | null,
    subject: '',
    letter_date: new Date().toISOString().substring(0, 10),
    received_date: new Date().toISOString().substring(0, 10),
    priority: 'normal',
    security_level: 'Biasa',
    notes: '',
    letter_source: 'Manual',
    
    // Initial Disposition
    instruction: '',
    to_unit_id: null as number | null,
    to_name: '',
    due_date: '',
    is_koordinator: false,
    attachment: null as File | null,
});

const handleFileUpload = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.attachment = target.files[0];
    }
};

const submit = () => {
    form.post('/disposisi');
};
</script>

<template>
    <AppLayout title="Input Surat Disposisi">
        <Head title="Input Disposisi Baru" />

        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <span class="badge-lane-disposition d-inline-flex align-items-center gap-1 mb-2">
                    <i class="bi bi-diagram-3-fill"></i> Lajur Kedua
                </span>
                <h2 class="fw-bold mb-1 text-dark">Registrasi Surat Disposisi</h2>
                <p class="text-muted mb-0 small">Masukkan surat masuk untuk diajukan ke Sekretaris Jenderal atau langsung didisposisikan.</p>
            </div>
            <Link href="/disposisi" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </Link>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="st-card">
                    <form @submit.prevent="submit">
                        <!-- Metadata Surat -->
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Identitas Surat Masuk</h5>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Sumber Surat</label>
                                <select v-model="form.letter_source" class="form-select" required>
                                    <option value="Manual">Manual / Cetak</option>
                                    <option value="SRIKANDI">Aplikasi SRIKANDI</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Kategori Dokumen</label>
                                <select v-model="form.category_id" class="form-select">
                                    <option :value="null">Pilih Kategori</option>
                                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.category_name }}</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nomor Surat Masuk</label>
                                <input v-model="form.letter_number" type="text" class="form-control" placeholder="Contoh: 123/EXT/VIII/2026" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Instansi / Unit Pengirim</label>
                                <input v-model="form.sender_unit" type="text" class="form-control" placeholder="Nama instansi/badan pengirim" required />
                                <div v-if="form.errors.sender_unit" class="text-danger small mt-1">{{ form.errors.sender_unit }}</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nama Pengirim / Penandatangan</label>
                                <input v-model="form.sender_name" type="text" class="form-control" placeholder="Nama pejabat/pengirim" required />
                                <div v-if="form.errors.sender_name" class="text-danger small mt-1">{{ form.errors.sender_name }}</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nomor WhatsApp Pengirim</label>
                                <input v-model="form.sender_phone" type="text" class="form-control" placeholder="08xxxxxxxxxx (Opsional)" />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Tanggal Surat</label>
                                <input v-model="form.letter_date" type="date" class="form-control" />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Tanggal Diterima TU</label>
                                <input v-model="form.received_date" type="date" class="form-control" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Tingkat Prioritas</label>
                                <select v-model="form.priority" class="form-select">
                                    <option value="normal">Biasa</option>
                                    <option value="high">Penting</option>
                                    <option value="urgent">Mendesak / Segera</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Derajat Keamanan</label>
                                <select v-model="form.security_level" class="form-select">
                                    <option value="Biasa">Biasa</option>
                                    <option value="Rahasia">Rahasia</option>
                                    <option value="Sangat Rahasia">Sangat Rahasia</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Perihal Surat</label>
                                <textarea v-model="form.subject" class="form-control" rows="2" placeholder="Tuliskan perihal surat secara lengkap..." required></textarea>
                                <div v-if="form.errors.subject" class="text-danger small mt-1">{{ form.errors.subject }}</div>
                            </div>
                        </div>

                        <!-- Arahan Disposisi Awal (Opsional) -->
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">2. Arahan Disposisi Pimpinan (Opsional)</h5>

                        <div class="row g-3 mb-4 p-3 bg-light rounded-3 border">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Diteruskan Kepada (Unit Kerja)</label>
                                <select v-model="form.to_unit_id" class="form-select">
                                    <option :value="null">-- Pilih Unit Penerima Disposisi --</option>
                                    <option v-for="u in units" :key="u.id" :value="u.id">{{ u.unit_name }}</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nama Pejabat / Penerima (Jika ada)</label>
                                <input v-model="form.to_name" type="text" class="form-control" placeholder="Nama pejabat penerima arahan" />
                            </div>

                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Isi Instruksi / Catatan Disposisi</label>
                                <textarea v-model="form.instruction" class="form-control" rows="2" placeholder="Contoh: Mohon dipelajari dan ditindaklanjuti sesuai ketentuan..."></textarea>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Batas Waktu (Due Date)</label>
                                <input v-model="form.due_date" type="date" class="form-control" />
                                <div class="form-check mt-2">
                                    <input id="koordinator" v-model="form.is_koordinator" type="checkbox" class="form-check-input" />
                                    <label for="koordinator" class="form-check-label small fw-semibold">Sebagai Koordinator</label>
                                </div>
                            </div>
                        </div>

                        <!-- Lampiran -->
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">3. Lampiran & Catatan TU</h5>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Unggah Dokumen Lampiran (PDF)</label>
                                <input type="file" class="form-control" @change="handleFileUpload" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Catatan Tambahan TU</label>
                                <textarea v-model="form.notes" class="form-control" rows="2" placeholder="Catatan penerimaan surat..."></textarea>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <Link href="/disposisi" class="btn btn-secondary">Batal</Link>
                            <button type="submit" class="btn btn-primary-blue" :disabled="form.processing">
                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                                <i class="bi bi-check2-circle me-1"></i> Simpan Surat Disposisi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
