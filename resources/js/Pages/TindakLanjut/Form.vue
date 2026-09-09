<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Letter, LetterCategory, Unit, LetterNumberType } from '@/types';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps<{
    letter?: Letter | null;
    categories: LetterCategory[];
    units: Unit[];
    letterNumberTypes: LetterNumberType[];
    allowedStatuses: string[];
}>();

const isEditing = !!props.letter;

const form = useForm({
    letter_number: props.letter?.letter_number || '',
    letter_number_type_id: props.letter?.letter_number_type_id || null,
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
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(`/tindak-lanjut/${props.letter.id}`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                // berhasil
            },
            onError: (errors) => {
                console.log('Update gagal:', errors);
            },
        });
    } else {
        form.post('/tindak-lanjut', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                // berhasil
            },
            onError: (errors) => {
                console.log('Input gagal:', errors);
            },
        });
    }
};

const letterTypeOptions = [
    { value: 'in', label: 'Surat Masuk' },
    { value: 'out', label: 'Surat Keluar' },
];

const letterSourceOptions = [
    { value: 'Manual', label: 'Manual / Fisik' },
    { value: 'SRIKANDI', label: 'Aplikasi SRIKANDI' },
];

const priorityOptions = [
    { value: 'normal', label: 'Biasa / Normal' },
    { value: 'high', label: 'Penting' },
    { value: 'urgent', label: 'Amat Segera / Mendesak' },
];

const securityOptions = [
    { value: 'Biasa', label: 'Biasa' },
    { value: 'Rahasia', label: 'Rahasia' },
    { value: 'Sangat Rahasia', label: 'Sangat Rahasia' },
];

const letterNumberTypeOptions = computed(() =>
    props.letterNumberTypes.map((t) => ({ value: t.id, label: t.type_name }))
);

const categoryOptions = computed(() =>
    props.categories.map((c) => ({ value: c.id, label: c.category_name }))
);

const unitOptions = computed(() =>
    props.units.map((u) => ({ value: u.id, label: u.unit_name }))
);

const statusOptions = computed(() =>
    props.allowedStatuses.map((s) => ({ value: s, label: s }))
);
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
                <h2 class="fw-bold mb-1 text-dark">{{ isEditing ? 'Ubah Data Tindak Lanjut' : 'Registrasi Tindak / TTD'
                    }}</h2>
                <p class="text-muted mb-0 small">Lengkapi informasi naskah dinas untuk proses verifikasi dan
                    penandatanganan.</p>
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
                                <SearchableSelect v-model="form.letter_type" :options="letterTypeOptions"
                                    placeholder="Pilih Jenis Surat" search-placeholder="Cari jenis surat..." />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Jenis Naskah Dinas</label>
                                <SearchableSelect v-model="form.letter_number_type_id"
                                    :options="letterNumberTypeOptions" placeholder="Pilih Jenis Naskah (Opsional)"
                                    search-placeholder="Cari jenis naskah..." clearable />
                                <small class="text-muted">Menentukan awalan kode resi (mis. ND_MEMO-...)</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Sumber Naskah</label>
                                <SearchableSelect v-model="form.letter_source" :options="letterSourceOptions"
                                    placeholder="Pilih Sumber Naskah" search-placeholder="Cari sumber..." />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Kategori Dokumen</label>
                                <SearchableSelect v-model="form.category_id" :options="categoryOptions"
                                    placeholder="Pilih Kategori" search-placeholder="Cari kategori..." clearable />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nomor Surat Asal / Konsep</label>
                                <input v-model="form.letter_number" type="text" class="form-control"
                                    placeholder="Contoh: B-123/TU/08/2026 (Opsional)" />
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
                                <textarea v-model="form.subject" class="form-control" rows="2"
                                    placeholder="Tuliskan perihal surat secara lengkap..." required></textarea>
                                <div v-if="form.errors.subject" class="text-danger small mt-1">{{ form.errors.subject }}
                                </div>
                            </div>
                        </div>

                        <!-- Pengirim & Penerima -->
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">2. Pengirim & Tujuan Unit</h5>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Unit / Instansi Pengirim</label>
                                <input v-model="form.sender_unit" type="text" class="form-control"
                                    placeholder="Nama instansi/unit kerja" />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nama Pengirim / Pembawa</label>
                                <input v-model="form.sender_name" type="text" class="form-control"
                                    placeholder="Nama lengkap pengirim" required />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nomor WhatsApp Pengirim</label>
                                <input v-model="form.sender_phone" type="text" class="form-control"
                                    placeholder="08xxxxxxxxxx" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Unit Tujuan / Penerima Internal</label>
                                <SearchableSelect v-model="form.recipient_unit_id" :options="unitOptions"
                                    placeholder="Pilih Unit Penerima" search-placeholder="Cari unit..." clearable />
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Tingkat Prioritas</label>
                                <SearchableSelect v-model="form.priority" :options="priorityOptions"
                                    placeholder="Pilih Prioritas" search-placeholder="Cari..." />
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Derajat Keamanan</label>
                                <SearchableSelect v-model="form.security_level" :options="securityOptions"
                                    placeholder="Pilih Derajat Keamanan" search-placeholder="Cari..." />
                            </div>
                        </div>

                        <!-- Status & Posisi -->
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">3. Status Pengendalian & Tindakan</h5>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Status Awal</label>
                                <SearchableSelect v-model="form.status" :options="statusOptions"
                                    placeholder="Pilih Status" search-placeholder="Cari status..." />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Posisi Berkas Awal</label>
                                <input v-model="form.current_position" type="text" class="form-control"
                                    placeholder="Contoh: Tata Usaha / Arsiparis" required />
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Tindakan yang Dimohonkan</label>
                                <div class="row g-2">
                                    <div v-for="opt in actionOptions" :key="opt" class="col-md-3 col-6">
                                        <div class="form-check">
                                            <input :id="`form-act-${opt}`" v-model="form.requested_actions"
                                                type="checkbox" class="form-check-input" :value="opt" />
                                            <label :for="`form-act-${opt}`" class="form-check-label small">
                                                {{ opt }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Unggah Lampiran Naskah (PDF/DOCX)</label>
                                <input type="file" class="form-control"
                                    accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                    @change="handleFileUpload" />
                                <small class="text-muted">Maksimal 20 MB</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Catatan Khusus</label>
                                <textarea v-model="form.notes" class="form-control" rows="2"
                                    placeholder="Catatan tambahan dari penginput..."></textarea>
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
