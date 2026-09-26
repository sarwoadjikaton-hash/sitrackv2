<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import DisposisiTabs from '@/Components/DisposisiTabs.vue';
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
</script>

<template>
    <AppLayout title="Lajur Disposisi Pimpinan">
        <Head title="Lajur Disposisi" />

        <DisposisiTabs />

        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="badge-lane-disposition d-inline-flex align-items-center gap-1 mb-2">
                    <i class="bi bi-diagram-3-fill"></i> Lajur Kedua
                </span>
                <h2 class="fw-bold mb-1 text-dark">Lajur Disposisi</h2>
                <p class="text-muted mb-0 small">Kelola surat yang memerlukan arahan Sekretaris Jenderal, penerusan ke unit, dan tindak lanjut disposisi.</p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <Link href="/disposisi/create" class="btn btn-sm btn-primary-blue">
                    <i class="bi bi-plus-lg me-1"></i> Input Surat Disposisi
                </Link>
            </div>
        </div>

        <!-- Filter Panel -->
        <div class="st-card p-3 mb-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-5">
                    <label class="form-label small fw-bold mb-1">Cari Surat</label>
                    <input
                        v-model="search"
                        type="text"
                        class="form-control"
                        placeholder="Agenda, perihal, nomor surat, pengirim..."
                        @keyup.enter="handleFilter"
                    />
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Sumber Surat</label>
                    <select v-model="sourceFilter" class="form-select" @change="handleFilter">
                        <option value="">Semua Sumber</option>
                        <option value="Manual">Manual</option>
                        <option value="SRIKANDI">SRIKANDI</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Status Lajur</label>
                    <select v-model="statusFilter" class="form-select" @change="handleFilter">
                        <option value="">Semua Status</option>
                        <option v-for="s in allowedStatuses" :key="s" :value="s">{{ s }}</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary-blue w-100 py-2" type="button" @click="handleFilter">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="st-card p-0 overflow-hidden">
            <div class="table-responsive">
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

            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Menampilkan {{ letters.data.length }} dari total {{ letters.total }} dokumen
                </small>
                <Pagination :links="letters.links" />
            </div>
        </div>
    </AppLayout>
</template>
