<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import type { LetterNumberType, Unit, LetterNumberAvailabilityBatch } from '@/types';

type NumberDetail = {
    id: number;
    type_id: number;
    sequence_number: number;
    number_text?: string | null;
    status: string;
    unit_id: number | null;
    unit?: { id: number; unit_name: string };
    processing_unit_text?: string | null;
    signatory?: string | null;
    destination?: string | null;
    subject?: string | null;
    reserved_for?: string | null;
    letter_date?: string | null;
    incoming_date?: string | null;
    used_at?: string | null;
    created_at?: string | null;
    linked_letter_id?: number | null;
    letter?: { id: number; tracking_code: string; agenda_number?: string | null };
};

const props = defineProps<{
    year: number;
    types: LetterNumberType[];
    units: Unit[];
    availableSlots: Record<number, number[]>;
    allNumbers: Record<number, NumberDetail[]>;
    batchesByType: Record<number, LetterNumberAvailabilityBatch[]>;
    openTypeId: number;
    defaultSpreadsheetUrl?: string;
}>();

const selectedYear = ref(props.year);
const activeTypeId = ref(props.openTypeId || (props.types[0]?.id ?? 0));

// --- Workbook Dropdown Navigation ---
const dropdownOpen = ref(false);
const dropdownTrigger = ref<HTMLElement | null>(null);
const dropdownSearch = ref('');
const dropdownStyle = ref<{ top: string; left: string; width: string }>({ top: '0px', left: '0px', width: '280px' });

const filteredTypes = computed(() => {
    if (!dropdownSearch.value.trim()) return props.types;
    const q = dropdownSearch.value.toLowerCase();
    return props.types.filter((t) =>
        t.workbook_name.toLowerCase().includes(q) || t.type_name?.toLowerCase().includes(q)
    );
});

const openDropdown = async () => {
    dropdownOpen.value = !dropdownOpen.value;
    if (dropdownOpen.value) {
        dropdownSearch.value = '';
        await nextTick();
        positionDropdown();
    }
};

const positionDropdown = () => {
    if (!dropdownTrigger.value) return;
    const rect = dropdownTrigger.value.getBoundingClientRect();
    dropdownStyle.value = {
        top: `${rect.bottom + window.scrollY + 6}px`,
        left: `${rect.left + window.scrollX}px`,
        width: `${Math.max(rect.width, 280)}px`,
    };
};

const selectWorkbookType = (typeId: number) => {
    switchTab(typeId);
    dropdownOpen.value = false;
};

const handleClickOutside = (e: MouseEvent) => {
    const target = e.target as HTMLElement;
    if (dropdownTrigger.value && !dropdownTrigger.value.contains(target) && !target.closest('.workbook-dropdown-panel')) {
        dropdownOpen.value = false;
    }
};

const handleScrollResize = () => {
    if (dropdownOpen.value) positionDropdown();
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('resize', handleScrollResize);
    window.addEventListener('scroll', handleScrollResize, true);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('resize', handleScrollResize);
    window.removeEventListener('scroll', handleScrollResize, true);
});

const activeType = computed(() => props.types.find((t) => t.id === activeTypeId.value));
const currentBatches = computed(() => props.batchesByType[activeTypeId.value] || []);
const currentAvailableSlots = computed(() => props.availableSlots[activeTypeId.value] || []);
const currentAllNumbers = computed(() => props.allNumbers[activeTypeId.value] || []);

// --- Filter & Search in Main Table ---
const searchQuery = ref('');
const statusFilter = ref<'all' | 'available' | 'used' | 'preorder' | 'reserved'>('all');
const viewMode = ref<'table' | 'grid'>('table');

const filteredNumbers = computed(() => {
    let list = currentAllNumbers.value;

    if (statusFilter.value !== 'all') {
        list = list.filter((n) => n.status === statusFilter.value);
    }

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter((n) => {
            const seqStr = String(n.sequence_number);
            const padded = seqStr.padStart(activeType.value?.sequence_padding || 4, '0');
            const numText = n.number_text?.toLowerCase() || '';
            const subj = n.subject?.toLowerCase() || '';
            const unit = (n.unit?.unit_name || n.processing_unit_text || '').toLowerCase();
            const dateStr = n.letter_date || '';
            const reserved = n.reserved_for?.toLowerCase() || '';

            return (
                seqStr.includes(q) ||
                padded.includes(q) ||
                numText.includes(q) ||
                subj.includes(q) ||
                unit.includes(q) ||
                dateStr.includes(q) ||
                reserved.includes(q)
            );
        });
    }

    return list;
});

// Statistics
const stats = computed(() => {
    const all = currentAllNumbers.value;
    const total = all.length;
    const available = all.filter((n) => n.status === 'available').length;
    const used = all.filter((n) => n.status === 'used').length;
    const preorder = all.filter((n) => n.status === 'preorder').length;
    const reserved = all.filter((n) => n.status === 'reserved').length;
    return { total, available, used, preorder, reserved };
});

// --- Detail & Status Modal ---
const showDetailModal = ref(false);
const selectedNumber = ref<NumberDetail | null>(null);
const statusForm = ref('available');

const openNumberDetail = (num: NumberDetail) => {
    if (selectionMode.value) {
        toggleSelectNumber(num);
        return;
    }
    selectedNumber.value = num;
    statusForm.value = num.status;
    showDetailModal.value = true;
};

const saveStatus = () => {
    if (!selectedNumber.value) return;
    router.put(
        `/ketersediaan-nomor/number/${selectedNumber.value.id}/status`,
        { status: statusForm.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                showDetailModal.value = false;
            },
        }
    );
};

const deleteNumber = () => {
    if (!selectedNumber.value) return;
    if (confirm(`Hapus nomor urut ${selectedNumber.value.sequence_number} secara permanen?`)) {
        router.delete(`/ketersediaan-nomor/number/${selectedNumber.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showDetailModal.value = false;
            },
        });
    }
};

// --- Bulk Selection ---
const selectionMode = ref(false);
const selectedIds = ref<Set<number>>(new Set());
const bulkStatusTarget = ref('available');

const selectedCount = computed(() => selectedIds.value.size);

const toggleSelectionMode = () => {
    selectionMode.value = !selectionMode.value;
    selectedIds.value = new Set();
};

const toggleSelectNumber = (num: NumberDetail) => {
    const next = new Set(selectedIds.value);
    if (next.has(num.id)) {
        next.delete(num.id);
    } else {
        next.add(num.id);
    }
    selectedIds.value = next;
};

const toggleSelectAll = () => {
    if (selectedIds.value.size === filteredNumbers.value.length) {
        selectedIds.value = new Set();
    } else {
        selectedIds.value = new Set(filteredNumbers.value.map((n) => n.id));
    }
};

const isSelected = (id: number) => selectedIds.value.has(id);

const clearSelection = () => {
    selectedIds.value = new Set();
};

const bulkDeleteSelected = () => {
    if (selectedIds.value.size === 0) return;
    if (
        !confirm(
            `Hapus ${selectedIds.value.size} nomor terpilih secara permanen? Nomor berstatus Terpakai akan dilewati.`
        )
    )
        return;

    router.delete('/ketersediaan-nomor/number/bulk-destroy', {
        data: { ids: Array.from(selectedIds.value) },
        preserveScroll: true,
        onSuccess: () => {
            clearSelection();
        },
    });
};

const bulkUpdateStatus = () => {
    if (selectedIds.value.size === 0) return;
    if (
        !confirm(
            `Ubah status ${selectedIds.value.size} nomor terpilih menjadi "${statusLabel(bulkStatusTarget.value)}"?`
        )
    )
        return;

    router.put(
        '/ketersediaan-nomor/number/bulk-status',
        {
            ids: Array.from(selectedIds.value),
            status: bulkStatusTarget.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                clearSelection();
            },
        }
    );
};

const statusLabel = (status: string) => {
    if (status === 'available') return 'Tersedia';
    if (status === 'reserved') return 'Direservasi';
    if (status === 'preorder') return 'Pre-Order';
    if (status === 'used') return 'Terpakai';
    return status;
};

// --- Google Spreadsheet API Synchronization ---
const showSyncModal = ref(false);
const syncSpreadsheetUrl = ref(
    props.defaultSpreadsheetUrl ||
        'https://docs.google.com/spreadsheets/d/1Qv27GijtAHpEAUu-Vz0YfiLactdUCAt_owmIONpXeyc/edit'
);
const isSyncing = ref(false);

const executeSync = (targetAll: boolean = false) => {
    isSyncing.value = true;
    router.post(
        '/ketersediaan-nomor/sync-spreadsheet',
        {
            spreadsheet_url: syncSpreadsheetUrl.value,
            type_id: targetAll ? null : activeTypeId.value,
            year: selectedYear.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSyncing.value = false;
                showSyncModal.value = false;
            },
        }
    );
};

// --- Manual Create Modal ---
const showModal = ref(false);
const modalPurpose = ref<'available' | 'preorder' | 'reservation'>('available');

const form = useForm({
    type_id: activeTypeId.value,
    number_year: selectedYear.value,
    purpose: 'available',
    start_number: 1,
    end_number: 50,
    preorder_start_number: null as number | null,
    preorder_end_number: null as number | null,
    reservation_sequence_number: null as number | null,
    unit_id: null as number | null,
    unit_text: '',
    pic_name: '',
    period_date: new Date().toISOString().substring(0, 10),
    letter_date: new Date().toISOString().substring(0, 10),
    notes: '',
});

const changeYear = () => {
    clearSelection();
    router.get('/ketersediaan-nomor', { year: selectedYear.value, open_type: activeTypeId.value });
};

const switchTab = (typeId: number) => {
    clearSelection();
    activeTypeId.value = typeId;
    form.type_id = typeId;
};

const openCreateModal = (purpose: 'available' | 'preorder' | 'reservation') => {
    modalPurpose.value = purpose;
    form.purpose = purpose;
    form.type_id = activeTypeId.value;
    form.number_year = selectedYear.value;
    form.reset(
        'notes',
        'pic_name',
        'unit_text',
        'unit_id',
        'start_number',
        'end_number',
        'preorder_start_number',
        'preorder_end_number',
        'reservation_sequence_number'
    );

    if (purpose === 'preorder' && currentAvailableSlots.value.length > 0) {
        form.preorder_start_number = currentAvailableSlots.value[0];
        form.preorder_end_number = currentAvailableSlots.value[0];
    } else if (purpose === 'reservation' && currentAvailableSlots.value.length > 0) {
        form.reservation_sequence_number = currentAvailableSlots.value[0];
    }

    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submitBatch = () => {
    form.post('/ketersediaan-nomor', {
        onSuccess: () => {
            closeModal();
        },
    });
};

const formatDateIndo = (dateStr?: string | null) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <AppLayout title="Ketersediaan Nomor Surat">
        <Head title="Ketersediaan Nomor Surat" />

        <!-- Header Section -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="text-uppercase fw-bold small text-primary" style="letter-spacing: .08em;">
                    Manajemen Penomoran & Ketersediaan
                </span>
                <h2 class="fw-bold mb-1 text-dark">Ketersediaan Nomor Surat</h2>
                <p class="text-muted mb-0 small">
                    Data ketersediaan nomor naskah dinas langsung sinkron dengan Google Spreadsheet.
                </p>
            </div>

            <!-- Year Filter, Sync API & Actions -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Year Selector -->
                <div class="input-group input-group-sm year-filter" style="width: 140px;">
                    <span class="input-group-text bg-white fw-bold">Tahun</span>
                    <select v-model="selectedYear" class="form-select fw-bold year-select" @change="changeYear">
                        <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">
                            {{ y }}
                        </option>
                    </select>
                </div>

                <!-- Google Spreadsheet API Sync Button -->
                <div class="btn-group">
                    <button
                        type="button"
                        class="btn btn-sm btn-success fw-bold d-flex align-items-center gap-2 shadow-sm"
                        :disabled="isSyncing"
                        @click="executeSync(false)"
                        title="Tarik data sheet ini dari Google Spreadsheet"
                    >
                        <i class="bi" :class="isSyncing ? 'bi-arrow-repeat spin' : 'bi-cloud-download'"></i>
                        <span>{{ isSyncing ? 'Menarik Data...' : 'Tarik Spreadsheet' }}</span>
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm btn-success dropdown-toggle dropdown-toggle-split"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        :disabled="isSyncing"
                    >
                        <span class="visually-hidden">Toggle Sync Options</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <a class="dropdown-item py-2 fw-semibold" href="#" @click.prevent="executeSync(false)">
                                <i class="bi bi-file-earmark-text me-2 text-success"></i> Tarik Sheet Ini ({{ activeType?.workbook_name }})
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 fw-semibold" href="#" @click.prevent="executeSync(true)">
                                <i class="bi bi-collection me-2 text-primary"></i> Tarik Semua Jenis Naskah
                            </a>
                        </li>
                        <li><hr class="dropdown-divider" /></li>
                        <li>
                            <a class="dropdown-item py-2" href="#" @click.prevent="showSyncModal = true">
                                <i class="bi bi-gear me-2 text-muted"></i> Pengaturan Link Spreadsheet
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Manual Allocation Dropdown -->
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-primary-blue" @click="openCreateModal('available')">
                        <i class="bi bi-plus-lg me-1"></i> Buat Stok
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary-blue dropdown-toggle dropdown-toggle-split"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <a class="dropdown-item py-2 fw-semibold" href="#" @click.prevent="openCreateModal('preorder')">
                                <i class="bi bi-cart-check me-2 text-primary"></i> Pre-Order Nomor
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 fw-semibold" href="#" @click.prevent="openCreateModal('reservation')">
                                <i class="bi bi-bookmark-check me-2 text-warning"></i> Reservasi Nomor
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Workbook Selector & Quick Stats -->
        <div class="row g-3 mb-4">
            <!-- Workbook Selector -->
            <div class="col-lg-4 col-md-5">
                <div class="st-card p-3 h-100 bg-white shadow-sm d-flex flex-column justify-content-center">
                    <label class="form-label text-muted small fw-bold mb-1 text-uppercase">Pilih Jenis Naskah</label>
                    <button
                        ref="dropdownTrigger"
                        type="button"
                        class="btn btn-outline-primary fw-bold d-flex align-items-center justify-content-between gap-2 text-truncate"
                        @click="openDropdown"
                    >
                        <span class="d-flex align-items-center gap-2 text-truncate">
                            <i class="bi bi-journal-text text-primary"></i>
                            <span class="text-truncate">{{ activeType?.workbook_name || 'Pilih Jenis Naskah' }}</span>
                        </span>
                        <i class="bi" :class="dropdownOpen ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </button>

                    <Teleport to="body">
                        <div
                            v-if="dropdownOpen"
                            class="workbook-dropdown-panel shadow-lg"
                            :style="{ top: dropdownStyle.top, left: dropdownStyle.left, width: dropdownStyle.width }"
                        >
                            <div class="workbook-dropdown-search">
                                <i class="bi bi-search text-muted"></i>
                                <input
                                    v-model="dropdownSearch"
                                    type="text"
                                    placeholder="Cari jenis naskah..."
                                    class="form-control form-control-sm border-0 shadow-none"
                                    @click.stop
                                />
                            </div>

                            <div class="workbook-dropdown-list">
                                <button
                                    v-for="type in filteredTypes"
                                    :key="type.id"
                                    type="button"
                                    class="workbook-dropdown-item d-flex align-items-center justify-content-between fw-semibold"
                                    :class="{ active: activeTypeId === type.id }"
                                    @click="selectWorkbookType(type.id)"
                                >
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="bi bi-file-earmark-text"></i>
                                        {{ type.workbook_name }}
                                    </span>
                                    <i v-if="activeTypeId === type.id" class="bi bi-check-lg text-primary"></i>
                                </button>

                                <div v-if="filteredTypes.length === 0" class="text-center text-muted small py-3">
                                    Tidak ada jenis naskah yang cocok.
                                </div>
                            </div>
                        </div>
                    </Teleport>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="col-lg-8 col-md-7">
                <div class="row g-2 h-100">
                    <div class="col-6 col-sm-3">
                        <div class="st-card p-3 h-100 bg-white border-start border-4 border-primary shadow-sm text-center">
                            <span class="text-muted small fw-bold d-block">TOTAL NOMOR</span>
                            <h4 class="fw-bold text-dark mb-0">{{ stats.total }}</h4>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="st-card p-3 h-100 bg-white border-start border-4 border-success shadow-sm text-center">
                            <span class="text-muted small fw-bold d-block">TERSEDIA</span>
                            <h4 class="fw-bold text-success mb-0">{{ stats.available }}</h4>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="st-card p-3 h-100 bg-white border-start border-4 border-danger shadow-sm text-center">
                            <span class="text-muted small fw-bold d-block">TERPAKAI</span>
                            <h4 class="fw-bold text-danger mb-0">{{ stats.used }}</h4>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="st-card p-3 h-100 bg-white border-start border-4 border-warning shadow-sm text-center">
                            <span class="text-muted small fw-bold d-block">PRE-ORDER/RES.</span>
                            <h4 class="fw-bold text-warning mb-0">{{ stats.preorder + stats.reserved }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN TABLE SECTION: SPREADSHEET VIEW -->
        <div class="st-card shadow-sm mb-4">
            <!-- Table Controls Bar -->
            <div class="p-3 border-bottom bg-light d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <!-- Status Filter Pills -->
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    <button
                        type="button"
                        class="btn btn-sm"
                        :class="statusFilter === 'all' ? 'btn-dark fw-bold' : 'btn-outline-secondary'"
                        @click="statusFilter = 'all'"
                    >
                        Semua ({{ stats.total }})
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm"
                        :class="statusFilter === 'available' ? 'btn-success fw-bold' : 'btn-outline-success'"
                        @click="statusFilter = 'available'"
                    >
                        Tersedia ({{ stats.available }})
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm"
                        :class="statusFilter === 'used' ? 'btn-danger fw-bold' : 'btn-outline-danger'"
                        @click="statusFilter = 'used'"
                    >
                        Terpakai ({{ stats.used }})
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm"
                        :class="statusFilter === 'preorder' ? 'btn-info text-white fw-bold' : 'btn-outline-info'"
                        @click="statusFilter = 'preorder'"
                    >
                        Pre-Order ({{ stats.preorder }})
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm"
                        :class="statusFilter === 'reserved' ? 'btn-warning fw-bold' : 'btn-outline-warning'"
                        @click="statusFilter = 'reserved'"
                    >
                        Reservasi ({{ stats.reserved }})
                    </button>
                </div>

                <!-- Search & View Toggle -->
                <div class="d-flex align-items-center gap-2">
                    <div class="input-group input-group-sm" style="max-width: 280px;">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="form-control"
                            placeholder="Cari no. urut, perihal, unit..."
                        />
                        <button
                            v-if="searchQuery"
                            class="btn btn-outline-secondary"
                            type="button"
                            @click="searchQuery = ''"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- View Switcher -->
                    <div class="btn-group btn-group-sm">
                        <button
                            type="button"
                            class="btn"
                            :class="viewMode === 'table' ? 'btn-primary-blue' : 'btn-outline-secondary'"
                            @click="viewMode = 'table'"
                            title="Tampilan Tabel Spreadsheet"
                        >
                            <i class="bi bi-table"></i>
                        </button>
                        <button
                            type="button"
                            class="btn"
                            :class="viewMode === 'grid' ? 'btn-primary-blue' : 'btn-outline-secondary'"
                            @click="viewMode = 'grid'"
                            title="Tampilan Grid Slot"
                        >
                            <i class="bi bi-grid-3x3-gap"></i>
                        </button>
                    </div>

                    <!-- Bulk Mode Toggle -->
                    <button
                        type="button"
                        class="btn btn-sm"
                        :class="selectionMode ? 'btn-danger' : 'btn-outline-secondary'"
                        @click="toggleSelectionMode"
                    >
                        <i class="bi bi-check2-square me-1"></i>
                        {{ selectionMode ? 'Batal Pilih' : 'Pilih' }}
                    </button>
                </div>
            </div>

            <!-- Bulk Actions Bar (When selecting) -->
            <div v-if="selectionMode" class="p-2 px-3 bg-primary-subtle border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" @click="toggleSelectAll">
                        {{ selectedIds.size === filteredNumbers.length ? 'Batal Pilih Semua' : 'Pilih Semua' }}
                    </button>
                    <span class="fw-bold text-dark small">{{ selectedCount }} nomor dipilih</span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <select v-model="bulkStatusTarget" class="form-select form-select-sm" style="width: auto;">
                        <option value="available">Ubah ke: Tersedia</option>
                        <option value="reserved">Ubah ke: Direservasi</option>
                        <option value="preorder">Ubah ke: Pre-Order</option>
                        <option value="used">Ubah ke: Terpakai</option>
                    </select>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary-blue"
                        :disabled="selectedCount === 0"
                        @click="bulkUpdateStatus"
                    >
                        Terapkan
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        :disabled="selectedCount === 0"
                        @click="bulkDeleteSelected"
                    >
                        <i class="bi bi-trash me-1"></i> Hapus
                    </button>
                </div>
            </div>

            <!-- TABEL 4 KOLOM UTAMA: TANGGAL SURAT, NOMOR URUT, PERIHAL SURAT, UNIT -->
            <div v-if="viewMode === 'table'" class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-modern">
                    <thead class="table-light">
                        <tr>
                            <th v-if="selectionMode" style="width: 40px;" class="text-center">#</th>
                            <th style="width: 140px;">Tanggal Surat</th>
                            <th style="width: 130px;">Nomor Urut</th>
                            <th>Perihal Surat</th>
                            <th style="width: 240px;">Unit</th>
                            <th style="width: 130px;">Status</th>
                            <th style="width: 80px;" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="num in filteredNumbers"
                            :key="num.id"
                            :class="{ 'table-primary': isSelected(num.id) }"
                            style="cursor: pointer;"
                            @click="openNumberDetail(num)"
                        >
                            <!-- Checkbox for Bulk -->
                            <td v-if="selectionMode" class="text-center" @click.stop>
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    :checked="isSelected(num.id)"
                                    @change="toggleSelectNumber(num)"
                                />
                            </td>

                            <!-- 1. TANGGAL SURAT -->
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <i class="bi bi-calendar3 text-muted small"></i>
                                    <span :class="num.letter_date ? 'fw-semibold text-dark' : 'text-muted fst-italic small'">
                                        {{ formatDateIndo(num.letter_date) }}
                                    </span>
                                </div>
                            </td>

                            <!-- 2. NOMOR URUT -->
                            <td>
                                <span class="badge bg-light text-dark border font-monospace fs-6 px-2 py-1">
                                    {{ String(num.sequence_number).padStart(activeType?.sequence_padding || 4, '0') }}
                                </span>
                                <div v-if="num.number_text" class="small text-muted font-monospace text-truncate mt-1" style="max-width: 180px;" :title="num.number_text">
                                    {{ num.number_text }}
                                </div>
                            </td>

                            <!-- 3. PERIHAL SURAT -->
                            <td>
                                <div v-if="num.subject" class="fw-semibold text-dark">
                                    {{ num.subject }}
                                </div>
                                <div v-else-if="num.status === 'reserved'" class="text-warning fw-semibold small">
                                    <i class="bi bi-bookmark-fill me-1"></i>
                                    {{ num.reserved_for ? `Direservasi untuk: ${num.reserved_for}` : 'Slot Direservasi' }}
                                </div>
                                <div v-else-if="num.status === 'preorder'" class="text-info fw-semibold small">
                                    <i class="bi bi-cart-fill me-1"></i>
                                    Alokasi Pre-Order
                                </div>
                                <div v-else class="text-muted small fst-italic">
                                    <i class="bi bi-dash-circle me-1"></i> Slot nomor tersedia belum digunakan
                                </div>
                            </td>

                            <!-- 4. UNIT -->
                            <td>
                                <div v-if="num.unit?.unit_name || num.processing_unit_text" class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 text-wrap text-start">
                                        <i class="bi bi-building me-1"></i>
                                        {{ num.unit?.unit_name || num.processing_unit_text }}
                                    </span>
                                </div>
                                <div v-else class="text-muted small fst-italic">-</div>
                            </td>

                            <!-- STATUS BADGE -->
                            <td>
                                <span
                                    class="badge px-3 py-2 fw-bold rounded-pill text-nowrap"
                                    :class="{
                                        'bg-success text-white': num.status === 'available',
                                        'bg-danger text-white': num.status === 'used',
                                        'bg-info text-white': num.status === 'preorder',
                                        'bg-warning text-dark': num.status === 'reserved',
                                    }"
                                >
                                    {{ statusLabel(num.status) }}
                                </span>
                            </td>

                            <!-- AKSI -->
                            <td class="text-end" @click.stop>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    title="Lihat Detail / Ubah Status"
                                    @click="openNumberDetail(num)"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </td>
                        </tr>

                        <tr v-if="filteredNumbers.length === 0">
                            <td :colspan="selectionMode ? 7 : 6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-muted opacity-50"></i>
                                Tidak ada data nomor surat yang cocok dengan pencarian / filter.
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-success" @click="executeSync(false)">
                                        <i class="bi bi-cloud-download me-1"></i> Tarik Data dari Spreadsheet
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- GRID VIEW (ALTERNATIVE) -->
            <div v-else class="p-4">
                <div class="d-flex flex-wrap gap-1">
                    <button
                        v-for="num in filteredNumbers"
                        :key="num.id"
                        type="button"
                        class="badge px-2 py-2 font-monospace border-0 position-relative"
                        :class="{
                            'bg-success text-white': num.status === 'available' && !isSelected(num.id),
                            'bg-warning text-dark': num.status === 'reserved' && !isSelected(num.id),
                            'bg-info text-white': num.status === 'preorder' && !isSelected(num.id),
                            'bg-danger text-white': num.status === 'used' && !isSelected(num.id),
                            'bg-primary text-white selected-badge': isSelected(num.id),
                        }"
                        @click="openNumberDetail(num)"
                    >
                        <i v-if="isSelected(num.id)" class="bi bi-check-lg me-1"></i>
                        {{ String(num.sequence_number).padStart(activeType?.sequence_padding || 4, '0') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- SPREADSHEET URL SETTINGS MODAL -->
        <Modal :show="showSyncModal" max-width="lg" @close="showSyncModal = false">
            <div class="p-4">
                <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-google text-success me-2"></i> Integrasi Google Spreadsheet
                    </h5>
                    <button type="button" class="btn-close" @click="showSyncModal = false"></button>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark">URL Link / ID Google Spreadsheet</label>
                    <input
                        v-model="syncSpreadsheetUrl"
                        type="text"
                        class="form-control"
                        placeholder="https://docs.google.com/spreadsheets/d/1Qv27GijtAHpEAUu-Vz0YfiLactdUCAt_owmIONpXeyc/edit"
                    />
                    <small class="text-muted d-block mt-1">
                        Pastikan Google Spreadsheet diset ke <strong>"Anyone with the link can view"</strong> (Siapa saja yang memiliki link dapat melihat).
                    </small>
                </div>

                <div class="alert alert-info small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    SiTrack akan otomatis membaca tab/sheet yang namanya cocok dengan Jenis Naskah SiTrack (contoh: <code>NODIN-MEMORANDUM</code>, <code>BIASA-UNDANGAN</code>, <code>KEPUTUSAN</code>, dll) dan memetakan 4 kolom utama: <strong>Tanggal Surat</strong>, <strong>Nomor Urut</strong>, <strong>Perihal Surat</strong>, dan <strong>Unit</strong>.
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2">
                    <button type="button" class="btn btn-secondary" @click="showSyncModal = false">
                        Tutup
                    </button>
                    <button
                        type="button"
                        class="btn btn-success fw-bold d-flex align-items-center gap-2"
                        :disabled="isSyncing || !syncSpreadsheetUrl"
                        @click="executeSync(false)"
                    >
                        <i class="bi" :class="isSyncing ? 'bi-arrow-repeat spin' : 'bi-cloud-download'"></i>
                        <span>{{ isSyncing ? 'Sedang Menarik...' : 'Simpan & Tarik Data Sekarang' }}</span>
                    </button>
                </div>
            </div>
        </Modal>

        <!-- NUMBER DETAIL / STATUS MODAL -->
        <Modal :show="showDetailModal" max-width="md" @close="showDetailModal = false">
            <div v-if="selectedNumber" class="p-4">
                <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                    <h5 class="fw-bold text-dark mb-0">
                        Detail Nomor: {{ String(selectedNumber.sequence_number).padStart(activeType?.sequence_padding || 4, '0') }}
                    </h5>
                    <button type="button" class="btn-close" @click="showDetailModal = false"></button>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">NOMOR SURAT LENGKAP</label>
                    <div class="font-monospace fw-bold fs-6 text-primary">
                        {{ selectedNumber.number_text || '-' }}
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold">TANGGAL SURAT</label>
                        <div class="fw-semibold text-dark">
                            {{ formatDateIndo(selectedNumber.letter_date) }}
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold">STATUS</label>
                        <div>
                            <span class="badge px-3 py-1.5 fw-bold rounded-pill text-nowrap" :class="{
                                'bg-success text-white': selectedNumber.status === 'available',
                                'bg-danger text-white': selectedNumber.status === 'used',
                                'bg-info text-white': selectedNumber.status === 'preorder',
                                'bg-warning text-dark': selectedNumber.status === 'reserved',
                            }">
                                {{ statusLabel(selectedNumber.status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">PERIHAL SURAT</label>
                    <div class="fw-semibold text-dark">
                        {{ selectedNumber.subject || '-' }}
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">UNIT PENGOLAH ARSIP</label>
                    <div class="fw-semibold text-dark">
                        {{ selectedNumber.unit?.unit_name || selectedNumber.processing_unit_text || '-' }}
                    </div>
                </div>

                <div v-if="selectedNumber.signatory" class="mb-3">
                    <label class="form-label text-muted small fw-bold">PENANDATANGAN</label>
                    <div class="fw-semibold text-dark">
                        {{ selectedNumber.signatory }}
                    </div>
                </div>

                <div v-if="selectedNumber.reserved_for" class="mb-3">
                    <label class="form-label text-muted small fw-bold">DIRESERVASI UNTUK</label>
                    <div class="fw-semibold text-warning">
                        {{ selectedNumber.reserved_for }}
                    </div>
                </div>

                <!-- Ubah Status Manual -->
                <div class="pt-3 border-top mb-3">
                    <label class="form-label fw-bold small text-dark">Ubah Status Nomor</label>
                    <select v-model="statusForm" class="form-select form-select-sm">
                        <option value="available">Tersedia (Kosongkan)</option>
                        <option value="reserved">Direservasi</option>
                        <option value="preorder">Pre-Order</option>
                        <option value="used">Terpakai</option>
                    </select>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2">
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="deleteNumber">
                        <i class="bi bi-trash me-1"></i> Hapus Nomor
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-secondary" @click="showDetailModal = false">
                            Tutup
                        </button>
                        <button type="button" class="btn btn-sm btn-primary-blue" @click="saveStatus">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- MODAL MANUAL CREATION (STOK / PREORDER / RESERVASI) -->
        <Modal :show="showModal" max-width="lg" @close="closeModal">
            <form class="p-4" @submit.prevent="submitBatch">
                <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                    <h5 class="fw-bold text-dark mb-0">
                        {{
                            modalPurpose === 'available'
                                ? 'Buat Stok Ketersediaan Nomor'
                                : modalPurpose === 'preorder'
                                    ? 'Alokasi Pre-Order Nomor'
                                    : 'Reservasi Nomor Surat'
                        }}
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Jenis Naskah</label>
                        <select v-model="form.type_id" class="form-select" disabled>
                            <option v-for="t in types" :key="t.id" :value="t.id">
                                {{ t.workbook_name }}
                            </option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Tahun</label>
                        <input v-model="form.number_year" type="number" class="form-control" disabled />
                    </div>

                    <!-- Available Stock Inputs -->
                    <template v-if="modalPurpose === 'available'">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Nomor Awal</label>
                            <input v-model="form.start_number" type="number" class="form-control" min="1" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Nomor Akhir</label>
                            <input v-model="form.end_number" type="number" class="form-control" min="1" required />
                        </div>
                    </template>

                    <!-- Pre-order Inputs -->
                    <template v-if="modalPurpose === 'preorder'">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Nomor Awal (Tersedia)</label>
                            <input v-model="form.preorder_start_number" type="number" class="form-control" min="1" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Nomor Akhir (Tersedia)</label>
                            <input v-model="form.preorder_end_number" type="number" class="form-control" min="1" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Unit Pemohon</label>
                            <select v-model="form.unit_id" class="form-select">
                                <option :value="null">Pilih Unit Kerja...</option>
                                <option v-for="u in units" :key="u.id" :value="u.id">{{ u.unit_name }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Nama PIC / Pemohon</label>
                            <input v-model="form.pic_name" type="text" class="form-control" placeholder="Nama petugas pemohon" />
                        </div>
                    </template>

                    <!-- Reservation Inputs -->
                    <template v-if="modalPurpose === 'reservation'">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Nomor Urut</label>
                            <input v-model="form.reservation_sequence_number" type="number" class="form-control" min="1" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Tanggal Surat / Target</label>
                            <input v-model="form.letter_date" type="date" class="form-control" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Unit Pemohon</label>
                            <select v-model="form.unit_id" class="form-select">
                                <option :value="null">Pilih Unit Kerja...</option>
                                <option v-for="u in units" :key="u.id" :value="u.id">{{ u.unit_name }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Nama PIC / Keterangan Reservasi</label>
                            <input v-model="form.pic_name" type="text" class="form-control" placeholder="Contoh: Booking Bu Ai" />
                        </div>
                    </template>

                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Catatan / Keterangan</label>
                        <textarea v-model="form.notes" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top mt-4">
                    <button type="button" class="btn btn-secondary" @click="closeModal">Batal</button>
                    <button type="submit" class="btn btn-primary-blue" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Alokasi' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>

<style scoped>
.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.workbook-dropdown-panel {
    position: absolute;
    background: #fff;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    z-index: 1060;
    max-height: 380px;
    display: flex;
    flex-direction: column;
}

.workbook-dropdown-search {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-bottom: 1px solid #f1f3f5;
    gap: 8px;
}

.workbook-dropdown-list {
    overflow-y: auto;
    max-height: 300px;
    padding: 4px;
}

.workbook-dropdown-item {
    width: 100%;
    padding: 8px 12px;
    border: none;
    background: transparent;
    border-radius: 6px;
    text-align: left;
    font-size: 0.875rem;
    color: #333;
    transition: background 0.15s ease-in-out;
}

.workbook-dropdown-item:hover {
    background: #f1f5f9;
}

.workbook-dropdown-item.active {
    background: #e0f2fe;
    color: #0369a1;
}

.table-modern thead th {
    background-color: #f8fafc;
    color: #475569;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 700;
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
}

.table-modern tbody td {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.875rem;
}

.selected-badge {
    outline: 2px solid #16a34a;
    outline-offset: 1px;
}
</style>