<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Letter } from '@/types';

const props = defineProps<{
    letter: Letter;
}>();

// Print & Navigation
const printPage = () => window.print();
const goBack = () => window.history.back();

// Format waktu cetak untuk footer
const printDateTime = computed(() => {
    return new Date().toLocaleString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).replace(/\./g, ':');
});

// Format tanggal standar Indonesia
const formatDate = (dateStr?: string | null) => {
    if (!dateStr) return '-';
    try {
        return new Date(dateStr).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    } catch {
        return dateStr;
    }
};

// Unit lists matching the official Kemnaker Lembar Disposisi exactly
const leftColumnUnits = [
    'Direktorat Jenderal Pembinaan Pelatihan Vokasi & Produktivitas',
    'Direktorat Jenderal Pembinaan Penempatan Tenaga Kerja & PKK',
    'Direktorat Jenderal Pembinaan Hubungan Industrial & Jamsosnaker',
    'Direktorat Jenderal Pembinaan Pengawasan Ketenagakerjaan & K3',
    'Inspektorat Jenderal',
    'Badan Perencanaan Pengembangan Ketenagakerjaan',
    'Staf Ahli Menteri Bidang Ekonomi Ketenagakerjaan',
    'Staf Ahli Menteri Bidang Hubungan Internasional',
    'Staf Ahli Menteri Bidang Hubungan Antarlembaga',
    'Staff Ahli Menteri Sosial, Politik, dan Kebijakan Publik',
];

const rightColumnUnits = [
    'PPSDM Ketenagakerjaan',
    'Pusat Pasar Kerja',
    'Biro Perencanaan & Manj. Kinerja',
    'Biro Keuangan & BMN',
    'Biro Organisasi & SDM Aparatur',
    'Biro Hukum',
    'Biro Umum',
    'Biro Kerja Sama',
    'Biro Hubungan Masyarakat',
    'Politeknik Ketenagakerjaan',
    'Bagian TU Pimpinan dan Protokol',
    'Subbagian TU Sekjen, SAM dan SKM',
];

// Lajur Disposisi actions matching official sheet
const lajurActionsLeft = [
    'Dipelajari / Dicermati',
    'Ditindaklanjuti',
    'Agar Dimonitor',
    'Dikoordinasikan',
    'Segera Buat Laporan',
    'Siapkan Bahan',
    'Bahas Dengan Saya',
    'Saran dan Penjelasan',
];

const lajurActionsRight = [
    'Siapkan Jawaban',
    'Copy untuk Saya',
    'Dipedomani',
    'Untuk Diketahui',
    'File / Simpan / Arsip',
    'Agendakan / Acarakan',
    'Mendampingi Sekjen',
    'Mewakili Sekjen',
];

// Normalize helper to match units loosely
const normalize = (str?: string | null) => (str || '').toLowerCase().replace(/[^a-z0-9]/g, '');

// Pre-select units from letter and dispositions
const initialSelectedUnits: string[] = [];
if (props.letter.recipient_unit?.unit_name) {
    initialSelectedUnits.push(props.letter.recipient_unit.unit_name);
}
if (props.letter.dispositions && props.letter.dispositions.length > 0) {
    props.letter.dispositions.forEach((d) => {
        if (d.to_unit?.unit_name) initialSelectedUnits.push(d.to_unit.unit_name);
        if (d.to_name) initialSelectedUnits.push(d.to_name);
    });
}

const selectedUnits = ref<string[]>(initialSelectedUnits);

const isUnitChecked = (unitName: string) => {
    const target = normalize(unitName);
    return selectedUnits.value.some((u) => {
        const norm = normalize(u);
        return norm.includes(target) || target.includes(norm);
    });
};

const toggleUnit = (unitName: string) => {
    if (isUnitChecked(unitName)) {
        selectedUnits.value = selectedUnits.value.filter((u) => {
            const norm = normalize(u);
            const target = normalize(unitName);
            return !norm.includes(target) && !target.includes(norm);
        });
    } else {
        selectedUnits.value.push(unitName);
    }
};

// Selected actions
const selectedActions = ref<string[]>(
    props.letter.requested_actions
        ? props.letter.requested_actions.split(',').map((s) => s.trim())
        : ['Ditindaklanjuti']
);

const isActionChecked = (actionName: string) => {
    return selectedActions.value.includes(actionName);
};

const toggleAction = (actionName: string) => {
    if (selectedActions.value.includes(actionName)) {
        selectedActions.value = selectedActions.value.filter((a) => a !== actionName);
    } else {
        selectedActions.value.push(actionName);
    }
};
</script>

<template>
    <Head :title="`Cetak Disposisi - ${letter.agenda_number || letter.letter_number || letter.tracking_code}`" />

    <!-- TOOLBAR (Hanya di layar) -->
    <div class="print-toolbar no-print">
        <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="toolbar-info">
                <h6 class="mb-0 fw-bold text-dark">
                    Format Lembar Disposisi: {{ letter.agenda_number || letter.letter_number || letter.tracking_code }}
                </h6>
                <small class="text-muted">
                    Klik checkbox unit/lajur disposisi untuk memilih, klik teks untuk mengedit sebelum mencetak.
                </small>
            </div>
            <div class="toolbar-actions d-flex gap-2">
                <button @click="goBack" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </button>
                <button @click="printPage" class="btn btn-primary-blue btn-sm px-4 fw-bold shadow-sm">
                    <i class="bi bi-printer me-1"></i> Cetak Dokumen
                </button>
            </div>
        </div>
    </div>

    <!-- AREA KERTAS (A4 Standar Kemnaker) -->
    <div class="print-container shadow-lg my-4">
        <!-- HEADER KOP SURAT KEMNAKER -->
        <div class="kop-header d-flex align-items-center mb-2">
            <div class="logo-box me-3 text-center" style="min-width: 70px;">
                <img src="/images/kemnaker_logo.png" alt="KEMNAKER" width="56" height="56" class="d-block mx-auto" />
                <div class="logo-text text-center fw-bold mt-1">KEMNAKER</div>
            </div>
            <div class="header-titles flex-grow-1 text-center pe-5">
                <div class="instansi-name fw-bold">KEMENTERIAN KETENAGAKERJAAN REPUBLIK INDONESIA</div>
                <div class="sekjen-name fw-bold">SEKRETARIAT JENDERAL</div>
            </div>
        </div>

        <!-- MAIN TABLE LEMBAR DISPOSISI -->
        <div class="disposisi-card-table">
            <!-- TITLE ROW -->
            <div class="table-header-title text-center fw-bold">
                LEMBAR DISPOSISI
            </div>

            <!-- METADATA ROWS -->
            <table class="meta-table w-100">
                <tbody>
                    <tr>
                        <td width="18%" class="meta-label">Tanggal Terima Surat</td>
                        <td width="2%" class="text-center">:</td>
                        <td width="30%" class="meta-val" contenteditable="true">
                            {{ formatDate(letter.received_date) }}
                        </td>
                        <td width="16%" class="meta-label border-start">Jenis Surat</td>
                        <td width="2%" class="text-center">:</td>
                        <td width="32%" class="meta-val" contenteditable="true">
                            {{ letter.letter_number_type?.workbook_name || letter.category?.category_name || 'Biasa' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tanggal Surat</td>
                        <td class="text-center">:</td>
                        <td class="meta-val" contenteditable="true">
                            {{ formatDate(letter.letter_date) }}
                        </td>
                        <td class="meta-label border-start">Sifat Surat</td>
                        <td class="text-center">:</td>
                        <td class="meta-val" contenteditable="true">
                            {{ letter.security_level || (letter.priority === 'urgent' ? 'Sangat Segera' : (letter.priority === 'high' ? 'Segera' : 'Biasa')) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-label">Nomor Surat</td>
                        <td class="text-center">:</td>
                        <td colspan="4" class="meta-val fw-bold" contenteditable="true">
                            {{ letter.letter_number || letter.agenda_number || '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-label">Asal Surat</td>
                        <td class="text-center">:</td>
                        <td colspan="4" class="meta-val" contenteditable="true">
                            {{ letter.sender_unit || letter.sender_name || '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-label" valign="top">Perihal</td>
                        <td class="text-center" valign="top">:</td>
                        <td colspan="4" class="meta-val" contenteditable="true" style="line-height: 1.4;">
                            {{ letter.subject }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- UNIT DISPOSISI (2 KOLOM CHECKBOX) -->
            <div class="units-container d-flex border-top border-dark">
                <!-- KOLOM KIRI (10 Unit + 1 Custom Line) -->
                <div class="units-col left-col flex-fill p-2 border-end border-dark">
                    <div
                        v-for="u in leftColumnUnits"
                        :key="u"
                        class="unit-row d-flex align-items-center"
                        @click="toggleUnit(u)"
                    >
                        <div class="custom-checkbox me-2" :class="{ 'checked': isUnitChecked(u) }">
                            <span v-if="isUnitChecked(u)">✓</span>
                        </div>
                        <span class="unit-name">{{ u }}</span>
                    </div>
                </div>

                <!-- KOLOM KANAN (12 Unit) -->
                <div class="units-col right-col flex-fill p-2">
                    <div
                        v-for="u in rightColumnUnits"
                        :key="u"
                        class="unit-row d-flex align-items-center"
                        @click="toggleUnit(u)"
                    >
                        <div class="custom-checkbox me-2" :class="{ 'checked': isUnitChecked(u) }">
                            <span v-if="isUnitChecked(u)">✓</span>
                        </div>
                        <span class="unit-name">{{ u }}</span>
                    </div>
                </div>
            </div>

            <!-- LAJUR DISPOSISI & CATATAN + TTD (BOTTOM SECTION) -->
            <div class="bottom-section d-flex border-top border-dark">
                <!-- LAJUR DISPOSISI (KIRI: 2 SUB-KOLOM CHECKBOX) -->
                <div class="lajur-box border-end border-dark p-2" style="width: 52%;">
                    <div class="lajur-header text-center fw-bold border-bottom border-dark pb-1 mb-2">
                        LAJUR DISPOSISI
                    </div>
                    <div class="d-flex">
                        <!-- Sub Kolom 1 -->
                        <div class="lajur-subcol flex-fill pe-2 border-end border-dark">
                            <div
                                v-for="act in lajurActionsLeft"
                                :key="act"
                                class="lajur-row d-flex align-items-center"
                                @click="toggleAction(act)"
                            >
                                <div class="custom-checkbox me-2" :class="{ 'checked': isActionChecked(act) }">
                                    <span v-if="isActionChecked(act)">✓</span>
                                </div>
                                <span class="lajur-name">{{ act }}</span>
                            </div>
                        </div>

                        <!-- Sub Kolom 2 -->
                        <div class="lajur-subcol flex-fill ps-2">
                            <div
                                v-for="act in lajurActionsRight"
                                :key="act"
                                class="lajur-row d-flex align-items-center"
                                @click="toggleAction(act)"
                            >
                                <div class="custom-checkbox me-2" :class="{ 'checked': isActionChecked(act) }">
                                    <span v-if="isActionChecked(act)">✓</span>
                                </div>
                                <span class="lajur-name">{{ act }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CATATAN & TANDA TANGAN (KANAN) -->
                <div class="notes-and-signature-col flex-fill d-flex flex-column">
                    <!-- KOTAK CATATAN (ATAS) -->
                    <div class="notes-box p-2 flex-grow-1">
                        <div class="notes-header text-center fw-bold mb-1">Catatan</div>
                        <div
                            class="notes-content"
                            contenteditable="true"
                            style="min-height: 80px; font-size: 13px; line-height: 1.4;"
                        >
                            {{ letter.notes || letter.dispositions?.[0]?.instruction || '' }}
                        </div>
                    </div>

                    <!-- KOTAK PEJABAT PENANDATANGAN (BAWAH) -->
                    <div class="signature-box border-top border-dark p-2 text-center">
                        <div class="fw-bold mb-1" style="font-size: 13px;">Sekretaris Jenderal,</div>
                        <div class="signature-space" style="height: 50px;"></div>
                        <div class="pejabat-name fw-bold" style="font-size: 13px;">
                            Dr. Cris Kuntadi, S.E., M.M.
                        </div>
                        <div class="pejabat-nip fw-bold" style="font-size: 12px;">
                            NIP 19690624 199003 1 004
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FOOTER TEKS HALUS -->
        <div class="print-footer-info d-flex justify-content-between align-items-center mt-2 no-print-bg">
            <span class="text-muted">
                Kode Tracking: <strong>{{ letter.tracking_code }}</strong> · Agenda: <strong>{{ letter.agenda_number || '-' }}</strong>
            </span>
            <span class="text-muted">
                Dicetak dari Sistem SiTrack · {{ printDateTime }}
            </span>
        </div>
    </div>
</template>

<style scoped>
/* --- TOOLBAR DI LAYAR --- */
.print-toolbar {
    background: #ffffff;
    padding: 12px 24px;
    border-bottom: 1px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.btn-primary-blue {
    background: linear-gradient(135deg, #1e3a8a, #2563eb);
    color: white;
    border: none;
}

/* --- AREA KERTAS A4 --- */
.print-container {
    width: 210mm;
    min-height: 297mm;
    margin: 20px auto;
    padding: 15mm 18mm;
    background: white;
    font-family: 'Arial', 'Calibri', sans-serif;
    color: black;
    box-sizing: border-box;
}

/* --- KOP SURAT --- */
.kop-header {
    padding-bottom: 4px;
    margin-bottom: 12px;
}

.logo-text {
    font-size: 9px;
    letter-spacing: 0.8px;
    color: #0f172a;
    margin-top: 2px;
}

.instansi-name {
    font-size: 15px;
    letter-spacing: 0.5px;
    color: black;
}

.sekjen-name {
    font-size: 17px;
    letter-spacing: 0.8px;
    color: black;
}

/* --- TABEL UTAMA --- */
.disposisi-card-table {
    border: 2px solid black;
    background: white;
}

.table-header-title {
    font-size: 14px;
    letter-spacing: 0.8px;
    padding: 6px;
    border-bottom: 1.5px solid black;
    background: #ffffff;
}

/* --- METADATA --- */
.meta-table {
    border-collapse: collapse;
}

.meta-table td {
    padding: 4px 6px;
    font-size: 12.5px;
    vertical-align: middle;
}

.meta-label {
    font-size: 12px;
    white-space: nowrap;
}

.meta-val {
    font-size: 12.5px;
}

/* --- UNIT DISPOSISI --- */
.units-col {
    background: white;
}

.unit-row {
    margin-bottom: 3.5px;
    cursor: pointer;
    user-select: none;
}

.unit-name {
    font-size: 11.5px;
    line-height: 1.25;
    color: black;
}

.custom-checkbox {
    width: 15px;
    height: 15px;
    min-width: 15px;
    border: 1.5px solid black;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: bold;
    line-height: 1;
    background: white;
}

.custom-checkbox.checked {
    background: #f1f5f9;
}

.custom-unit-input {
    outline: none;
    min-height: 16px;
}

.custom-unit-input:empty::before {
    content: attr(placeholder);
    color: #888;
}

/* --- LAJUR DISPOSISI --- */
.lajur-header {
    font-size: 12.5px;
    letter-spacing: 0.5px;
}

.lajur-row {
    margin-bottom: 3px;
    cursor: pointer;
    user-select: none;
}

.lajur-name {
    font-size: 11.5px;
    line-height: 1.2;
}

/* --- CATATAN & TTD --- */
.notes-header {
    font-size: 12.5px;
}

.notes-content {
    outline: none;
}

/* --- FOOTER INFO --- */
.print-footer-info {
    font-size: 10px;
    padding-top: 5px;
}

/* --- PRINT MEDIA RULES --- */
@media print {
    .no-print {
        display: none !important;
    }

    body {
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .print-container {
        margin: 0 !important;
        padding: 10mm 12mm !important;
        width: 100% !important;
        min-height: auto !important;
        box-shadow: none !important;
    }

    @page {
        size: A4 portrait;
        margin: 8mm;
    }

    .custom-checkbox {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>