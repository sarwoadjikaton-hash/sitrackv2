<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import type { LetterNumberType, Unit, LetterNumberAvailabilityBatch } from '@/types';

type NumberDetail = {
    id: number;
    sequence_number: number;
    status: string;
    unit_id: number | null;
    unit?: { id: number; unit_name: string };
    processing_unit_text?: string | null;
    signatory?: string | null;
    destination?: string | null;
    subject?: string | null;
    reserved_for?: string | null;
    letter_date?: string | null;
    used_at?: string | null;
    created_at?: string | null;
};

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

const props = defineProps<{
    year: number;
    types: LetterNumberType[];
    units: Unit[];
    availableSlots: Record<number, number[]>;
    allNumbers: Record<number, NumberDetail[]>;
    batchesByType: Record<number, LetterNumberAvailabilityBatch[]>;
    openTypeId: number;
}>();

const selectedYear = ref(props.year);
const activeTypeId = ref(props.openTypeId || (props.types[0]?.id ?? 0));

const activeType = computed(() => props.types.find((t) => t.id === activeTypeId.value));
const currentBatches = computed(() => props.batchesByType[activeTypeId.value] || []);
const currentAvailableSlots = computed(() => props.availableSlots[activeTypeId.value] || []);
const currentAllNumbers = computed(() => props.allNumbers[activeTypeId.value] || []);

const showDetailModal = ref(false);
const selectedNumber = ref<NumberDetail | null>(null);

// --- Bulk selection ---
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

const isSelected = (id: number) => selectedIds.value.has(id);

const clearSelection = () => {
    selectedIds.value = new Set();
};

const bulkDeleteSelected = () => {
    if (selectedIds.value.size === 0) return;
    if (!confirm(`Hapus ${selectedIds.value.size} nomor terpilih secara permanen? Nomor berstatus Terpakai akan dilewati.`)) return;

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
    if (!confirm(`Ubah status ${selectedIds.value.size} nomor terpilih menjadi "${statusLabel(bulkStatusTarget.value)}"?`)) return;

    router.put('/ketersediaan-nomor/number/bulk-status', {
        ids: Array.from(selectedIds.value),
        status: bulkStatusTarget.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            clearSelection();
        },
    });
};

const statusLabel = (status: string) => {
    if (status === 'available') return 'Tersedia';
    if (status === 'reserved') return 'Direservasi';
    if (status === 'preorder') return 'Pre-Order';
    if (status === 'used') return 'Terpakai';
    return status;
};

const statusForm = ref(selectedNumber.value?.status ?? 'available');

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
    router.put(`/ketersediaan-nomor/number/${selectedNumber.value.id}/status`, { status: statusForm.value }, {
        preserveScroll: true,
        onSuccess: () => { showDetailModal.value = false; },
    });
};

const deleteNumber = () => {
    if (!selectedNumber.value) return;
    if (confirm(`Hapus nomor urut ${selectedNumber.value.sequence_number} secara permanen?`)) {
        router.delete(`/ketersediaan-nomor/number/${selectedNumber.value.id}`, {
            preserveScroll: true,
            onSuccess: () => { showDetailModal.value = false; },
        });
    }
};

// Modal State
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
        'reservation_sequence_number',
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

const deleteBatch = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus alokasi batch nomor ini?')) {
        router.delete(`/ketersediaan-nomor/${id}`, {
            preserveScroll: true,
        });
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
                    Manajemen Penomoran
                </span>
                <h2 class="fw-bold mb-1 text-dark">Ketersediaan Nomor Surat</h2>
                <p class="text-muted mb-0 small">
                    Kelola stok ketersediaan nomor naskah dinas, alokasi pre-order, dan reservasi nomor.
                </p>
            </div>

            <!-- Year Filter & Actions -->
            <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm year-filter">
                    <span class="input-group-text bg-white fw-bold">Tahun</span>

                    <select v-model="selectedYear" class="form-select fw-bold year-select" @change="changeYear">
                        <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">
                            {{ y }}
                        </option>
                    </select>
                </div>

                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-primary-blue" @click="openCreateModal('available')">
                        <i class="bi bi-plus-lg me-1"></i> Buat Stok
                    </button>
                    <button type="button" class="btn btn-sm btn-primary-blue dropdown-toggle dropdown-toggle-split"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <a class="dropdown-item py-2 fw-semibold" href="#"
                                @click.prevent="openCreateModal('preorder')">
                                <i class="bi bi-cart-check me-2 text-primary"></i> Pre-Order Nomor
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 fw-semibold" href="#"
                                @click.prevent="openCreateModal('reservation')">
                                <i class="bi bi-bookmark-check me-2 text-warning"></i> Reservasi Nomor
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Workbook Tabs Nav -->
        <div class="st-card p-3 mb-4 bg-white shadow-sm">
            <button ref="dropdownTrigger" type="button"
                class="btn btn-primary-blue fw-bold d-flex align-items-center gap-2" @click="openDropdown">
                <i class="bi bi-journal-text"></i>
                {{ activeType?.workbook_name || 'Pilih Jenis Naskah' }}
                <i class="bi ms-2" :class="dropdownOpen ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </button>

            <Teleport to="body">
                <div v-if="dropdownOpen" class="workbook-dropdown-panel shadow-lg"
                    :style="{ top: dropdownStyle.top, left: dropdownStyle.left, width: dropdownStyle.width }">
                    <div class="workbook-dropdown-search">
                        <i class="bi bi-search text-muted"></i>
                        <input v-model="dropdownSearch" type="text" placeholder="Cari jenis naskah..."
                            class="form-control form-control-sm border-0 shadow-none" @click.stop />
                    </div>

                    <div class="workbook-dropdown-list">
                        <button v-for="type in filteredTypes" :key="type.id" type="button"
                            class="workbook-dropdown-item d-flex align-items-center justify-content-between fw-semibold"
                            :class="{ active: activeTypeId === type.id }" @click="selectWorkbookType(type.id)">
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

        <!-- Active Type Details & Available Slots Badge -->
        <div v-if="activeType" class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="st-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold text-dark mb-0">{{ activeType.type_name }}</h5>
                        <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2">
                            Tahun {{ selectedYear }}
                        </span>
                    </div>

                    <!-- Available Numbers Badges Box -->
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                            <span class="fw-bold small text-dark">
                                <i class="bi bi-check2-circle me-1 text-success"></i>
                                Slot Nomor Masih Tersedia ({{ currentAvailableSlots.length }})
                            </span>

                            <div class="d-flex align-items-center gap-2">
                                <template v-if="!selectionMode">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        @click="toggleSelectionMode">
                                        <i class="bi bi-check2-square me-1"></i> Pilih Nomor
                                    </button>
                                </template>
                                <template v-else>
                                    <span class="small text-muted fw-semibold">{{ selectedCount }} dipilih</span>

                                    <select v-model="bulkStatusTarget" class="form-select form-select-sm"
                                        style="width: auto;">
                                        <option value="available">Tersedia</option>
                                        <option value="reserved">Direservasi</option>
                                        <option value="preorder">Pre-Order</option>
                                        <option value="used">Terpakai</option>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-primary-blue"
                                        :disabled="selectedCount === 0" @click="bulkUpdateStatus">
                                        Ubah Status
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        :disabled="selectedCount === 0" @click="bulkDeleteSelected">
                                        <i class="bi bi-trash me-1"></i> Hapus Terpilih
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        @click="toggleSelectionMode">
                                        Batal
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 small mb-2">
                            <span class="d-flex align-items-center gap-1">
                                <span class="legend-dot bg-white border"></span> Tersedia
                            </span>
                            <span class="d-flex align-items-center gap-1">
                                <span class="legend-dot bg-warning"></span> Direservasi
                            </span>
                            <span class="d-flex align-items-center gap-1">
                                <span class="legend-dot bg-primary"></span> Terpakai
                            </span>
                            <span class="d-flex align-items-center gap-1">
                                <span class="legend-dot bg-info"></span> Pre-Order
                            </span>
                        </div>
                        <div class="d-flex flex-wrap gap-1" style="max-height: 200px; overflow-y: auto;">
                            <button v-for="num in currentAllNumbers" :key="num.id" type="button"
                                class="badge px-2 py-1 font-monospace border-0 position-relative" :class="{
                                    'bg-white text-dark border': num.status === 'available' && !isSelected(num.id),
                                    'bg-warning text-dark': num.status === 'reserved' && !isSelected(num.id),
                                    'bg-info text-white': num.status === 'preorder' && !isSelected(num.id),
                                    'bg-primary text-white': num.status === 'used' && !isSelected(num.id),
                                    'bg-success text-white selected-badge': isSelected(num.id),
                                }" @click="openNumberDetail(num)">
                                <i v-if="isSelected(num.id)" class="bi bi-check-lg me-1"></i>
                                {{ String(num.sequence_number).padStart(activeType.sequence_padding || 4, '0') }}
                            </button>
                            <span v-if="currentAllNumbers.length === 0" class="text-muted small">
                                Belum ada nomor tersedia. Silakan klik tombol <strong>Buat Stok</strong> di atas.
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Action Info -->
            <div class="col-lg-4">
                <div class="st-card h-100 bg-light border-0">
                    <h6 class="fw-bold text-dark mb-2">Panduan Alokasi Nomor</h6>
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-1">
                            <strong>Buat Stok:</strong> Menghasilkan rentang slot nomor baru dengan status
                            <em>Tersedia</em>.
                        </li>
                        <li class="mb-1">
                            <strong>Pre-Order:</strong> Mengalokasikan rentang kontinu nomor tersedia untuk unit kerja.
                        </li>
                        <li class="mb-1">
                            <strong>Reservasi:</strong> Menandai 1 slot nomor khusus untuk tanggal tertentu.
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Batches Table -->
        <div class="st-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-bold text-dark mb-0">Daftar Batch Ketersediaan & Alokasi</h5>
            </div>

            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Keperluan</th>
                            <th>Rentang Nomor</th>
                            <th>Unit / Pemohon</th>
                            <th>Total Slot</th>
                            <th>Status Slot</th>
                            <th>Catatan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="batch in currentBatches" :key="batch.id">
                            <!-- TANGGAL -->
                            <td>
                                <span class="fw-semibold text-dark">
                                    {{
                                        batch.period_month
                                            ? new Date(batch.period_month).toLocaleDateString('id-ID', {
                                                day: '2-digit',
                                                month: '2-digit',
                                                year: 'numeric',
                                            })
                                            : batch.letter_date
                                                ? new Date(batch.letter_date).toLocaleDateString('id-ID')
                                                : '-'
                                    }}
                                </span>
                            </td>

                            <!-- KEPERLUAN -->
                            <td>
                                <span class="badge px-3 py-2 fw-bold rounded-pill" :class="{
                                    'bg-success-subtle text-success': batch.purpose === 'available',
                                    'bg-primary-subtle text-primary': batch.purpose === 'preorder',
                                    'bg-warning-subtle text-warning': batch.purpose === 'reservation',
                                }">
                                    {{
                                        batch.purpose === 'available'
                                            ? 'Tersedia'
                                            : batch.purpose === 'preorder'
                                                ? 'Pre-Order'
                                                : 'Reservasi'
                                    }}
                                </span>
                            </td>

                            <!-- RENTANG NOMOR -->
                            <td>
                                <span class="fw-bold text-dark font-monospace">
                                    {{ String(batch.start_sequence).padStart(activeType?.sequence_padding || 4, '0') }}
                                    <template v-if="batch.start_sequence !== batch.end_sequence">
                                        s/d
                                        {{ String(batch.end_sequence).padStart(activeType?.sequence_padding || 4, '0')
                                        }}
                                    </template>
                                </span>
                            </td>

                            <!-- UNIT / PEMOHON -->
                            <td>
                                <div class="fw-semibold text-dark">
                                    {{ batch.unit?.unit_name || batch.unit_text || '-' }}
                                </div>
                                <small v-if="batch.pic_name" class="text-muted">
                                    PIC: {{ batch.pic_name }}
                                </small>
                            </td>

                            <!-- TOTAL SLOT -->
                            <td>
                                <span class="fw-bold">
                                    {{ batch.slot_total || (batch.end_sequence - batch.start_sequence + 1) }}
                                </span>
                            </td>

                            <!-- STATUS -->
                            <td>
                                <div class="d-flex gap-1 small flex-wrap">
                                    <span class="badge bg-success-subtle text-success" title="Tersedia">
                                        {{ batch.slot_available || 0 }} Tersedia
                                    </span>
                                    <span class="badge bg-warning-subtle text-warning" title="Direservasi">
                                        {{ batch.slot_reserved || 0 }} Reserved
                                    </span>
                                    <span class="badge bg-primary-subtle text-primary" title="Terpakai">
                                        {{ batch.slot_used || 0 }} Terpakai
                                    </span>
                                </div>
                            </td>

                            <!-- CATATAN -->
                            <td>
                                <small class="text-muted">{{ batch.notes || '-' }}</small>
                            </td>

                            <!-- AKSI -->
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger" title="Hapus Batch"
                                    @click="deleteBatch(batch.id)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>

                        <tr v-if="currentBatches.length === 0">
                            <td colspan="8" class="text-center py-4 text-muted">
                                Belum ada data batch ketersediaan nomor untuk jenis naskah ini.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Create / Preorder / Reservation -->
        <Modal :show="showModal" @close="closeModal">
            <template #title>
                <i class="bi me-2" :class="{
                    'bi-plus-circle text-primary': modalPurpose === 'available',
                    'bi-cart-check text-primary': modalPurpose === 'preorder',
                    'bi-bookmark-check text-warning': modalPurpose === 'reservation',
                }"></i>
                {{
                    modalPurpose === 'available'
                        ? 'Buat Stok Nomor Tersedia'
                        : modalPurpose === 'preorder'
                            ? 'Pre-Order Rentang Nomor'
                            : 'Reservasi Nomor Surat'
                }}
            </template>

            <form @submit.prevent="submitBatch">
                <!-- Jenis Naskah & Tahun -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Jenis Naskah</label>
                        <select v-model="form.type_id" class="form-select" required>
                            <option v-for="t in types" :key="t.id" :value="t.id">
                                {{ t.workbook_name }}
                            </option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tahun</label>
                        <input v-model.number="form.number_year" type="number" class="form-control" min="2020"
                            max="2100" required />
                    </div>
                </div>

                <!-- Range Input for Available -->
                <div v-if="modalPurpose === 'available'" class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Tanggal Ketersediaan</label>
                        <input v-model="form.period_date" type="date" class="form-control" required />
                        <small class="text-muted">Nomor hanya tersedia untuk tanggal ini.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Nomor Awal</label>
                        <input v-model.number="form.start_number" type="number" min="1" class="form-control" required />
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Nomor Akhir</label>
                        <input v-model.number="form.end_number" type="number" :min="form.start_number"
                            class="form-control" required />
                    </div>
                </div>

                <!-- Range Input for Pre-Order -->
                <div v-if="modalPurpose === 'preorder'" class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Dari Nomor Tersedia</label>
                        <select v-model.number="form.preorder_start_number" class="form-select" required>
                            <option v-for="num in currentAvailableSlots" :key="num" :value="num">{{ num }}</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Sampai Nomor Tersedia</label>
                        <select v-model.number="form.preorder_end_number" class="form-select" required>
                            <option v-for="num in currentAvailableSlots" :key="num" :value="num">{{ num }}</option>
                        </select>
                    </div>
                </div>

                <!-- Single Slot for Reservation -->
                <div v-if="modalPurpose === 'reservation'" class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Pilih Nomor Tersedia</label>
                        <select v-model.number="form.reservation_sequence_number" class="form-select" required>
                            <option v-for="num in currentAvailableSlots" :key="num" :value="num">{{ num }}</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Rencana Tanggal Surat</label>
                        <input v-model="form.letter_date" type="date" class="form-control" required />
                    </div>
                </div>

                <!-- Unit & PIC for Pre-Order / Reservation -->
                <div v-if="modalPurpose !== 'available'" class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Unit Pengolah</label>
                        <select v-model="form.unit_id" class="form-select">
                            <option :value="null">Pilih Unit (atau ketik manual)</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.unit_name }}</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Nama PIC / Pemesan</label>
                        <input v-model="form.pic_name" type="text" class="form-control"
                            placeholder="Nama staf pemesan" />
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Catatan Keperluan</label>
                    <textarea v-model="form.notes" class="form-control" rows="2"
                        placeholder="Keterangan peruntukan nomor..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2">
                    <button type="button" class="btn btn-secondary" @click="closeModal">Batal</button>
                    <button type="submit" class="btn btn-primary-blue" :disabled="form.processing">
                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                        Simpan Alokasi
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Modal Detail Nomor -->
        <Modal :show="showDetailModal" @close="showDetailModal = false">
            <template #title>Detail Nomor Urut</template>

            <div v-if="selectedNumber" class="small">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Nomor Urut</dt>
                    <dd class="col-7 fw-bold font-monospace">
                        {{ String(selectedNumber.sequence_number).padStart(activeType?.sequence_padding || 4, '0') }}
                    </dd>

                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7">
                        <div class="d-flex gap-2 align-items-center">
                            <select v-model="statusForm" class="form-select form-select-sm">
                                <option value="available">Tersedia</option>
                                <option value="reserved">Direservasi</option>
                                <option value="preorder">Pre-Order</option>
                                <option value="used">Terpakai</option>
                            </select>
                            <button type="button" class="btn btn-sm btn-primary-blue" @click="saveStatus">
                                Simpan
                            </button>
                        </div>
                    </dd>

                    <dt class="col-5 text-muted">Unit / Pemohon</dt>
                    <dd class="col-7">{{ selectedNumber.unit?.unit_name || selectedNumber.processing_unit_text || '-' }}
                    </dd>

                    <dt class="col-5 text-muted">Reserved For</dt>
                    <dd class="col-7">{{ selectedNumber.reserved_for || '-' }}</dd>

                    <dt class="col-5 text-muted">Penandatangan</dt>
                    <dd class="col-7">{{ selectedNumber.signatory || '-' }}</dd>

                    <dt class="col-5 text-muted">Tujuan</dt>
                    <dd class="col-7">{{ selectedNumber.destination || '-' }}</dd>

                    <dt class="col-5 text-muted">Perihal</dt>
                    <dd class="col-7">{{ selectedNumber.subject || '-' }}</dd>

                    <dt class="col-5 text-muted">Tanggal Surat</dt>
                    <dd class="col-7">
                        {{ selectedNumber.letter_date ? new Date(selectedNumber.letter_date).toLocaleDateString('id-ID')
                            : '-'
                        }}
                    </dd>

                    <dt class="col-5 text-muted">Digunakan Pada</dt>
                    <dd class="col-7">
                        {{ selectedNumber.used_at ? new Date(selectedNumber.used_at).toLocaleString('id-ID') : '-' }}
                    </dd>
                </dl>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-3">
                <button type="button" class="btn btn-outline-danger btn-sm" @click="deleteNumber">
                    <i class="bi bi-trash me-1"></i> Hapus Nomor Ini
                </button>
                <div class="d-flex gap-2">
                    <a v-if="selectedNumber" :href="`/data-surat?workbook=${activeTypeId}&search=${selectedNumber.sequence_number}`" class="btn btn-primary-blue btn-sm">
                        <i class="bi bi-pencil-square me-1"></i> Edit Data / Detail Surat
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" @click="showDetailModal = false">Tutup</button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

<style scoped>
.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
}

.year-filter {
    width: auto;
    min-width: 170px;
}

.year-filter .year-select {
    min-width: 100px;
    width: auto;
    padding-right: 2rem;
}

.workbook-dropdown {
    position: relative;
    z-index: 1030;
}

.workbook-dropdown-panel {
    position: absolute;
    z-index: 2000;
    background: #fff;
    border-radius: 0.75rem;
    padding: 0.5rem;
    max-height: 340px;
    display: flex;
    flex-direction: column;
}

.workbook-dropdown-search {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.6rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
}

.workbook-dropdown-search input:focus {
    outline: none;
    box-shadow: none;
}

.workbook-dropdown-list {
    overflow-y: auto;
}

.workbook-dropdown-item {
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    border-radius: 0.5rem;
    padding: 0.65rem 0.85rem;
    margin-bottom: 2px;
    color: #344054;
    transition: background-color 0.15s ease, color 0.15s ease;
}

.workbook-dropdown-item:hover {
    background-color: #f0f4ff;
    color: #1d4ed8;
}

.workbook-dropdown-item.active {
    background-color: #e8efff;
    color: #1d4ed8;
    font-weight: 700;
}

.workbook-dropdown-item:hover {
    background-color: #f0f4ff;
    color: #1d4ed8;
}

.workbook-dropdown-item.active {
    background-color: #e8efff;
    color: #1d4ed8;
    font-weight: 700;
}

.workbook-dropdown-item i {
    font-size: 0.95rem;
}

/* Override supaya card pembungkus dropdown tidak clip menu-nya */
.workbook-dropdown-wrapper {
    overflow: visible !important;
}

.selected-badge {
    box-shadow: 0 0 0 2px #198754;
}
</style>