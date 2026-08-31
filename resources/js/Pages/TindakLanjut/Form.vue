<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Letter, LetterCategory, Unit } from '@/types';

const props = defineProps<{
    letter?: Letter | null;
    categories: LetterCategory[];
    units: Unit[];
    allowedStatuses: string[];
}>();

const isEditing = !!props.letter;

const form = useForm({
    letter_number: props.letter?.letter_number || '',
    letter_type: props.letter?.letter_type || 'in',
    letter_source: props.letter?.letter_source || 'Manual',
    sender_unit: props.letter?.sender_unit || '',
    sender_name: props.letter?.sender_name || '',
    sender_phone: props.letter?.sender_phone || '',
    recipient_unit_id: props.letter?.recipient_unit_id || null,
    category_id: props.letter?.category_id || null,
    subject: props.letter?.subject || '',
    letter_date: props.letter?.letter_date ? props.letter.letter_date.substring(0, 10) : new Date().toISOString().substring(0, 10),
    received_date: props.letter?.received_date ? props.letter.received_date.substring(0, 10) : new Date().toISOString().substring(0, 10),
    priority: props.letter?.priority || 'normal',
    security_level: props.letter?.security_level || 'Biasa',
    status: props.letter?.status || 'Dokumen Diterima dan Diinput',
    current_position: props.letter?.current_position || 'Tata Usaha',
    requested_actions: props.letter?.requested_actions ? props.letter.requested_actions.split(', ') : [],
    notes: props.letter?.notes || '',
    attachment: null as File | null,
});

const actionOptions = [
    'Mohon Paraf',
    'Mohon Tanda Tangan',
    'Informasi',
    'Aksi',
    'Mohon Arahan',
    'Mohon Keputusan',
    'Mohon Persetujuan',
];

const handleFileUpload = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.attachment = target.files[0];
    }
};

const submit = () => {
    if (isEditing && props.letter) {
        form.put(`/tindak-lanjut/${props.letter.id}`);
    } else {
        form.post('/tindak-lanjut');
    }
};
</script>

<template>
    <AppLayout :title="isEditing ? 'Ubah Lembar Tindak Lanjut' : 'Input Tindak Lanjut / TTD Baru'">
        <Head :title="isEditing ? 'Ubah Tindak Lanjut' : 'Input Tindak Lanjut'" />

        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <span class="badge-lane-signature d-inline-flex align-items-center gap-1 mb-2">
                    <i class="bi bi-pen-fill"></i> Lajur Pertama
                </span>
                <h2 class="fw-bold mb-1 text-dark">{{ isEditing ? 'Ubah Data Tindak Lanjut' : 'Registrasi Tindak Lanjut / TTD' }}</h2>
                <p class="text-muted mb-0 small">Lengkapi informasi naskah dinas untuk proses verifikasi dan penandatanganan.</p>
            </div>
            <Link href="/tindak-lanjut" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </Link>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="st-card">
                    <form @submit.prevent="submit">
                        <!-- Informasi Utama -->
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Informasi & Identitas Dokumen</h5>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Jenis Surat</label>
                                <select v-model="form.letter_type" class="form-select" required>
                                    <option value="in">Surat Masuk</option>
                                    <option value="out">Surat Keluar</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Sumber Naskah</label>
                                <select v-model="form.letter_source" class="form-select" required>
                                    <option value="Manual">Manual / Fisik</option>
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

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nomor Surat Asal / Konsep</label>
                                <input v-model="form.letter_number" type="text" class="form-control" placeholder="Contoh: B-123/TU/08/2026 (Opsional)" />
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Tanggal Surat</label>
                                <input v-model="form.letter_date" type="date" class="form-control" />
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Tanggal Diterima</label>
                                <input v-model="form.received_date" type="date" class="form-control" />
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Perihal Naskah Dinas</label>
                                <textarea v-model="form.subject" class="form-control" rows="2" placeholder="Tuliskan perihal surat secara lengkap..." required></textarea>
                                <div v-if="form.errors.subject" class="text-danger small mt-1">{{ form.errors.subject }}</div>
                            </div>
                        </div>

                        <!-- Pengirim & Penerima -->
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">2. Pengirim & Tujuan Unit</h5>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Unit / Instansi Pengirim</label>
                                <input v-model="form.sender_unit" type="text" class="form-control" placeholder="Nama instansi/unit kerja" />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nama Pengirim / Pembawa</label>
                                <input v-model="form.sender_name" type="text" class="form-control" placeholder="Nama lengkap pengirim" required />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nomor WhatsApp Pengirim</label>
                                <input v-model="form.sender_phone" type="text" class="form-control" placeholder="08xxxxxxxxxx" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Unit Tujuan / Penerima Internal</label>
                                <select v-model="form.recipient_unit_id" class="form-select">
                                    <option :value="null">Pilih Unit Penerima</option>
                                    <option v-for="u in units" :key="u.id" :value="u.id">{{ u.unit_name }}</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Tingkat Prioritas</label>
                                <select v-model="form.priority" class="form-select">
                                    <option value="normal">Biasa / Normal</option>
                                    <option value="high">Penting</option>
                                    <option value="urgent">Amat Segera / Mendesak</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Derajat Keamanan</label>
                                <select v-model="form.security_level" class="form-select">
                                    <option value="Biasa">Biasa</option>
                                    <option value="Rahasia">Rahasia</option>
                                    <option value="Sangat Rahasia">Sangat Rahasia</option>
                                </select>
                            </div>
                        </div>

                        <!-- Status & Posisi -->
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">3. Status Pengendalian & Tindakan</h5>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Status Awal</label>
                                <select v-model="form.status" class="form-select" required>
                                    <option v-for="s in allowedStatuses" :key="s" :value="s">{{ s }}</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Posisi Berkas Awal</label>
                                <input v-model="form.current_position" type="text" class="form-control" placeholder="Contoh: Tata Usaha / Arsiparis" required />
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Tindakan yang Dimohonkan</label>
                                <div class="row g-2">
                                    <div v-for="opt in actionOptions" :key="opt" class="col-md-3 col-6">
                                        <div class="form-check">
                                            <input
                                                :id="`form-act-${opt}`"
                                                v-model="form.requested_actions"
                                                type="checkbox"
                                                class="form-check-input"
                                                :value="opt"
                                            />
                                            <label :for="`form-act-${opt}`" class="form-check-label small">
                                                {{ opt }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Unggah Lampiran Naskah (PDF/DOCX)</label>
                                <input type="file" class="form-control" @change="handleFileUpload" />
                                <small class="text-muted">Maksimal 20 MB</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Catatan Khusus</label>
                                <textarea v-model="form.notes" class="form-control" rows="2" placeholder="Catatan tambahan dari penginput..."></textarea>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <Link href="/tindak-lanjut" class="btn btn-secondary">Batal</Link>
                            <button type="submit" class="btn btn-primary-blue" :disabled="form.processing">
                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                                <i class="bi bi-check2-circle me-1"></i>
                                {{ isEditing ? 'Simpan Perubahan' : 'Catat Lembar Tindak Lanjut' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
