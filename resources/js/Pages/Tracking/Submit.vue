<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Unit, LetterNumberType } from '@/types';

const props = defineProps<{
    units: Unit[];
    types: LetterNumberType[];
}>();

const form = useForm({
    sender_name: '',
    sender_phone: '',
    unit_id: null as number | null,
    destination: '',
    priority: 'Biasa',
    type_id: (props.types[0]?.id || 1) as number,
    subject: '',
    letter_date: new Date().toISOString().substring(0, 10),
    notes: '',
});

// Opsi untuk Unit Pengusul (SearchableSelect)
const unitOptions = computed(() =>
    props.units.map((u) => ({ value: u.id, label: u.unit_name }))
);

const typeOptions = computed(() =>
    props.types.map((t) => ({ value: t.id, label: t.workbook_name }))
);

const priorityOptions = [
    { value: 'Biasa', label: 'Biasa' },
    { value: 'Segera', label: 'Segera' },
];

const submit = () => {
    form.post('/ajukan-surat');
};
</script>

<template>
    <PublicLayout>

        <Head title="Permohonan Paraf Naskah Dinas" />

        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="st-card shadow-lg p-4 p-md-5 animate-card">
                        <div class="text-center mb-5">
                            <span
                                class="badge-floating badge bg-info-subtle text-info px-3 py-2 rounded-pill fw-bold mb-3">
                                <i class="bi bi-pen-fill me-1"></i> Layanan Permohonan Paraf Naskah Dinas
                            </span>
                            <h2 class="fw-bold text-dark mb-1">Permohonan Paraf & Tanda Tangan Naskah Dinas</h2>
                            <p class="text-muted small">Lengkapi formulir permohonan paraf untuk registrasi naskah dinas dan perolehan nomor serta kode tracking resmi.</p>
                        </div>

                        <form @submit.prevent="submit">
                            <div class="row g-4 mb-4">
                                <!-- 1. Identitas Pengirim & Kontak -->
                                <div class="col-md-6 animate-field" style="--delay: 1">
                                    <label class="form-label small fw-bold">Nama Lengkap Pengirim / Pembawa</label>
                                    <input v-model="form.sender_name" type="text" class="form-control custom-input"
                                        placeholder="Nama lengkap Anda" required />
                                    <Transition name="fade-error">
                                        <div v-if="form.errors.sender_name" class="text-danger small mt-1">{{
                                            form.errors.sender_name }}</div>
                                    </Transition>
                                </div>

                                <div class="col-md-6 animate-field" style="--delay: 2">
                                    <label class="form-label small fw-bold">Nomor WhatsApp Aktif</label>
                                    <input v-model="form.sender_phone" type="text" class="form-control custom-input"
                                        placeholder="08xxxxxxxxxx" required />
                                    <Transition name="fade-error">
                                        <div v-if="form.errors.sender_phone" class="text-danger small mt-1">{{
                                            form.errors.sender_phone }}</div>
                                    </Transition>
                                </div>

                                <!-- 2. Unit Pengusul & Sifat Naskah -->
                                <div class="col-md-6 animate-field" style="--delay: 3">
                                    <label class="form-label small fw-bold">Unit Pengusul</label>
                                    <SearchableSelect v-model="form.unit_id" :options="unitOptions"
                                        placeholder="-- Pilih Unit Pengusul --" />
                                    <Transition name="fade-error">
                                        <div v-if="form.errors.unit_id" class="text-danger small mt-1">{{
                                            form.errors.unit_id }}</div>
                                    </Transition>
                                </div>

                                <div class="col-md-6 animate-field" style="--delay: 4">
                                    <label class="form-label small fw-bold">Sifat Naskah</label>
                                    <SearchableSelect
                                        v-model="form.priority"
                                        :options="priorityOptions"
                                        :searchable="false"
                                        placeholder="Pilih Sifat Naskah"
                                    />
                                    <Transition name="fade-error">
                                        <div v-if="form.errors.priority" class="text-danger small mt-1">{{
                                            form.errors.priority }}</div>
                                    </Transition>
                                </div>

                                <!-- 3. Unit Tujuan & Jenis Naskah -->
                                <div class="col-12 animate-field" style="--delay: 5">
                                    <label class="form-label small fw-bold">Unit Tujuan</label>
                                    <input v-model="form.destination" type="text" class="form-control custom-input"
                                        placeholder="Contoh: Direktur Jenderal Pembinaan Hubungan Industrial dan Jaminan Sosial Tenaga Kerja" required />
                                    <div class="form-text text-muted small mt-1">
                                        <i class="bi bi-info-circle me-1 text-primary"></i>Ketik nama lengkap unit tujuan dan jangan gunakan singkatan.
                                    </div>
                                    <Transition name="fade-error">
                                        <div v-if="form.errors.destination" class="text-danger small mt-1">{{
                                            form.errors.destination }}</div>
                                    </Transition>
                                </div>

                                <div class="col-12 animate-field" style="--delay: 6">
                                    <label class="form-label small fw-bold">Jenis Naskah</label>
                                    <SearchableSelect v-model="form.type_id" :options="typeOptions"
                                        placeholder="-- Pilih Jenis Naskah --" />
                                    <Transition name="fade-error">
                                        <div v-if="form.errors.type_id" class="text-danger small mt-1">{{
                                            form.errors.type_id }}</div>
                                    </Transition>
                                </div>

                                <!-- 4. Perihal & Keterangan -->
                                <div class="col-12 animate-field" style="--delay: 7">
                                    <label class="form-label small fw-bold">Perihal Naskah Dinas</label>
                                    <textarea v-model="form.subject" class="form-control custom-input" rows="3"
                                        placeholder="Tuliskan perihal surat / naskah dinas permohonan paraf secara lengkap..." required></textarea>
                                    <Transition name="fade-error">
                                        <div v-if="form.errors.subject" class="text-danger small mt-1">{{
                                            form.errors.subject }}</div>
                                    </Transition>
                                </div>

                                <div class="col-12 animate-field" style="--delay: 8">
                                    <label class="form-label small fw-bold">Keterangan Tambahan / Catatan Khusus</label>
                                    <textarea v-model="form.notes" class="form-control custom-input" rows="2"
                                         placeholder="Catatan pengantar berkas atau instruksi tambahan..."></textarea>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-submit-track w-100 py-3" :disabled="form.processing">
                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else class="bi bi-send-check-fill me-2"></i>
                                Kirim Permohonan Paraf Naskah Dinas
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
.animate-card {
    animation: fadeInUp 0.8s ease-out forwards;
}

.animate-field {
    opacity: 0;
    transform: translateY(15px);
    animation:
        fadeInUp 0.5s ease-out calc(var(--delay) * 0.08s) forwards,
        clearFieldTransform 0.01s linear calc(var(--delay) * 0.08s + 0.5s) forwards;
}

@keyframes clearFieldTransform {
    to {
        transform: none;
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.badge-floating {
    animation: float 3s ease-in-out infinite;
    display: inline-block;
}

@keyframes float {
    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-5px);
    }
}

.custom-input {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.custom-input:focus {
    border-color: #14b8a6;
    box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.1);
    transform: translateY(-2px);
}

.btn-submit-track {
    background: #167992;
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-submit-track:hover:not(:disabled) {
    background: #126277;
    transform: scale(1.02);
    box-shadow: 0 8px 16px -6px rgba(22, 121, 146, 0.4);
}

.btn-submit-track:active {
    transform: scale(0.98);
}

.fade-error-enter-active,
.fade-error-leave-active {
    transition: all 0.3s ease;
}

.fade-error-enter-from,
.fade-error-leave-to {
    opacity: 0;
    transform: translateX(-10px);
}
</style>