<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { Letter } from '@/types';

const props = defineProps<{
    letter: Letter;
    qrCodeBase64: string;
}>();

// Buat state lokal agar checkbox bisa diklik (interaktif)
const tempActions = ref<string[]>(
    props.letter.requested_actions ? props.letter.requested_actions.split(', ') : []
);

const toggleAction = (action: string) => {
    if (tempActions.value.includes(action)) {
        tempActions.value = tempActions.value.filter(a => a !== action);
    } else {
        tempActions.value.push(action);
    }
};

const isChecked = (action: string) => tempActions.value.includes(action);

const printPage = () => window.print();
const goBack = () => window.history.back();
</script>

<template>

    <Head :title="`Cetak Lembar Pendamping - ${letter.tracking_code}`" />

    <div class="print-toolbar no-print">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="toolbar-info">
                <h6 class="mb-0 fw-bold text-dark">Format Pendamping: {{ letter.agenda_number }}</h6>
                <small class="text-muted">Klik teks untuk edit, klik kotak untuk centang/uncheck.</small>
            </div>
            <div class="toolbar-actions d-flex gap-2">
                <button @click="goBack" class="btn btn-outline-dark btn-sm px-3">Kembali</button>
                <button @click="printPage" class="btn btn-teal btn-sm px-4 fw-bold">Cetak</button>
            </div>
        </div>
    </div>

    <!-- Area Kertas -->
    <div class="print-container shadow-lg my-4" contenteditable="true">

        <div class="header-box">
            <h2 class="text-center fw-bold mb-0">KEMENTERIAN KETENAGAKERJAAN REPUBLIK INDONESIA</h2>
            <h2 class="text-center fw-bold">SUB BAGIAN TATA USAHA SEKJEN, SAHLI, STAFSUS</h2>
        </div>

        <table class="main-table">
            <tr>
                <td width="20%">Asal Surat</td>
                <td width="2%">:</td>
                <td width="53%">{{ letter.sender_unit || letter.sender_name }}</td>
                <td width="25%" class="border-left text-center">
                    {{ letter.received_date ? new Date(letter.received_date).toLocaleDateString('id-ID', {
                        day:
                            '2-digit', month: 'long', year: 'numeric'
                    }) : '-' }}
                </td>
            </tr>
            <tr>
                <td>Jenis Naskah Dinas</td>
                <td>:</td>
                <td colspan="2">{{ letter.category?.category_name || 'ND/Memo' }}</td>
            </tr>
            <tr>
                <td>Agenda Nomor</td>
                <td>:</td>
                <td colspan="2" class="fw-bold">{{ letter.agenda_number }}</td>
            </tr>
            <tr>
                <td valign="top">Hal</td>
                <td valign="top">:</td>
                <td colspan="2" valign="top">{{ letter.subject }}</td>
            </tr>
        </table>

        <div class="middle-section">
            <!-- Checkbox Area -->
            <div class="checkbox-area" contenteditable="false"> <!-- Kita kunci agar ikon tidak terhapus saat ngetik -->
                <div class="row">
                    <div class="col-6">
                        <div class="check-item" @click="toggleAction('Mohon Paraf')">
                            <i class="bi" :class="isChecked('Mohon Paraf') ? 'bi-check-square-fill' : 'bi-square'"></i>
                            Mohon Paraf
                        </div>
                        <div class="check-item" @click="toggleAction('Informasi')">
                            <i class="bi" :class="isChecked('Informasi') ? 'bi-check-square-fill' : 'bi-square'"></i>
                            Informasi
                        </div>
                        <div class="check-item" @click="toggleAction('Mohon Arahan')">
                            <i class="bi" :class="isChecked('Mohon Arahan') ? 'bi-check-square-fill' : 'bi-square'"></i>
                            Mohon Arahan
                        </div>
                        <div class="check-item" @click="toggleAction('Mohon Persetujuan')">
                            <i class="bi"
                                :class="isChecked('Mohon Persetujuan') ? 'bi-check-square-fill' : 'bi-square'"></i>
                            Mohon Persetujuan
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="check-item" @click="toggleAction('Mohon Tanda Tangan')">
                            <i class="bi"
                                :class="isChecked('Mohon Tanda Tangan') ? 'bi-check-square-fill' : 'bi-square'"></i>
                            Mohon Tanda Tangan
                        </div>
                        <div class="check-item" @click="toggleAction('Aksi')">
                            <i class="bi" :class="isChecked('Aksi') ? 'bi-check-square-fill' : 'bi-square'"></i> Aksi
                        </div>
                        <div class="check-item" @click="toggleAction('Mohon KepuTUSan')">
                            <i class="bi"
                                :class="isChecked('Mohon KepuTUSan') ? 'bi-check-square-fill' : 'bi-square'"></i> Mohon
                            KepuTUSan
                        </div>
                    </div>
                </div>
            </div>
            <div class="receiver-area">
                <p class="mb-0 text-center small fw-bold">Yang Menerima <br> Surat :</p>
                <div class="signature-space"></div>
                <p class="mb-0 text-center small">(....................................)</p>
            </div>
        </div>

        <div class="notes-section" contenteditable="true">
            <p class="fw-bold mb-1">Catatan / Tindak Lanjut Sekjen :</p>
            <div class="notes-content">{{ letter.notes }}</div>
        </div>

        <!-- Metadata Bawah -->
        <div class="bottom-info mt-3" contenteditable="true">
            <table class="border-0 w-100">
                <tr>
                    <td width="15%">Jenis Naskah</td>
                    <td width="2%">:</td>
                    <td>{{ letter.category?.category_name || 'ND/Memo' }}</td>
                </tr>
                <tr>
                    <td>Nomor</td>
                    <td>:</td>
                    <td>{{ letter.letter_number || '-' }}</td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>:</td>
                    <td>{{ letter.received_date ? new Date(letter.received_date).toLocaleDateString('id-ID', {
                        day:
                            '2-digit', month: 'long', year: 'numeric'
                    }) : '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="qr-footer mt-4" contenteditable="false">
            <div class="d-flex align-items-center border border-dark p-2">
                <div class="qr-img me-3">
                    <div class="qr-img me-3">
                        <img :src="qrCodeBase64" width="80" height="80">
                    </div>
                </div>
                <div class="qr-text small" style="font-size: 11px; line-height: 1.2;">
                    <p class="mb-0 fw-bold">Scan QR untuk Update Status Surat</p>
                    <p class="mb-0">Kode Tracking: <strong>{{ letter.tracking_code }}</strong></p>
                    <p class="mb-0 text-muted">Petugas wajib login terlebih dahulu.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Toolbar */
.print-toolbar {
    background: #ffffff;
    padding: 12px 24px;
    border-bottom: 1px solid #dee2e6;
    position: sticky;
    top: 0;
    z-index: 2000;
}

.btn-teal {
    background-color: #38a89d;
    color: white;
    border: none;
}

.btn-teal:hover {
    background-color: #2d8a81;
    color: white;
}

/* KERTAS */
.print-container {
    width: 210mm;
    margin: 30px auto;
    padding: 15mm;
    background: white;
    font-family: Arial, sans-serif;
    color: black;
    /* PERBAIKAN KURSOR */
    cursor: text !important;
    caret-color: black !important;
    outline: none;
    position: relative;
}

/* Memastikan teks yang bisa diedit punya kursor yang jelas */
[contenteditable="true"] {
    min-height: 1em;
    cursor: text !important;
}

/* AREA CHECKBOX */
.checkbox-area {
    flex: 3;
    padding: 15px;
    border-right: 2px solid black;
    background-color: #fff;
    cursor: default;
    /* Area ini tidak untuk ngetik, tapi klik */
}

.check-item {
    font-size: 13px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    cursor: pointer;
    /* Memberi tanda bisa diklik */
    user-select: none;
}

.check-item:hover {
    color: #2563eb;
}

.check-item i {
    margin-right: 10px;
    font-size: 18px;
    color: #000;
}

/* STYLE TABEL & GARIS */
.header-box {
    border: 2px solid black;
    border-bottom: none;
    padding: 10px;
}

.header-box h2 {
    font-size: 16px;
    line-height: 1.4;
}

.main-table {
    width: 100%;
    border: 2px solid black;
    border-collapse: collapse;
}

.main-table td {
    border: 1px solid black;
    padding: 8px;
    font-size: 14px;
}

.middle-section {
    display: flex;
    border: 2px solid black;
    border-top: none;
}

.receiver-area {
    flex: 1.2;
    padding: 10px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.signature-space {
    height: 90px;
}

.notes-section {
    border: 2px solid black;
    border-top: none;
    padding: 10px;
    min-height: 200px;
}

@media print {
    .no-print {
        display: none !important;
    }

    .print-container {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        box-shadow: none !important;
    }

    /* Matikan warna hover saat print */
    .print-container {
        background: white !important;
    }

    .check-item i {
        color: black !important;
    }
}
</style>