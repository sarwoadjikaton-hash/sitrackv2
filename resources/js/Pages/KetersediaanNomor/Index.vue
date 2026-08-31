<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import { LetterNumberType, Unit, LetterNumberAvailabilityBatch } from '@/types';

const props = defineProps<{
    year: number;
    types: LetterNumberType[];
    units: Unit[];
    availableSlots: Record<number, number[]>;
    batchesByType: Record<number, LetterNumberAvailabilityBatch[]>;
    openTypeId: number;
}>();

onMounted(() => {
    console.log('Batches:', props.batchesByType);
});

const selectedYear = ref(props.year);
const activeTypeId = ref(props.openTypeId || (props.types[0]?.id ?? 0));

const activeType = computed(() => props.types.find((t) => t.id === activeTypeId.value));
const currentBatches = computed(() => props.batchesByType[activeTypeId.value] || []);
const currentAvailableSlots = computed(() => props.availableSlots[activeTypeId.value] || []);

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
    router.get('/ketersediaan-nomor', { year: selectedYear.value, open_type: activeTypeId.value });
};

const switchTab = (typeId: number) => {
    activeTypeId.value = typeId;
    form.type_id = typeId;
};

const openCreateModal = (purpose: 'available' | 'preorder' | 'reservation') => {
    modalPurpose.value = purpose;
    form.purpose = purpose;
    form.type_id = activeTypeId.value;
    form.number_year = selectedYear.value;
    form.reset('notes', 'pic_name', 'unit_text', 'unit_id');

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

<style scoped>
.year-filter {
    width: auto;
    min-width: 170px;
}

.year-filter .year-select {
    min-width: 100px;
    width: auto;
    padding-right: 2rem;
}
</style>

<template>
    <AppLayout title="Ketersediaan Nomor Surat">

        <Head title="Ketersediaan Nomor Surat" />

        <!-- Header Section -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="text-uppercase fw-bold small text-primary" style="letter-spacing: .08em;">Manajemen
                    Penomoran</span>
                <h2 class="fw-bold mb-1 text-dark">Ketersediaan Nomor Surat</h2>
                <p class="text-muted mb-0 small">Kelola stok ketersediaan nomor naskah dinas, alokasi pre-order, dan
                    reservasi nomor.</p>
            </div>

            <!-- Year Filter & Actions -->
            <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm year-filter">
                    <span class="input-group-text bg-white fw-bold">
                        Tahun
                    </span>

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
        <div class="st-card p-2 mb-4 bg-white overflow-hidden shadow-sm">
            <ul class="nav nav-pills flex-nowrap overflow-auto py-1 px-1 gap-1" style="scrollbar-width: thin;">
                <li v-for="type in types" :key="type.id" class="nav-item">
                    <button type="button" class="nav-link text-nowrap py-2 px-3 fw-bold rounded-3"
                        :class="activeTypeId === type.id ? 'active bg-primary text-white shadow-sm' : 'text-secondary bg-transparent'"
                        @click="switchTab(type.id)">
                        {{ type.workbook_name }}
                    </button>
                </li>
            </ul>
        </div>

        <!-- Active Type Details & Available Slots Badge -->
        <div v-if="activeType" class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="st-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">{{ activeType.type_name }}</h5>
                            <small class="text-muted font-monospace">{{ activeType.number_pattern }}</small>
                        </div>
                        <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2">
                            Tahun {{ selectedYear }}
                        </span>
                    </div>

                    <!-- Available Numbers Badges Box -->
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold small text-dark">
                                <i class="bi bi-check2-circle me-1 text-success"></i> Slot Nomor Masih Tersedia ({{
                                    currentAvailableSlots.length }})
                            </span>
                        </div>
                        <div class="d-flex flex-wrap gap-1" style="max-height: 120px; overflow-y: auto;">
                            <span v-for="num in currentAvailableSlots" :key="num"
                                class="badge bg-white text-dark border px-2 py-1 font-monospace">
                                {{ String(num).padStart(activeType.sequence_padding || 4, '0') }}
                            </span>
                            <span v-if="currentAvailableSlots.length === 0" class="text-muted small">
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
                        <li class="mb-1"><strong>Buat Stok:</strong> Menghasilkan rentang slot nomor baru dengan status
                            <em>Tersedia</em>.
                        </li>
                        <li class="mb-1"><strong>Pre-Order:</strong> Mengalokasikan rentang kontinu nomor tersedia untuk
                            unit kerja.</li>
                        <li class="mb-1"><strong>Reservasi:</strong> Menandai 1 slot nomor khusus untuk tanggal
                            tertentu.</li>
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
                                    year: 'numeric'
                                    })
                                    : (batch.letter_date ? new Date(batch.letter_date).toLocaleDateString('id-ID') :
                                    '-')
                                    }}
                                </span>
                            </td>

                            <!-- KEPERLUAN -->
                            <td>
                                <span class="badge px-3 py-2 fw-bold rounded-pill" :class="{
                                    'bg-success-subtle text-success':
                                        batch.purpose === 'available',

                                    'bg-primary-subtle text-primary':
                                        batch.purpose === 'preorder',

                                    'bg-warning-subtle text-warning':
                                        batch.purpose === 'reservation',
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
                                    {{
                                        String(batch.start_sequence)
                                            .padStart(
                                                activeType?.sequence_padding || 4,
                                                '0'
                                            )
                                    }}

                                    <template v-if="
                                        batch.start_sequence !==
                                        batch.end_sequence
                                    ">
                                        s/d

                                        {{
                                            String(batch.end_sequence)
                                                .padStart(
                                                    activeType?.sequence_padding || 4,
                                                    '0'
                                                )
                                        }}
                                    </template>
                                </span>
                            </td>

                            <!-- UNIT / PEMOHON -->
                            <td>
                                <div class="fw-semibold text-dark">
                                    {{
                                        batch.unit?.unit_name ||
                                        batch.unit_text ||
                                        '-'
                                    }}
                                </div>

                                <small v-if="batch.pic_name" class="text-muted">
                                    PIC: {{ batch.pic_name }}
                                </small>
                            </td>

                            <!-- TOTAL SLOT -->
                            <td>
                                <span class="fw-bold">
                                    {{
                                        batch.slot_total ||
                                        (
                                            batch.end_sequence -
                                            batch.start_sequence +
                                            1
                                        )
                                    }}
                                </span>
                            </td>

                            <!-- STAtus -->
                            <td>
                                <div class="d-flex gap-1 small flex-wrap">

                                    <span class="badge bg-success-subtle text-success" title="Tersedia">
                                        {{ batch.slot_available || 0 }}
                                        Tersedia
                                    </span>

                                    <span class="badge bg-warning-subtle text-warning" title="Direservasi">
                                        {{ batch.slot_reserved || 0 }}
                                        Reserved
                                    </span>

                                    <span class="badge bg-primary-subtle text-primary" title="Terpakai">
                                        {{ batch.slot_used || 0 }}
                                        Terpakai
                                    </span>

                                </div>
                            </td>

                            <!-- CATATAN -->
                            <td>
                                <small class="text-muted">
                                    {{ batch.notes || '-' }}
                                </small>
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
                                Belum ada data batch ketersediaan nomor
                                untuk jenis naskah ini.
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
                        : (modalPurpose === 'preorder' ? 'Pre-Order Rentang Nomor' : 'Reservasi Nomor Surat')
                }}
            </template>

            <form @submit.prevent="submitBatch">
                <!-- Jenis Naskah & Tahun -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">
                            Jenis Naskah
                        </label>
                        <select v-model="form.type_id" class="form-select" required>
                            <option v-for="t in types" :key="t.id" :value="t.id">
                                {{ t.workbook_name }}
                            </option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">
                            Tahun
                        </label>
                        <input v-model.number="form.number_year" type="number" class="form-control" min="2020"
                            max="2100" required />
                    </div>
                </div>

                <!-- Range Input for Available -->
                <div v-if="modalPurpose === 'available'" class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">
                            Tanggal Ketersediaan
                        </label>
                        <input v-model="form.period_date" type="date" class="form-control" required />
                        <small class="text-muted">
                            Nomor hanya tersedia untuk tanggal ini.
                        </small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold">
                            Nomor Awal
                        </label>
                        <input v-model.number="form.start_number" type="number" min="1" class="form-control" required />
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold">
                            Nomor Akhir
                        </label>
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
    </AppLayout>
</template>
