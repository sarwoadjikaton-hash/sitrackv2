<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Letter, LetterNumberType, PaginatedData } from '@/types';

const props = defineProps<{
    letters: PaginatedData<Letter>;
    types: LetterNumberType[];
    selectedWorkbookId: number;
    filters: {
        workbook: number;
        search: string;
        status: string;
        sort?: string;
        periode?: string;
        tanggal?: string;
        bulan?: number;
        year?: number;
    };
    allowedStatuses: string[];
}>();

const search = ref(props.filters.search || '');
const currentWorkbookId = ref(props.selectedWorkbookId || 0);
const filterSort = ref(props.filters.sort || 'date_desc');
const filterPeriode = ref(props.filters.periode || 'all');
const filterTanggal = ref(props.filters.tanggal || new Date().toISOString().substring(0, 10));
const filterBulan = ref(props.filters.bulan || (new Date().getMonth() + 1));
const statusFilter = ref(props.filters.status || '');

const workbookOptions = computed(() => [
    { value: 0, label: 'Semua Workbook' },
    ...(props.types || []).map((t) => ({ value: t.id, label: t.workbook_name })),
]);

const sortOptions = [
    { value: 'date_desc', label: 'Tanggal Terbaru' },
    { value: 'date_asc', label: 'Tanggal Terlama' },
    { value: 'number_desc', label: 'No. Agenda (Besar → Kecil)' },
    { value: 'number_asc', label: 'No. Agenda (Kecil → Besar)' },
];

const applyFilter = () => {
    router.get('/tindak-lanjut', {
        workbook: currentWorkbookId.value,
        search: search.value,
        status: statusFilter.value,
        sort: filterSort.value,
        periode: filterPeriode.value,
        tanggal: filterPeriode.value === 'hari' ? filterTanggal.value : undefined,
        bulan: filterPeriode.value === 'bulan' ? filterBulan.value : undefined,
    }, { preserveState: true, replace: true });
};

watch([currentWorkbookId, filterSort, filterPeriode], () => {
    applyFilter();
});

// Quick Status Update Modal
const showStatusModal = ref(false);
const activeLetter = ref<Letter | null>(null);

const statusForm = useForm({
    status: '',
    current_position: '',
    requested_actions: [] as string[],
    note: '',
    attachment: null as File | null,
});

const defaultPositionByStatus: Record<string, string> = {
    'Diregistrasi': 'Unit Pengusul',
    'Diterima': 'Tata Usaha Sekjen',
    'Diperiksa Oleh TU Sekjen': 'TU Sekjen',
    'Diperiksa Oleh Kasubag TU Sekjen': 'Kasubag TU Sekjen',
    'Diperiksa Oleh Sekjen': 'Sekretaris Jenderal',
    'Selesai dan Siap Untuk diambil': 'Loket TU Sekjen',
    'Dokumen Sudah diambil': 'Unit Pengolah',
    'Revisi': 'Unit Pengusul (Perlu Revisi)',
    'Ditolak': 'Unit Pengusul (Ditolak)',
};

watch(() => statusForm.status, (newStatus) => {
    if (newStatus && defaultPositionByStatus[newStatus]) {
        statusForm.current_position = defaultPositionByStatus[newStatus];
    }
});

const actionOptions = [
    'Mohon Tanda Tangan',
    'Mohon Paraf',
];

const handleFileUpload = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        statusForm.attachment = target.files[0];
    }
};

const openStatusModal = (letter: Letter) => {
    // Guard tambahan: data dari SRIKANDI tidak boleh diubah progressnya dari sini
    if (letter.letter_source === 'SRIKANDI') return;

    activeLetter.value = letter;
    statusForm.status = letter.status;
    statusForm.current_position = letter.current_position;
    statusForm.requested_actions = letter.requested_actions ? letter.requested_actions.split(', ') : [];
    statusForm.note = '';
    statusForm.attachment = null;
    showStatusModal.value = true;
};

const closeStatusModal = () => {
    showStatusModal.value = false;
    activeLetter.value = null;
    statusForm.attachment = null;
};

const submitStatusUpdate = () => {
    if (!activeLetter.value) return;
    const trackingCode = activeLetter.value.tracking_code;
    const targetStatus = statusForm.status;
    statusForm.post(`/tindak-lanjut/${activeLetter.value.id}/status`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            closeStatusModal();
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    message: `Status naskah ${trackingCode} berhasil diperbarui menjadi "${targetStatus}".`,
                    type: 'success'
                }
            }));
        },
    });
};

const deleteLetter = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus surat ini?')) {
        router.delete(`/tindak-lanjut/${id}`);
    }
};

const statsTotal = computed(() => props.letters.total || props.letters.data.length);
const statsProses = computed(() => props.letters.data.filter(l => l.status !== 'Dokumen Sudah diambil' && l.status !== 'Ditolak').length);
const statsSiap = computed(() => props.letters.data.filter(l => l.status === 'Selesai dan Siap Untuk diambil' || l.status === 'Selesai dan Siap Untuk Diambil').length);
const statsSelesai = computed(() => props.letters.data.filter(l => l.status === 'Dokumen Sudah diambil').length);
</script>

<template>
    <AppLayout title="Data Tindak Lanjut / TTD">
        <Head title="Data Tindak Lanjut & TTD" />

        <div class="space-y-5 w-full">
            <!-- Header (Figma Prototype Model) -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <div>
                    <h1 class="font-display fw-bold fs-4 text-dark mb-1">Data Tindak Lanjut / TTD</h1>
                    <p class="text-muted small mb-0">Lajur penandatanganan naskah dinas oleh pimpinan</p>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <Link href="/scan-status" class="d-flex align-items-center justify-content-center gap-2 px-3 py-2 rounded-3 text-dark fw-medium small border bg-white shadow-xs transition hover:bg-light flex-grow-1 flex-sm-grow-0">
                        <i class="bi bi-qr-code-scan text-primary"></i>
                        <span>Update via QR</span>
                    </Link>
                    <Link href="/tindak-lanjut/create" class="d-flex align-items-center justify-content-center gap-2 px-3 py-2 rounded-3 text-white fw-semibold small border-0 shadow-xs transition flex-grow-1 flex-sm-grow-0" style="background: #2743AF;">
                        <i class="bi bi-plus-lg"></i>
                        <span>Input Naskah Baru</span>
                    </Link>
                </div>
            </div>

            <!-- Mini stats (Figma Prototype Model) -->
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="rounded-3 border px-4 py-3 bg-white shadow-xs">
                        <div class="fs-4 fw-bold font-display text-dark">{{ statsTotal }}</div>
                        <div class="small text-muted mt-0.5">Total</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="rounded-3 border px-4 py-3" style="background-color: #fffbeb; border-color: #fef3c7;">
                        <div class="fs-4 fw-bold font-display" style="color: #b45309;">{{ statsProses }}</div>
                        <div class="small text-muted mt-0.5">Dalam Proses</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="rounded-3 border px-4 py-3" style="background-color: #ecfdf5; border-color: #d1fae5;">
                        <div class="fs-4 fw-bold font-display" style="color: #047857;">{{ statsSiap }}</div>
                        <div class="small text-muted mt-0.5">Siap Diambil</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="rounded-3 border px-4 py-3 bg-white shadow-xs">
                        <div class="fs-4 fw-bold font-display text-muted">{{ statsSelesai }}</div>
                        <div class="small text-muted mt-0.5">Sudah Diambil</div>
                    </div>
                </div>
            </div>

            <!-- Main Filter & Table Card (Figma Prototype Model) -->
            <div class="bg-white rounded-4 border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-3 p-sm-4 border-bottom border-slate-100 d-flex flex-column flex-md-row gap-3">
                    <div class="position-relative flex-grow-1">
                        <i class="bi bi-search position-absolute text-muted" style="left: 12px; top: 50%; transform: translateY(-50%);"></i>
                        <input
                            v-model="search"
                            type="text"
                            class="form-control rounded-3 py-2 small shadow-none"
                            style="padding-left: 36px;"
                            placeholder="Cari perihal, kode tracking, unit pengirim..."
                            @keyup.enter="applyFilter"
                        />
                    </div>
                    <div style="min-width: 180px;">
                        <SearchableSelect
                            v-model="statusFilter"
                            :options="[
                                { value: '', label: 'Semua Status' },
                                ...allowedStatuses.map(s => ({ value: s, label: s }))
                            ]"
                            placeholder="Semua Status"
                            size="sm"
                            @update:model-value="applyFilter"
                        />
                    </div>
                    <div style="min-width: 200px;">
                        <SearchableSelect
                            v-model="currentWorkbookId"
                            :options="[
                                { value: 0, label: 'Semua Jenis Naskah' },
                                ...types.map(t => ({ value: t.id, label: `[${t.type_code}] ${t.type_name}` }))
                            ]"
                            placeholder="Semua Jenis Naskah"
                            size="sm"
                            @update:model-value="applyFilter"
                        />
                    </div>
                </div>

            <!-- Desktop Table View (>= md) -->
            <div class="d-none d-md-block table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Agenda & Resi</th>
                            <th>Perihal & Nomor Surat</th>
                            <th>Pengirim & Tujuan</th>
                            <th>Posisi Berkas</th>
                            <th>Status & Tindakan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="letter in letters.data" :key="letter.id">
                            <td>
                                <template v-if="letter.letter_source === 'SRIKANDI'">
                                    <span class="badge bg-info-subtle text-info fw-bold px-2 py-1">
                                        <i class="bi bi-cloud-check-fill me-1"></i>SRIKANDI
                                    </span>
                                </template>
                                <template v-else>
                                    <div class="fw-bold text-primary">{{ letter.agenda_number || '-' }}</div>
                                    <span class="badge bg-light text-dark font-monospace border">{{ letter.tracking_code }}</span>
                                </template>
                            </td>
                            <td>
                                <div class="fw-bold text-dark text-truncate" style="max-width: 260px;" :title="letter.subject">
                                    {{ letter.subject }}
                                </div>
                                <small class="text-muted d-block">No: {{ letter.letter_number || '(Belum ada nomor)' }}</small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ letter.sender_unit || letter.sender_name }}</div>
                                <small class="text-muted">&rarr; {{ letter.recipient_unit?.unit_name || letter.destination || 'Tata Usaha' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1">
                                    <i class="bi bi-geo-alt me-1"></i>{{ letter.current_position }}
                                </span>
                            </td>
                            <td>
                                <StatusBadge :status="letter.status" class="mb-1 d-inline-block" />
                                <div v-if="letter.requested_actions" class="small text-muted">
                                    <i class="bi bi-check2-square text-primary me-1"></i>{{ letter.requested_actions }}
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button v-if="letter.letter_source !== 'SRIKANDI'" type="button"
                                        class="btn btn-outline-primary" title="Update Status Cepat"
                                        @click="openStatusModal(letter)">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                    <button v-else type="button" class="btn btn-outline-secondary"
                                        title="Progres dikelola di SRIKANDI, tidak dapat diubah di sini" disabled>
                                        <i class="bi bi-lock-fill"></i>
                                    </button>
                                    <Link :href="`/cetak/pendamping/${letter.id}`" class="btn btn-outline-secondary"
                                        title="Cetak Lembar Pendamping" target="_blank">
                                        <i class="bi bi-printer"></i>
                                    </Link>
                                    <Link :href="`/tindak-lanjut/${letter.id}/edit`" class="btn btn-outline-secondary"
                                        title="Ubah Detail">
                                        <i class="bi bi-pencil-square"></i>
                                    </Link>
                                    <button type="button" class="btn btn-outline-danger" title="Hapus Surat"
                                        @click="deleteLetter(letter.id)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="letters.data.length === 0">
                            <td colspan="6" class="text-center py-5 text-muted">
                                Belum ada berkas pada lajur tindak lanjut / penandatanganan sesuai filter yang dipilih.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View (< md) -->
            <div class="d-block d-md-none">
                <div v-for="letter in letters.data" :key="'mob-' + letter.id" class="p-3 border-bottom bg-white">
                    <!-- Top row: Agenda & Status -->
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <div>
                            <template v-if="letter.letter_source === 'SRIKANDI'">
                                <span class="badge bg-info-subtle text-info fw-bold px-2 py-1">
                                    <i class="bi bi-cloud-check-fill me-1"></i>SRIKANDI
                                </span>
                            </template>
                            <template v-else>
                                <div class="fw-bold text-primary fs-6">{{ letter.agenda_number || '-' }}</div>
                                <span class="badge bg-light text-dark font-monospace border mt-0.5">{{ letter.tracking_code }}</span>
                            </template>
                        </div>
                        <StatusBadge :status="letter.status" />
                    </div>

                    <!-- Subject & Letter Number -->
                    <div class="mb-2">
                        <div class="fw-bold text-dark mb-1 leading-snug">{{ letter.subject }}</div>
                        <div class="small text-muted font-monospace">No: {{ letter.letter_number || '(Belum ada nomor)' }}</div>
                    </div>

                    <!-- Meta info box -->
                    <div class="bg-light rounded-3 p-2.5 mb-2.5 small text-secondary">
                        <div class="d-flex align-items-center gap-1.5 mb-1.5 text-truncate">
                            <i class="bi bi-send text-muted flex-shrink-0"></i>
                            <span class="fw-semibold text-dark text-truncate">{{ letter.sender_unit || letter.sender_name }}</span>
                            <span class="text-muted">&rarr;</span>
                            <span class="text-truncate">{{ letter.recipient_unit?.unit_name || letter.destination || 'Tata Usaha' }}</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <div class="d-flex align-items-center gap-1.5">
                                <i class="bi bi-geo-alt text-primary flex-shrink-0"></i>
                                <span class="fw-medium text-dark">{{ letter.current_position }}</span>
                            </div>
                            <div v-if="letter.requested_actions" class="text-truncate text-muted" style="max-width: 180px;">
                                <i class="bi bi-check2-square text-primary me-1"></i>{{ letter.requested_actions }}
                            </div>
                        </div>
                    </div>

                    <!-- Actions Row -->
                    <div class="d-flex align-items-center justify-content-end gap-1.5 flex-wrap">
                        <button v-if="letter.letter_source !== 'SRIKANDI'" type="button"
                            class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 px-2.5 py-1.5 flex-grow-1 justify-content-center"
                            @click="openStatusModal(letter)">
                            <i class="bi bi-arrow-repeat"></i>
                            <span class="small fw-semibold">Update Status</span>
                        </button>
                        <Link :href="`/cetak/pendamping/${letter.id}`" class="btn btn-sm btn-outline-secondary p-1.5 px-2.5" title="Cetak Lembar Pendamping" target="_blank">
                            <i class="bi bi-printer"></i>
                        </Link>
                        <Link :href="`/tindak-lanjut/${letter.id}/edit`" class="btn btn-sm btn-outline-secondary p-1.5 px-2.5" title="Ubah Detail">
                            <i class="bi bi-pencil-square"></i>
                        </Link>
                        <button type="button" class="btn btn-sm btn-outline-danger p-1.5 px-2.5" title="Hapus Surat" @click="deleteLetter(letter.id)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>

                <div v-if="letters.data.length === 0" class="text-center py-5 text-muted p-3">
                    Belum ada berkas pada lajur tindak lanjut / penandatanganan sesuai filter yang dipilih.
                </div>
            </div>

            <!-- Pagination Bar -->
            <div class="p-3 border-top d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 text-center text-sm-start">
                <small class="text-muted">
                    Menampilkan {{ letters.data.length }} dari total {{ letters.total }} dokumen
                </small>
                <Pagination :links="letters.links" />
            </div>
        </div>

        <!-- Quick Status Update Modal -->
        <Modal :show="showStatusModal" @close="closeStatusModal">
            <template #title>
                Update Status: {{ activeLetter?.agenda_number }}
            </template>

            <form @submit.prevent="submitStatusUpdate">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Status Dokumen</label>
                    <SearchableSelect
                        v-model="statusForm.status"
                        :options="allowedStatuses.map(s => ({ value: s, label: s }))"
                        placeholder="Pilih Status..."
                    />
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Posisi Berkas Terkini</label>
                    <input v-model="statusForm.current_position" type="text" class="form-control"
                        placeholder="Contoh: Sekretaris Jenderal / Arsiparis / TU" required />
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Tindakan / Mohon</label>
                    <div class="row g-2">
                        <div v-for="opt in actionOptions" :key="opt" class="col-6">
                            <div class="form-check">
                                <input :id="`act-${opt}`" v-model="statusForm.requested_actions" type="checkbox"
                                    class="form-check-input" :value="opt" />
                                <label :for="`act-${opt}`" class="form-check-label small">
                                    {{ opt }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Unggah / Perbarui Lampiran Naskah</label>
                    <input type="file" class="form-control"
                        accept=".pdf,.docx,.doc,.jpg,.jpeg,.png,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/*"
                        @change="handleFileUpload" />
                    <small class="text-muted">Maksimal 20 MB (Opsional, untuk menambahkan atau memperbarui file lampiran naskah)</small>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Catatan Perubahan</label>
                    <textarea v-model="statusForm.note" class="form-control" rows="2"
                        placeholder="Catatan perpindahan atau instruksi tambahan..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <button type="button" class="btn btn-secondary" @click="closeStatusModal">Batal</button>
                    <button type="submit" class="btn btn-primary-blue" :disabled="statusForm.processing">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </Modal>
        </div>
    </AppLayout>
</template>