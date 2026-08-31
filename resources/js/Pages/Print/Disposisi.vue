<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Letter } from '@/types';

const props = defineProps<{
    letter: Letter;
}>();

// Fungsi untuk mencetak
const printPage = () => window.print();

// Fungsi untuk kembali
const goBack = () => window.history.back();

// Format waktu cetak untuk footer
const printDateTime = computed(() => {
    return new Date().toLocaleString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).replace(/\./g, ':');
});

// Mengambil daftar penerima disposisi untuk kolom "Kepada"
const recipients = computed(() => {
    if (!props.letter.dispositions || props.letter.dispositions.length === 0) return '-';
    return props.letter.dispositions.map(d => d.to_unit?.unit_name || d.to_name).join(', ');
});
</script>

<template>

    <Head :title="`Cetak Disposisi - ${letter.agenda_number}`" />

    <!-- TOOLBAR (Hanya di layar) -->
    <div class="print-toolbar no-print">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="toolbar-info">
                <h6 class="mb-0 fw-bold text-dark">Format Disposisi: {{ letter.agenda_number }}</h6>
                <small class="text-muted">Klik teks pada kertas untuk mengedit konten sebelum dicetak.</small>
            </div>
            <div class="toolbar-actions d-flex gap-2">
                <button @click="goBack" class="btn btn-outline-dark btn-sm px-3">Kembali</button>
                <button @click="printPage" class="btn btn-primary-blue btn-sm px-4 fw-bold">Cetak</button>
            </div>
        </div>
    </div>

    <!-- AREA KERTAS -->
    <div class="print-container shadow-lg my-4" contenteditable="true">

        <div class="text-center mb-4">
            <h2 class="title-main">LEMBAR DISPOSISI SEKRETARIS JENDERAL</h2>
        </div>

        <table class="disposisi-table">
            <tr>
                <td width="22%" class="fw-bold">Diserahkan pada</td>
                <td width="3%" class="text-center">:</td>
                <td width="75%">{{ letter.received_date ? new Date(letter.received_date).toLocaleDateString('id-ID', {
                    day: '2-digit', month: 'long', year: 'numeric' }) : '-' }}</td>
            </tr>
            <tr>
                <td class="fw-bold">Deadline Surat</td>
                <td class="text-center">:</td>
                <td>-</td>
            </tr>
            <tr>
                <td class="fw-bold">Asal Surat</td>
                <td class="text-center">:</td>
                <td>{{ letter.sender_unit || letter.sender_name }}</td>
            </tr>
            <tr>
                <td class="fw-bold">Kepada</td>
                <td class="text-center">:</td>
                <td>{{ recipients }}</td>
            </tr>
            <tr>
                <td class="fw-bold">Perihal</td>
                <td class="text-center">:</td>
                <td class="fw-bold">{{ letter.subject }}</td>
            </tr>
            <tr>
                <td valign="top" class="fw-bold pt-3">Catatan / Arahan</td>
                <td valign="top" class="text-center pt-3">:</td>
                <td valign="top" class="notes-area">
                    <!-- Area kosong luas untuk arahan tangan atau ketikan -->
                    <div class="min-h-notes">{{ letter.notes }}</div>
                </td>
            </tr>
        </table>

        <!-- FOOTER TEKS HALUS -->
        <div class="print-footer-info mt-3" contenteditable="false">
            Kode tracking: <strong>{{ letter.tracking_code }}</strong> ·
            Dicetak otomatis dari Sistem SiTrack pada {{ printDateTime }}
        </div>
    </div>
</template>

<style scoped>
/* --- TOOLBAR --- */
.print-toolbar {
    background: #ffffff;
    padding: 12px 24px;
    border-bottom: 1px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.btn-primary-blue {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    color: white;
    border: none;
}

/* --- KERTAS --- */
.print-container {
    width: 210mm;
    min-height: 297mm;
    margin: 30px auto;
    padding: 20mm;
    background: white;
    font-family: Arial, Helvetica, sans-serif;
    color: black;
    outline: none;
    cursor: text;
    caret-color: black;
}

.title-main {
    font-size: 18px;
    font-weight: 800;
    text-decoration: underline;
    letter-spacing: 0.5px;
}

/* --- TABEL --- */
.disposisi-table {
    width: 100%;
    border: 2px solid black;
    border-collapse: collapse;
}

.disposisi-table td {
    border: 1px solid black;
    padding: 12px 15px;
    font-size: 15px;
    line-height: 1.5;
}

/* Menghilangkan border kiri/kanan di kolom tengah (titik dua) agar terlihat bersih */
.disposisi-table td:nth-child(2) {
    border-left: none;
    border-right: none;
    padding-left: 0;
    padding-right: 0;
}

.disposisi-table td:nth-child(1) {
    border-right: none;
}

.notes-area {
    height: 450px;
    /* Area catatan dibuat luas sesuai gambar */
    vertical-align: top;
}

.min-h-notes {
    min-height: 100%;
}

/* --- FOOTER --- */
.print-footer-info {
    font-size: 11px;
    color: #666;
    font-style: italic;
}

/* --- PRINT RULES --- */
@media print {
    .no-print {
        display: none !important;
    }

    body {
        background: white !important;
    }

    .print-container {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        box-shadow: none !important;
        cursor: default;
    }

    @page {
        margin: 15mm;
    }
}

/* Hover effect saat di layar untuk menandakan area bisa diedit */
.print-container:hover {
    background-color: #fafafa;
}
</style>