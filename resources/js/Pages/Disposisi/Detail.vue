<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';
import { Letter, Disposition, LetterStatusLog, LetterRelation, Unit } from '@/types';

const props = defineProps<{
    letter: Letter;
    dispositions: Disposition[];
    statusLogs: LetterStatusLog[];
    relations: LetterRelation[];
    units: Unit[];
    otherLetters: Partial<Letter>[];
    allowedStatuses: string[];
}>();

// Add Instruction Modal
const showInstructionModal = ref(false);
const instructionForm = useForm({
    parent_disposition_id: null as number | null,
    from_name: 'Sekretaris Jenderal',
    to_unit_id: null as number | null,
    to_name: '',
    instruction: '',
    due_date: '',
    is_koordinator: false,
    item_status: 'Didisposisikan',
});

const openAddInstruction = (parentId: number | null = null, fromName: string = 'Sekretaris Jenderal') => {
    instructionForm.parent_disposition_id = parentId;
    instructionForm.from_name = fromName;
    instructionForm.reset('instruction', 'to_name', 'to_unit_id', 'due_date');
    showInstructionModal.value = true;
};

const submitInstruction = () => {
    instructionForm.post(`/disposisi/${props.letter.id}/add-instruction`, {
        onSuccess: () => {
            showInstructionModal.value = false;
        },
    });
};

// Update Disposition Item Modal
const showItemModal = ref(false);
const activeItem = ref<Disposition | null>(null);
const itemForm = useForm({
    status: 'Didisposisikan',
    follow_up_note: '',
});

const openUpdateItem = (item: Disposition) => {
    activeItem.value = item;
    itemForm.status = item.status;
    itemForm.follow_up_note = item.follow_up_note || '';
    showItemModal.value = true;
};

const submitItemUpdate = () => {
    if (!activeItem.value) return;
    itemForm.put(`/disposisi/${props.letter.id}/item/${activeItem.value.id}`, {
        onSuccess: () => {
            showItemModal.value = false;
            activeItem.value = null;
        },
    });
};

// Add Relation Modal
const showRelationModal = ref(false);
const relationForm = useForm({
    source_letter_id: props.letter.id,
    target_letter_id: '' as number | string,
    relation_type: 'TERKAIT_DENGAN',
    notes: '',
});

const openRelationModal = () => {
    relationForm.reset();
    relationForm.source_letter_id = props.letter.id;
    showRelationModal.value = true;
};

const submitRelation = () => {
    relationForm.post('/letter-relations', {
        onSuccess: () => {
            showRelationModal.value = false;
        },
    });
};

const deleteRelation = (id: number) => {
    if (confirm('Hapus relasi surat ini?')) {
        router.delete(`/letter-relations/${id}`);
    }
};
</script>

<template>
    <AppLayout :title="`Detail Disposisi: ${letter.agenda_number || letter.tracking_code}`">
        <Head :title="`Detail Surat: ${letter.agenda_number}`" />

        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="badge-lane-disposition d-inline-flex align-items-center gap-1 mb-2">
                    <i class="bi bi-diagram-3-fill"></i> Lajur Disposisi
                </span>
                <h2 class="fw-bold mb-1 text-dark">{{ letter.subject }}</h2>
                <div class="d-flex align-items-center gap-2 text-muted small">
                    <span>Agenda: <strong class="text-primary">{{ letter.agenda_number || '-' }}</strong></span>
                    <span>&bull;</span>
                    <span>Resi: <strong class="font-monospace text-dark">{{ letter.tracking_code }}</strong></span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <Link
                    :href="`/cetak/disposisi/${letter.id}`"
                    class="btn btn-sm btn-outline-secondary"
                    target="_blank"
                >
                    <i class="bi bi-printer me-1"></i> Cetak Lembar Disposisi
                </Link>
                <button
                    type="button"
                    class="btn btn-sm btn-primary-blue"
                    @click="openAddInstruction(null, 'Sekretaris Jenderal')"
                >
                    <i class="bi bi-plus-lg me-1"></i> Tambah Arahan
                </button>
                <Link href="/disposisi" class="btn btn-sm btn-outline-secondary">
                    Kembali
                </Link>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Document Metadata & Relations -->
            <div class="col-lg-5">
                <!-- Metadata Card -->
                <div class="st-card mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h5 class="fw-bold text-dark mb-0">Informasi Surat Masuk</h5>
                        <StatusBadge :status="letter.status" />
                    </div>

                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted">Nomor Surat Asal</dt>
                        <dd class="col-7 fw-bold text-primary">{{ letter.letter_number || '-' }}</dd>

                        <dt class="col-5 text-muted">Sumber / Pengirim</dt>
                        <dd class="col-7 fw-semibold text-dark">{{ letter.sender_unit || letter.sender_name }}</dd>

                        <dt class="col-5 text-muted">Tanggal Surat</dt>
                        <dd class="col-7">{{ letter.letter_date ? new Date(letter.letter_date).toLocaleDateString('id-ID') : '-' }}</dd>

                        <dt class="col-5 text-muted">Diterima TU</dt>
                        <dd class="col-7">{{ letter.received_date ? new Date(letter.received_date).toLocaleDateString('id-ID') : '-' }}</dd>

                        <dt class="col-5 text-muted">Posisi Terkini</dt>
                        <dd class="col-7 fw-bold text-dark">
                            <i class="bi bi-geo-alt-fill text-primary me-1"></i>{{ letter.current_position }}
                        </dd>

                        <dt class="col-5 text-muted">Prioritas / Keamanan</dt>
                        <dd class="col-7">
                            <span class="badge bg-secondary-subtle text-secondary me-1">{{ letter.priority }}</span>
                            <span class="badge bg-light text-dark border">{{ letter.security_level }}</span>
                        </dd>

                        <dt class="col-5 text-muted">Catatan TU</dt>
                        <dd class="col-7 text-muted">{{ letter.notes || '-' }}</dd>

                        <dt class="col-5 text-muted">Dokumen PDF</dt>
                        <dd class="col-7">
                            <a
                                v-if="letter.attachment_path"
                                :href="`/storage/${letter.attachment_path}`"
                                target="_blank"
                                class="btn btn-xs btn-outline-danger d-inline-flex align-items-center gap-1 py-1 px-2"
                            >
                                <i class="bi bi-file-earmark-pdf"></i> Lihat File
                            </a>
                            <span v-else class="text-muted">Tidak ada lampiran</span>
                        </dd>
                    </dl>
                </div>

                <!-- Letter Relations Card -->
                <div class="st-card">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Relasi Antar Dokumen</h5>
                            <small class="text-muted">Hubungan surat antar lajur</small>
                        </div>
                        <button type="button" class="btn btn-xs btn-outline-blue" @click="openRelationModal">
                            <i class="bi bi-plus-lg me-1"></i> Hubungkan
                        </button>
                    </div>

                    <div v-if="relations.length > 0" class="d-flex flex-column gap-2">
                        <div
                            v-for="rel in relations"
                            :key="rel.id"
                            class="p-2 border rounded-3 bg-light d-flex justify-content-between align-items-center"
                        >
                            <div>
                                <span class="badge bg-primary-subtle text-primary small mb-1">
                                    {{ rel.relation_type }}
                                </span>
                                <div class="fw-semibold small text-dark">
                                    {{ rel.target_letter?.agenda_number || rel.source_letter?.agenda_number }} -
                                    {{ rel.target_letter?.subject || rel.source_letter?.subject }}
                                </div>
                            </div>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger border-0"
                                title="Hapus Relasi"
                                @click="deleteRelation(rel.id)"
                            >
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div v-else class="text-center py-3 text-muted small">
                        Belum ada dokumen yang direlasikan.
                    </div>
                </div>
            </div>

            <!-- Right Column: Multi-Level Dispositions & Timeline Logs -->
            <div class="col-lg-7">
                <!-- Dispositions Tree Card -->
                <div class="st-card mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="bi bi-diagram-3 me-2 text-primary"></i>Pohon Arahan Disposisi
                        </h5>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-blue"
                            @click="openAddInstruction(null, 'Sekretaris Jenderal')"
                        >
                            <i class="bi bi-plus-lg me-1"></i> Arahan Baru
                        </button>
                    </div>

                    <!-- Disposition Items Tree List -->
                    <div class="d-flex flex-column gap-3">
                        <div
                            v-for="disp in dispositions"
                            :key="disp.id"
                            class="p-3 border rounded-3 bg-white shadow-xs"
                        >
                            <!-- Root Item Header -->
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold text-primary">
                                    <i class="bi bi-person-circle me-1"></i>{{ disp.from_name }} &rarr;
                                    <span class="text-dark">{{ disp.to_unit?.unit_name || disp.to_name || 'Unit' }}</span>
                                    <span v-if="disp.is_koordinator" class="badge bg-warning text-dark ms-2">Koordinator</span>
                                </span>
                                <span class="badge" :class="disp.status === 'Selesai' ? 'bg-success' : 'bg-warning text-dark'">
                                    {{ disp.status }}
                                </span>
                            </div>

                            <!-- Root Item Content -->
                            <p class="text-dark fw-medium mb-2 small bg-light p-2 rounded-2 border">
                                "{{ disp.instruction }}"
                            </p>

                            <div class="d-flex align-items-center justify-content-between small text-muted mb-2">
                                <span>Tenggat: <strong class="text-dark">{{ disp.due_date ? new Date(disp.due_date).toLocaleDateString('id-ID') : '-' }}</strong></span>
                                <div class="d-flex gap-1">
                                    <button
                                        type="button"
                                        class="btn btn-xs btn-outline-primary py-1 px-2"
                                        @click="openUpdateItem(disp)"
                                    >
                                        <i class="bi bi-pencil-square me-1"></i> Update Tindak Lanjut
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-xs btn-outline-secondary py-1 px-2"
                                        @click="openAddInstruction(disp.id, disp.to_unit?.unit_name || disp.to_name || 'Pejabat')"
                                    >
                                        <i class="bi bi-arrow-return-right me-1"></i> Teruskan
                                    </button>
                                </div>
                            </div>

                            <div v-if="disp.follow_up_note" class="alert alert-info py-2 px-3 mb-0 small">
                                <strong>Tindak Lanjut Unit:</strong> {{ disp.follow_up_note }}
                            </div>

                            <!-- Sub-Dispositions (Children) -->
                            <div v-if="disp.children && disp.children.length > 0" class="mt-3 ps-4 border-start border-3 border-primary d-flex flex-column gap-2">
                                <div
                                    v-for="sub in disp.children"
                                    :key="sub.id"
                                    class="p-2 border rounded-2 bg-light"
                                >
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold small text-primary">
                                            <i class="bi bi-arrow-return-right me-1"></i>{{ sub.from_name }} &rarr; {{ sub.to_unit?.unit_name || sub.to_name }}
                                        </span>
                                        <span class="badge" :class="sub.status === 'Selesai' ? 'bg-success' : 'bg-warning text-dark'">
                                            {{ sub.status }}
                                        </span>
                                    </div>
                                    <p class="small text-dark mb-1">"{{ sub.instruction }}"</p>
                                    <div class="text-end">
                                        <button
                                            type="button"
                                            class="btn btn-xs btn-outline-primary py-0 px-2"
                                            @click="openUpdateItem(sub)"
                                        >
                                            Update Progres
                                        </button>
                                    </div>
                                    <div v-if="sub.follow_up_note" class="small text-muted mt-1 fst-italic">
                                        Progres: {{ sub.follow_up_note }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="dispositions.length === 0" class="text-center py-4 text-muted">
                            Belum ada arahan disposisi yang dicatat. Klik <strong>Tambah Arahan</strong> di atas.
                        </div>
                    </div>
                </div>

                <!-- Audit Timeline Logs Card -->
                <div class="st-card">
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Perjalanan Dokumen
                    </h5>

                    <div class="timeline ps-3 border-start border-2 border-primary-subtle position-relative">
                        <div
                            v-for="log in statusLogs"
                            :key="log.id"
                            class="mb-3 position-relative ps-3"
                        >
                            <span
                                class="position-absolute start-0 top-0 translate-middle-x rounded-circle bg-primary"
                                style="width: 10px; height: 10px; margin-left: -13px; margin-top: 5px;"
                            ></span>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-dark small">{{ log.status }}</strong>
                                <small class="text-muted">{{ new Date(log.changed_at).toLocaleString('id-ID') }}</small>
                            </div>
                            <div class="small text-primary fw-semibold">{{ log.position }}</div>
                            <p class="small text-muted mb-0">{{ log.note }} &bull; <em>Oleh: {{ log.changed_by }}</em></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Instruction Modal -->
        <Modal :show="showInstructionModal" @close="showInstructionModal = false">
            <template #title>
                Tambah Arahan Disposisi
            </template>

            <form @submit.prevent="submitInstruction">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Dari (Pemberi Disposisi)</label>
                    <input v-model="instructionForm.from_name" type="text" class="form-control" required />
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Diteruskan Kepada (Unit Kerja)</label>
                    <select v-model="instructionForm.to_unit_id" class="form-select">
                        <option :value="null">-- Pilih Unit Kerja --</option>
                        <option v-for="u in units" :key="u.id" :value="u.id">{{ u.unit_name }}</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Pejabat / Penerima (Opsional)</label>
                    <input v-model="instructionForm.to_name" type="text" class="form-control" placeholder="Nama staf/pejabat penerima" />
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Instruksi / Arahan Disposisi</label>
                    <textarea v-model="instructionForm.instruction" class="form-control" rows="3" placeholder="Tuliskan instruksi lengkap..." required></textarea>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-8">
                        <label class="form-label small fw-bold">Batas Waktu (Due Date)</label>
                        <input v-model="instructionForm.due_date" type="date" class="form-control" />
                    </div>
                    <div class="col-4 d-flex align-items-end">
                        <div class="form-check pb-2">
                            <input id="modal-koor" v-model="instructionForm.is_koordinator" type="checkbox" class="form-check-input" />
                            <label for="modal-koor" class="form-check-label small fw-bold">Koordinator</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <button type="button" class="btn btn-secondary" @click="showInstructionModal = false">Batal</button>
                    <button type="submit" class="btn btn-primary-blue" :disabled="instructionForm.processing">
                        Simpan Arahan
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Update Item Status Modal -->
        <Modal :show="showItemModal" @close="showItemModal = false">
            <template #title>
                Update Tindak Lanjut Disposisi
            </template>

            <form @submit.prevent="submitItemUpdate">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Status Tindak Lanjut</label>
                    <select v-model="itemForm.status" class="form-select" required>
                        <option value="Didisposisikan">Didisposisikan</option>
                        <option value="Dalam Tindak Lanjut">Dalam Tindak Lanjut</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Dikembalikan">Dikembalikan</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Catatan Progres Tindak Lanjut</label>
                    <textarea v-model="itemForm.follow_up_note" class="form-control" rows="3" placeholder="Tuliskan perkembangan atau hasil tindak lanjut..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <button type="button" class="btn btn-secondary" @click="showItemModal = false">Batal</button>
                    <button type="submit" class="btn btn-primary-blue" :disabled="itemForm.processing">
                        Perbarui Progres
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Relation Modal -->
        <Modal :show="showRelationModal" @close="showRelationModal = false">
            <template #title>
                Hubungkan dengan Dokumen Lain
            </template>

            <form @submit.prevent="submitRelation">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Pilih Dokumen yang Terkait</label>
                    <select v-model="relationForm.target_letter_id" class="form-select" required>
                        <option value="">-- Pilih Dokumen --</option>
                        <option v-for="ol in otherLetters" :key="ol.id" :value="ol.id">
                            [{{ ol.process_lane === 'disposition' ? 'Disposisi' : 'Tindak Lanjut' }}]
                            {{ ol.agenda_number || ol.tracking_code }} - {{ ol.subject }}
                        </option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Jenis Relasi</label>
                    <select v-model="relationForm.relation_type" class="form-select" required>
                        <option value="TINDAK_LANJUT_DARI">Tindak Lanjut Dari</option>
                        <option value="BALASAN_DARI">Balasan Dari</option>
                        <option value="HASIL_DISPOSISI_DARI">Hasil Disposisi Dari</option>
                        <option value="TERKAIT_DENGAN">Terkait Dengan</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <button type="button" class="btn btn-secondary" @click="showRelationModal = false">Batal</button>
                    <button type="submit" class="btn btn-primary-blue" :disabled="relationForm.processing">
                        Simpan Hubungan
                    </button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
