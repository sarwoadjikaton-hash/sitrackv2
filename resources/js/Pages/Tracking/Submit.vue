<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { LetterCategory, Unit, LetterNumberType, LetterNumber } from '@/types';
import axios from 'axios';

const props = defineProps<{
    categories: LetterCategory[];
    units: Unit[];
    types: LetterNumberType[];
}>();

const selectedTypeId = ref(props.types[0]?.id || 1);
const availableSlots = ref<LetterNumber[]>([]);
const loadingSlots = ref(false);

const form = useForm({
    sender_name: '',
    sender_phone: '',
    recipient_unit_id: null as number | null,
    category_id: null as number | null,
    subject: '',
    letter_date: new Date().toISOString().substring(0, 10),
    letter_number_id: '' as number | string,
    notes: '',
});

// Opsi untuk SearchableSelect (butuh format { value, label })
const unitOptions = computed(() =>
    props.units.map((u) => ({ value: u.id, label: u.unit_name }))
);

const categoryOptions = computed(() =>
    props.categories.map((c) => ({ value: c.id, label: c.category_name }))
);

const typeOptions = computed(() =>
    props.types.map((t) => ({ value: t.id, label: t.workbook_name }))
);

const slotOptions = computed(() =>
    availableSlots.value.map((slot) => ({
        value: slot.id,
        label: `Nomor Urut ${slot.sequence_number}`,
    }))
);

const fetchSlots = async (typeId: number) => {
    loadingSlots.value = true;
    try {
        const res = await axios.get('/data-surat/slots', {
            params: { type_id: typeId, year: new Date().getFullYear() },
        });
        if (res.data.ok) {
            availableSlots.value = res.data.items;
            if (availableSlots.value.length > 0) {
                form.letter_number_id = availableSlots.value[0].id;
            } else {
                form.letter_number_id = '';
            }
        }
    } catch (e) {
        console.error(e);
    } finally {
        loadingSlots.value = false;
    }
};

fetchSlots(selectedTypeId.value);

watch(selectedTypeId, (newVal) => {
    if (newVal) fetchSlots(newVal as number);
});

const submit = () => {
    form.post('/ajukan-surat');
};
</script>

<template>
    <PublicLayout>

        <Head title="Pengajuan Surat Mandiri" />

        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="st-card shadow-lg p-4 p-md-5 animate-card">
                        <div class="text-center mb-5">
                            <span
                                class="badge-floating badge bg-info-subtle text-info px-3 py-2 rounded-pill fw-bold mb-3">
                                <i class="bi bi-send-fill me-1"></i> Layanan Pengajuan Mandiri
                            </span>
                            <h2 class="fw-bold text-dark mb-1">Pengajuan Surat & Permohonan</h2>
                            <p class="text-muted small">Dapatkan kode tracking dan nomor naskah dinas resmi secara
                                instan.</p>
                        </div>

                        <form @submit.prevent="submit">
                            <div class="row g-4 mb-4">
                                <div class="col-md-6 animate-field" style="--delay: 1">
                                    <label class="form-label small fw-bold">Nama Lengkap Pengirim</label>
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

                                <div class="col-md-6 animate-field" style="--delay: 3">
                                    <label class="form-label small fw-bold">Unit Tujuan</label>
                                    <SearchableSelect v-model="form.recipient_unit_id" :options="unitOptions"
                                        placeholder="-- Pilih Unit Tujuan --" />
                                </div>

                                <div class="col-md-6 animate-field" style="--delay: 4">
                                    <label class="form-label small fw-bold">Kategori Naskah</label>
                                    <SearchableSelect v-model="form.category_id" :options="categoryOptions"
                                        placeholder="-- Pilih Kategori --" />
                                </div>

                                <div class="col-md-6 animate-field" style="--delay: 5">
                                    <label class="form-label small fw-bold">Jenis Naskah</label>
                                    <SearchableSelect v-model="selectedTypeId" :options="typeOptions" />
                                </div>

                                <div class="col-md-6 animate-field" style="--delay: 6">
                                    <label class="form-label small fw-bold">Alokasi Nomor Tersedia</label>
                                    <SearchableSelect v-model="form.letter_number_id" :options="slotOptions"
                                        :loading="loadingSlots" loading-text="Memuat slot..."
                                        placeholder="-- Pilih Nomor Tersedia --"
                                        empty-text="Tidak ada slot nomor tersedia" />
                                </div>

                                <div class="col-md-6 animate-field" style="--delay: 7">
                                    <label class="form-label small fw-bold">Tanggal Surat</label>
                                    <input v-model="form.letter_date" type="date" class="form-control custom-input" />
                                </div>

                                <div class="col-12 animate-field" style="--delay: 8">
                                    <label class="form-label small fw-bold">Perihal Naskah</label>
                                    <textarea v-model="form.subject" class="form-control custom-input" rows="3"
                                        placeholder="Tuliskan perihal surat Anda..." required></textarea>
                                </div>

                                <div class="col-12 animate-field" style="--delay: 9">
                                    <label class="form-label small fw-bold">Keterangan Tambahan</label>
                                    <textarea v-model="form.notes" class="form-control custom-input" rows="2"
                                        placeholder="Catatan pengantar berkas..."></textarea>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-submit-track w-100 py-3" :disabled="form.processing">
                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else class="bi bi-send-check-fill me-2"></i>
                                Kirim Pengajuan Surat
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
/* Animasi tetap sama seperti sebelumnya */
.animate-card {
    animation: fadeInUp 0.8s ease-out forwards;
}

.animate-field {
    opacity: 0;
    transform: translateY(15px);
    animation:
        fadeInUp 0.5s ease-out calc(var(--delay) * 0.1s) forwards,
        clearFieldTransform 0.01s linear calc(var(--delay) * 0.1s + 0.5s) forwards;
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
    background: linear-gradient(135deg, #0f172a 0%, #0d9488 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-submit-track:hover:not(:disabled) {
    filter: brightness(1.1);
    transform: scale(1.02);
    box-shadow: 0 10px 20px -10px rgba(13, 148, 136, 0.5);
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