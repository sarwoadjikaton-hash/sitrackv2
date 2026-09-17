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

const actionOptions = [
    'Mohon Paraf',
    'Mohon Tanda Tangan',
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
    statusForm.post(`/tindak-lanjut/${activeLetter.value.id}/status`, {
        forceFormData: true,
        onSuccess: () => closeStatusModal(),
    });
};

const deleteLetter = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus surat ini?')) {
        router.delete(`/tindak-lanjut/${id}`);
    }
};
</script>

<template>
    <AppLayout title="Tindak Lanjut / Penandatanganan">

        <Head title="Tindak Lanjut & TTD" />

        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="badge-lane-signature d-inline-flex align-items-center gap-1 mb-2">
                    <i class="bi bi-pen-fill"></i> Lajur Pertama
                </span>
                <h2 class="fw-bold mb-1 text-dark">Tindak Lanjut / Penandatanganan</h2>
                <p class="text-muted mb-0 small">Pemeriksaan administrasi, pengendalian tata naskah, paraf, dan tanda
                    tangan pimpinan.</p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <Link href="/scan-status" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-qr-code-scan me-1"></i> Update via QR
                </Link>
                <Link href="/tindak-lanjut/create" class="btn btn-sm btn-primary-blue shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Catat Naskah Baru
                </Link>
            </div>
        </div>

        <!-- Filter Section (Matches Laporan Data layout) -->
        <div class="st-card p-3 mb-4 shadow-sm">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Filter Workbook</label>
                    <SearchableSelect v-model="currentWorkbookId" :options="workbookOptions"
                        placeholder="Pilih Workbook" />
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Urutkan</label>
                    <SearchableSelect v-model="filterSort" :options="sortOptions" placeholder="Urutkan" />
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Periode</label>
                    <select v-model="filterPeriode" class="form-select" @change="applyFilter">
                        <option value="all">Semua Waktu</option>
                        <option value="hari">Harian</option>
                        <option value="bulan">Bulanan</option>
                    </select>
                </div>
                <div class="col-md-2" v-if="filterPeriode === 'hari'">
                    <label class="form-label small fw-bold">Tanggal</label>
                    <input type="date" v-model="filterTanggal" class="form-control" @change="applyFilter">
                </div>
                <div class="col-md-2" v-if="filterPeriode === 'bulan'">
                    <label class="form-label small fw-bold">Bulan</label>
                    <select v-model="filterBulan" class="form-select" @change="applyFilter">
                        <option v-for="m in 12" :key="m" :value="m">Bulan {{ m }}</option>
                    </select>
                </div>
                <div :class="filterPeriode === 'all' ? 'col-md-5' : 'col-md-3'">
                    <label class="form-label small fw-bold">Pencarian</label>
                    <div class="input-group">
                        <input v-model="search" type="text" class="form-control"
                            placeholder="Cari perihal, nomor, nama pengirim, dll..." @keyup.enter="applyFilter" />
                        <button class="btn btn-primary-blue" @click="applyFilter">Cari</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Status Pills -->
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" class="btn btn-xs rounded-pill px-3 py-1 fw-semibold transition"
                :class="statusFilter === '' ? 'btn-primary shadow-xs' : 'btn-outline-secondary'"
                @click="statusFilter = ''; applyFilter()">
                Semua Status
            </button>
            <button v-for="s in allowedStatuses" :key="s" type="button" class="btn btn-xs rounded-pill px-3 py-1 fw-semibold transition"
                :class="statusFilter === s ? 'btn-primary shadow-xs' : 'btn-outline-secondary'"
                @click="statusFilter = s; applyFilter()">
                {{ s }}
            </button>
        </div>

        <!-- Table -->
        <div class="st-card p-0 overflow-hidden shadow-sm">
            <div class="table-responsive">
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
                                    <span class="badge bg-light text-dark font-monospace border">{{ letter.tracking_code
                                        }}</span>
                                </template>
                            </td>
                            <td>
                                <div class="fw-bold text-dark text-truncate" style="max-width: 260px;"
                                    :title="letter.subject">
                                    {{ letter.subject }}
                                </div>
                                <small class="text-muted d-block">No: {{ letter.letter_number || '(Belum ada nomor)'
                                    }}</small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ letter.sender_unit || letter.sender_name }}</div>
                                <small class="text-muted">&rarr; {{ letter.recipient_unit?.unit_name || letter.destination || 'Tata Usaha'
                                    }}</small>
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

            <div class="p-3 border-top d-flex justify-content-between align-items-center">
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
                    <select v-model="statusForm.status" class="form-select" required>
                        <option v-for="s in allowedStatuses" :key="s" :value="s">{{ s }}</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Posisi Berkas Terkini</label>
                    <input v-model="statusForm.current_position" type="text" class="form-control"
                        placeholder="Contoh: Meja Sekjen / Arsiparis / TU" required />
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
    </AppLayout>
</template>