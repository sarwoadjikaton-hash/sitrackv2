<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import { LetterNumberType } from '@/types';

defineProps<{
    types: LetterNumberType[];
}>();

const showModal = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null as number | null,
    type_code: '',
    type_name: '',
    workbook_name: '',
    uses_security_access: false,
    extra_field: 'scan_result' as 'scan_result' | 'nd_pengantar',
    number_pattern: '{security}-{signer}/{sequence}/{classification}/{month_roman}/{year}',
    sequence_padding: 4,
    default_signer_code: '1',
    display_order: 0,
    is_active: true,
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (t: LetterNumberType) => {
    isEditing.value = true;
    form.id = t.id;
    form.type_code = t.type_code;
    form.type_name = t.type_name;
    form.workbook_name = t.workbook_name;
    form.uses_security_access = t.uses_security_access;
    form.extra_field = t.extra_field;
    form.number_pattern = t.number_pattern;
    form.sequence_padding = t.sequence_padding;
    form.default_signer_code = t.default_signer_code || '1';
    form.display_order = t.display_order || 0;
    form.is_active = t.is_active;
    showModal.value = true;
};

const submitType = () => {
    form.post('/master/number-types', {
        onSuccess: () => {
            showModal.value = false;
        },
    });
};

const deleteType = (id: number) => {
    if (confirm('Hapus profil jenis naskah penomoran ini?')) {
        router.delete(`/master/number-types/${id}`);
    }
};
</script>

<template>
    <AppLayout title="Jenis Naskah & Formula Penomoran">
        <Head title="Jenis Naskah & Formula" />

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="text-uppercase fw-bold small text-primary">Master Penomoran</span>
                <h2 class="fw-bold mb-1 text-dark">Jenis Naskah & Formula</h2>
                <p class="text-muted mb-0 small">Konfigurasi pola nomor dinas, digit urut, dan field ekstra sesuai referensi workbook REKAP NOMOR 2026.</p>
            </div>

            <button type="button" class="btn btn-sm btn-primary-blue" @click="openCreateModal">
                <i class="bi bi-plus-lg me-1"></i> Tambah Jenis Naskah
            </button>
        </div>

        <div class="st-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Urutan</th>
                            <th>Kode & Nama Jenis</th>
                            <th>Workbook Excel</th>
                            <th>Pola Formula Nomor</th>
                            <th>Digit</th>
                            <th>Field Ekstra</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in types" :key="t.id">
                            <td class="fw-bold text-center" style="width: 60px;">{{ t.display_order }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ t.type_name }}</div>
                                <span class="badge bg-light text-dark border font-monospace">{{ t.type_code }}</span>
                            </td>
                            <td>
                                <span class="fw-semibold text-primary">{{ t.workbook_name }}</span>
                            </td>
                            <td>
                                <code class="font-monospace small text-dark">{{ t.number_pattern }}</code>
                            </td>
                            <td class="text-center font-monospace fw-bold">{{ t.sequence_padding }} digit</td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary small">
                                    {{ t.extra_field === 'nd_pengantar' ? 'ND Pengantar' : 'Hasil Pindai' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge" :class="t.is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
                                    {{ t.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" @click="openEditModal(t)">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" @click="deleteType(t.id)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Modal :show="showModal" max-width="lg" @close="showModal = false">
            <template #title>
                {{ isEditing ? 'Ubah Jenis Naskah & Formula' : 'Tambah Jenis Naskah Baru' }}
            </template>

            <form @submit.prevent="submitType">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Kode Jenis</label>
                        <input v-model="form.type_code" type="text" class="form-control text-uppercase font-monospace" placeholder="ND_MEMO" required />
                    </div>

                    <div class="col-md-8">
                        <label class="form-label small fw-bold">Nama Jenis Naskah</label>
                        <input v-model="form.type_name" type="text" class="form-control" placeholder="Contoh: Nota Dinas / Memorandum" required />
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nama Workbook Excel</label>
                        <input v-model="form.workbook_name" type="text" class="form-control" placeholder="NODIN-MEMORANDUM" required />
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Digit Urut</label>
                        <input v-model.number="form.sequence_padding" type="number" min="1" max="8" class="form-control font-monospace" required />
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Urutan Tampil</label>
                        <input v-model.number="form.display_order" type="number" class="form-control" />
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold">Pola Formula Nomor</label>
                        <input v-model="form.number_pattern" type="text" class="form-control font-monospace" required />
                        <small class="text-muted d-block mt-1">
                            Placeholder tersedia: <code>{sequence}</code>, <code>{year}</code>, <code>{month_roman}</code>, <code>{month}</code>, <code>{signer}</code>, <code>{security}</code>, <code>{classification}</code>
                        </small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Field Akhir / Ekstra</label>
                        <select v-model="form.extra_field" class="form-select">
                            <option value="scan_result">Hasil Pindai (Standard)</option>
                            <option value="nd_pengantar">ND Pengantar (Khusus Nodin/Memo)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Kode Penandatangan Default</label>
                        <input v-model="form.default_signer_code" type="text" class="form-control font-monospace" placeholder="1" />
                    </div>
                </div>

                <div class="d-flex gap-4 mb-4">
                    <div class="form-check">
                        <input id="security-acc" v-model="form.uses_security_access" type="checkbox" class="form-check-input" />
                        <label for="security-acc" class="form-check-label small fw-semibold">Gunakan Kode Keamanan ({security})</label>
                    </div>
                    <div class="form-check">
                        <input id="type-active" v-model="form.is_active" type="checkbox" class="form-check-input" />
                        <label for="type-active" class="form-check-label small fw-semibold">Status Aktif</label>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <button type="button" class="btn btn-secondary" @click="showModal = false">Batal</button>
                    <button type="submit" class="btn btn-primary-blue" :disabled="form.processing">
                        Simpan Jenis Naskah
                    </button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
