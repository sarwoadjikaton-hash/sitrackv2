<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import MasterTabs from '@/Components/MasterTabs.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { LetterNumberType } from '@/types';

const props = defineProps<{
    typeStats: LetterNumberType[];
    selectedYear: number;
    totalSignature: number;
    totalDisposition: number;
}>();

const year = ref(props.selectedYear);

const yearOptions = [
    { value: 2024, label: 'Tahun 2024' },
    { value: 2025, label: 'Tahun 2025' },
    { value: 2026, label: 'Tahun 2026' },
    { value: 2027, label: 'Tahun 2027' },
    { value: 2028, label: 'Tahun 2028' },
];

const changeYear = () => {
    router.get('/master/rekap', { year: year.value });
};

const exportCsv = (type: 'letters' | 'data_surat') => {
    window.location.href = `/master/rekap/export?type=${type}`;
};
</script>

<template>
    <AppLayout title="Rekapitulasi Master Persuratan">
        <Head title="Rekap Master" />

        <MasterTabs />

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="text-uppercase fw-bold small text-primary">Laporan Eksekutif</span>
                <h2 class="fw-bold mb-1 text-dark">Rekapitulasi Master Persuratan</h2>
                <p class="text-muted mb-0 small">Ringkasan menyeluruh pemakaian penomoran dan lajur dokumen persuratan.</p>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div style="min-width: 130px;">
                    <SearchableSelect
                        v-model="year"
                        :options="yearOptions"
                        :searchable="false"
                        size="sm"
                        @update:model-value="changeYear"
                    />
                </div>

                <button type="button" class="btn btn-sm btn-outline-success" @click="exportCsv('data_surat')">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export Data Surat CSV
                </button>
                <button type="button" class="btn btn-sm btn-primary-blue" @click="exportCsv('letters')">
                    <i class="bi bi-download me-1"></i> Export Semua Surat CSV
                </button>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <StatCard
                    title="Total Surat Tindak Lanjut"
                    :value="totalSignature"
                    icon="bi-pen-fill"
                    subtitle="Lajur Penandatanganan Pimpinan"
                />
            </div>
            <div class="col-md-6">
                <StatCard
                    title="Total Surat Disposisi"
                    :value="totalDisposition"
                    icon="bi-diagram-3-fill"
                    subtitle="Lajur Disposisi Pimpinan"
                />
            </div>
        </div>

        <!-- Rekap Table -->
        <div class="st-card p-0 overflow-hidden">
            <div class="p-3 border-bottom bg-light">
                <h5 class="fw-bold text-dark mb-0">Statistik Stok Penomoran per Jenis Naskah (Tahun {{ year }})</h5>
            </div>

            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Jenis Naskah</th>
                            <th>Workbook Excel</th>
                            <th class="text-center">Total Slot</th>
                            <th class="text-center">Tersedia</th>
                            <th class="text-center">Direservasi</th>
                            <th class="text-center">Digunakan</th>
                            <th>Persentase Terpakai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(t, idx) in typeStats" :key="t.id">
                            <td class="fw-bold text-center">{{ idx + 1 }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ t.type_name }}</div>
                                <code class="small text-muted">{{ t.number_pattern }}</code>
                            </td>
                            <td>
                                <span class="fw-semibold text-primary">{{ t.workbook_name }}</span>
                            </td>
                            <td class="text-center fw-bold">{{ t.total_slots || 0 }}</td>
                            <td class="text-center text-success fw-bold">{{ t.available_slots || 0 }}</td>
                            <td class="text-center text-warning fw-bold">{{ t.reserved_slots || 0 }}</td>
                            <td class="text-center text-primary fw-bold">{{ t.used_slots || 0 }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div
                                            class="progress-bar bg-primary"
                                            :style="{
                                                width: t.total_slots && t.total_slots > 0
                                                    ? `${((t.used_slots || 0) / t.total_slots) * 100}%`
                                                    : '0%'
                                            }"
                                        ></div>
                                    </div>
                                    <small class="fw-bold text-muted" style="min-width: 40px;">
                                        {{ t.total_slots && t.total_slots > 0 ? Math.round(((t.used_slots || 0) / t.total_slots) * 100) : 0 }}%
                                    </small>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
