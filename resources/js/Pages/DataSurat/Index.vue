<script setup lang="ts">
declare function route(name: string, params?: any, absolute?: boolean): string;

import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import { LetterNumberType, Unit, LetterNumber, PaginatedData } from '@/types';
import axios from 'axios';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps<{
    records: PaginatedData<LetterNumber> | LetterNumber[];
    types: LetterNumberType[];
    units: Unit[];
    selectedWorkbookId: number;
    selectedYear: number;
    filters: {
        workbook: number;
        search: string;
        year: number;
        periode?: string;
        sort?: string;
    };
    stats: {
        used: number;
        available: number;
        reserved: number;
    };
    periodeLabel: string;
    isPrintMode: boolean;
}>();

const search = ref(props.filters.search || '');
const currentWorkbookId = ref(props.selectedWorkbookId || 0);
const filterPeriode = ref(props.filters.periode || 'all');
const filterSort = ref(props.filters.sort || 'number_desc');
const filterTanggal = ref(new Date().toISOString().substring(0, 10));
const filterBulan = ref(new Date().getMonth() + 1);

const workbookOptions = computed(() => [
    { value: 0, label: 'Semua Workbook' },
    ...props.types.map((t) => ({ value: t.id, label: t.workbook_name })),
]);

const sortOptions = [
    { value: 'number_desc', label: 'No. Urut (Besar → Kecil)' },
    { value: 'number_asc', label: 'No. Urut (Kecil → Besar)' },
    { value: 'date_desc', label: 'Tanggal Terbaru' },
    { value: 'date_asc', label: 'Tanggal Terlama' },
];

// Modal state
const showEditModal = ref(false);
const showImportModal = ref(false);
const editingId = ref<number | null>(null);
const isEditing = ref(false);

const availableSlots = ref<LetterNumber[]>([]);
const loadingSlots = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    letter_number_id: '' as number | string,
    type_id: props.types[0]?.id || 1,
    incoming_date: new Date().toISOString().substring(0, 10),
    letter_date: new Date().toISOString().substring(0, 10),
    unit_id: null as number | null,
    processing_unit_text: '',
    signatory: '',
    request_type: '',
    destination: '',
    security_access: 'B',
    classification_code: 'UM.01',
    subject: '',
    technical_officer: '',
    scan_result: '',
    nd_pengantar: '',
    attachment: null as File | null,
});

const importForm = useForm({
    type_id: null as number | null,
    file: null as File | null,
});

const importStatusMessages = [
    'Membaca file Excel...',
    'Memvalidasi setiap baris...',
    'Menyimpan data ke database...',
    'Menghasilkan nomor resi...',
    'Hampir selesai, mohon tunggu...',
];
const importStatusIndex = ref(0);
let importStatusTimer: ReturnType<typeof setInterval> | null = null;

const startImportStatusCycle = () => {
    importStatusIndex.value = 0;
    importStatusTimer = setInterval(() => {
        importStatusIndex.value = (importStatusIndex.value + 1) % importStatusMessages.length;
    }, 4000);
};

const stopImportStatusCycle = () => {
    if (importStatusTimer) clearInterval(importStatusTimer);
    importStatusTimer = null;
};

// LOGIKA DATA UNTUK TABEL (Anti-Crash)
const displayRecords = computed(() => {
    if (!props.records) return [];
    if (Array.isArray(props.records)) return props.records;
    return props.records.data || [];
});

const openImportModal = () => {
    importForm.reset();
    importForm.type_id = currentWorkbookId.value > 0 ? currentWorkbookId.value : (props.types[0]?.id || null);
    showImportModal.value = true;
};

const handleFile = (e: any) => {
    importForm.file = e.target.files[0];
};

const submitImport = () => {
    if (importForm.type_id === null || !importForm.file) {
        alert("Pilih jenis naskah dan file Excel terlebih dahulu.");
        return;
    }
    startImportStatusCycle();
    importForm.transform((data) => ({
        ...data,
        type_id: data.type_id === 0 ? null : data.type_id,
    })).post(route('letters.import.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
        onFinish: () => {
            stopImportStatusCycle();
        },
    });
};

const selectedType = computed(() => props.types.find((t) => t.id === Number(form.type_id)));

const previewNumber = computed(() => {
    if (!selectedType.value || !form.letter_number_id) return '-';
    const slot = availableSlots.value.find((s) => s.id === Number(form.letter_number_id));
    if (!slot) return '-';

    const padding = selectedType.value.sequence_padding || 4;
    const seq = String(slot.sequence_number).padStart(padding, '0');
    const year = slot.number_year || new Date().getFullYear();
    const dateObj = new Date(form.letter_date);
    const monthNum = isNaN(dateObj.getTime()) ? new Date().getMonth() + 1 : dateObj.getMonth() + 1;
    const romanMonthsList = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
    const monthRoman = romanMonthsList[monthNum] || 'I';

    return selectedType.value.number_pattern
        .replace('{sequence}', seq)
        .replace('{year}', String(year))
        .replace('{month_roman}', monthRoman)
        .replace('{month}', String(monthNum).padStart(2, '0'))
        .replace('{signer}', selectedType.value.default_signer_code || '1')
        .replace('{security}', form.security_access || '')
        .replace('{classification}', form.classification_code || '')
        .replace(/\{[a-zA-Z0-9_]+\}/g, '')
        .replace(/^-+/, '').replace(/\/+/g, '/');
});

const fetchSlots = async (typeId: number) => {
    loadingSlots.value = true;
    try {
        const res = await axios.get('/data-surat/slots', {
            params: { type_id: typeId, year: props.selectedYear },
        });
        if (res.data.ok) availableSlots.value = res.data.items;
    } finally {
        loadingSlots.value = false;
    }
};

watch(() => form.type_id, (val) => { if (!isEditing.value && val) fetchSlots(Number(val)); });

const onUnitSelect = () => {
    const u = props.units.find((unit) => unit.id === Number(form.unit_id));
    if (u) {
        form.processing_unit_text = u.unit_name;
        if (!form.signatory) form.signatory = u.pic_name || '';
    }
};

const applyFilter = () => {
    router.get('/data-surat', {
        workbook: currentWorkbookId.value,
        search: search.value,
        periode: filterPeriode.value,
        tanggal: filterTanggal.value,
        bulan: filterBulan.value,
        tahun: props.selectedYear,
        sort: filterSort.value,
    }, { preserveState: true });
};

watch(filterPeriode, () => applyFilter());
watch(filterSort, () => applyFilter());
watch(currentWorkbookId, () => applyFilter());

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.type_id = currentWorkbookId.value > 0 ? currentWorkbookId.value : (props.types[0]?.id || 1);
    fetchSlots(Number(form.type_id));
    showEditModal.value = true;
};

const openEditModal = (record: LetterNumber) => {
    isEditing.value = true;
    editingId.value = record.id;

    form.letter_number_id = record.id;
    form.type_id = record.type_id ?? record.type?.id ?? props.types[0]?.id ?? 1;
    form.incoming_date = record.incoming_date?.substring(0, 10) || '';
    form.letter_date = record.letter_date?.substring(0, 10) || '';
    form.unit_id = record.unit_id ?? null;
    form.processing_unit_text = record.processing_unit_text || '';
    form.signatory = record.signatory || '';
    form.request_type = record.request_type || '';
    form.destination = record.destination || '';
    form.security_access = record.security_access || 'B';
    form.classification_code = record.classification_code || 'UM.01';
    form.subject = record.subject || '';
    form.technical_officer = record.technical_officer || '';
    form.scan_result = record.scan_result || '';
    form.nd_pengantar = record.nd_pengantar || '';
    form.attachment = null;

    showEditModal.value = true;
};

const closeModal = () => {
    showEditModal.value = false;
    showImportModal.value = false;
    showViewModal.value = false;
};

const submitDataSurat = () => {
    const action = isEditing.value ? `/data-surat/${editingId.value}` : '/data-surat';
    if (isEditing.value) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(action, {
            forceFormData: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(action, {
            forceFormData: true,
            onSuccess: () => closeModal(),
        });
    }
};

const printData = () => {
    window.print();
};

onMounted(() => {
    if (props.isPrintMode) {
        setTimeout(() => { window.print(); }, 1000);
    }
});

const exportExcel = () => {
    window.location.href = route('data-surat.export', {
        workbook: currentWorkbookId.value,
        search: search.value,
        year: props.selectedYear,
    });
};

// ===== Modal View Detail =====
const showViewModal = ref(false);
const viewingRecord = ref<LetterNumber | null>(null);

const romanMonths = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

const toRoman = (dateStr: string | null | undefined) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '-';
    return romanMonths[d.getMonth() + 1] || '-';
};

const formatTanggal = (dateStr: string | null | undefined) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '-';
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
};

const openViewModal = (record: LetterNumber) => {
    viewingRecord.value = record;
    showViewModal.value = true;
};

const closeViewModal = () => {
    showViewModal.value = false;
    viewingRecord.value = null;
};

const switchToEdit = () => {
    if (!viewingRecord.value) return;
    const record = viewingRecord.value;
    closeViewModal();
    openEditModal(record);
};
</script>

<template>
    <AppLayout title="Laporan Data Surat">

        <Head title="Data Surat" />

        <!-- Header Section -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">

            <div>
                <span class="eyebrow-text">BUKU REGISTER SURAT</span>
                <h2 class="fw-bold mb-1 text-dark">Data Surat</h2>
                <p class="text-muted mb-0 small">
                    Catat, kelola, dan pantau seluruh data surat dalam satu tempat.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle"
                        data-bs-toggle="dropdown">
                        <i class="bi bi-printer me-1"></i> Cetak Laporan
                    </button>
                    <ul class="dropdown-menu shadow-sm border-0">
                        <li><a class="dropdown-item py-2" href="#" @click.prevent="printData"><i
                                    class="bi bi-file-earmark-pdf text-danger me-2"></i> Cetak PDF (Semua)</a></li>
                        <li><a class="dropdown-item py-2" href="#" @click.prevent="exportExcel"><i
                                    class="bi bi-file-earmark-excel text-success me-2"></i> Unduh Excel</a></li>
                    </ul>
                </div>
                <button @click="openCreateModal" class="btn btn-sm btn-primary-blue"><i class="bi bi-plus-lg me-1"></i>
                    Tambah Data</button>
                <button @click="openImportModal" class="btn btn-sm btn-outline-success shadow-sm"><i
                        class="bi bi-file-earmark-excel me-1"></i> Import Excel</button>
            </div>
        </div>

        <!-- Print Header -->
        <div class="d-none d-print-block mb-4">
            <div class="text-center border-bottom pb-2">
                <h3 class="fw-bold mb-0">LAPORAN REGISTER SURAT KELUAR & DINAS</h3>
                <h5 class="mb-0 text-uppercase text-secondary small">{{ periodeLabel }}</h5>
            </div>
            <div class="d-flex justify-content-between mt-2 small">
                <span>Workbook: {{ currentWorkbookId === 0 ? 'Semua' : selectedType?.workbook_name }}</span>
                <span>Tahun: {{ selectedYear }} | Cetak: {{ new Date().toLocaleString('id-ID') }}</span>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="st-card p-3 mb-4 no-print shadow-sm">
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
                    <select v-model="filterPeriode" class="form-select">
                        <option value="all">Semua Waktu</option>
                        <option value="hari">Harian</option>
                        <option value="bulan">Bulanan</option>
                    </select>
                </div>
                <div class="col-md-2" v-if="filterPeriode === 'hari'">
                    <input type="date" v-model="filterTanggal" class="form-control" @change="applyFilter">
                </div>
                <div class="col-md-2" v-if="filterPeriode === 'bulan'">
                    <select v-model="filterBulan" class="form-select" @change="applyFilter">
                        <option v-for="m in 12" :key="m" :value="m">Bulan {{ m }}</option>
                    </select>
                </div>
                <div :class="filterPeriode === 'all' ? 'col-md-5' : 'col-md-3'">
                    <label class="form-label small fw-bold">Pencarian</label>
                    <div class="input-group">
                        <input v-model="search" type="text" class="form-control"
                            placeholder="Cari nama, perihal, nomor, atau isi PDF..." @keyup.enter="applyFilter" />
                        <button class="btn btn-primary-blue" @click="applyFilter">Cari</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Banner -->
        <div class="row g-3 mb-4 no-print">
            <div class="col-md-4">
                <StatCard title="Digunakan" :value="stats.used" icon="bi-file-earmark-check-fill" />
            </div>
            <div class="col-md-4">
                <StatCard title="Tersedia" :value="stats.available" icon="bi-check-circle-fill" />
            </div>
            <div class="col-md-4">
                <StatCard title="Reservasi" :value="stats.reserved" icon="bi-bookmark-check-fill" />
            </div>
        </div>

        <!-- Data Table -->
        <div class="st-card p-0 overflow-hidden shadow-sm">
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th class="text-center">No Urut</th>
                            <th>Nomor & Tanggal</th>
                            <th>Unit Pengolah</th>
                            <th>Tujuan</th>
                            <th>Perihal Surat</th>
                            <th>Hasil / ND</th>
                            <th v-if="!isPrintMode" class="text-end no-print">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="record in displayRecords" :key="record.id">
                            <td class="text-center fw-bold">{{
                                String(record.sequence_number).padStart(record.type?.sequence_padding || 4, '0') }}</td>
                            <td>
                                <div class="fw-bold text-primary">{{ record.number_text || '-' }}</div>
                                <small class="text-muted">Tgl: {{ record.letter_date ? new
                                    Date(record.letter_date).toLocaleDateString('id-ID') : '-' }}</small>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ record.processing_unit_text || '-' }}</div>
                                <small class="text-muted">TTD: {{ record.signatory || '-' }}</small>
                            </td>
                            <td>
                                <div>{{ record.destination || '-' }}</div><small class="text-muted">Mohon: {{
                                    record.request_type || '-' }}</small>
                            </td>
                            <td style="max-width: 300px;">
                                <div :class="{ 'text-truncate': !isPrintMode }">{{ record.subject }}</div>
                                <small v-if="record.technical_officer" class="text-muted">Oleh: {{
                                    record.technical_officer }}</small>
                            </td>
                            <td><small>{{ record.scan_result || record.nd_pengantar || '-' }}</small></td>
                            <td v-if="!isPrintMode" class="text-end no-print">
                                <div class="d-inline-flex gap-1">
                                    <button @click="openViewModal(record)"
                                        class="btn btn-sm btn-outline-secondary border-0" title="Lihat Detail">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button @click="openEditModal(record)"
                                        class="btn btn-sm btn-outline-primary border-0" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="displayRecords.length === 0">
                            <td colspan="7" class="text-center py-5 text-muted">Belum ada data surat.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-3 border-top d-flex justify-content-between align-items-center no-print" v-if="!isPrintMode">
                <small class="text-muted">Menampilkan {{ (props.records as any)?.data?.length || 0 }} dari {{
                    (props.records as any)?.total || 0 }} data</small>
                <Pagination v-if="(props.records as any)?.links" :links="(props.records as any).links" />
            </div>
        </div>

        <!-- MODAL VIEW DETAIL -->
        <Modal :show="showViewModal" max-width="lg" @close="closeViewModal">
            <div class="p-4" v-if="viewingRecord">
                <div class="d-flex justify-content-between align-items-start mb-4 pb-3 border-bottom">
                    <div>
                        <span class="text-uppercase fw-bold small text-primary" style="letter-spacing:.06em;">
                            Detail Surat
                        </span>
                        <h5 class="fw-bold mb-0 text-dark">
                            {{ viewingRecord.number_text || '-' }}
                        </h5>
                    </div>
                    <span class="badge bg-primary-subtle text-primary px-3 py-2">
                        No. Urut {{ String(viewingRecord.sequence_number).padStart(viewingRecord.type?.sequence_padding
                            || 4, '0') }}
                    </span>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="detail-item mb-3">
                            <label>Tanggal Masuk</label>
                            <p>{{ formatTanggal(viewingRecord.incoming_date) }}</p>
                        </div>
                        <div class="detail-item mb-3">
                            <label>Unit Pengolah Arsip</label>
                            <p>{{ viewingRecord.processing_unit_text || '-' }}</p>
                        </div>
                        <div class="detail-item mb-3">
                            <label>Penandatangan Surat</label>
                            <p>{{ viewingRecord.signatory || '-' }}</p>
                        </div>
                        <div class="detail-item mb-3">
                            <label>Permohonan</label>
                            <p>{{ viewingRecord.request_type || '-' }}</p>
                        </div>
                        <div class="detail-item mb-3">
                            <label>Tujuan Surat</label>
                            <p>{{ viewingRecord.destination || '-' }}</p>
                        </div>
                        <div class="detail-item mb-0">
                            <label>Tanggal Surat</label>
                            <p>{{ formatTanggal(viewingRecord.letter_date) }}</p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <div class="detail-item">
                                    <label>Keamanan Akses</label>
                                    <p>{{ viewingRecord.security_access || '-' }}</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="detail-item">
                                    <label>Bulan (Romawi)</label>
                                    <p>{{ toRoman(viewingRecord.letter_date) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="detail-item mb-3">
                            <label>Kode Klas. Arsip</label>
                            <p class="font-monospace">{{ viewingRecord.classification_code || '-' }}</p>
                        </div>
                        <div class="detail-item mb-3">
                            <label>Nomor Surat</label>
                            <p class="font-monospace fw-bold text-primary">{{ viewingRecord.number_text || '-' }}</p>
                        </div>
                        <div class="detail-item mb-3">
                            <label>Petugas Unit Teknis</label>
                            <p>{{ viewingRecord.technical_officer || '-' }}</p>
                        </div>
                        <div class="detail-item mb-0">
                            <label>ND Pengantar</label>
                            <p>{{ viewingRecord.nd_pengantar || '-' }}</p>
                        </div>
                    </div>

                    <div class="col-12" v-if="viewingRecord.attachment_path">
                        <div class="detail-item">
                            <label>Lampiran PDF</label>
                            <a :href="`/storage/${viewingRecord.attachment_path}`" target="_blank"
                                class="btn btn-sm btn-outline-primary mt-1">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Buka File PDF
                            </a>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="detail-item">
                            <label>Perihal Surat</label>
                            <p class="mb-0">{{ viewingRecord.subject || '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-4 mt-4 border-top">
                    <button type="button" class="btn btn-secondary" @click="closeViewModal">Tutup</button>
                    <button type="button" class="btn btn-primary-blue" @click="switchToEdit">
                        <i class="bi bi-pencil-square me-1"></i> Edit Data Ini
                    </button>
                </div>
            </div>
        </Modal>

        <!-- MODAL IMPORT -->
        <Modal :show="showImportModal" @close="importForm.processing ? null : closeModal()">
            <div class="p-4">
                <h4 class="fw-bold mb-1">Import Data Massal</h4>
                <p class="text-muted small mb-4">Gunakan format template yang sudah disediakan.</p>

                <template v-if="!importForm.processing">
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Target Jenis Naskah</label>
                        <select v-model="importForm.type_id" class="form-select">
                            <option :value="null" disabled>-- Pilih Jenis Naskah --</option>
                            <option :value="0">Semua Jenis Naskah (deteksi dari kolom Excel)</option>
                            <option v-for="t in types" :key="t.id" :value="t.id">{{ t.workbook_name }}</option>
                        </select>
                        <small class="text-muted">
                            Pilih "Semua Jenis Naskah" kalau file Excel-mu sudah punya kolom JENIS NASKAH per baris.
                        </small>
                    </div>
                    <div class="border-dashed p-5 text-center rounded-4 mb-3" @click="fileInput?.click()"
                        style="cursor:pointer; background: #f8fafc;">
                        <i class="bi bi-cloud-arrow-up display-4 text-primary"></i>
                        <h6 class="mt-2 fw-bold">{{ importForm.file ? importForm.file.name : 'Klik untuk pilih file' }}
                        </h6>
                        <input type="file" ref="fileInput" class="d-none" @change="handleFile" accept=".xlsx, .xls">
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a :href="route('template.dataSurat')"
                            class="text-primary small fw-bold text-decoration-none"><i class="bi bi-download me-1"></i>
                            Template</a>
                        <div class="d-flex gap-2">
                            <button @click="closeModal" class="btn btn-light">Batal</button>
                            <button @click="submitImport" class="btn btn-primary-blue">Mulai Import</button>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="import-progress py-4 text-center">
                        <div class="import-progress-ring mx-auto mb-3"></div>
                        <h6 class="fw-bold text-dark mb-1">{{ importStatusMessages[importStatusIndex] }}</h6>
                        <p class="text-muted small mb-4">
                            Proses ini bisa memakan waktu beberapa menit untuk file besar.<br>Jangan tutup atau refresh
                            halaman ini.
                        </p>
                        <div class="import-progress-bar">
                            <div class="import-progress-bar-fill"></div>
                        </div>
                    </div>
                </template>
            </div>
        </Modal>

        <!-- MODAL EDIT -->
        <Modal :show="showEditModal" max-width="lg" @close="closeModal">
            <div class="p-4">
                <h5 class="fw-bold mb-4">{{ isEditing ? 'Edit Data Surat' : 'Catat Surat Baru' }}</h5>
                <form @submit.prevent="submitDataSurat">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6" v-if="!isEditing">
                            <label class="form-label small fw-bold">Pilih Nomor Urut</label>
                            <select v-model="form.letter_number_id" class="form-select font-monospace fw-bold">
                                <option v-for="slot in availableSlots" :key="slot.id" :value="slot.id">
                                    No. {{ String(slot.sequence_number).padStart(selectedType?.sequence_padding || 4,
                                        '0') }} [{{ slot.status }}]
                                </option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <div class="alert alert-primary py-2 px-3 small font-monospace">Preview: <strong>{{
                                previewNumber }}</strong></div>
                        </div>
                        <!-- Tanggal -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tanggal Masuk</label>
                            <input type="date" v-model="form.incoming_date" class="form-control" required />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tanggal Surat</label>
                            <input type="date" v-model="form.letter_date" class="form-control" required />
                        </div>

                        <!-- Unit Pengolah -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Unit Pengolah</label>
                            <select v-model="form.unit_id" class="form-select" @change="onUnitSelect">
                                <option :value="null">-- Pilih Unit --</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                    {{ unit.unit_name }}
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Penandatangan</label>
                            <input type="text" v-model="form.signatory" class="form-control"
                                placeholder="Nama penandatangan" />
                        </div>

                        <!-- Permohonan & Tujuan -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Jenis Permohonan</label>
                            <input type="text" v-model="form.request_type" class="form-control"
                                placeholder="Jenis permohonan" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tujuan Surat</label>
                            <input type="text" v-model="form.destination" class="form-control"
                                placeholder="Tujuan surat" />
                        </div>

                        <!-- Keamanan & Klasifikasi -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Keamanan Akses</label>
                            <select v-model="form.security_access" class="form-select">
                                <option value="B">B - Biasa</option>
                                <option value="T">T - Terbatas</option>
                                <option value="R">R - Rahasia</option>
                                <option value="SR">SR - Sangat Rahasia</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Kode Klasifikasi Arsip</label>
                            <input type="text" v-model="form.classification_code" class="form-control font-monospace"
                                placeholder="Contoh: UM.01" />
                        </div>

                        <!-- Unit Teknis -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Petugas Unit Teknis</label>
                            <input type="text" v-model="form.technical_officer" class="form-control"
                                placeholder="Nama petugas" />
                        </div>

                        <!-- Hasil / ND -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Hasil Scan</label>
                            <input type="text" v-model="form.scan_result" class="form-control"
                                placeholder="Hasil scan / keterangan" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">ND Pengantar</label>
                            <input type="text" v-model="form.nd_pengantar" class="form-control"
                                placeholder="Nomor ND pengantar" />
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Perihal Surat</label>
                            <textarea v-model="form.subject" class="form-control" rows="2" required></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Lampiran PDF (opsional, isinya bisa dicari)</label>
                            <input type="file" class="form-control" accept=".pdf"
                                @change="(e: any) => form.attachment = e.target.files[0]" />
                            <small class="text-muted">
                                Isi teks di dalam PDF akan otomatis bisa dicari lewat kolom pencarian.
                                <span v-if="isEditing && viewingRecord === null">Kosongkan jika tidak ingin mengganti
                                    file yang sudah ada.</span>
                            </small>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" @click="closeModal" class="btn btn-secondary">Batal</button>
                        <button type="submit" class="btn btn-primary-blue" :disabled="form.processing">Simpan
                            Data</button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>

<style scoped>
.table-modern {
    border-collapse: collapse;
    width: 100%;
}

.table-modern th {
    background: #f8fafc;
    color: #475569;
    font-size: 11px;
    text-transform: uppercase;
    padding: 12px;
    border-bottom: 2px solid #e2e8f0;
}

.table-modern td {
    padding: 12px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: top;
}

.detail-item label {
    display: block;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94a3b8;
    margin-bottom: 0.25rem;
}

.detail-item p {
    font-size: 0.9rem;
    color: #1e293b;
    margin-bottom: 0;
    word-break: break-word;
}

@media print {
    @page {
        size: landscape;
        margin: 10mm;
    }

    .no-print {
        display: none !important;
    }

    .table-modern {
        border: 1px solid black !important;
    }

    .table-modern th,
    .table-modern td {
        border: 1px solid #ccc !important;
        color: black !important;
    }

    .text-truncate {
        overflow: visible !important;
        white-space: normal !important;
    }
}

.border-dashed {
    border: 2px dashed #dee2e6;
    transition: 0.3s;
}

.border-dashed:hover {
    background-color: #eff6ff !important;
    border-color: #2563eb !important;
}

.import-progress-ring {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: 4px solid #e2e8f0;
    border-top-color: #167992;
    animation: importRingSpin 1s linear infinite;
}

@keyframes importRingSpin {
    to {
        transform: rotate(360deg);
    }
}

.import-progress-bar {
    width: 100%;
    height: 6px;
    border-radius: 999px;
    background: #e2e8f0;
    overflow: hidden;
    position: relative;
}

.import-progress-bar-fill {
    position: absolute;
    top: 0;
    left: -40%;
    width: 40%;
    height: 100%;
    border-radius: 999px;
    background: #167992;
    animation: importBarSlide 1.4s ease-in-out infinite;
}

@keyframes importBarSlide {
    0% {
        left: -40%;
    }

    50% {
        left: 60%;
    }

    100% {
        left: 100%;
    }
}
</style>