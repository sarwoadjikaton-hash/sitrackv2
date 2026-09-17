<script setup lang="ts">
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Html5Qrcode } from "html5-qrcode";
import type { Letter } from '@/types';

declare function route(name: string, params?: any): string;

const props = defineProps<{
    tracking: string;
    letter: Letter | null;
    allowedStatuses: string[];
}>();

const manualId = ref(props.tracking || '');
let html5QrCode: Html5Qrcode | null = null;
let fileQrCode: Html5Qrcode | null = null;

const isCameraRunning = ref(false);
const isCameraLoading = ref(false);
const cameraError = ref<string | null>(null);
const cameras = ref<{ id: string; label: string }[]>([]);
const selectedCameraId = ref<string>('');
const isSecureContext = ref(true);
const currentOrigin = ref('');

const isFileScanning = ref(false);
const fileScanError = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

// --- LOGIKA FORM UPDATE ---
const form = useForm({
    tracking_code: props.letter?.tracking_code || '',
    status: props.letter?.status || '',
    current_position: props.letter?.current_position || '',
    requested_actions: props.letter?.requested_actions
        ? (typeof props.letter.requested_actions === 'string' ? props.letter.requested_actions.split(', ') : props.letter.requested_actions)
        : [],
    note: '',
});

// Update form otomatis jika surat ditemukan (hasil search/scan)
watch(() => props.letter, (newVal) => {
    if (newVal) {
        form.tracking_code = newVal.tracking_code;
        form.status = newVal.status;
        form.current_position = newVal.current_position || '';
        form.requested_actions = newVal.requested_actions
            ? (typeof newVal.requested_actions === 'string' ? newVal.requested_actions.split(', ') : newVal.requested_actions)
            : [];
    }
}, { immediate: true });

const actionOptions = [
    'Mohon Paraf', 'Mohon Tanda Tangan'
];

const extractCode = (str: string) => {
    if (!str) return '';
    const m = str.match(/tracking\/([A-Za-z0-9_\-]+)/i);
    if (m) return m[1];
    return str.trim();
};

// --- LOGIKA PENCARIAN & SCAN ---
const handleManualSearch = () => {
    if (!manualId.value) return;
    router.get(route('scan-status.index'), { tracking: extractCode(manualId.value) }, { preserveState: true });
};

const onScanSuccess = (decodedText: string) => {
    stopCamera();
    router.get(route('scan-status.index'), { tracking: extractCode(decodedText) });
};

const submitUpdate = () => {
    form.post(route('scan-status.update'), {
        onSuccess: () => {
            form.reset('note');
        }
    });
};

// --- LOGIKA KAMERA ---
const startCamera = async (cameraId?: string) => {
    if (!html5QrCode) return;
    cameraError.value = null;
    isCameraLoading.value = true;

    try {
        if (isCameraRunning.value) {
            await html5QrCode.stop();
            isCameraRunning.value = false;
        }

        const cameraConfig = cameraId
            ? { deviceId: { exact: cameraId } }
            : { facingMode: "environment" };

        await html5QrCode.start(
            cameraConfig,
            {
                fps: 15,
                qrbox: { width: 250, height: 250 },
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
            cameraError.value = "Izin akses kamera ditolak oleh browser. Mohon izinkan kamera pada setelan situs.";
        } else if (errStr.includes('NotFoundError') || errStr.includes('DevicesNotFoundError')) {
            cameraError.value = "Tidak ditemukan perangkat kamera yang terpasang pada perangkat ini.";
        } else if (!window.isSecureContext) {
            cameraError.value = "Browser memblokir kamera karena koneksi HTTP (non-HTTPS). Gunakan fitur Upload Foto QR di bawah atau izinkan IP di setelan Chrome.";
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
            console.error(e);
        }
        isCameraRunning.value = false;
    }
};

const toggleCamera = () => {
    if (isCameraRunning.value) {
        stopCamera();
    } else {
        startCamera(selectedCameraId.value || undefined);
    }
};

// --- SCAN DARI FILE GAMBAR ---
const triggerFileInput = () => {
    fileScanError.value = null;
    fileInputRef.value?.click();
};

const handleFileUpload = async (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (!target.files || target.files.length === 0) return;
    const file = target.files[0];
    fileScanError.value = null;
    isFileScanning.value = true;

    try {
        if (!fileQrCode) {
            fileQrCode = new Html5Qrcode("reader-file-hidden");
        }
        const decodedText = await fileQrCode.scanFile(file, true);
        onScanSuccess(decodedText);
    } catch (err: any) {
        console.error("File scan error:", err);
        fileScanError.value = "QR Code tidak terdeteksi pada file gambar tersebut. Pastikan gambar QR jelas dan tidak buram.";
    } finally {
        isFileScanning.value = false;
        target.value = '';
    }
};

onMounted(async () => {
    isSecureContext.value = window.isSecureContext;
    currentOrigin.value = window.location.origin;

    html5QrCode = new Html5Qrcode("reader");

    try {
        const devices = await Html5Qrcode.getCameras();
        if (devices && devices.length > 0) {
            cameras.value = devices.map(d => ({ id: d.id, label: d.label || `Kamera ${d.id}` }));
            selectedCameraId.value = devices[0].id;
        }
    } catch (e) {
        console.warn("Could not list cameras:", e);
    }

    // Auto-start camera
    startCamera();
});

onUnmounted(() => {
    stopCamera();
    if (html5QrCode) {
        try {
            html5QrCode.clear();
        } catch (e) { }
    }
    if (fileQrCode) {
        try {
            fileQrCode.clear();
        } catch (e) { }
    }
});
</script>

<template>
    <AppLayout title="Update Status Surat">

        <Head title="Scan QR Code" />

        <!-- Header Halaman -->
        <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 animate-fade-in">
            <div>
                <span class="eyebrow-text">QR CODE TRACKING</span>
                <h2 class="fw-bold text-dark mb-1">Update Status Surat</h2>
                <p class="text-muted small mb-0">Scan QR Code pada lembar cetak pendamping untuk membaca ID Pelacakan
                    surat.</p>
            </div>
            <Link href="/tindak-lanjut" class="btn btn-light rounded-pill px-4 fw-bold shadow-sm border">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </Link>
        </div>

        <!-- Hidden container untuk scan file -->
        <div id="reader-file-hidden" style="display: none;"></div>
        <input ref="fileInputRef" type="file" accept="image/*" class="d-none" @change="handleFileUpload" />

        <!-- Bagian Atas: SCANNER & MANUAL INPUT -->
        <div class="st-card p-0 overflow-hidden border-0 shadow-lg mb-4 animate-fade-in">
            <div
                class="card-header-epic d-flex justify-content-between align-items-center px-4 py-3 border-bottom bg-white flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-0 fs-6">Pemindai QR Surat</h5>
                    <small class="text-muted" style="font-size: 11px;">Gunakan kamera langsung, upload foto QR, atau ketik ID manual.</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span v-if="isCameraRunning" class="badge bg-success-subtle text-success border px-2 py-1 small fw-bold">
                        <i class="bi bi-camera-video-fill me-1"></i> Kamera Aktif
                    </span>
                    <span v-else class="badge bg-secondary-subtle text-secondary border px-2 py-1 small fw-bold">
                        <i class="bi bi-camera-video-off me-1"></i> Kamera Nonaktif
                    </span>
                </div>
            </div>

            <div class="p-4 p-lg-5">
                <div class="row g-4 align-items-start">
                    <!-- KIRI: Scanner Box -->
                    <div class="col-lg-7">
                        <div class="scanner-card position-relative rounded-4 overflow-hidden border bg-dark p-2 text-center">
                            <!-- Area Video HTML5 QR Code -->
                            <div id="reader" class="rounded-3 overflow-hidden" style="min-height: 280px; width: 100%;"></div>

                            <!-- Controls Bar -->
                            <div class="d-flex align-items-center justify-content-center gap-2 mt-3 flex-wrap">
                                <button type="button" class="btn btn-sm btn-light fw-bold px-3 shadow-sm" :disabled="isCameraLoading" @click="toggleCamera">
                                    <span v-if="isCameraLoading" class="spinner-border spinner-border-sm me-1"></span>
                                    <i v-else class="bi" :class="isCameraRunning ? 'bi-stop-fill text-danger' : 'bi-play-fill text-success'"></i>
                                    {{ isCameraRunning ? 'Matikan Kamera' : 'Nyalakan Kamera' }}
                                </button>

                                <button type="button" class="btn btn-sm btn-outline-light fw-bold px-3" :disabled="isFileScanning" @click="triggerFileInput">
                                    <span v-if="isFileScanning" class="spinner-border spinner-border-sm me-1"></span>
                                    <i v-else class="bi bi-image me-1 text-info"></i>
                                    Upload / Foto QR
                                </button>

                                <select v-if="cameras.length > 1" v-model="selectedCameraId" class="form-select form-select-sm w-auto bg-dark text-white border-secondary" @change="startCamera(selectedCameraId)">
                                    <option v-for="cam in cameras" :key="cam.id" :value="cam.id">
                                        📷 {{ cam.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Alert Error Kamera jika ada -->
                        <div v-if="cameraError" class="alert alert-warning border-0 small mt-3 mb-0 py-2 d-flex align-items-start gap-2 rounded-3">
                            <i class="bi bi-exclamation-triangle-fill text-warning flex-shrink-0 mt-1"></i>
                            <div>
                                <div class="fw-bold">Kendala Akses Kamera:</div>
                                <div>{{ cameraError }}</div>
                            </div>
                        </div>

                        <!-- Alert Error File Scan jika ada -->
                        <div v-if="fileScanError" class="alert alert-danger border-0 small mt-3 mb-0 py-2 d-flex align-items-start gap-2 rounded-3">
                            <i class="bi bi-x-circle-fill text-danger flex-shrink-0 mt-1"></i>
                            <div>{{ fileScanError }}</div>
                        </div>

                        <!-- Info Buka Akses Kamera di LAN IP HTTP -->
                        <div v-if="!isSecureContext" class="alert alert-info border-0 small mt-3 mb-0 py-2 rounded-3">
                            <div class="fw-bold mb-1"><i class="bi bi-shield-lock me-1"></i> Info Akses Kamera di Jaringan Lokal (IP):</div>
                            <p class="mb-1" style="font-size: 11.5px;">
                                Browser modern membatasi kamera pada koneksi IP tanpa SSL. Anda tetap bisa menggunakan tombol <strong>"Upload / Foto QR"</strong> atau input ID manual.
                            </p>
                            <details class="mt-1" style="font-size: 11px;">
                                <summary class="text-primary fw-semibold cursor-pointer">Cara aktifkan kamera di Chrome/Edge</summary>
                                <div class="mt-1 p-2 bg-white rounded border">
                                    1. Buka tab baru di browser: <code class="user-select-all">chrome://flags/#unsafely-treat-insecure-origin-as-secure</code><br>
                                    2. Masukkan alamat: <code class="user-select-all text-primary fw-bold">{{ currentOrigin }}</code><br>
                                    3. Ubah dropdown menjadi <strong>Enabled</strong> lalu klik <strong>Relaunch</strong>.
                                </div>
                            </details>
                        </div>
                    </div>

                    <!-- KANAN: Petunjuk & Input Manual -->
                    <div class="col-lg-5">
                        <div class="instruction-box p-4 rounded-4 bg-light shadow-sm">
                            <h6 class="fw-bold text-dark mb-3"><i
                                    class="bi bi-info-circle-fill me-2 text-primary"></i>Petunjuk Cepat</h6>
                            <ol class="small text-muted ps-3 mb-4">
                                <li class="mb-2">Arahkan kamera ke QR Code surat, atau klik <strong>Upload / Foto QR</strong>.</li>
                                <li class="mb-2">Pastikan kode QR tercetak tajam dan tidak tertutup.</li>
                                <li>Setelah terbaca, formulir pembaruan status akan otomatis terbuka.</li>
                            </ol>

                            <div class="manual-input-section pt-3 border-top">
                                <label class="form-label small fw-bold text-dark">Input ID Pelacakan Manual / Barcode Scanner</label>
                                <div class="input-group">
                                    <input v-model="manualId" type="text"
                                        class="form-control font-monospace border-primary-subtle"
                                        placeholder="TUS-YYYYMMDD-XXX" autofocus @keyup.enter="handleManualSearch">
                                    <button @click="handleManualSearch" class="btn btn-teal px-3 fw-bold">
                                        <i class="bi bi-search me-1"></i> Buka
                                    </button>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                    Mendukung scanner barcode USB/Bluetooth atau ketik manual ID surat.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bagian Bawah: HASIL & FORM UPDATE (Hanya muncul jika surat ditemukan) -->
        <div v-if="letter" id="update-section" class="row g-4 animate-fade-in">
            <!-- Summary Dokumen -->
            <div class="col-lg-4">
                <div class="st-card h-100 shadow-sm border-0">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="badge bg-primary px-3 py-2 rounded-pill font-monospace">{{ letter.tracking_code
                        }}</span>
                        <StatusBadge :status="letter.status" />
                    </div>
                    <h5 class="fw-bold text-dark mb-1">{{ letter.subject }}</h5>
                    <p class="text-muted small mb-3">Agenda: {{ letter.agenda_number || '-' }}</p>

                    <div class="border-top pt-3 mt-3">
                        <small class="text-muted d-block mb-1">DARI UNIT / PENGIRIM</small>
                        <div class="fw-bold small">{{ letter.sender_name }}</div>
                    </div>
                </div>
            </div>

            <!-- Form Update -->
            <div class="col-lg-8">
                <div class="st-card shadow-lg border-0 p-4 p-md-5">
                    <h5 class="fw-bold text-dark mb-4 border-bottom pb-3"><i
                            class="bi bi-pencil-square me-2 text-primary"></i>Update Progress Naskah</h5>

                    <form @submit.prevent="submitUpdate">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">STATUS BARU</label>
                                <select v-model="form.status" class="form-select" required>
                                    <option v-for="s in allowedStatuses" :key="s" :value="s">{{ s }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">POSISI BERKAS SEKARANG</label>
                                <input v-model="form.current_position" type="text" class="form-control"
                                    placeholder="Contoh: Meja Sekretaris" required />
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small mb-3">TINDAKAN LANJUTAN</label>
                            <div class="action-grid">
                                <label v-for="opt in actionOptions" :key="opt" class="action-pill"
                                    :class="{ 'is-selected': form.requested_actions.includes(opt) }">
                                    <input type="checkbox" v-model="form.requested_actions" :value="opt" class="d-none">
                                    <i class="bi"
                                        :class="form.requested_actions.includes(opt) ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                                    {{ opt }}
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small">CATATAN PEMINDAIAN</label>
                            <textarea v-model="form.note" class="form-control" rows="2"
                                placeholder="Keterangan singkat..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary-blue w-100 py-3 rounded-3 fw-bold"
                            :disabled="form.processing">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                            <i v-else class="bi bi-check-circle-fill me-2"></i> Simpan Pembaruan Posisi
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Jika tidak ditemukan -->
        <div v-else-if="tracking" class="alert alert-danger shadow-sm border-0 p-4 text-center animate-fade-in">
            <i class="bi bi-exclamation-octagon-fill display-5 mb-2 d-block"></i>
            <h5 class="fw-bold">Berkas Tidak Ditemukan</h5>
            <p class="mb-0">ID Pelacakan <strong>{{ tracking }}</strong> tidak ditemukan dalam data tindak lanjut.
                Pastikan kode benar atau berkas sudah diproses di lajur lain.</p>
        </div>
    </AppLayout>
</template>

<style scoped lang="scss">
.eyebrow-text {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.5px;
    color: var(--st-primary);
    text-transform: uppercase;
}

.btn-teal {
    background: linear-gradient(135deg, var(--st-teal, #42B8A8), var(--st-teal-dark, #2a8175));
    color: white;

    &:hover {
        background: linear-gradient(135deg, var(--st-teal-light, #64cbbd), var(--st-teal, #42B8A8));
        color: white;
    }
}

.border-dashed {
    border: 3px dashed #cbd5e1;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 10px;
}

.action-pill {
    padding: 10px 15px;
    background: #fff;
    border: 2px solid #f1f5f9;
    border-radius: 12px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.2s;

    &:hover {
        border-color: #3b82f6;
    }

    &.is-selected {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #3b82f6;
    }
}

/* Override styling library html5-qrcode */
:deep(#reader) {
    border: none !important;
    background: #0f172a;
}

:deep(#reader video) {
    border-radius: 12px;
    max-height: 380px;
    object-fit: cover;
}

:deep(#reader__scan_region) {
    background: transparent !important;
}

:deep(#reader__dashboard_section_csr button) {
    background: #2563eb !important;
    color: white !important;
    border: none !important;
    padding: 10px 20px !important;
    border-radius: 50px !important;
    font-weight: bold !important;
    font-size: 13px !important;
    margin: 10px 0;
}
</style>