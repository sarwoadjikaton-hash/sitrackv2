<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';
import DisposisiTabs from '@/Components/DisposisiTabs.vue';
import { Letter, Disposition, LetterStatusLog, LetterRelation, Unit, LetterNumberType } from '@/types';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps<{
    letter: Letter;
    dispositions: Disposition[];
    statusLogs: LetterStatusLog[];
    relations: LetterRelation[];
    units: Unit[];
    letterNumberTypes: LetterNumberType[];
    allowedStatuses: string[];
}>();

// Add Instruction Modal
const showInstructionModal = ref(false);
const instructionForm = useForm({
    parent_disposition_id: null as number | null,
    from_name: 'Sekretaris Jenderal',
    to_unit_ids: [] as number[],
    koordinator_unit_id: null as number | null,
    to_name: '',
    instruction: '',
    due_date: '',
    item_status: 'Didisposisikan',
    attachment: null as File | null,
});

watch(() => instructionForm.to_unit_ids, (ids) => {
    if (instructionForm.koordinator_unit_id && !ids.includes(instructionForm.koordinator_unit_id)) {
        instructionForm.koordinator_unit_id = null;
    }
});

const unitSearch = ref('');
const filteredUnits = computed(() =>
    props.units.filter((u) =>
        u.unit_name.toLowerCase().includes(unitSearch.value.toLowerCase())
    )
);

const removeUnit = (id: number) => {
    instructionForm.to_unit_ids = instructionForm.to_unit_ids.filter((uid) => uid !== id);
};

const selectedUnitNames = computed(() =>
    instructionForm.to_unit_ids
        .map((id) => props.units.find((u) => u.id === id))
        .filter((u): u is typeof props.units[number] => !!u)
);

const handleInstructionFile = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        instructionForm.attachment = target.files[0];
    }
};

const openAddInstruction = (parentId: number | null = null, fromName: string = 'Sekretaris Jenderal') => {
    instructionForm.parent_disposition_id = parentId;
    instructionForm.from_name = fromName;
    instructionForm.reset('instruction', 'to_name', 'to_unit_ids', 'koordinator_unit_id', 'due_date', 'attachment');
    showInstructionModal.value = true;
};

const submitInstruction = () => {
    instructionForm.post(`/disposisi/${props.letter.id}/add-instruction`, {
        onSuccess: () => {
            showInstructionModal.value = false;
        },
    });
};

// Update Disposition Item Modal
const showItemModal = ref(false);
const activeItem = ref<Disposition | null>(null);
const itemForm = useForm({
    status: 'Didisposisikan',
    follow_up_note: '',
    attachment: null as File | null,
});

const openUpdateItem = (item: Disposition) => {
    activeItem.value = item;
    itemForm.status = item.status;
    itemForm.follow_up_note = item.follow_up_note || '';
    itemForm.attachment = null;
    itemForm.clearErrors();
    showItemModal.value = true;
};

const handleItemFile = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        itemForm.attachment = target.files[0];
    }
};

const submitItemUpdate = () => {
    if (!activeItem.value) return;
    itemForm.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(`/disposisi/${props.letter.id}/item/${activeItem.value.id}`, {
        onSuccess: () => {
            showItemModal.value = false;
            activeItem.value = null;
        },
    });
};

// Add Relation Modal
const showRelationModal = ref(false);
const relationForm = useForm({
    source_letter_id: props.letter.id,
    target_letter_id: null as number | null,
    relation_type: 'TERKAIT_DENGAN',
    notes: '',
});

const relationFilterLane = ref<'disposition' | 'signature' | null>(null);
const relationFilterTypeId = ref<number | null>(null);
const relationSearchQuery = ref('');
const relationSearchResults = ref<Array<{ id: number; agenda_number: string | null; tracking_code: string; subject: string; process_lane: string }>>([]);
const relationSearchLoading = ref(false);
const selectedTargetLetter = ref<{ id: number; agenda_number: string | null; tracking_code: string; subject: string; process_lane: string } | null>(null);

const laneFilterOptions = [
    { value: 'disposition', label: 'Lajur Disposisi' },
    { value: 'signature', label: 'Lajur Tindak Lanjut' },
];

const typeFilterOptions = computed(() =>
    props.letterNumberTypes.map((t) => ({ value: t.id, label: t.type_name }))
);

let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

const runRelationSearch = () => {
    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(async () => {
        relationSearchLoading.value = true;
        try {
            const params = new URLSearchParams();
            params.set('exclude_id', String(props.letter.id));
            if (relationFilterLane.value) params.set('lane', relationFilterLane.value);
            if (relationFilterTypeId.value) params.set('letter_number_type_id', String(relationFilterTypeId.value));
            if (relationSearchQuery.value.trim()) params.set('q', relationSearchQuery.value.trim());

            const res = await fetch(`/letter-relations/search?${params.toString()}`);
            relationSearchResults.value = await res.json();
        } catch {
            relationSearchResults.value = [];
        } finally {
            relationSearchLoading.value = false;
        }
    }, 300);
};

watch([relationFilterLane, relationFilterTypeId, relationSearchQuery], runRelationSearch);

const pickTargetLetter = (letter: typeof selectedTargetLetter.value) => {
    selectedTargetLetter.value = letter;
    relationForm.target_letter_id = letter?.id ?? null;
};

const relationTypeOptions = [
    { value: 'TINDAK_LANJUT_DARI', label: 'Tindak Lanjut Dari (surat ini melanjutkan dokumen terpilih)' },
    { value: 'BALASAN_DARI', label: 'Balasan Dari (surat ini membalas dokumen terpilih)' },
    { value: 'HASIL_DISPOSISI_DARI', label: 'Hasil Disposisi Dari (surat ini hasil disposisi dokumen terpilih)' },
    { value: 'TERKAIT_DENGAN', label: 'Terkait Dengan (hubungan umum, tanpa arah khusus)' },
];

const relationLabels: Record<string, { forward: string; reverse: string }> = {
    TINDAK_LANJUT_DARI: { forward: 'Tindak Lanjut dari', reverse: 'Ditindaklanjuti oleh' },
    BALASAN_DARI: { forward: 'Balasan dari', reverse: 'Dibalas oleh' },
    HASIL_DISPOSISI_DARI: { forward: 'Hasil Disposisi dari', reverse: 'Menghasilkan Disposisi ke' },
    TERKAIT_DENGAN: { forward: 'Terkait dengan', reverse: 'Terkait dengan' },
};

const relationView = (rel: LetterRelation) => {
    const sourceId = (rel as any).source_letter_id ?? (rel as any).source_letter?.id;
    const isSource = sourceId === props.letter.id;
    const otherLetter = isSource ? (rel as any).target_letter : (rel as any).source_letter;
    const pair = relationLabels[rel.relation_type] ?? { forward: rel.relation_type, reverse: rel.relation_type };

    return {
        label: isSource ? pair.forward : pair.reverse,
        otherLetter,
    };
};

const openRelationModal = () => {
    relationForm.reset();
    relationForm.source_letter_id = props.letter.id;
    relationForm.target_letter_id = null;
    relationFilterLane.value = null;
    relationFilterTypeId.value = null;
    relationSearchQuery.value = '';
    relationSearchResults.value = [];
    selectedTargetLetter.value = null;
    showRelationModal.value = true;
};

const submitRelation = () => {
    relationForm.post('/letter-relations', {
        onSuccess: () => {
            showRelationModal.value = false;
        },
    });
};

const deleteRelation = (id: number) => {
    if (confirm('Hapus relasi surat ini?')) {
        router.delete(`/letter-relations/${id}`);
    }
};
</script>

<template>
    <AppLayout :title="`Detail Disposisi: ${letter.agenda_number || letter.tracking_code}`">

        <Head :title="`Detail Surat: ${letter.agenda_number}`" />

        <DisposisiTabs />

        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="badge-lane-disposition d-inline-flex align-items-center gap-1 mb-2">
                    <i class="bi bi-diagram-3-fill"></i> Lajur Disposisi
                </span>
                <h2 class="fw-bold mb-1 text-dark">{{ letter.subject }}</h2>
                <div class="d-flex align-items-center gap-2 text-muted small">
                    <span>Agenda: <strong class="text-primary">{{ letter.agenda_number || '-' }}</strong></span>
                    <span>&bull;</span>
                    <span>Resi: <strong class="font-monospace text-dark">{{ letter.tracking_code }}</strong></span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <Link :href="`/cetak/disposisi/${letter.id}`" class="btn btn-sm btn-outline-secondary" target="_blank">
                    <i class="bi bi-printer me-1"></i> Cetak Lembar Disposisi
                </Link>
                <button type="button" class="btn btn-sm btn-primary-blue"
                    @click="openAddInstruction(null, 'Sekretaris Jenderal')">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Arahan
                </button>
                <Link href="/disposisi" class="btn btn-sm btn-outline-secondary">
                    Kembali
                </Link>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Document Metadata & Relations -->
            <div class="col-lg-5">
                <!-- Metadata Card -->
                <div class="st-card mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h5 class="fw-bold text-dark mb-0">Informasi Surat Masuk</h5>
                        <StatusBadge :status="letter.status" />
                    </div>

                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted">Nomor Surat Asal</dt>
                        <dd class="col-7 fw-bold text-primary">{{ letter.letter_number || '-' }}</dd>

                        <dt class="col-5 text-muted">Sumber / Pengirim</dt>
                        <dd class="col-7 fw-semibold text-dark">{{ letter.sender_unit || letter.sender_name }}</dd>

                        <dt class="col-5 text-muted">Tanggal Surat</dt>
                        <dd class="col-7">{{ letter.letter_date ? new
                            Date(letter.letter_date).toLocaleDateString('id-ID') : '-' }}</dd>

                        <dt class="col-5 text-muted">Diterima TU</dt>
                        <dd class="col-7">{{ letter.received_date ? new
                            Date(letter.received_date).toLocaleDateString('id-ID') : '-' }}</dd>

                        <dt class="col-5 text-muted">Posisi Terkini</dt>
                        <dd class="col-7 fw-bold text-dark">
                            <i class="bi bi-geo-alt-fill text-primary me-1"></i>{{ letter.current_position }}
                        </dd>

                        <dt class="col-5 text-muted">Prioritas / Keamanan</dt>
                        <dd class="col-7">
                            <span class="badge bg-secondary-subtle text-secondary me-1">{{ letter.priority }}</span>
                            <span class="badge bg-light text-dark border">{{ letter.security_level }}</span>
                        </dd>

                        <dt class="col-5 text-muted">Catatan TU</dt>
                        <dd class="col-7 text-muted">{{ letter.notes || '-' }}</dd>

                        <dt class="col-5 text-muted">Dokumen PDF</dt>
                        <dd class="col-7">
                            <a v-if="letter.attachment_path" :href="`/lampiran/view/${letter.attachment_path}`"
                                target="_blank"
                                class="btn btn-xs btn-outline-danger d-inline-flex align-items-center gap-1 py-1 px-2">
                                <i class="bi bi-file-earmark-pdf"></i> Lihat File
                            </a>
                            <span v-else class="text-muted">Tidak ada lampiran</span>
                        </dd>
                    </dl>
                </div>

                <!-- Letter Relations Card -->
                <div class="st-card">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Relasi Antar Dokumen</h5>
                            <small class="text-muted">Hubungan surat antar lajur</small>
                        </div>
                        <button type="button" class="btn btn-xs btn-outline-blue" @click="openRelationModal">
                            <i class="bi bi-plus-lg me-1"></i> Hubungkan
                        </button>
                    </div>

                    <div v-if="relations.length > 0" class="d-flex flex-column gap-2">
                        <div v-for="rel in relations" :key="rel.id" class="p-3 border rounded-3 bg-light">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="flex-grow-1">
                                    <span class="badge bg-primary-subtle text-primary small mb-2">
                                        <i class="bi bi-arrow-left-right me-1"></i>{{ relationView(rel).label }}
                                    </span>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-secondary-subtle text-secondary small">
                                            {{ relationView(rel).otherLetter?.process_lane === 'disposition' ?
                                                'Disposisi' : 'Tindak Lanjut' }}
                                        </span>
                                        <span class="fw-bold small text-dark font-monospace">
                                            {{ relationView(rel).otherLetter?.agenda_number ||
                                                relationView(rel).otherLetter?.tracking_code }}
                                        </span>
                                    </div>
                                    <div class="small text-muted">{{ relationView(rel).otherLetter?.subject }}</div>
                                    <div v-if="rel.notes" class="small text-dark fst-italic mt-1">"{{ rel.notes }}"
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger border-0 flex-shrink-0"
                                    title="Hapus Relasi" @click="deleteRelation(rel.id)">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-3 text-muted small">
                        Belum ada dokumen yang direlasikan.
                    </div>
                </div>
            </div>

            <!-- Right Column: Multi-Level Dispositions & Timeline Logs -->
            <div class="col-lg-7">
                <!-- Dispositions Tree Card -->
                <div class="st-card mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="bi bi-diagram-3 me-2 text-primary"></i>Pohon Arahan Disposisi
                        </h5>
                        <button type="button" class="btn btn-sm btn-outline-blue"
                            @click="openAddInstruction(null, 'Sekretaris Jenderal')">
                            <i class="bi bi-plus-lg me-1"></i> Arahan Baru
                        </button>
                    </div>

                    <!-- Disposition Items Tree List -->
                    <div class="d-flex flex-column gap-3">
                        <div v-for="disp in dispositions" :key="disp.id"
                            class="p-3 border rounded-3 bg-white shadow-xs">
                            <!-- Root Item Header -->
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold text-primary">
                                    <i class="bi bi-person-circle me-1"></i>{{ disp.from_name }} &rarr;
                                    <span class="text-dark">
                                        {{ disp.to_unit?.unit_name || 'Unit' }}<template v-if="disp.to_name"> (a.n. {{
                                            disp.to_name }})</template>
                                    </span>
                                    <span v-if="disp.is_koordinator" class="badge bg-warning text-dark ms-2">
                                        <i class="bi bi-star-fill me-1"></i>Koordinator
                                    </span>
                                </span>
                                <span class="badge"
                                    :class="disp.status === 'Selesai' ? 'bg-success' : 'bg-warning text-dark'">
                                    {{ disp.status }}
                                </span>
                            </div>

                            <!-- Root Item Content -->
                            <p class="text-dark fw-medium mb-2 small bg-light p-2 rounded-2 border">
                                "{{ disp.instruction }}"
                            </p>

                            <div class="d-flex align-items-center justify-content-between small text-muted mb-2">
                                <span>Tenggat: <strong class="text-dark">{{ disp.due_date ? new
                                    Date(disp.due_date).toLocaleDateString('id-ID') : '-' }}</strong></span>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2"
                                        @click="openUpdateItem(disp)">
                                        <i class="bi bi-pencil-square me-1"></i> Update Tindak Lanjut
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2"
                                        @click="openAddInstruction(disp.id, disp.to_unit?.unit_name || disp.to_name || 'Pejabat')">
                                        <i class="bi bi-arrow-return-right me-1"></i> Teruskan
                                    </button>
                                </div>
                            </div>

                            <div v-if="disp.follow_up_note" class="alert alert-info py-2 px-3 mb-0 small">
                                <strong>Tindak Lanjut Unit:</strong> {{ disp.follow_up_note }}
                            </div>

                            <div v-if="disp.attachment_path" class="mt-2">
                                <a :href="`/lampiran/view/${disp.attachment_path}`" target="_blank"
                                    class="btn btn-xs btn-outline-danger py-1 px-2">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Lihat Lampiran
                                </a>
                            </div>

                            <!-- Sub-Dispositions (Children) -->
                            <div v-if="disp.children && disp.children.length > 0"
                                class="mt-3 ps-4 border-start border-3 border-primary d-flex flex-column gap-2">
                                <div v-for="sub in disp.children" :key="sub.id" class="p-2 border rounded-2 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold small text-primary">
                                            <i class="bi bi-arrow-return-right me-1"></i>{{ sub.from_name }} &rarr;
                                            {{ sub.to_unit?.unit_name || 'Unit' }}<template v-if="sub.to_name"> (a.n. {{
                                                sub.to_name }})</template>
                                            <span v-if="sub.is_koordinator" class="badge bg-warning text-dark ms-1">
                                                <i class="bi bi-star-fill me-1"></i>Koordinator
                                            </span>
                                        </span>
                                        <span class="badge"
                                            :class="sub.status === 'Selesai' ? 'bg-success' : 'bg-warning text-dark'">
                                            {{ sub.status }}
                                        </span>
                                    </div>
                                    <p class="small text-dark mb-1">"{{ sub.instruction }}"</p>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2"
                                            @click="openUpdateItem(sub)">
                                            Update Progres
                                        </button>
                                    </div>
                                    <div v-if="sub.attachment_path" class="mt-1">
                                        <a :href="`/lampiran/view/${sub.attachment_path}`" target="_blank"
                                            class="btn btn-xs btn-outline-danger py-0 px-2">
                                            <i class="bi bi-file-earmark-pdf me-1"></i>Lihat Lampiran
                                        </a>
                                    </div>
                                    <div v-if="sub.follow_up_note" class="small text-muted mt-1 fst-italic">
                                        Progres: {{ sub.follow_up_note }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="dispositions.length === 0" class="text-center py-4 text-muted">
                            Belum ada arahan disposisi yang dicatat. Klik <strong>Tambah Arahan</strong> di atas.
                        </div>
                    </div>
                </div>

                <!-- Audit Timeline Logs Card -->
                <div class="st-card">
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Perjalanan Dokumen
                    </h5>

                    <div class="timeline ps-3 border-start border-2 border-primary-subtle position-relative">
                        <div v-for="log in statusLogs" :key="log.id" class="mb-3 position-relative ps-3">
                            <span class="position-absolute start-0 top-0 translate-middle-x rounded-circle bg-primary"
                                style="width: 10px; height: 10px; margin-left: -13px; margin-top: 5px;"></span>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-dark small">{{ log.status }}</strong>
                                <small class="text-muted">{{ new Date(log.changed_at).toLocaleString('id-ID') }}</small>
                            </div>
                            <div class="small text-primary fw-semibold">{{ log.position }}</div>
                            <p class="small text-muted mb-0">{{ log.note }} &bull; <em>Oleh: {{ log.changed_by }}</em>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Instruction Modal -->
        <Modal :show="showInstructionModal" @close="showInstructionModal = false">
            <template #title>
                Tambah Arahan Disposisi
            </template>

            <form @submit.prevent="submitInstruction">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Dari (Pemberi Disposisi)</label>
                    <input v-model="instructionForm.from_name" type="text" class="form-control" required />
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Diteruskan Kepada (Unit Kerja)</label>
                    <div class="dropdown">
                        <button
                            class="btn btn-outline-secondary dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center"
                            type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <span>
                                {{ instructionForm.to_unit_ids.length > 0 ? `${instructionForm.to_unit_ids.length} unit
                                dipilih` :
                                    'Pilih Unit Kerja' }}
                            </span>
                        </button>
                        <div class="dropdown-menu w-100 p-0 shadow-lg border-0">
                            <div class="p-2 border-bottom bg-light">
                                <input v-model="unitSearch" type="text" class="form-control form-control-sm"
                                    placeholder="Cari unit kerja..." @click.stop />
                            </div>
                            <div style="max-height: 220px; overflow-y: auto;">
                                <label v-for="u in filteredUnits" :key="u.id"
                                    class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 mb-0"
                                    style="cursor: pointer;" @click.stop>
                                    <input v-model="instructionForm.to_unit_ids" type="checkbox"
                                        class="form-check-input mt-0 flex-shrink-0" :value="u.id" />
                                    <span class="small">{{ u.unit_name }}</span>
                                </label>
                                <div v-if="filteredUnits.length === 0" class="text-muted small px-3 py-3 text-center">
                                    Tidak ada unit yang cocok.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chip unit terpilih -->
                    <div v-if="selectedUnitNames.length > 0" class="d-flex flex-wrap gap-1 mt-2">
                        <span v-for="u in selectedUnitNames" :key="u.id"
                            class="badge bg-primary-subtle text-primary d-flex align-items-center gap-1 py-2 px-2 fw-semibold">
                            {{ u.unit_name }}
                            <button type="button" class="btn-close btn-close-sm" style="font-size: 0.55rem;"
                                @click="removeUnit(u.id)"></button>
                        </span>
                    </div>
                </div>

                <!-- Koordinator -->
                <div v-if="instructionForm.to_unit_ids.length > 1" class="mb-3">
                    <label class="form-label small fw-bold">Tetapkan Koordinator</label>
                    <div class="border rounded-3 p-2 bg-light">
                        <label v-for="uid in instructionForm.to_unit_ids" :key="uid"
                            class="form-check d-flex align-items-center gap-2 mb-1" style="cursor: pointer;">
                            <input v-model="instructionForm.koordinator_unit_id" type="radio"
                                class="form-check-input mt-0" :value="uid" />
                            <span class="small">{{props.units.find(u => u.id === uid)?.unit_name}}</span>
                        </label>
                    </div>
                    <small class="text-muted">Pilih salah satu unit sebagai koordinator (opsional).</small>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Pejabat / Penerima (Opsional)</label>
                    <input v-model="instructionForm.to_name" type="text" class="form-control"
                        placeholder="Nama staf/pejabat penerima" />
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Instruksi / Arahan Disposisi</label>
                    <textarea v-model="instructionForm.instruction" class="form-control" rows="3"
                        placeholder="Tuliskan instruksi lengkap..." required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Lampiran (Opsional)</label>
                    <input type="file" class="form-control" @change="handleInstructionFile" />
                    <small class="text-muted">PDF/DOCX/gambar, maksimal 20 MB</small>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Batas Waktu (Due Date)</label>
                    <input v-model="instructionForm.due_date" type="date" class="form-control" />
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <button type="button" class="btn btn-secondary" @click="showInstructionModal = false">Batal</button>
                    <button type="submit" class="btn btn-primary-blue" :disabled="instructionForm.processing">
                        Simpan Arahan
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Update Item Status Modal -->
        <Modal :show="showItemModal" @close="showItemModal = false">
            <template #title>
                Update Tindak Lanjut Disposisi
            </template>

            <form @submit.prevent="submitItemUpdate">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Status Tindak Lanjut</label>
                    <select v-model="itemForm.status" class="form-select" required>
                        <option value="Didisposisikan">Didisposisikan</option>
                        <option value="Dalam Tindak Lanjut">Dalam Tindak Lanjut</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Dikembalikan">Dikembalikan</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Catatan Progres Tindak Lanjut</label>
                    <textarea v-model="itemForm.follow_up_note" class="form-control" rows="3"
                        placeholder="Tuliskan perkembangan atau hasil tindak lanjut..."></textarea>
                </div>


                <div class="mb-3">
                    <label class="form-label small fw-bold">Lampiran (Opsional)</label>
                    <input type="file" class="form-control" @change="handleItemFile" />
                    <small class="text-muted">PDF/DOCX/gambar, maksimal 20 MB</small>
                    <div v-if="activeItem?.attachment_path" class="mt-1">
                        <a :href="`/lampiran/view/${activeItem.attachment_path}`" target="_blank" class="small text-danger">
                            <i class="bi bi-file-earmark-pdf me-1"></i>Lampiran saat ini
                        </a>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <button type="button" class="btn btn-secondary" @click="showItemModal = false">Batal</button>
                    <button type="submit" class="btn btn-primary-blue" :disabled="itemForm.processing">
                        Perbarui Progres
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Relation Modal -->
        <Modal :show="showRelationModal" @close="showRelationModal = false">
            <template #title>
                Hubungkan dengan Dokumen Lain
            </template>

            <form @submit.prevent="submitRelation">
                <div class="alert alert-light border small mb-3 d-flex gap-2">
                    <i class="bi bi-info-circle text-primary flex-shrink-0"></i>
                    <span>Saring dulu berdasarkan lajur/jenis naskah, lalu cari dokumennya, supaya nggak salah pilih
                        dokumen
                        yang mirip.</span>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Filter Lajur</label>
                        <SearchableSelect v-model="relationFilterLane" :options="laneFilterOptions"
                            placeholder="Semua Lajur" search-placeholder="Cari lajur..." clearable />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Filter Jenis Naskah</label>
                        <SearchableSelect v-model="relationFilterTypeId" :options="typeFilterOptions"
                            placeholder="Semua Jenis Naskah" search-placeholder="Cari jenis naskah..." clearable />
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Cari Dokumen (agenda / resi / perihal)</label>
                    <input v-model="relationSearchQuery" type="text" class="form-control"
                        placeholder="Ketik untuk mencari..." />
                </div>

                <div class="border rounded-3 mb-3" style="max-height: 220px; overflow-y: auto;">
                    <div v-if="relationSearchLoading" class="text-center text-muted small py-3">
                        <span class="spinner-border spinner-border-sm me-2"></span>Mencari...
                    </div>
                    <div v-else-if="relationSearchResults.length === 0" class="text-center text-muted small py-3">
                        {{ relationSearchQuery || relationFilterLane || relationFilterTypeId ? 'Tidak ada dokumen yang cocok.' : 'Ketik kata kunci atau pilih filter untuk mulai mencari.' }}
                    </div>
                    <button v-for="l in relationSearchResults" :key="l.id" type="button"
                        class="d-flex justify-content-between align-items-center w-100 text-start p-2 border-0 border-bottom bg-white"
                        :class="{ 'bg-primary-subtle': selectedTargetLetter?.id === l.id }"
                        @click="pickTargetLetter(l)">
                        <div>
                            <span class="badge bg-secondary-subtle text-secondary small me-2">
                                {{ l.process_lane === 'disposition' ? 'Disposisi' : 'Tindak Lanjut' }}
                            </span>
                            <span class="fw-bold small font-monospace">{{ l.agenda_number || l.tracking_code }}</span>
                            <div class="small text-muted">{{ l.subject }}</div>
                        </div>
                        <i v-if="selectedTargetLetter?.id === l.id" class="bi bi-check-lg text-primary"></i>
                    </button>
                </div>

                <div v-if="selectedTargetLetter" class="alert alert-primary-subtle border small mb-3">
                    Dipilih: <strong>{{ selectedTargetLetter.agenda_number || selectedTargetLetter.tracking_code
                        }}</strong> —
                    {{ selectedTargetLetter.subject }}
                </div>
                <div v-if="relationForm.errors.target_letter_id" class="text-danger small mb-3">
                    {{ relationForm.errors.target_letter_id }}
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Hubungan Surat Ini Terhadap Dokumen Tersebut</label>
                    <SearchableSelect v-model="relationForm.relation_type" :options="relationTypeOptions"
                        placeholder="Pilih jenis relasi..." search-placeholder="Cari jenis relasi..." />
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Catatan (Opsional)</label>
                    <textarea v-model="relationForm.notes" class="form-control" rows="2"
                        placeholder="Keterangan tambahan mengenai hubungan ini..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <button type="button" class="btn btn-secondary" @click="showRelationModal = false">Batal</button>
                    <button type="submit" class="btn btn-primary-blue"
                        :disabled="relationForm.processing || !relationForm.target_letter_id">
                        Simpan Hubungan
                    </button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
