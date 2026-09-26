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

        <div class="space-y-5 w-full">
            <!-- Header Section (Figma Prototype Model) -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <div>
                    <h1 class="font-display fw-bold fs-4 text-dark mb-1">
                        Ketersediaan Nomor Surat
                    </h1>
                    <p class="text-muted small mb-0">
                        Manajemen stok penomoran 16 jenis naskah dinas 2026
                    </p>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Year Selector -->
                    <div class="d-flex align-items-center bg-white border border-slate-200 rounded-3 px-2 py-1 shadow-xs">
                        <span class="small fw-semibold text-muted me-1">Tahun:</span>
                        <select v-model="selectedYear" class="form-select form-select-sm border-0 fw-bold bg-transparent p-0 shadow-none" style="width: 70px;" @change="changeYear">
                            <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>

                    <!-- Tombol Tambah Batch Nomor -->
                    <button
                        type="button"
                        class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 text-white fw-semibold small shadow-xs border-0 transition"
                        style="background: #2743AF;"
                        @click="openCreateModal('available')"
                    >
                        <i class="bi bi-plus-lg"></i>
                        <span>Tambah Batch Nomor</span>
                    </button>

                    <!-- Tombol Sinkronisasi -->
                    <button
                        type="button"
                        class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 fw-medium small border bg-white text-dark shadow-xs transition hover:bg-light"
                        :disabled="isSyncing"
                        @click="executeSync(false)"
                        title="Sinkronisasi data dari Google Spreadsheet"
                    >
                        <i class="bi" :class="isSyncing ? 'bi-arrow-repeat spin' : 'bi-cloud-download text-primary'"></i>
                        <span>{{ isSyncing ? 'Menyinkronkan...' : 'Sinkronisasi' }}</span>
                    </button>

                    <!-- Tombol Pengaturan -->
                    <div class="dropdown">
                        <button
                            type="button"
                            class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 fw-medium small border bg-white text-dark shadow-xs transition hover:bg-light dropdown-toggle"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            title="Pengaturan dan opsi sinkronisasi"
                        >
                            <i class="bi bi-gear text-secondary"></i>
                            <span>Pengaturan</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border border-slate-200 rounded-3 py-1">
                            <li>
                                <a class="dropdown-item py-2 small fw-semibold d-flex align-items-center gap-2" href="#" @click.prevent="executeSync(false)">
                                    <i class="bi bi-file-earmark-text text-success"></i>
                                    <span>Tarik Lembar Ini ({{ activeType?.workbook_name }})</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 small fw-semibold d-flex align-items-center gap-2" href="#" @click.prevent="executeSync(true)">
                                    <i class="bi bi-collection text-primary"></i>
                                    <span>Tarik Semua Jenis Naskah</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1" /></li>
                            <li>
                                <a class="dropdown-item py-2 small d-flex align-items-center gap-2 text-dark" href="#" @click.prevent="showSyncModal = true">
                                    <i class="bi bi-sliders text-secondary"></i>
                                    <span>Pengaturan Lanjutan</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Workbook Selector & Card (Figma Prototype Model) -->
            <div class="bg-white rounded-4 border border-slate-200 shadow-sm overflow-hidden">
                <!-- Workbook Picker -->
                <div class="p-3 p-sm-4 border-bottom border-slate-100">
                    <label class="d-block small fw-bold text-muted mb-2">Pilih Jenis Naskah (Workbook)</label>
                    <select
                        v-model="activeTypeId"
                        class="form-select border border-slate-200 rounded-3 py-2 px-3 small fw-semibold text-dark shadow-none"
                        @change="switchTab(Number(activeTypeId))"
                    >
                        <option v-for="t in types" :key="t.id" :value="t.id">
                            [{{ t.type_code }}] {{ t.type_name }} — {{ t.workbook_name }}
                        </option>
                    </select>
                </div>

                <!-- Type Info & Stats -->
                <div class="p-3 p-sm-4">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-3 mb-4">
                        <div>
                            <h2 class="font-display fw-bold fs-5 text-dark mb-1">{{ activeType?.type_name }}</h2>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-muted border font-monospace">
                                    {{ activeType?.uses_security_access ? '⚠ Prefiks Keamanan' : 'Tanpa Prefiks' }}
                                </span>
                                <span class="badge bg-light text-muted border">
                                    {{ activeType?.extra_field === 'nd_pengantar' ? 'ND Pengantar' : 'Hasil Pindai' }}
                                </span>
                                <span class="badge bg-light text-muted border font-mono-tracking">
                                    Penanda Tangan: {{ activeType?.default_signer_code || 'Sekjen' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 4 Stat Cards (Figma Model) -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="rounded-3 border p-3" style="background-color: #f0f9ff; border-color: #e0f2fe;">
                                <div class="fs-3 fw-bold font-display" style="color: #0369a1;">{{ stats.available }}</div>
                                <div class="small text-muted mt-1">Tersedia</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rounded-3 border p-3" style="background-color: #f5f3ff; border-color: #ede9fe;">
                                <button type="button" class="w-100 text-start border-0 bg-transparent p-0" @click="openCreateModal('preorder')">
                                    <div class="fs-3 fw-bold font-display" style="color: #6d28d9;">{{ stats.preorder }}</div>
                                    <div class="small text-muted mt-1">Pre-Order</div>
                                </button>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rounded-3 border p-3" style="background-color: #fffbeb; border-color: #fef3c7;">
                                <button type="button" class="w-100 text-start border-0 bg-transparent p-0" @click="openCreateModal('reservation')">
                                    <div class="fs-3 fw-bold font-display" style="color: #b45309;">{{ stats.reserved }}</div>
                                    <div class="small text-muted mt-1">Reservasi</div>
                                </button>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rounded-3 border p-3" style="background-color: #f8fafc; border-color: #e2e8f0;">
                                <div class="fs-3 fw-bold font-display text-muted">{{ stats.used }}</div>
                                <div class="small text-muted mt-1">Terpakai</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Action Buttons -->
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <button
                            type="button"
                            class="d-flex align-items-center gap-1.5 px-3 py-1.5 rounded-3 small fw-medium border"
                            style="color: #6d28d9; background: #f5f3ff; border-color: #ddd6fe;"
                            @click="openCreateModal('preorder')"
                        >
                            <i class="bi bi-plus-lg"></i>
                            Pre-Order Nomor
                        </button>
                        <button
                            type="button"
                            class="d-flex align-items-center gap-1.5 px-3 py-1.5 rounded-3 small fw-medium border"
                            style="color: #b45309; background: #fffbeb; border-color: #fde68a;"
                            @click="openCreateModal('reservation')"
                        >
                            <i class="bi bi-hash"></i>
                            Reservasi Nomor Spesifik
                        </button>
                    </div>

                    <!-- Filter Bar & Search -->
                    <div class="d-flex flex-column flex-md-row gap-3 mb-4">
                        <div class="position-relative flex-grow-1">
                            <i class="bi bi-search position-absolute text-muted" style="left: 12px; top: 50%; transform: translateY(-50%);"></i>
                            <input
                                v-model="searchQuery"
                                type="text"
                                class="form-control rounded-3 py-2 small shadow-none"
                                style="padding-left: 36px;"
                                placeholder="Cari nomor, unit, perihal..."
                            />
                        </div>

                        <!-- Status Filter Buttons (Figma Prototype Model) -->
                        <div class="d-flex flex-wrap gap-1.5 align-items-center">
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-3 small fw-medium transition border"
                                :class="statusFilter === 'all' ? 'text-white border-0' : 'bg-white text-muted border-slate-200'"
                                :style="statusFilter === 'all' ? 'background: #2743AF;' : ''"
                                @click="statusFilter = 'all'"
                            >
                                Semua ({{ stats.total }})
                            </button>
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-3 small fw-medium transition border"
                                :class="statusFilter === 'available' ? 'text-white border-0' : 'bg-white text-muted border-slate-200'"
                                :style="statusFilter === 'available' ? 'background: #2743AF;' : ''"
                                @click="statusFilter = 'available'"
                            >
                                Tersedia ({{ stats.available }})
                            </button>
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-3 small fw-medium transition border"
                                :class="statusFilter === 'preorder' ? 'text-white border-0' : 'bg-white text-muted border-slate-200'"
                                :style="statusFilter === 'preorder' ? 'background: #2743AF;' : ''"
                                @click="statusFilter = 'preorder'"
                            >
                                Pre-Order ({{ stats.preorder }})
                            </button>
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-3 small fw-medium transition border"
                                :class="statusFilter === 'reserved' ? 'text-white border-0' : 'bg-white text-muted border-slate-200'"
                                :style="statusFilter === 'reserved' ? 'background: #2743AF;' : ''"
                                @click="statusFilter = 'reserved'"
                            >
                                Reservasi ({{ stats.reserved }})
                            </button>
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-3 small fw-medium transition border"
                                :class="statusFilter === 'used' ? 'text-white border-0' : 'bg-white text-muted border-slate-200'"
                                :style="statusFilter === 'used' ? 'background: #2743AF;' : ''"
                                @click="statusFilter = 'used'"
                            >
                                Terpakai ({{ stats.used }})
                            </button>

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

        <!-- Modal Pengaturan Lanjutan Link Spreadsheet -->
        <Modal :show="showSyncModal" max-width="md" @close="showSyncModal = false">
            <div class="bg-white rounded-2xl p-4 sm:p-5">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-sliders text-primary fs-5"></i>
                        <h5 class="fw-bold text-dark m-0" style="font-size: 1.05rem;">Pengaturan Lanjutan</h5>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none" @click="showSyncModal = false">
                        <i class="bi bi-x-lg fs-6"></i>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="form-label small fw-semibold text-secondary mb-1">Tautan Google Spreadsheet</label>
                        <input
                            v-model="syncSpreadsheetUrl"
                            type="url"
                            class="form-control rounded-lg"
                            placeholder="https://docs.google.com/spreadsheets/d/.../edit"
                        />
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                            Pastikan spreadsheet telah dibagikan dengan akses lihat (Viewer) atau menggunakan Service Account yang sesuai.
                        </small>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div class="small fw-semibold text-dark mb-1">Opsi Sinkronisasi:</div>
                        <div class="text-muted small" style="font-size: 0.75rem;">
                            Tahun Aktif: <strong class="text-dark">{{ selectedYear }}</strong> &bull; Jenis Naskah: <strong class="text-dark">{{ activeType?.workbook_name || '-' }}</strong>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 pt-3 border-top mt-4">
                    <button type="button" class="btn btn-light border flex-grow-1 text-secondary font-medium" @click="showSyncModal = false">
                        Batal
                    </button>
                    <button
                        type="button"
                        class="btn text-white flex-grow-1 font-medium"
                        style="background-color: #2743AF;"
                        :disabled="isSyncing"
                        @click="executeSync(false)"
                    >
                        <span v-if="isSyncing" class="spinner-border spinner-border-sm me-1" role="status"></span>
                        Simpan &amp; Sinkronkan
                    </button>
                </div>
            </div>
        </Modal>
        </div>
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