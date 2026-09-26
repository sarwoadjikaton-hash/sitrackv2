<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Letter, PaginatedData } from '@/types';

const props = defineProps<{
    letters: PaginatedData<Letter>;
    filters: {
        search: string;
        status: string;
        source: string;
    };
    allowedStatuses: string[];
}>();

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const sourceFilter = ref(props.filters.source || '');

const handleFilter = () => {
    router.get('/disposisi', {
        search: search.value,
        status: statusFilter.value,
        source: sourceFilter.value,
    }, { preserveState: true });
};

const statsTotal = computed(() => props.letters.total || props.letters.data.length);
const statsProses = computed(() => props.letters.data.filter(l => l.status !== 'Dokumen Sudah diambil' && l.status !== 'Selesai').length);
const statsTuntas = computed(() => props.letters.data.filter(l => l.status === 'Dokumen Sudah diambil' || l.status === 'Selesai').length);
</script>

<template>
    <AppLayout title="Lajur Disposisi Pimpinan">
        <Head title="Lajur Disposisi" />

        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="badge-lane-disposition d-inline-flex align-items-center gap-1 mb-2">
                    <i class="bi bi-diagram-3-fill"></i> Lajur Kedua
                </span>
                <h2 class="fw-bold mb-1 text-dark">Lajur Disposisi</h2>
                <p class="text-muted mb-0 small">Arahan disposisi Sekretaris Jenderal kepada unit kerja dan pejabat pelaksana.</p>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <Link href="/disposisi/create" class="btn btn-sm btn-primary-blue shadow-sm flex-grow-1 flex-md-grow-0 d-flex align-items-center justify-content-center">
                    <i class="bi bi-plus-lg me-1"></i> Input Surat Disposisi
                </Link>
            </div>
        </div>

        <!-- Stats Bar (Figma Model) -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-4">
                <div class="bg-white rounded-3 border p-3 shadow-xs">
                    <div class="fs-4 fw-bold text-dark font-display">{{ statsTotal }}</div>
                    <div class="small text-muted">Total Disposisi</div>
                </div>
            </div>
            <div class="col-6 col-sm-4">
                <div class="bg-white rounded-3 border p-3 shadow-xs" style="border-left: 4px solid #f59e0b !important;">
                    <div class="fs-4 fw-bold text-warning font-display">{{ statsProses }}</div>
                    <div class="small text-muted">Dalam Proses</div>
                </div>
            </div>
            <div class="col-6 col-sm-4">
                <div class="bg-white rounded-3 border p-3 shadow-xs" style="border-left: 4px solid #10b981 !important;">
                    <div class="fs-4 fw-bold text-success font-display">{{ statsTuntas }}</div>
                    <div class="small text-muted">Tuntas / Selesai</div>
                </div>
            </div>
        </div>

        <!-- Filter Panel -->
        <div class="st-card p-3 mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label small fw-bold mb-1">Cari Surat</label>
                    <input
                        v-model="search"
                        type="text"
                        class="form-control"
                        placeholder="Agenda, perihal, nomor surat, pengirim..."
                        @keyup.enter="handleFilter"
                    />
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-bold mb-1">Sumber Surat</label>
                    <SearchableSelect
                        v-model="sourceFilter"
                        :options="[
                            { value: '', label: 'Semua Sumber' },
                            { value: 'Manual', label: 'Manual' },
                            { value: 'SRIKANDI', label: 'SRIKANDI' }
                        ]"
                        placeholder="Semua Sumber"
                        size="sm"
                        @update:model-value="handleFilter"
                    />
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-bold mb-1">Status Lajur</label>
                    <SearchableSelect
                        v-model="statusFilter"
                        :options="[
                            { value: '', label: 'Semua Status' },
                            ...allowedStatuses.map(s => ({ value: s, label: s }))
                        ]"
                        placeholder="Semua Status"
                        size="sm"
                        @update:model-value="handleFilter"
                    />
                </div>
                <div class="col-12 col-md-1">
                    <button class="btn btn-primary-blue w-100 py-2 rounded-2 d-flex align-items-center justify-content-center" type="button" @click="handleFilter">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table & Mobile Card View -->
        <div class="st-card p-0 overflow-hidden">
            <!-- Desktop Table View (>= md) -->
            <div class="d-none d-md-block table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Agenda & Kode</th>
                            <th>Perihal & Nomor Surat</th>
                            <th>Pengirim & Sumber</th>
                            <th>Disposisi</th>
                            <th>Status Lajur</th>
                            <th>Posisi Berkas</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="letter in letters.data" :key="letter.id">
                            <td>
                                <div class="fw-bold text-primary">{{ letter.agenda_number || '-' }}</div>
                                <span class="badge bg-light text-dark font-monospace border">{{ letter.tracking_code }}</span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark text-truncate" style="max-width: 250px;" :title="letter.subject">
                                    {{ letter.subject }}
                                </div>
                                <small class="text-muted d-block">No: {{ letter.letter_number || '-' }}</small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ letter.sender_unit || letter.sender_name }}</div>
                                <span
                                    class="badge px-2 py-1 font-monospace"
                                    :class="letter.letter_source === 'SRIKANDI' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary'"
                                >
                                    {{ letter.letter_source }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1">
                                    <i class="bi bi-diagram-2 me-1"></i>{{ letter.dispositions_count || 0 }} Arahan
                                </span>
                            </td>
                            <td>
                                <StatusBadge :status="letter.status" />
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1">
                                    <i class="bi bi-geo-alt me-1 text-primary"></i>{{ letter.current_position }}
                                </span>
                            </td>
                            <td class="text-end">
                                <Link
                                    :href="`/disposisi/${letter.id}`"
                                    class="btn btn-sm btn-outline-blue"
                                >
                                    <i class="bi bi-folder2-open me-1"></i> Detail & Arahan
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="letters.data.length === 0">
                            <td colspan="7" class="text-center py-5 text-muted">
                                Belum ada berkas pada lajur disposisi.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View (< md) -->
            <div class="d-block d-md-none">
                <div v-for="letter in letters.data" :key="'mob-disp-' + letter.id" class="p-3 border-bottom bg-white">
                    <!-- Top: Agenda & Status -->
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <div>
                            <div class="fw-bold text-primary fs-6">{{ letter.agenda_number || '-' }}</div>
                            <span class="badge bg-light text-dark font-monospace border mt-0.5">{{ letter.tracking_code }}</span>
                        </div>
                        <StatusBadge :status="letter.status" />
                    </div>

                    <!-- Subject & No Surat -->
                    <div class="mb-2">
                        <div class="fw-bold text-dark mb-1 leading-snug">{{ letter.subject }}</div>
                        <small class="text-muted d-block font-monospace">No: {{ letter.letter_number || '-' }}</small>
                    </div>

                    <!-- Meta info box -->
                    <div class="bg-light rounded-3 p-2.5 mb-2.5 small text-secondary">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-1.5 flex-wrap">
                            <span class="fw-semibold text-dark text-truncate" style="max-width: 220px;">{{ letter.sender_unit || letter.sender_name }}</span>
                            <span class="badge px-2 py-0.5 font-monospace" :class="letter.letter_source === 'SRIKANDI' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary'">{{ letter.letter_source }}</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1"><i class="bi bi-diagram-2 me-1"></i>{{ letter.dispositions_count || 0 }} Arahan</span>
                            <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-geo-alt me-1 text-primary"></i>{{ letter.current_position }}</span>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div>
                        <Link :href="`/disposisi/${letter.id}`" class="btn btn-sm btn-outline-blue w-100 text-center d-flex align-items-center justify-content-center gap-1.5 py-1.5">
                            <i class="bi bi-folder2-open"></i>
                            <span>Detail &amp; Arahan</span>
                        </Link>
                    </div>
                </div>

                <div v-if="letters.data.length === 0" class="text-center py-5 text-muted p-3">
                    Belum ada berkas pada lajur disposisi.
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
    </AppLayout>
</template>
