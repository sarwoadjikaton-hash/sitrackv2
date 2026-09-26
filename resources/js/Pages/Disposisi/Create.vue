<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { LetterCategory, Unit } from '@/types';

const props = defineProps<{
    categories: LetterCategory[];
    units: Unit[];
    allowedStatuses: string[];
}>();

const letterSourceOptions = [
    { value: 'Manual', label: 'Manual / Cetak' },
    { value: 'SRIKANDI', label: 'Aplikasi SRIKANDI' },
];

const categoryOptions = computed(() => [
    { value: '', label: 'Pilih Kategori' },
    ...props.categories.map((c) => ({ value: c.id, label: c.category_name })),
]);

const priorityOptions = [
    { value: 'normal', label: 'Biasa' },
    { value: 'high', label: 'Penting' },
    { value: 'urgent', label: 'Mendesak / Segera' },
];

const securityLevelOptions = [
    { value: 'Biasa', label: 'Biasa' },
    { value: 'Rahasia', label: 'Rahasia' },
    { value: 'Sangat Rahasia', label: 'Sangat Rahasia' },
];

const form = useForm({
    letter_number: '',
    sender_unit: '',
    sender_name: '',
    sender_phone: '',
    category_id: null as number | null,
    subject: '',
    letter_date: new Date().toISOString().substring(0, 10),
    received_date: new Date().toISOString().substring(0, 10),
    priority: 'normal',
    security_level: 'Biasa',
    notes: '',
    letter_source: 'Manual',
    
    // Initial Disposition
    instruction: '',
    to_unit_ids: [] as number[],
    koordinator_unit_id: null as number | null,
    to_name: '',
    to_phone: '',
    due_date: '',
    attachment: null as File | null,
});

watch(() => form.to_unit_ids, (ids) => {
    if (form.koordinator_unit_id && !ids.includes(form.koordinator_unit_id)) {
        form.koordinator_unit_id = ids.length === 1 ? ids[0] : null;
    } else if (ids.length === 1 && !form.koordinator_unit_id) {
        form.koordinator_unit_id = ids[0];
    }
});

const unitSearch = ref('');
const filteredUnits = computed(() =>
    props.units.filter((u) =>
        u.unit_name.toLowerCase().includes(unitSearch.value.toLowerCase())
    )
);

const removeUnit = (id: number) => {
    form.to_unit_ids = form.to_unit_ids.filter((uid) => uid !== id);
};

const selectedUnitObjects = computed(() =>
    form.to_unit_ids
        .map((id) => props.units.find((u) => u.id === id))
        .filter((u): u is typeof props.units[number] => !!u)
);

const handleFileUpload = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.attachment = target.files[0];
    }
};

const submit = () => {
    form.post('/disposisi');
};
</script>

<template>
    <AppLayout title="Input Surat Disposisi">
        <Head title="Input Disposisi Baru" />

        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <span class="badge-lane-disposition d-inline-flex align-items-center gap-1 mb-2">
                    <i class="bi bi-diagram-3-fill"></i> Lajur Disposisi
                </span>
                <h2 class="fw-bold mb-1 text-dark">Registrasi Surat Disposisi</h2>
                <p class="text-muted mb-0 small">Masukkan surat masuk untuk diajukan ke Sekretaris Jenderal atau langsung didisposisikan.</p>
            </div>
            <Link href="/disposisi" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </Link>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="st-card">
                    <form @submit.prevent="submit">
                        <!-- Metadata Surat -->
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Identitas Surat Masuk</h5>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Sumber Surat</label>
                                <SearchableSelect
                                    v-model="form.letter_source"
                                    :options="letterSourceOptions"
                                    :searchable="false"
                                    placeholder="Pilih Sumber Surat"
                                />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Kategori Dokumen</label>
                                <SearchableSelect
                                    v-model="form.category_id"
                                    :options="categoryOptions"
                                    placeholder="Pilih Kategori"
                                />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nomor Surat Masuk</label>
                                <input v-model="form.letter_number" type="text" class="form-control" placeholder="Contoh: 123/EXT/VIII/2026" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Instansi / Unit Pengirim</label>
                                <input v-model="form.sender_unit" type="text" class="form-control" placeholder="Nama instansi/badan pengirim" required />
                                <div v-if="form.errors.sender_unit" class="text-danger small mt-1">{{ form.errors.sender_unit }}</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nama Pengirim / Penandatangan</label>
                                <input v-model="form.sender_name" type="text" class="form-control" placeholder="Nama pejabat/pengirim" required />
                                <div v-if="form.errors.sender_name" class="text-danger small mt-1">{{ form.errors.sender_name }}</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nomor WhatsApp Pengirim</label>
                                <input v-model="form.sender_phone" type="text" class="form-control" placeholder="08xxxxxxxxxx" />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Tanggal Surat</label>
                                <input v-model="form.letter_date" type="date" class="form-control" />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Tanggal Diterima TU</label>
                                <input v-model="form.received_date" type="date" class="form-control" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Tingkat Prioritas</label>
                                <SearchableSelect
                                    v-model="form.priority"
                                    :options="priorityOptions"
                                    :searchable="false"
                                    placeholder="Pilih Prioritas"
                                />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Derajat Keamanan</label>
                                <SearchableSelect
                                    v-model="form.security_level"
                                    :options="securityLevelOptions"
                                    :searchable="false"
                                    placeholder="Pilih Keamanan"
                                />
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Perihal Surat</label>
                                <textarea v-model="form.subject" class="form-control" rows="2" placeholder="Tuliskan perihal surat secara lengkap..." required></textarea>
                                <div v-if="form.errors.subject" class="text-danger small mt-1">{{ form.errors.subject }}</div>
                            </div>
                        </div>

                        <!-- Arahan Disposisi Awal (Multi Unit & Koordinator) -->
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">2. Arahan Disposisi Pimpinan</h5>

                        <div class="row g-3 mb-4 p-3 bg-light rounded-3 border">
                            <!-- Multi Unit Selector -->
                            <div class="col-12">
                                <label class="form-label small fw-bold">Diteruskan Kepada (Bisa Pilih Banyak Unit)</label>
                                <div class="dropdown">
                                    <button
                                        class="btn btn-outline-secondary dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center bg-white"
                                        type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                        <span>
                                            <i class="bi bi-building me-1 text-primary"></i>
                                            {{ form.to_unit_ids.length > 0 ? `${form.to_unit_ids.length} unit dipilih` : 'Klik untuk memilih satu atau beberapa unit kerja penerima...' }}
                                        </span>
                                    </button>
                                    <div class="dropdown-menu w-100 p-0 shadow-lg border-0">
                                        <div class="p-2 border-bottom bg-light">
                                            <input v-model="unitSearch" type="text" class="form-control form-control-sm"
                                                placeholder="Ketik untuk mencari unit kerja..." @click.stop />
                                        </div>
                                        <div style="max-height: 240px; overflow-y: auto;">
                                            <label v-for="u in filteredUnits" :key="u.id"
                                                class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 mb-0"
                                                style="cursor: pointer;" @click.stop>
                                                <input v-model="form.to_unit_ids" type="checkbox"
                                                    class="form-check-input mt-0 flex-shrink-0" :value="u.id" />
                                                <span class="small">{{ u.unit_name }}</span>
                                            </label>
                                            <div v-if="filteredUnits.length === 0" class="text-muted small px-3 py-3 text-center">
                                                Tidak ada unit kerja yang cocok.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Chips Unit Terpilih -->
                                <div v-if="selectedUnitObjects.length > 0" class="d-flex flex-wrap gap-2 mt-2">
                                    <span v-for="u in selectedUnitObjects" :key="u.id"
                                        class="badge d-flex align-items-center gap-1 py-2 px-2 fw-semibold"
                                        :class="form.koordinator_unit_id === u.id ? 'bg-warning-subtle text-dark border border-warning' : 'bg-primary-subtle text-primary border border-primary-subtle'">
                                        <i v-if="form.koordinator_unit_id === u.id" class="bi bi-star-fill text-warning me-1"></i>
                                        {{ u.unit_name }}
                                        <span v-if="form.koordinator_unit_id === u.id" class="badge bg-warning text-dark ms-1" style="font-size: 9px;">KOORDINATOR</span>
                                        <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.55rem;"
                                            @click="removeUnit(u.id)"></button>
                                    </span>
                                </div>
                            </div>

                            <!-- Tetapkan Koordinator -->
                            <div v-if="form.to_unit_ids.length > 0" class="col-12">
                                <label class="form-label small fw-bold text-dark d-flex align-items-center gap-1">
                                    <i class="bi bi-award-fill text-warning"></i> Tetapkan Satu Unit Sebagai Koordinator
                                </label>
                                <div class="border rounded-3 p-3 bg-white">
                                    <div class="row g-2">
                                        <div v-for="u in selectedUnitObjects" :key="u.id" class="col-md-6">
                                            <label class="form-check p-2 rounded border d-flex align-items-center gap-2 mb-0"
                                                :class="form.koordinator_unit_id === u.id ? 'border-primary bg-primary-subtle' : 'border-light bg-light'"
                                                style="cursor: pointer;">
                                                <input v-model="form.koordinator_unit_id" type="radio"
                                                    class="form-check-input mt-0 ms-1" :value="u.id" />
                                                <span class="small fw-semibold text-dark">{{ u.unit_name }}</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-muted">Unit koordinator akan menjadi penanggung jawab utama tindak lanjut.</small>
                                        <button v-if="form.koordinator_unit_id" type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none"
                                            @click="form.koordinator_unit_id = null">
                                            Reset Koordinator
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nama Koordinator</label>
                                <input v-model="form.to_name" type="text" class="form-control" placeholder="Nama pejabat / koordinator" />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">No. Telp Koordinator</label>
                                <input v-model="form.to_phone" type="text" class="form-control" placeholder="Contoh: 08123456789" />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Batas Waktu (Due Date)</label>
                                <input v-model="form.due_date" type="date" class="form-control" />
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Isi Instruksi / Catatan Disposisi</label>
                                <textarea v-model="form.instruction" class="form-control" rows="2" placeholder="Contoh: Mohon dipelajari dan ditindaklanjuti sesuai ketentuan..."></textarea>
                            </div>
                        </div>

                        <!-- Lampiran & Catatan TU -->
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">3. Lampiran & Catatan TU</h5>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Unggah Dokumen Lampiran (PDF)</label>
                                <input type="file" class="form-control" @change="handleFileUpload" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Catatan Tambahan TU</label>
                                <textarea v-model="form.notes" class="form-control" rows="2" placeholder="Catatan penerimaan surat..."></textarea>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex flex-column-reverse flex-sm-row justify-content-sm-end gap-2 pt-3 border-top">
                            <Link href="/disposisi" class="btn btn-secondary text-center">Batal</Link>
                            <button type="submit" class="btn btn-primary-blue text-center d-flex align-items-center justify-content-center" :disabled="form.processing">
                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                                <i class="bi bi-check2-circle me-1"></i> Simpan Surat Disposisi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
