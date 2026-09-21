<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, nextTick } from 'vue';
import { Letter } from '@/types';
import axios from 'axios';

const props = defineProps<{
    letter: Letter;
    qrCodeBase64: string;
    signaturePath?: string | null;
    signatureBase64?: string | null;
    receiverName?: string | null;
}>();

// Checkboxes state
const tempActions = ref<string[]>(
    props.letter.requested_actions ? props.letter.requested_actions.split(',').map((s) => s.trim()) : ['Mohon Tanda Tangan']
);

const toggleAction = (action: string) => {
    if (tempActions.value.includes(action)) {
        tempActions.value = tempActions.value.filter((a) => a !== action);
    } else {
        tempActions.value.push(action);
    }
};

const isChecked = (action: string) => tempActions.value.includes(action);

// --- Digital Signature State ---
const currentSignatureSrc = ref<string | null>(
    props.signatureBase64 ||
    (props.signaturePath ? (props.signaturePath.startsWith('data:') ? props.signaturePath : `/lampiran/view/${props.signaturePath}`) : null)
);
const receiverName = ref('');
const isEditingSignature = ref(!currentSignatureSrc.value);

const canvasRef = ref<HTMLCanvasElement | null>(null);
const isDrawing = ref(false);
const hasSignature = ref(false);
const isSaving = ref(false);
const saveSuccessMessage = ref('');

let ctx: CanvasRenderingContext2D | null = null;

const initCanvas = () => {
    if (!canvasRef.value) return;
    const canvas = canvasRef.value;
    ctx = canvas.getContext('2d');
    if (!ctx) return;

    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
};

const openSignatureCanvas = () => {
    isEditingSignature.value = true;
    hasSignature.value = false;
    nextTick(() => {
        initCanvas();
    });
};

const cancelEditSignature = () => {
    if (currentSignatureSrc.value) {
        isEditingSignature.value = false;
    }
};

const startDrawing = (e: MouseEvent | TouchEvent) => {
    if (!ctx || !canvasRef.value) return;
    isDrawing.value = true;
    hasSignature.value = true;
    const rect = canvasRef.value.getBoundingClientRect();
    const x = 'touches' in e ? e.touches[0].clientX - rect.left : e.clientX - rect.left;
    const y = 'touches' in e ? e.touches[0].clientY - rect.top : e.clientY - rect.top;
    ctx.beginPath();
    ctx.moveTo(x, y);
};

const draw = (e: MouseEvent | TouchEvent) => {
    if (!isDrawing.value || !ctx || !canvasRef.value) return;
    const rect = canvasRef.value.getBoundingClientRect();
    const x = 'touches' in e ? e.touches[0].clientX - rect.left : e.clientX - rect.left;
    const y = 'touches' in e ? e.touches[0].clientY - rect.top : e.clientY - rect.top;
    ctx.lineTo(x, y);
    ctx.stroke();
};

const stopDrawing = () => {
    if (!isDrawing.value) return;
    isDrawing.value = false;
};

const clearSignature = () => {
    if (!ctx || !canvasRef.value) return;
    ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
    hasSignature.value = false;
};

const saveDigitalSignature = async () => {
    if (!canvasRef.value || !hasSignature.value) {
        alert('Silakan buat tanda tangan terlebih dahulu pada kotak tanda tangan.');
        return;
    }

    isSaving.value = true;
    saveSuccessMessage.value = '';

    try {
        const dataUrl = canvasRef.value.toDataURL('image/png');
        const res = await axios.post(`/cetak/pendamping/${props.letter.id}/signature`, {
            signature_base64: dataUrl,
            receiver_name: receiverName.value,
            auto_update_status: true,
        });

        if (res.data.ok) {
            currentSignatureSrc.value = dataUrl;
            isEditingSignature.value = false;
            saveSuccessMessage.value = '✓ Tanda tangan berhasil disimpan & status diubah menjadi "Dokumen Sudah diambil"!';
            setTimeout(() => {
                saveSuccessMessage.value = '';
            }, 5000);
        }
    } catch (err: any) {
        alert('Gagal menyimpan tanda tangan: ' + (err.response?.data?.message || err.message));
    } finally {
        isSaving.value = false;
    }
};

onMounted(() => {
    if (isEditingSignature.value) {
        initCanvas();
    }
});

const formatDate = (dateStr?: string | null) => {
    if (!dateStr) return '-';
    if (typeof dateStr === 'string' && dateStr.includes('-')) {
        const parts = dateStr.split('T')[0].split('-');
        if (parts.length === 3) {
            const dateObj = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
            return dateObj.toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            });
        }
    }
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

const printPage = () => window.print();
const goBack = () => window.history.back();
</script>

<template>
    <Head :title="`Cetak Lembar Pendamping - ${letter.tracking_code}`" />

    <!-- Print & Action Toolbar -->
    <div class="print-toolbar no-print">
        <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="toolbar-info">
                <h6 class="mb-0 fw-bold text-dark">Format Pendamping: {{ letter.agenda_number || letter.tracking_code }}</h6>
                <small class="text-muted">
                    <span v-if="currentSignatureSrc && !isEditingSignature" class="text-success fw-semibold">✓ Lembar Pendamping sudah bertanda tangan / diparaf. </span>
                    <span v-else>Tanda tangani langsung di layar, lalu klik Simpan atau Cetak.</span>
                </small>
            </div>
            <div class="toolbar-actions d-flex align-items-center gap-2 flex-wrap">
                <template v-if="isEditingSignature">
                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm"
                        title="Hapus coretan tanda tangan"
                        @click="clearSignature"
                    >
                        <i class="bi bi-eraser me-1"></i> Hapus Coretan
                    </button>
                    <button
                        v-if="currentSignatureSrc"
                        type="button"
                        class="btn btn-outline-secondary btn-sm"
                        @click="cancelEditSignature"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="btn btn-success btn-sm fw-bold px-3 d-flex align-items-center gap-1"
                        :disabled="isSaving || !hasSignature"
                        @click="saveDigitalSignature"
                    >
                        <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="bi bi-check2-circle"></i>
                        Simpan TTD & Ambil Dokumen
                    </button>
                </template>
                <template v-else>
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm"
                        @click="openSignatureCanvas"
                    >
                        <i class="bi bi-pen me-1"></i> Ubah / TTD Ulang
                    </button>
                </template>
                <button @click="printPage" class="btn btn-primary-blue btn-sm px-4 fw-bold">
                    <i class="bi bi-printer me-1"></i> Cetak Dokumen
                </button>
                <button @click="goBack" class="btn btn-outline-secondary btn-sm px-3">
                    Kembali
                </button>
            </div>
        </div>

        <div v-if="saveSuccessMessage" class="alert alert-success py-2 px-3 mb-0 mt-2 text-center fw-bold small">
            {{ saveSuccessMessage }}
        </div>
    </div>

    <!-- Area Kertas A5 -->
    <div class="print-container shadow-lg my-4">
        <div class="header-box">
            <h2 class="text-center fw-bold mb-0">KEMENTERIAN KETENAGAKERJAAN REPUBLIK INDONESIA</h2>
            <h2 class="text-center fw-bold mb-0">SUB BAGIAN TATA USAHA SEKJEN, SAHLI, STAFSUS</h2>
        </div>

        <table class="main-table">
            <tr>
                <td width="20%">Asal Surat</td>
                <td width="2%">:</td>
                <td width="53%" contenteditable="true">{{ letter.sender_unit || letter.sender_name }}</td>
                <td width="25%" class="border-left text-center" contenteditable="true">
                    {{ formatDate(letter.letter_date || letter.received_date) }}
                </td>
            </tr>
            <tr>
                <td>Jenis Naskah Dinas</td>
                <td>:</td>
                <td colspan="2" contenteditable="true">{{ letter.letter_number_type?.type_name || letter.letter_number_type?.workbook_name || letter.category?.category_name || '-' }}</td>
            </tr>
            <tr>
                <td>Agenda Nomor</td>
                <td>:</td>
                <td colspan="2" class="fw-bold font-monospace" contenteditable="true">{{ letter.agenda_number || '-' }}</td>
            </tr>
            <tr>
                <td valign="top">Hal</td>
                <td valign="top">:</td>
                <td colspan="2" valign="top" contenteditable="true">{{ letter.subject }}</td>
            </tr>
        </table>

        <div class="middle-section">
            <!-- Checkbox Area -->
            <div class="checkbox-area">
                <div class="row">
                    <div class="col-6">
                        <div class="check-item" @click="toggleAction('Mohon Tanda Tangan')">
                            <i class="bi" :class="isChecked('Mohon Tanda Tangan') ? 'bi-check-square-fill text-primary' : 'bi-square'"></i>
                            <span class="ms-1">Mohon Tanda Tangan</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="check-item" @click="toggleAction('Mohon Paraf')">
                            <i class="bi" :class="isChecked('Mohon Paraf') ? 'bi-check-square-fill text-primary' : 'bi-square'"></i>
                            <span class="ms-1">Mohon Paraf</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Receiver Signature Area (With Existing TTD Image or Canvas) -->
            <div class="receiver-area">
                <p class="mb-1 text-center small fw-bold" style="font-size: 9.5px;">
                    Yang Menerima Surat :
                </p>

                <!-- If already signed and not currently drawing new one -->
                <div v-if="currentSignatureSrc && !isEditingSignature" class="signature-display-box position-relative">
                    <img
                        :src="currentSignatureSrc"
                        alt="Tanda Tangan Penerima"
                        class="signature-img"
                        @error="isEditingSignature = true"
                    />
                </div>

                <!-- Interactive Signature Canvas (When signing / re-signing) -->
                <div v-else class="signature-canvas-box position-relative">
                    <canvas
                        ref="canvasRef"
                        width="150"
                        height="60"
                        class="signature-canvas"
                        @mousedown="startDrawing"
                        @mousemove="draw"
                        @mouseup="stopDrawing"
                        @mouseleave="stopDrawing"
                        @touchstart.passive="startDrawing"
                        @touchmove.passive="draw"
                        @touchend.passive="stopDrawing"
                    ></canvas>
                    <span v-if="!hasSignature" class="signature-hint no-print">
                        Goreskan TTD di sini
                    </span>
                </div>

                <div class="text-center mt-1">
                    <p class="mb-0 text-center small text-dark" style="font-size: 9px;" contenteditable="true" @input="receiverName = ($event.target as HTMLElement).innerText">
                        ( {{ receiverName ? receiverName : '....................................' }} )
                    </p>
                </div>
            </div>
        </div>

        <div class="notes-section">
            <p class="fw-bold mb-1" style="font-size: 9.5px;">Catatan / Tindak Lanjut Sekjen :</p>
            <div class="notes-content" contenteditable="true">{{ letter.notes || '' }}</div>
        </div>

        <!-- Metadata Bawah -->
        <div class="bottom-info mt-2">
            <table class="border-0 w-100">
                <tr>
                    <td width="20%">Jenis Naskah</td>
                    <td width="2%">:</td>
                    <td contenteditable="true">{{ letter.letter_number_type?.type_name || letter.letter_number_type?.workbook_name || letter.category?.category_name || '-' }}</td>
                </tr>
                <tr>
                    <td>Nomor Surat</td>
                    <td>:</td>
                    <td class="font-monospace fw-bold" contenteditable="true">{{ letter.letter_number || '-' }}</td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>:</td>
                    <td contenteditable="true">
                        {{ formatDate(letter.letter_date || letter.received_date) }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="qr-footer mt-2">
            <div class="d-flex align-items-center border border-dark p-2">
                <div class="qr-img me-3">
                    <img :src="qrCodeBase64" width="56" height="56" alt="QR Code" />
                </div>
                <div class="qr-text small" style="font-size: 9.5px; line-height: 1.25;">
                    <p class="mb-0 fw-bold">Scan QR untuk Update Status & Tracking Surat</p>
                    <p class="mb-0">Kode Resi: <strong class="font-monospace">{{ letter.tracking_code }}</strong></p>
                    <p class="mb-0 text-muted">Aplikasi SiTrack - TU SEKJEN</p>
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

.btn-primary-blue {
    background-color: #0284c7;
    color: white;
    border: none;
}

.btn-primary-blue:hover {
    background-color: #0369a1;
    color: white;
}

/* KERTAS A5 */
.print-container {
    width: 148mm;
    min-height: 210mm;
    margin: 20px auto;
    padding: 6mm 8mm;
    background: white;
    font-family: Arial, sans-serif;
    color: black;
    cursor: text;
    caret-color: black;
    outline: none;
    position: relative;
    box-sizing: border-box;
}

[contenteditable="true"] {
    min-height: 1em;
    cursor: text !important;
}

[contenteditable="true"]:hover {
    background: #f8fafc;
}

/* AREA CHECKBOX */
.checkbox-area {
    flex: 3;
    padding: 10px;
    border-right: 1.5px solid black;
    background-color: #fff;
    cursor: default;
}

.check-item {
    font-size: 9.5px;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
}

.check-item:hover {
    color: #0284c7;
}

.check-item i {
    font-size: 14px;
}

/* STYLE TABEL & GARIS */
.header-box {
    border: 1.5px solid black;
    border-bottom: none;
    padding: 6px;
}

.header-box h2 {
    font-size: 10.5px;
    line-height: 1.3;
}

.main-table {
    width: 100%;
    border: 1.5px solid black;
    border-collapse: collapse;
}

.main-table td {
    border: 1px solid black;
    padding: 3.5px 5px;
    font-size: 9px;
}

.middle-section {
    display: flex;
    border: 1.5px solid black;
    border-top: none;
}

.receiver-area {
    flex: 1.4;
    padding: 6px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
}

.signature-canvas-box {
    width: 150px;
    height: 60px;
    border: 1px dashed #94a3b8;
    background: #fafafa;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: crosshair;
}

.signature-display-box {
    width: 150px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
}

.signature-img {
    max-width: 145px;
    max-height: 55px;
    object-fit: contain;
}

.signature-canvas {
    width: 150px;
    height: 60px;
    touch-action: none;
}

.signature-hint {
    position: absolute;
    font-size: 9px;
    color: #94a3b8;
    pointer-events: none;
    user-select: none;
}

.receiver-input {
    width: 140px;
    text-align: center;
    background: transparent;
    outline: none;
}

.receiver-input:focus {
    border-bottom: 1px solid #0284c7 !important;
}

.notes-section {
    border: 1.5px solid black;
    border-top: none;
    padding: 6px 8px;
    min-height: 85px;
}

.notes-content {
    min-height: 65px;
    font-size: 9px;
    outline: none;
}

.bottom-info td {
    padding: 1.5px 4px;
    font-size: 8.5px;
}

@media print {
    @page {
        size: A5 portrait;
        margin: 4mm 5mm;
    }

    .no-print {
        display: none !important;
    }

    .print-container {
        margin: 0 !important;
        padding: 4mm 6mm !important;
        width: 100% !important;
        min-height: auto !important;
        box-shadow: none !important;
    }

    .signature-canvas-box {
        border: none !important;
        background: transparent !important;
    }

    [contenteditable="true"]:hover {
        background: transparent !important;
    }
}
</style>