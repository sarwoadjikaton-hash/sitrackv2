<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Letter, LetterCategory, Unit, LetterNumberType, LetterNumber } from '@/types';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import axios from 'axios';

const props = defineProps<{
    letter?: Letter | null;
    categories: LetterCategory[];
    units: Unit[];
    letterNumberTypes: LetterNumberType[];
    allowedStatuses: string[];
}>();

const isEditing = !!props.letter;

const extractClassification = (numStr?: string | null): string => {
    if (!numStr) return 'UM.01';
    const parts = numStr.split('/');
    if (parts.length >= 4) {
        return parts[2] || 'UM.01';
    }
    return 'UM.01';
};

const classificationCode = ref(extractClassification(props.letter?.letter_number));
const selectedSlotId = ref<number | null>(null);
const availableSlots = ref<LetterNumber[]>([]);
const loadingSlots = ref(false);

const getInitialActions = (action?: string | string[] | null): string[] => {
    if (!action) return ['Mohon Paraf'];
    if (Array.isArray(action)) return action;
    return action.split(',').map((s) => s.trim()).filter(Boolean);
};

const form = useForm({
    letter_number: props.letter?.letter_number || '',
    letter_number_type_id: props.letter?.letter_number_type_id || props.letterNumberTypes[0]?.id || null,
    slot_id: null as number | null,
    letter_type: props.letter?.letter_type || 'in',
    letter_source: props.letter?.letter_source || 'Manual',
    sender_unit: props.letter?.sender_unit || '',
    sender_name: props.letter?.sender_name || '',
    sender_phone: props.letter?.sender_phone || '',
    destination: props.letter?.destination || props.letter?.recipient_unit?.unit_name || '',
    recipient_unit_id: props.letter?.recipient_unit_id || null,
    category_id: props.letter?.category_id || null,
    subject: props.letter?.subject || '',
    letter_date: props.letter?.letter_date ? props.letter.letter_date.substring(0, 10) : new Date().toISOString().substring(0, 10),
    received_date: props.letter?.received_date ? props.letter.received_date.substring(0, 10) : new Date().toISOString().substring(0, 10),
    priority: props.letter?.priority || 'Biasa',
    security_level: props.letter?.security_level || 'Biasa',
    status: props.letter?.status || 'Diregistrasi',
    current_position: props.letter?.current_position || 'Tata Usaha Sekjen',
    requested_actions: getInitialActions(props.letter?.requested_actions),
    notes: props.letter?.notes || '',
    attachment: null as File | null,
});

const selectedType = computed(() =>
    props.letterNumberTypes.find((t) => t.id === Number(form.letter_number_type_id))
);

const fetchSlots = async (typeId: number) => {
    if (!typeId) {
        availableSlots.value = [];
        return;
    }
    loadingSlots.value = true;
    try {
        const year = new Date(form.letter_date || new Date()).getFullYear();
        const res = await axios.get('/data-surat/slots', {
            params: { type_id: typeId, year },
        });
        if (res.data.ok) {
            availableSlots.value = res.data.items;
        }
    } catch (e) {
        console.error(e);
    } finally {
        loadingSlots.value = false;
    }
};

watch(() => form.letter_number_type_id, (newVal) => {
    if (newVal) {
        fetchSlots(Number(newVal));
    }
}, { immediate: true });

const romanMonths = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

const previewNumberText = computed(() => {
    if (!selectedType.value) return form.letter_number || '-';

    const padding = selectedType.value.sequence_padding || 4;
    let seq = 'xxxx';
    let year = new Date().getFullYear();

    if (selectedSlotId.value) {
        const slot = availableSlots.value.find((s) => s.id === Number(selectedSlotId.value));
        if (slot) {
            seq = String(slot.sequence_number).padStart(padding, '0');
            year = slot.number_year || year;
        }
    } else if (form.letter_number) {
        const match = form.letter_number.match(/\/(\d{3,6})\//);
        if (match) {
            seq = match[1];
        }
    }

    const dateObj = new Date(form.letter_date);
    const monthNum = isNaN(dateObj.getTime()) ? new Date().getMonth() + 1 : dateObj.getMonth() + 1;
    const monthRoman = romanMonths[monthNum] || 'I';
    const signer = selectedType.value.default_signer_code || '1';
    const security = form.security_level === 'Rahasia' ? 'R' : (form.security_level === 'Sangat Rahasia' ? 'SR' : 'B');
    const pattern = selectedType.value.number_pattern || 'B-{signer}/{sequence}/{classification}/{month_roman}/{year}';

    const classCode = classificationCode.value ? classificationCode.value.trim() : '';

    return pattern
        .replace('{sequence}', seq)
        .replace('{year}', String(year))
        .replace('{month_roman}', monthRoman)
        .replace('{month}', String(monthNum).padStart(2, '0'))
        .replace('{signer}', signer)
        .replace('{security}', security)
        .replace('{classification}', classCode)
        .replace(/\{[a-zA-Z0-9_]+\}/g, '')
        .replace(/^-+/, '')
        .replace(/\/+/g, '/');
});

const applyGeneratedNumber = () => {
    if (previewNumberText.value && previewNumberText.value !== '-') {
        form.letter_number = previewNumberText.value;
    }
};

const availableOnlySlots = computed(() =>
    availableSlots.value.filter((s) => s.status === 'available')
);

const slotOptions = computed(() =>
    availableOnlySlots.value.map((slot) => ({
        value: slot.id,
        label: `No. Urut ${String(slot.sequence_number).padStart(selectedType.value?.sequence_padding || 4, '0')}`,
    }))
);

watch(selectedSlotId, (newVal) => {
    form.slot_id = newVal ? Number(newVal) : null;
    if (newVal) {
        applyGeneratedNumber();
    }
});

watch(classificationCode, () => {
    if (selectedSlotId.value) {
        applyGeneratedNumber();
    }
});

const actionOptions = [
    'Mohon Paraf',
    'Mohon Tanda Tangan',
];

const handleFileUpload = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.attachment = target.files[0];
    }
};

const submit = () => {
    if (isEditing && props.letter) {
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(`/tindak-lanjut/${props.letter.id}`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                // berhasil
            },
            onError: (errors) => {
                console.log('Update gagal:', errors);
            },
        });
    } else {
        form.post('/tindak-lanjut', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                // berhasil
            },
            onError: (errors) => {
                console.log('Input gagal:', errors);
            },
        });
    }
};

const letterSourceOptions = [
    { value: 'Manual', label: 'Manual / Fisik' },
    { value: 'SRIKANDI', label: 'Aplikasi SRIKANDI' },
];

const priorityOptions = [
    { value: 'Biasa', label: 'Biasa' },
    { value: 'Segera', label: 'Segera' },
    { value: 'normal', label: 'Biasa / Normal' },
    { value: 'high', label: 'Penting' },
    { value: 'urgent', label: 'Amat Segera / Mendesak' },
];

const securityOptions = [
    { value: 'Biasa', label: 'Biasa' },
    { value: 'Rahasia', label: 'Rahasia' },
    { value: 'Sangat Rahasia', label: 'Sangat Rahasia' },
];

const letterNumberTypeOptions = computed(() =>
    props.letterNumberTypes.map((t) => ({ value: t.id, label: t.type_name }))
);

const unitOptions = computed(() =>
    props.units.map((u) => ({ value: u.id, label: u.unit_name }))
);

const unitDestinationOptions = computed(() =>
    props.units.map((u) => ({ value: u.unit_name, label: u.unit_name }))
);

const isManualDestination = ref(false);

watch(() => form.destination, (newVal) => {
    if (!newVal) {
        form.recipient_unit_id = null;
        return;
    }
    const matched = props.units.find(
        (u) => u.unit_name.toLowerCase() === String(newVal).trim().toLowerCase()
    );
    form.recipient_unit_id = matched ? matched.id : null;
});

const statusOptions = computed(() =>
    props.allowedStatuses.map((s) => ({ value: s, label: s }))
);
</script>

<template>
    <AppLayout :title="isEditing ? 'Ubah Lembar Tindak Lanjut' : 'Input Tindak Lanjut / TTD Baru'">

        <Head :title="isEditing ? 'Ubah Tindak Lanjut' : 'Input Tindak Lanjut'" />

        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <span class="badge-lane-signature d-inline-flex align-items-center gap-1 mb-2">
                    <i class="bi bi-pen-fill"></i> Lajur Pertama
                </span>
                <h2 class="fw-bold mb-1 text-dark">{{ isEditing ? 'Ubah Data Tindak Lanjut' : 'Registrasi Tindak / TTD'
                    }}</h2>
                <p class="text-muted mb-0 small">Lengkapi informasi naskah dinas untuk proses verifikasi dan
                    penandatanganan.</p>
            </div>
            <Link href="/tindak-lanjut" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </Link>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="st-card">
                    <form @submit.prevent="submit">
                        <!-- Alert Kesalahan Validasi jika ada -->
                        <div v-if="Object.keys(form.errors).length > 0" class="alert alert-danger mb-4 rounded-3">
                            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Mohon lengkapi atau perbaiki field berikut:</div>
                            <ul class="mb-0 ps-3 small">
                                <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                            </ul>
                        </div>

                        <!-- Informasi Utama -->
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Informasi & Identitas Dokumen</h5>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Jenis Naskah Dinas</label>
                                <SearchableSelect v-model="form.letter_number_type_id"
                                    :options="letterNumberTypeOptions" placeholder="Pilih Jenis Naskah"
                                    search-placeholder="Cari jenis naskah..." />
                                <small class="text-muted">Menentukan awalan kode resi dan pola format nomor dinas</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Sumber Naskah</label>
                                <SearchableSelect v-model="form.letter_source" :options="letterSourceOptions"
                                    placeholder="Pilih Sumber Naskah" search-placeholder="Cari sumber..." />
                            </div>

                            <!-- Alokasi / Penomoran Surat -->
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Pilih Slot Nomor Tersedia (Opsional)</label>
                                <SearchableSelect
                                    v-model="selectedSlotId"
                                    :options="slotOptions"
                                    :loading="loadingSlots"
                                    loading-text="Memuat slot..."
                                    placeholder="-- Pilih Nomor Tersedia --"
                                    search-placeholder="Cari nomor urut..."
                                    empty-text="Tidak ada slot nomor tersedia"
                                />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Kode Klasifikasi Arsip</label>
                                <input v-model="classificationCode" type="text" class="form-control font-monospace"
                                    placeholder="Contoh: UM.01 / KS.06 / KP.09.00" />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nomor Surat Resmi</label>
                                <div class="input-group">
                                    <input v-model="form.letter_number" type="text" class="form-control font-monospace fw-bold"
                                        placeholder="Contoh: B-1/0758/UM.01/VIII/2026" />
                                    <button type="button" class="btn btn-outline-primary" @click="applyGeneratedNumber" title="Terapkan Formula">
                                        <i class="bi bi-magic"></i>
                                    </button>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    Formula: <span class="text-primary font-monospace">{{ previewNumberText }}</span>
                                </small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Tanggal Surat</label>
                                <input v-model="form.letter_date" type="date" class="form-control" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Tanggal Diterima</label>
                                <input v-model="form.received_date" type="date" class="form-control" />
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Perihal Naskah Dinas</label>
                                <textarea v-model="form.subject" class="form-control" rows="2"
                                    placeholder="Tuliskan perihal surat secara lengkap..." required></textarea>
                                <div v-if="form.errors.subject" class="text-danger small mt-1">{{ form.errors.subject }}
                                </div>
                            </div>
                        </div>

                        <!-- Pengirim & Penerima -->
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">2. Pengirim & Tujuan Unit</h5>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Unit / Instansi Pengirim</label>
                                <input v-model="form.sender_unit" type="text" class="form-control"
                                    placeholder="Nama instansi/unit kerja" />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nama Pengirim / Pembawa</label>
                                <input v-model="form.sender_name" type="text" class="form-control"
                                    placeholder="Nama lengkap pengirim" required />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nomor WhatsApp Pengirim</label>
                                <input v-model="form.sender_phone" type="text" class="form-control"
                                    placeholder="08xxxxxxxxxx" />
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <label class="form-label small fw-bold mb-0">Unit Tujuan</label>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small text-primary fw-semibold"
                                        @click="isManualDestination = !isManualDestination">
                                        <i class="bi" :class="isManualDestination ? 'bi-list-ul' : 'bi-pencil-square'"></i>
                                        {{ isManualDestination ? 'Pilih dari Daftar' : 'Ketik Manual' }}
                                    </button>
                                </div>

                                <!-- Mode 1: Searchable Dropdown + Ketik Bebas / Tambah Otomatis -->
                                <div v-if="!isManualDestination">
                                    <SearchableSelect
                                        v-model="form.destination"
                                        :options="unitDestinationOptions"
                                        :allow-custom="true"
                                        placeholder="-- Pilih atau cari unit tujuan --"
                                        search-placeholder="Ketik untuk mencari atau ketik nama instansi baru..."
                                        custom-placeholder="✨ Gunakan: &quot;{text}&quot; (Input Bebas)"
                                        empty-text="Unit tidak terdaftar. Pilih opsi di atas untuk menggunakannya."
                                    />
                                    <small class="text-muted d-block mt-1">
                                        Pilih unit terdaftar atau ketik nama baru lalu klik/pilih untuk menggunakan.
                                    </small>
                                </div>

                                <!-- Mode 2: Input Teks Manual Langsung -->
                                <div v-else>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-primary"><i class="bi bi-building"></i></span>
                                        <input v-model="form.destination" type="text" class="form-control"
                                            placeholder="Contoh: Kementerian Pertanian / Biro Hubungan Luar Negeri..." />
                                        <button v-if="form.destination" type="button" class="btn btn-outline-secondary" @click="form.destination = ''" title="Hapus teks">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        Mode ketik manual aktif. Bebas menulis nama instansi atau unit apapun.
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Tingkat Prioritas</label>
                                <SearchableSelect v-model="form.priority" :options="priorityOptions"
                                    placeholder="Pilih Prioritas" search-placeholder="Cari..." />
                            </div>
                        </div>

                        <!-- Status & Posisi -->
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">3. Status Pengendalian & Tindakan</h5>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Status</label>
                                <SearchableSelect v-model="form.status" :options="statusOptions"
                                    placeholder="Pilih Status" search-placeholder="Cari status..." />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Posisi Berkas</label>
                                <input v-model="form.current_position" type="text" class="form-control"
                                    placeholder="Contoh: Tata Usaha / Arsiparis" required />
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Tindakan yang Dimohonkan</label>
                                <div class="d-flex gap-4 mt-1">
                                    <div v-for="opt in actionOptions" :key="opt" class="form-check">
                                        <input :id="`form-act-${opt}`" v-model="form.requested_actions"
                                            type="checkbox" class="form-check-input" :value="opt" />
                                        <label :for="`form-act-${opt}`" class="form-check-label fw-semibold small" style="cursor: pointer;">
                                            {{ opt }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Unggah Lampiran (PDF/DOCX)</label>
                                <input type="file" class="form-control"
                                    accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                    @change="handleFileUpload" />
                                <small class="text-muted">Maksimal 20 MB</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Catatan Khusus</label>
                                <textarea v-model="form.notes" class="form-control" rows="2"
                                    placeholder="Catatan tambahan dari penginput..."></textarea>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <Link href="/tindak-lanjut" class="btn btn-secondary">Batal</Link>
                            <button type="submit" class="btn btn-primary-blue" :disabled="form.processing">
                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                                <i class="bi bi-check2-circle me-1"></i>
                                {{ isEditing ? 'Simpan Perubahan' : 'Catat Lembar Tindak Lanjut' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
