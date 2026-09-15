<script setup lang="ts">
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Html5QrcodeScanner } from "html5-qrcode"; // Garis merah di sini harusnya sudah hilang
import type { Letter } from '@/types';

// TAMBAHKAN BARIS INI untuk menghilangkan error "Cannot find name 'route'"
declare function route(name: string, params?: any): string;

const props = defineProps<{
    tracking: string;
    letter: Letter | null;
    allowedStatuses: string[];
}>();

const manualId = ref(props.tracking || '');
let scanner: any = null;

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
    if (scanner) scanner.clear(); // Hentikan kamera setelah berhasil scan
    router.get(route('scan-status.index'), { tracking: extractCode(decodedText) });
};

const submitUpdate = () => {
    form.post(route('scan-status.update'), {
        onSuccess: () => {
            form.reset('note');
            // Jika ingin scanner terbuka lagi setelah simpan, bisa refresh di sini
        }
    });
};

onMounted(() => {
    scanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 }, false);
    scanner.render(onScanSuccess, () => { });
});

onUnmounted(() => {
    if (scanner) scanner.clear();
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

        <!-- Bagian Atas: SCANNER & MANUAL INPUT -->
        <div class="st-card p-0 overflow-hidden border-0 shadow-lg mb-4 animate-fade-in">
            <div
                class="card-header-epic d-flex justify-content-between align-items-center px-4 py-3 border-bottom bg-white">
                <div>
                    <h5 class="fw-bold mb-0 fs-6">Status Surat</h5>
                    <small class="text-muted" style="font-size: 11px;">Gunakan kamera atau input manual untuk memproses
                        berkas.</small>
                </div>
                <span class="badge bg-primary-subtle text-primary border px-2 py-1 small fw-bold">
                    <i class="bi bi-camera-fill me-1"></i> Mode Pemindai Aktif
                </span>
            </div>

            <div class="p-4 p-lg-5">
                <div class="row g-5 align-items-start">
                    <!-- KIRI: Scanner -->
                    <div class="col-lg-7">
                        <div id="reader" class="rounded-4 overflow-hidden border-dashed bg-light"></div>
                    </div>

                    <!-- KANAN: Petunjuk & Input Manual -->
                    <div class="col-lg-5">
                        <div class="instruction-box p-4 rounded-4 bg-light shadow-sm">
                            <h6 class="fw-bold text-dark mb-3"><i
                                    class="bi bi-info-circle-fill me-2 text-primary"></i>Petunjuk Scan</h6>
                            <ol class="small text-muted ps-3 mb-4">
                                <li class="mb-2">Arahkan kamera ke QR Code surat.</li>
                                <li class="mb-2">Pastikan pencahayaan cukup agar terbaca.</li>
                                <li>Setelah terbaca, form update akan muncul di bawah.</li>
                            </ol>

                            <div class="alert alert-warning border-0 small mb-4 py-2"
                                style="background-color: #fff9db; color: #856404;">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                Kamera tidak muncul? Gunakan <strong>HTTPS</strong> atau <strong>localhost</strong>.
                            </div>

                            <div class="manual-input-section pt-3 border-top">
                                <label class="form-label small fw-bold text-dark">Input ID Pelacakan Manual</label>
                                <div class="input-group">
                                    <input v-model="manualId" type="text"
                                        class="form-control font-monospace border-primary-subtle"
                                        placeholder="TUS-YYYYMMDD-XXX" @keyup.enter="handleManualSearch">
                                    <button @click="handleManualSearch" class="btn btn-teal px-3 fw-bold">Buka</button>
                                </div>
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
                                <label class="form-label fw-bold small">STAtus BARU</label>
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
    background-color: #38a89d;
    color: white;

    &:hover {
        background-color: #2d8a81;
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