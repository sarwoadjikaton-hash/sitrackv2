<script setup lang="ts">
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import StatusHistoryTimeline from '@/Components/StatusHistoryTimeline.vue';
import { Html5Qrcode } from "html5-qrcode";
import type { Letter } from '@/types';

declare function route(name: string, params?: any): string;

const props = defineProps<{
    tracking: string;
    letter: Letter | null;
    allowedStatuses: string[];
}>();

const mode = ref<'camera' | 'file' | 'manual'>(props.tracking ? 'manual' : 'camera');
const resultCardTab = ref<'form' | 'history'>('form');
const scanInput = ref(props.tracking || '');
const notFound = ref(!!props.tracking && !props.letter);

let html5QrCode: Html5Qrcode | null = null;
let fileQrCode: Html5Qrcode | null = null;

const isCameraRunning = ref(false);
const isCameraLoading = ref(false);
const cameraError = ref<string | null>(null);
const cameras = ref<{ id: string; label: string }[]>([]);
const selectedCameraId = ref<string>('');
const isSecureContext = ref(true);

const isFileScanning = ref(false);
const fileScanError = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const uploadedFilePreview = ref<string | null>(null);
const uploadedFileName = ref<string | null>(null);

const defaultPositionByStatus: Record<string, string> = {
    'Diregistrasi': 'Unit Pengusul',
    'Diterima': 'Tata Usaha Sekjen',
    'Diperiksa Oleh TU Sekjen': 'TU Sekjen',
    'Diperiksa Oleh Kasubag TU Sekjen': 'Kasubag TU Sekjen',
    'Diperiksa Oleh Sekjen': 'Sekretaris Jenderal',
    'Selesai dan Siap Untuk diambil': 'Loket TU Sekjen',
    'Selesai dan Siap Untuk Diambil': 'Loket TU Sekjen',
    'Dokumen Sudah diambil': 'Unit Pengolah',
    'Revisi': 'Unit Pengusul (Perlu Revisi)',
    'Ditolak': 'Unit Pengusul (Ditolak)',
};

// --- FORM UPDATE ---
const form = useForm({
    tracking_code: props.letter?.tracking_code || '',
    status: props.letter?.status || '',
    current_position: props.letter?.current_position || '',
    requested_actions: props.letter?.requested_actions
        ? (typeof props.letter.requested_actions === 'string' ? props.letter.requested_actions.split(', ') : props.letter.requested_actions)
        : [] as string[],
    note: '',
});

watch(() => props.letter, (newVal) => {
    if (newVal) {
        form.tracking_code = newVal.tracking_code;
        form.status = newVal.status;
        form.current_position = newVal.current_position || '';
        form.requested_actions = newVal.requested_actions
            ? (typeof newVal.requested_actions === 'string' ? newVal.requested_actions.split(', ') : newVal.requested_actions)
            : [];
        notFound.value = false;
    } else if (props.tracking) {
        notFound.value = true;
    }
}, { immediate: true });

watch(() => form.status, (newStatus) => {
    if (newStatus && defaultPositionByStatus[newStatus]) {
        form.current_position = defaultPositionByStatus[newStatus];
    }
});

const extractCode = (str: string) => {
    if (!str) return '';
    const m = str.match(/tracking\/([A-Za-z0-9_\-]+)/i);
    if (m) return m[1];
    return str.trim();
};

// --- SEARCH & SCAN ---
const handleSearch = () => {
    const q = extractCode(scanInput.value);
    if (!q) return;
    router.get(route('scan-status.index'), { tracking: q }, { preserveState: false });
};

const onScanSuccess = (decodedText: string) => {
    stopCamera();
    const clean = extractCode(decodedText);
    scanInput.value = clean;
    mode.value = 'manual';
    router.get(route('scan-status.index'), { tracking: clean });
};

const submitUpdate = () => {
    const targetStatus = form.status;
    const trackingCode = props.letter?.tracking_code || form.tracking_code;
    form.post(route('scan-status.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('note');
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    message: `Status naskah ${trackingCode} berhasil diperbarui menjadi "${targetStatus}".`,
                    type: 'success'
                }
            }));
        }
    });
};

// --- CAMERA LOGIC ---
const startCamera = async (cameraId?: string) => {
    if (!html5QrCode) {
        try {
            html5QrCode = new Html5Qrcode("reader");
        } catch (e) {
            console.warn("Could not init Html5Qrcode reader:", e);
            return;
        }
    }
    cameraError.value = null;
    isCameraLoading.value = true;

    try {
        if (isCameraRunning.value) {
            try { await html5QrCode.stop(); } catch (e) { }
            isCameraRunning.value = false;
        }

        const cameraConfig = cameraId
            ? { deviceId: { exact: cameraId } }
            : { facingMode: "environment" };

        await html5QrCode.start(
            cameraConfig,
            {
                fps: 15,
                qrbox: { width: 220, height: 220 },
                aspectRatio: 1.0,
            },
            (decodedText) => onScanSuccess(decodedText),
            () => { /* frame mismatch ignored */ }
        );

        isCameraRunning.value = true;
    } catch (err: any) {
        console.error("Camera start error:", err);
        const errStr = String(err?.message || err || '');
        if (errStr.includes('Permission') || errStr.includes('NotAllowedError')) {
            cameraError.value = "Izin kamera ditolak. Mohon izinkan akses kamera pada setelan browser Anda.";
        } else if (errStr.includes('NotFoundError') || errStr.includes('DevicesNotFoundError')) {
            cameraError.value = "Kamera tidak ditemukan pada perangkat ini.";
        } else if (!window.isSecureContext) {
            cameraError.value = "Koneksi non-HTTPS membatasi streaming kamera. Gunakan menu 'Upload Lembar Pendamping' atau input kode manual.";
        } else {
            cameraError.value = "Gagal mengakses kamera: " + (err?.message || "Pastikan kamera tidak digunakan aplikasi lain.");
        }
    } finally {
        isCameraLoading.value = false;
    }
};

const stopCamera = async () => {
    if (html5QrCode && isCameraRunning.value) {
        try {
            await html5QrCode.stop();
        } catch (e) {
            console.warn("Error stopping camera:", e);
        } finally {
            isCameraRunning.value = false;
        }
    }
};

const toggleCamera = () => {
    if (isCameraRunning.value) {
        stopCamera();
    } else {
        startCamera(selectedCameraId.value || undefined);
    }
};

// --- FILE UPLOAD SCAN ---
const triggerFileInput = () => {
    fileScanError.value = null;
    fileInputRef.value?.click();
};

const processImageFile = async (file: File) => {
    if (!file) return;
    fileScanError.value = null;
    isFileScanning.value = true;
    uploadedFileName.value = file.name;

    try {
        uploadedFilePreview.value = URL.createObjectURL(file);
    } catch (e) {
        uploadedFilePreview.value = null;
    }

    try {
        if (!fileQrCode) {
            fileQrCode = new Html5Qrcode("reader-file-hidden");
        }
        const decodedText = await fileQrCode.scanFile(file, false);
        onScanSuccess(decodedText);
    } catch (err: any) {
        console.error("File scan error:", err);
        fileScanError.value = "QR Code tidak terdeteksi pada berkas ini. Pastikan foto Lembar Pendamping terlihat jelas, fokus pada bagian QR Code, dan tidak buram/terpotong.";
    } finally {
        isFileScanning.value = false;
    }
};

const handleFileUpload = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (!target.files || target.files.length === 0) return;
    const file = target.files[0];
    processImageFile(file);
    target.value = '';
};

const handleDrop = (e: DragEvent) => {
    isDragging.value = false;
    if (!e.dataTransfer || !e.dataTransfer.files || e.dataTransfer.files.length === 0) return;
    const file = e.dataTransfer.files[0];
    if (file.type.startsWith('image/')) {
        processImageFile(file);
    } else {
        fileScanError.value = "Format file tidak didukung. Harap unggah file foto/gambar (JPG, JPEG, PNG, WEBP).";
    }
};

const resetUploadedFile = () => {
    uploadedFilePreview.value = null;
    uploadedFileName.value = null;
    fileScanError.value = null;
};

// --- SAFE BACK NAVIGATION ---
const goBack = () => {
    try {
        stopCamera();
    } catch (e) {}
    
    if (window.history && window.history.length > 1) {
        window.history.back();
    } else {
        router.visit('/tindak-lanjut');
    }
};

onMounted(async () => {
    isSecureContext.value = window.isSecureContext;

    try {
        html5QrCode = new Html5Qrcode("reader");
    } catch (e) {
        console.warn("Could not init Html5Qrcode reader:", e);
    }

    try {
        const devices = await Html5Qrcode.getCameras();
        if (devices && devices.length > 0) {
            cameras.value = devices.map(d => ({ id: d.id, label: d.label || `Kamera ${d.id}` }));
            selectedCameraId.value = devices[0].id;
        }
    } catch (e) {
        console.warn("Could not list cameras:", e);
    }

    if (mode.value === 'camera') {
        startCamera();
    }
});

onUnmounted(async () => {
    await stopCamera();
    if (html5QrCode) {
        try { html5QrCode.clear(); } catch (e) { }
    }
    if (fileQrCode) {
        try { fileQrCode.clear(); } catch (e) { }
    }
});

watch(mode, (newMode) => {
    if (newMode === 'camera') {
        setTimeout(() => startCamera(), 100);
    } else {
        stopCamera();
    }
});
</script>

<template>
    <AppLayout title="Scan & Update Status">
        <Head title="Scan & Update Status Surat" />

        <!-- Hidden input & container for file scanning -->
        <div id="reader-file-hidden" style="display: none;"></div>
        <input ref="fileInputRef" type="file" accept="image/*" class="d-none" @change="handleFileUpload" />

        <div class="max-w-2xl mx-auto space-y-5">
            <!-- Header -->
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div>
                    <h1 class="font-display fw-bold fs-4 text-dark mb-1">Scan &amp; Update Status Surat</h1>
                    <p class="text-muted small mb-0">Pindai QR code dari kamera, upload lembar fisik, atau masukkan kode tracking</p>
                </div>
                <button type="button" @click="goBack" class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 text-dark fw-medium small border bg-white shadow-xs transition hover:bg-light">
                    <i class="bi bi-arrow-left text-muted"></i>
                    <span>Kembali</span>
                </button>
            </div>

            <!-- Mode Switcher (3 Opsi: Buka Kamera, Upload Lembar Pendamping, Input Manual) -->
            <div class="d-flex gap-1 p-1 bg-slate-100 rounded-3 border border-slate-200 flex-wrap flex-sm-nowrap">
                <button
                    type="button"
                    @click="mode = 'camera'"
                    class="d-flex align-items-center justify-content-center gap-2 px-3 py-2 rounded-2 text-sm fw-medium border-0 transition flex-grow-1"
                    :class="mode === 'camera' ? 'bg-white text-dark shadow-xs fw-semibold' : 'text-muted bg-transparent hover:text-dark'"
                >
                    <i class="bi bi-camera-video"></i>
                    <span>Buka Kamera QR</span>
                </button>
                <button
                    type="button"
                    @click="mode = 'file'"
                    class="d-flex align-items-center justify-content-center gap-2 px-3 py-2 rounded-2 text-sm fw-medium border-0 transition flex-grow-1"
                    :class="mode === 'file' ? 'bg-white text-dark shadow-xs fw-semibold' : 'text-muted bg-transparent hover:text-dark'"
                >
                    <i class="bi bi-file-earmark-arrow-up"></i>
                    <span>Upload Lembar Pendamping</span>
                </button>
                <button
                    type="button"
                    @click="mode = 'manual'"
                    class="d-flex align-items-center justify-content-center gap-2 px-3 py-2 rounded-2 text-sm fw-medium border-0 transition flex-grow-1"
                    :class="mode === 'manual' ? 'bg-white text-dark shadow-xs fw-semibold' : 'text-muted bg-transparent hover:text-dark'"
                >
                    <i class="bi bi-keyboard"></i>
                    <span>Input Manual</span>
                </button>
            </div>

            <!-- 1. CAMERA VIEW -->
            <div v-show="mode === 'camera'" class="bg-white rounded-4 border border-slate-200 shadow-sm overflow-hidden">
                <div class="relative bg-slate-900 text-center p-3" style="min-height: 320px;">
                    <!-- Video / Html5QrCode Container -->
                    <div id="reader" class="rounded-3 overflow-hidden mx-auto" style="width: 100%; max-width: 440px; min-height: 260px;"></div>

                    <!-- Non-running fallback overlay if camera not active -->
                    <div v-if="!isCameraRunning && !isCameraLoading" class="d-flex flex-column align-items-center justify-content-center py-5 text-white">
                        <div class="w-16 h-16 rounded-3 bg-slate-800 d-flex align-items-center justify-center mb-3 text-slate-400">
                            <i class="bi bi-camera fs-2"></i>
                        </div>
                        <div class="fw-semibold text-white mb-1">Kamera Belum Aktif</div>
                        <p class="text-white-50 small mb-3">Arahkan kamera ke QR code pada Lembar Cetak Pendamping</p>
                        <button type="button" @click="toggleCamera" class="btn text-white fw-semibold px-4 py-2 rounded-3 shadow-sm border-0" style="background: #2743AF;">
                            <i class="bi bi-camera-video me-1.5"></i> Nyalakan Kamera
                        </button>
                    </div>

                    <!-- Camera Controls -->
                    <div v-if="isCameraRunning || isCameraLoading" class="d-flex align-items-center justify-content-center gap-2 mt-3 flex-wrap">
                        <button type="button" class="btn btn-sm btn-light fw-medium px-3 rounded-2 shadow-xs" :disabled="isCameraLoading" @click="toggleCamera">
                            <span v-if="isCameraLoading" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi" :class="isCameraRunning ? 'bi-stop-fill text-danger' : 'bi-play-fill text-success'"></i>
                            {{ isCameraRunning ? 'Matikan Kamera' : 'Nyalakan Kamera' }}
                        </button>

                        <button type="button" class="btn btn-sm btn-outline-light fw-medium px-3 rounded-2" @click="mode = 'file'">
                            <i class="bi bi-upload me-1"></i>
                            Upload Berkas Fisik
                        </button>

                        <select v-if="cameras.length > 1" v-model="selectedCameraId" class="form-select form-select-sm w-auto bg-dark text-white border-secondary rounded-2" @change="startCamera(selectedCameraId)">
                            <option v-for="cam in cameras" :key="cam.id" :value="cam.id">
                                📷 {{ cam.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Footer tip / switch to file mode -->
                <div class="p-3 bg-slate-50 border-top text-center text-muted small d-flex align-items-center justify-content-center gap-2">
                    <span>Memiliki hasil print lembar pendamping?</span>
                    <button type="button" @click="mode = 'file'" class="btn btn-link p-0 text-decoration-none fw-semibold" style="color: #2743AF;">
                        Upload file / foto lembar pendamping
                    </button>
                </div>
            </div>

            <!-- 2. FILE UPLOAD VIEW (Lembar Pendamping Fisik) -->
            <div v-show="mode === 'file'" class="bg-white rounded-4 border border-slate-200 shadow-sm overflow-hidden p-4">
                <div
                    class="dropzone-box rounded-4 p-4 text-center transition cursor-pointer border-2"
                    :class="{ 'border-primary bg-primary-subtle/10': isDragging, 'border-dashed border-slate-300 hover:border-slate-400 bg-slate-50/50': !isDragging }"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop"
                    @click="triggerFileInput"
                >
                    <!-- Scanning state -->
                    <div v-if="isFileScanning" class="py-5 d-flex flex-column align-items-center justify-content-center">
                        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                        <h6 class="fw-bold text-dark mb-1">Menganalisis QR Code...</h6>
                        <p class="text-muted small mb-0">Sedang membaca kode QR dari foto lembar pendamping Anda</p>
                    </div>

                    <!-- Selected image preview state (before result / on retry) -->
                    <div v-else-if="uploadedFilePreview" class="py-3 d-flex flex-column align-items-center justify-content-center">
                        <div class="position-relative mb-3">
                            <img :src="uploadedFilePreview" alt="Preview Lembar Pendamping" class="rounded-3 shadow-xs border" style="max-height: 200px; max-width: 100%; object-fit: contain;" />
                            <span class="position-absolute top-0 end-0 translate-middle-y badge bg-dark rounded-pill px-2 py-1 shadow-sm small">
                                {{ uploadedFileName }}
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" @click.stop="triggerFileInput" class="btn btn-sm btn-outline-primary rounded-2 fw-semibold">
                                <i class="bi bi-arrow-repeat me-1"></i> Pilih Gambar Lain
                            </button>
                            <button type="button" @click.stop="resetUploadedFile" class="btn btn-sm btn-light border rounded-2 text-muted">
                                <i class="bi bi-x-lg me-1"></i> Hapus
                            </button>
                        </div>
                    </div>

                    <!-- Empty / Idle state -->
                    <div v-else class="py-4 d-flex flex-column align-items-center justify-content-center">
                        <div class="w-16 h-16 rounded-4 bg-primary-subtle text-primary d-flex align-items-center justify-content-center mb-3 shadow-xs">
                            <i class="bi bi-file-earmark-arrow-up fs-2"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1.5">Unggah Foto / Scan Lembar Pendamping</h6>
                        <p class="text-muted small mb-3 max-w-md mx-auto">
                            Tarik dan letakkan file gambar di sini, atau klik tombol di bawah untuk memilih dari perangkat / galeri.
                        </p>
                        <button type="button" class="btn text-white fw-semibold px-4 py-2 rounded-3 shadow-xs border-0" style="background: #2743AF;">
                            <i class="bi bi-folder2-open me-1.5"></i> Pilih File Fisik / Foto
                        </button>
                        <div class="text-muted mt-2.5" style="font-size: 0.75rem;">
                            Mendukung file gambar: JPG, JPEG, PNG, WEBP
                        </div>
                    </div>
                </div>

                <!-- Tips Lembar Pendamping -->
                <div class="mt-3 p-3 bg-slate-50 rounded-3 border border-slate-200/70 d-flex align-items-start gap-2.5">
                    <i class="bi bi-info-circle-fill text-primary flex-shrink-0 mt-0.5"></i>
                    <div class="small text-muted">
                        <strong class="text-dark">Petunjuk Lembar Fisik:</strong> Pastikan QR Code pada lembar cetak pendamping berada dalam pencahayaan yang cukup, tegak, dan tidak terpotong saat difoto/discan.
                    </div>
                </div>

                <!-- Quick Switch to Camera -->
                <div class="text-center mt-3 pt-2 border-top">
                    <span class="text-muted small">Ingin scan langsung menggunakan kamera? </span>
                    <button type="button" @click="mode = 'camera'" class="btn btn-link p-0 text-decoration-none fw-semibold small" style="color: #2743AF;">
                        Buka Kamera QR
                    </button>
                </div>
            </div>

            <!-- Error alerts if camera or file failed -->
            <div v-if="cameraError && mode === 'camera'" class="alert alert-warning border-0 small py-2.5 px-3 rounded-3 d-flex align-items-start gap-2 mb-0">
                <i class="bi bi-exclamation-triangle-fill text-warning flex-shrink-0 mt-0.5"></i>
                <div class="flex-grow-1">
                    <div class="fw-bold">Kendala Kamera:</div>
                    <div>{{ cameraError }}</div>
                    <div class="mt-1">
                        <button type="button" @click="mode = 'file'" class="btn btn-sm btn-link p-0 text-decoration-none fw-semibold text-warning-emphasis">
                            → Gunakan opsi Upload Lembar Pendamping
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="fileScanError && mode === 'file'" class="alert alert-danger border-0 small py-2.5 px-3 rounded-3 d-flex align-items-start gap-2 mb-0">
                <i class="bi bi-x-circle-fill text-danger flex-shrink-0 mt-0.5"></i>
                <div class="flex-grow-1">
                    <div class="fw-bold">Gagal Mendeteksi QR:</div>
                    <div>{{ fileScanError }}</div>
                    <div class="mt-2 d-flex gap-2">
                        <button type="button" @click="triggerFileInput" class="btn btn-sm btn-danger px-3 rounded-2 fw-semibold">
                            <i class="bi bi-arrow-clockwise me-1"></i> Coba Gambar Lain
                        </button>
                        <button type="button" @click="mode = 'camera'" class="btn btn-sm btn-outline-danger px-3 rounded-2 fw-medium">
                            <i class="bi bi-camera me-1"></i> Beralih ke Kamera
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3. MANUAL INPUT CARD -->
            <div v-show="mode === 'manual'" class="bg-white rounded-4 border border-slate-200 shadow-sm p-4">
                <label class="d-block text-xs fw-bold text-muted text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                    Kode Tracking / Nomor Agenda
                </label>
                <div class="d-flex gap-2">
                    <input
                        v-model="scanInput"
                        type="text"
                        placeholder="Masukkan kode tracking atau nomor agenda..."
                        class="form-control rounded-3 py-2.5 px-3 font-mono text-sm border-slate-200 focus:border-primary"
                        @keydown.enter.prevent="handleSearch"
                        autofocus
                    />
                    <button
                        type="button"
                        @click="handleSearch"
                        class="btn text-white px-4 py-2.5 rounded-3 fw-semibold d-flex align-items-center gap-2 flex-shrink-0 border-0 shadow-xs"
                        style="background: #2743AF;"
                    >
                        <i class="bi bi-search"></i>
                        <span>Cari</span>
                    </button>
                </div>
            </div>

            <!-- NOT FOUND ALERT -->
            <div v-if="notFound && !letter" class="bg-white rounded-4 border border-danger-subtle shadow-sm p-4 d-flex align-items-center gap-3">
                <div class="w-10 h-10 rounded-3 bg-danger-subtle text-danger d-flex align-items-center justify-center flex-shrink-0">
                    <i class="bi bi-exclamation-circle-fill fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark text-sm">Surat tidak ditemukan</div>
                    <div class="text-muted small mt-0.5">
                        Kode tracking <span class="font-mono fw-semibold text-dark">{{ scanInput }}</span> tidak terdaftar dalam basis data.
                    </div>
                </div>
            </div>

            <!-- FOUND RESULT & STATUS UPDATE CARD -->
            <div v-if="letter" class="bg-white rounded-4 border border-slate-200 shadow-sm overflow-hidden">
                <!-- Letter Info Header -->
                <div class="p-4 border-bottom border-slate-100 bg-slate-50">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                        <div>
                            <span class="font-mono text-xs text-muted fw-bold">{{ letter.tracking_code || letter.agenda_number }}</span>
                            <h3 class="font-display fw-bold text-dark fs-6 mt-1 mb-0 leading-snug">{{ letter.subject }}</h3>
                        </div>
                        <StatusBadge :status="letter.status" />
                    </div>
                    <div class="d-flex align-items-center gap-3 text-muted small mt-2 flex-wrap" style="font-size: 0.78rem;">
                        <span class="d-flex align-items-center gap-1.5">
                            <i class="bi bi-geo-alt-fill text-primary"></i>
                            <span>Posisi: <strong class="text-dark">{{ letter.current_position || 'Belum Ditentukan' }}</strong></span>
                        </span>
                        <span>&bull;</span>
                        <span>Pengirim: <strong class="text-dark">{{ letter.sender_unit || letter.sender_name }}</strong></span>
                    </div>
                </div>

                <!-- Result Card Navigation Tabs -->
                <div class="px-4 pt-3 pb-0 bg-white border-bottom">
                    <div class="d-flex gap-1 p-1 bg-slate-100 rounded-3 border border-slate-200">
                        <button
                            type="button"
                            @click="resultCardTab = 'form'"
                            class="btn btn-sm d-flex align-items-center justify-content-center gap-1.5 flex-grow-1 border-0 py-1.5 rounded-2 font-medium"
                            :class="resultCardTab === 'form' ? 'bg-white text-dark shadow-xs fw-bold' : 'text-muted bg-transparent'"
                        >
                            <i class="bi bi-pencil-square text-primary"></i>
                            <span>Form Pembaruan Status</span>
                        </button>
                        <button
                            type="button"
                            @click="resultCardTab = 'history'"
                            class="btn btn-sm d-flex align-items-center justify-content-center gap-1.5 flex-grow-1 border-0 py-1.5 rounded-2 font-medium"
                            :class="resultCardTab === 'history' ? 'bg-white text-dark shadow-xs fw-bold' : 'text-muted bg-transparent'"
                        >
                            <i class="bi bi-clock-history text-primary"></i>
                            <span>Riwayat Perjalanan ({{ letter.status_logs?.length || 0 }})</span>
                        </button>
                    </div>
                </div>

                <!-- TAB 1: Update Status Form -->
                <form v-if="resultCardTab === 'form'" @submit.prevent="submitUpdate" class="p-4 space-y-4">
                    <div>
                        <label class="form-label fw-semibold text-dark small mb-1.5">Perbarui ke Status</label>
                        <SearchableSelect
                            v-model="form.status"
                            :options="allowedStatuses.map(s => ({ value: s, label: s }))"
                            placeholder="Pilih Status..."
                            size="md"
                        />
                    </div>

                    <div>
                        <label class="form-label fw-semibold text-dark small mb-1.5">Posisi Berkas Sekarang</label>
                        <input
                            v-model="form.current_position"
                            type="text"
                            placeholder="Contoh: Meja Kasubag TU / Sekretaris Jenderal"
                            class="form-control rounded-3 text-sm py-2 px-3 border-slate-200"
                            required
                        />
                    </div>

                    <div>
                        <label class="form-label fw-semibold text-dark small mb-1.5">Catatan Pembaruan</label>
                        <textarea
                            v-model="form.note"
                            rows="2"
                            placeholder="Keterangan singkat hasil disposisi/paraf..."
                            class="form-control rounded-3 text-sm py-2 px-3 border-slate-200"
                        ></textarea>
                    </div>

                    <button
                        type="submit"
                        class="w-100 btn text-white py-2.5 rounded-3 fw-semibold d-flex align-items-center justify-content-center gap-2 border-0 shadow-xs mt-3"
                        style="background: #2743AF;"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="spinner-border spinner-border-sm"></span>
                        <i v-else class="bi bi-check2-circle fs-6"></i>
                        <span>Simpan Pembaruan Status</span>
                    </button>
                </form>

                <!-- TAB 2: Riwayat Perjalanan Berkas (Timeline) -->
                <div v-else-if="resultCardTab === 'history'" class="p-4">
                    <StatusHistoryTimeline
                        :logs="letter.status_logs"
                        :current-status="letter.status"
                        :current-position="letter.current_position"
                        :created-date="letter.created_at"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(#reader) {
    border: none !important;
    background: transparent !important;
}

:deep(#reader video) {
    border-radius: 12px;
    max-height: 280px;
    object-fit: cover;
    margin: 0 auto;
}

:deep(#reader__scan_region) {
    background: transparent !important;
}

.dropzone-box {
    border-style: dashed;
    border-color: #cbd5e1;
    background: #f8fafc;
    transition: all 0.2s ease-in-out;
}

.dropzone-box:hover {
    border-color: #2743af;
    background: #f1f5f9;
}
</style>