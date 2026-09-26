<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import TrackingHero from '@/Components/TrackingHero.vue';
import { Letter, LetterStatusLog, Disposition, PageProps } from '@/types';

const props = defineProps<{
    searchCode: string;
    letter?: Letter | null;
    logs: LetterStatusLog[];
    dispositions: Disposition[];
    progress: number;
}>();

const page = usePage<PageProps>();
const user = computed(() => page.props.auth?.user);

const inputCode = ref(props.searchCode || '');

const handleSearch = () => {
    if (!inputCode.value) return;
    router.get('/tracking', { code: inputCode.value }, { preserveState: true });
};

const formatDate = (dateStr: string) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const formatTime = (dateStr: string) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).replace('.', ':') + ' WIB';
};

const formatDateTime = (dateStr: string) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}`;
};

const isReadyForPickup = computed(() => {
    if (!props.letter) return false;
    const s = props.letter.status;
    return s === 'Selesai dan Siap Untuk diambil' || s === 'Dokumen Sudah diambil' || s === 'Selesai' || s === 'Surat Selesai di Paraf/TTD dan bisa diambil';
});

const isValidAttachment = (path: any) => {
    if (!path || typeof path !== 'string') return false;
    const p = path.trim().toLowerCase();
    if (p === '0' || p === 'null' || p === 'undefined' || p === '') return false;
    if (p.includes('signatures/') || p.includes('sig_')) return false;
    return p.includes('.') && p.length >= 4;
};

/* ============================================================
   WORKFLOW DEFINITIONS & SEQUENTIAL TIMELINE RECONCILIATION
   ============================================================ */
interface WorkflowStepDef {
    key: string;
    title: string;
    aliases: string[];
    defaultDesc: string;
    defaultPosition: string;
    defaultRole: string;
}

const signatureWorkflowDefs: WorkflowStepDef[] = [
    {
        key: 'pengajuan',
        title: 'Pengajuan Berhasil',
        aliases: ['pengajuan berhasil', 'diregistrasi', 'registrasi', 'pengajuan diajukan'],
        defaultDesc: 'Permohonan paraf naskah dinas berhasil diajukan via portal SiTrack.',
        defaultPosition: 'Bagian TU Pimpinan dan Protokol',
        defaultRole: 'Pemohon (Publik)',
    },
    {
        key: 'diterima',
        title: 'Diterima',
        aliases: ['diterima', 'surat diterima', 'diterima tu', 'diterima loket'],
        defaultDesc: 'Pembaruan data surat dan status operasional di loket Tata Usaha Sekjen.',
        defaultPosition: 'Tata Usaha Sekjen',
        defaultRole: 'Tata Usaha Sekjen',
    },
    {
        key: 'diperiksa_tu',
        title: 'Diperiksa Oleh TU Sekjen',
        aliases: ['diperiksa oleh tu sekjen', 'pemeriksaan tu sekjen', 'verifikasi tu sekjen'],
        defaultDesc: 'Verifikasi kelengkapan format, substansi naskah dinas, dan arsip pendukung.',
        defaultPosition: 'Tata Usaha Sekjen',
        defaultRole: 'Staf TU Sekjen',
    },
    {
        key: 'diperiksa_kasubag',
        title: 'Diperiksa Oleh Kasubag TU Sekjen',
        aliases: ['diperiksa oleh kasubag tu sekjen', 'pemeriksaan kasubag tu sekjen'],
        defaultDesc: 'Pemeriksaan keabsahan redaksional dan pemberian paraf kendali Kasubag TU.',
        defaultPosition: 'Kasubag TU Sekjen',
        defaultRole: 'Kasubag TU Sekjen',
    },
    {
        key: 'diperiksa_sekjen',
        title: 'Diperiksa Oleh Sekjen',
        aliases: ['diperiksa oleh sekjen', 'pemeriksaan sekjen', 'penandatanganan sekjen'],
        defaultDesc: 'Penyampaian berkas kepada Sekretaris Jenderal untuk penandatanganan/paraf resmi.',
        defaultPosition: 'Sekretaris Jenderal',
        defaultRole: 'Sekretaris Jenderal',
    },
    {
        key: 'siap_diambil',
        title: 'Selesai dan Siap Untuk diambil',
        aliases: [
            'selesai dan siap untuk diambil',
            'surat selesai di paraf/ttd dan bisa diambil',
            'selesai dan siap diambil',
        ],
        defaultDesc: 'Naskah telah selesai ditandatangani/diparaf dan siap diambil oleh unit pengusul.',
        defaultPosition: 'Tata Usaha Sekjen',
        defaultRole: 'Tata Usaha',
    },
    {
        key: 'sudah_diambil',
        title: 'Dokumen Sudah diambil',
        aliases: ['dokumen sudah diambil', 'sudah diambil', 'selesai'],
        defaultDesc: 'Fisik naskah dinas resmi telah diserahterimakan dan proses dinyatakan tuntas.',
        defaultPosition: 'Unit Pengolah / Pengusul',
        defaultRole: 'Unit Pengolah',
    },
];

const dispositionWorkflowDefs: WorkflowStepDef[] = [
    {
        key: 'diterima_tu',
        title: 'Surat Diterima TU',
        aliases: ['surat diterima tu', 'diterima', 'surat masuk diterima', 'diregistrasi', 'pengajuan berhasil'],
        defaultDesc: 'Surat masuk diterima, dicatat, dan diagendakan ke lajur disposisi pimpinan.',
        defaultPosition: 'Tata Usaha Sekjen',
        defaultRole: 'Admin Operator',
    },
    {
        key: 'diajukan_sekjen',
        title: 'Diajukan ke Sekjen',
        aliases: ['diajukan ke sekjen', 'pengajuan ke sekjen'],
        defaultDesc: 'Dokumen masuk disampaikan kepada pimpinan untuk telaah dan arahan tertulis.',
        defaultPosition: 'TU Pimpinan',
        defaultRole: 'TU Pimpinan',
    },
    {
        key: 'didisposisikan',
        title: 'Didisposisikan',
        aliases: ['didisposisikan', 'disposisi terbit'],
        defaultDesc: 'Pimpinan menerbitkan instruksi lembar disposisi resmi kepada unit kerja.',
        defaultPosition: 'Sekretaris Jenderal',
        defaultRole: 'Sekretaris Jenderal',
    },
    {
        key: 'diteruskan_unit',
        title: 'Diteruskan ke Unit',
        aliases: ['diteruskan ke unit', 'distribusi ke unit kerja', 'diteruskan ke unit kerja'],
        defaultDesc: 'Lembar disposisi fisik/digital diteruskan kepada unit kerja pelaksana.',
        defaultPosition: 'Tata Usaha / Unit',
        defaultRole: 'Tata Usaha / Unit',
    },
    {
        key: 'tindak_lanjut',
        title: 'Dalam Tindak Lanjut',
        aliases: ['dalam tindak lanjut', 'proses tindak lanjut'],
        defaultDesc: 'Unit kerja memproses butir arahan instruksi disposisi dan mencatat tindak lanjut.',
        defaultPosition: 'Pejabat Unit Kerja',
        defaultRole: 'Pejabat Unit Kerja',
    },
    {
        key: 'selesai_disposisi',
        title: 'Selesai',
        aliases: ['selesai', 'dokumen sudah diambil', 'tuntas'],
        defaultDesc: 'Seluruh butir arahan instruksi disposisi telah tuntas ditindaklanjuti.',
        defaultPosition: 'Sistem / Pimpinan',
        defaultRole: 'Sistem / Pimpinan',
    },
];

interface TimelineItem {
    id: string | number;
    title: string;
    stepNumber: number;
    isCompleted: boolean;
    isCurrent: boolean;
    isPending: boolean;
    isException: boolean;
    exceptionType?: 'warning' | 'danger';
    dateTime?: string;
    note?: string;
    position?: string;
    changedBy?: string;
    attachmentPath?: string;
    attachmentName?: string;
}

const orderedTimeline = computed<TimelineItem[]>(() => {
    if (!props.letter) return [];

    const isDisposition = props.letter.process_lane === 'disposition';
    const defs = isDisposition ? dispositionWorkflowDefs : signatureWorkflowDefs;

    const normalize = (str: string) => (str || '').toLowerCase().trim();
    const currentStatusStr = normalize(props.letter.status);

    // Find step index helper
    const findStepIndex = (statusStr: string) => {
        const norm = normalize(statusStr);
        return defs.findIndex(d => 
            normalize(d.title) === norm || d.aliases.some(alias => normalize(alias) === norm)
        );
    };

    // Find max step index reached from logs and current letter status
    const currentStepIdx = findStepIndex(currentStatusStr);
    let maxLogIdx = -1;

    // Map logs to step indexes
    const matchedLogMap = new Map<number, LetterStatusLog>();
    const extraLogs: LetterStatusLog[] = [];

    (props.logs || []).forEach(log => {
        const idx = findStepIndex(log.status);
        if (idx !== -1) {
            matchedLogMap.set(idx, log);
            if (idx > maxLogIdx) {
                maxLogIdx = idx;
            }
        } else {
            extraLogs.push(log);
        }
    });

    const activeMaxIdx = Math.max(currentStepIdx, maxLogIdx);

    // Check if current status is an exception (Revisi, Ditolak, Dikembalikan)
    const isRevisi = currentStatusStr.includes('revisi');
    const isDitolak = currentStatusStr.includes('tolak') || currentStatusStr.includes('kembali');
    const hasException = isRevisi || isDitolak;

    const items: TimelineItem[] = [];

    // Helper for fallback date
    const fallbackDate = formatDateTime(props.letter.received_date || props.letter.created_at);

    // Build standard sequential pipeline
    defs.forEach((stepDef, idx) => {
        const matchedLog = matchedLogMap.get(idx);
        const isCompleted = idx <= activeMaxIdx && activeMaxIdx !== -1;
        const isCurrent = idx === activeMaxIdx && !hasException;
        const isPending = !isCompleted;

        let title = matchedLog ? matchedLog.status : stepDef.title;
        let dateTime = matchedLog ? formatDateTime(matchedLog.changed_at) : '';
        let note = matchedLog ? matchedLog.note : '';
        let position = matchedLog ? matchedLog.position : stepDef.defaultPosition;
        let changedBy = matchedLog ? matchedLog.changed_by : '';
        let attachmentPath = matchedLog?.attachment_path;
        let attachmentName = matchedLog?.attachment_name;

        if (isCompleted && !matchedLog) {
            // Step was skipped by admin but passed in sequential order
            note = 'Telah diverifikasi dan diselesaikan sesuai alur proses SOP.';
            position = stepDef.defaultPosition;
            changedBy = 'Sistem / Petugas';
            dateTime = fallbackDate;
        } else if (isPending) {
            note = stepDef.defaultDesc;
            position = stepDef.defaultPosition;
        }

        items.push({
            id: matchedLog ? matchedLog.id : `step-${idx}`,
            title: title,
            stepNumber: idx + 1,
            isCompleted: isCompleted,
            isCurrent: isCurrent,
            isPending: isPending,
            isException: false,
            dateTime: dateTime,
            note: note,
            position: position,
            changedBy: changedBy,
            attachmentPath: attachmentPath || undefined,
            attachmentName: attachmentName || undefined,
        });
    });

    // If there is an exception status (e.g. Revisi / Ditolak), insert it right after the last reached completed step
    if (hasException) {
        const exceptionLog = (props.logs || []).slice().reverse().find(l => {
            const n = normalize(l.status);
            return n.includes('revisi') || n.includes('tolak') || n.includes('kembali');
        });

        const insertIndex = Math.max(0, activeMaxIdx + 1);
        items.splice(insertIndex, 0, {
            id: 'exception-status',
            title: props.letter.status,
            stepNumber: insertIndex + 1,
            isCompleted: true,
            isCurrent: true,
            isPending: false,
            isException: true,
            exceptionType: isRevisi ? 'warning' : 'danger',
            dateTime: exceptionLog ? formatDateTime(exceptionLog.changed_at) : fallbackDate,
            note: exceptionLog?.note || props.letter.notes || (isRevisi ? 'Dokumen memerlukan perbaikan/revisi dari unit pengusul.' : 'Pengajuan dokumen ditolak/dikembalikan.'),
            position: props.letter.current_position || 'Tata Usaha Sekjen',
            changedBy: exceptionLog?.changed_by || 'Petugas Verifikator',
            attachmentPath: exceptionLog?.attachment_path || undefined,
            attachmentName: exceptionLog?.attachment_name || undefined,
        });
    }

    return items;
});
</script>

<template>
    <PublicLayout>

        <Head title="Lacak Surat & Naskah Dinas" />

        <div class="tracking-viewport" :class="{ 'has-results': letter || searchCode }">
            <TrackingHero v-model="inputCode" @search="handleSearch" />

            <!-- ============ Result Area ============ -->
            <div v-if="letter || searchCode" id="results" class="container py-5 mt-n5 position-relative"
                style="z-index: 20;">

                <!-- If Found -->
                <div v-if="letter" class="row justify-content-center animate-fade-up">
                    <div class="col-lg-11">

                        <!-- ===== UNIFIED SINGLE TRACKING CARD ===== -->
                        <div class="tracking-unified-card">
                            <!-- Header (Blue Gradient) -->
                            <div class="resi-header-card">
                                <div class="resi-header-inner">
                                    <div>
                                        <div class="resi-label">KODE RESI</div>
                                        <div class="resi-code">{{ letter.tracking_code }}</div>
                                    </div>
                                    <div class="resi-lane-badge">
                                        <template v-if="letter.process_lane === 'disposition'">
                                            <i class="bi bi-diagram-3-fill me-1"></i> Lajur 2 &mdash; Disposisi
                                        </template>
                                        <template v-else>
                                            <i class="bi bi-pen-fill me-1"></i> Lajur 1 &mdash; Tindak Lanjut
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Unified Card Body (2 Columns) -->
                            <div class="tracking-card-body">
                                <div class="row g-0">
                                    <!-- LEFT COLUMN: Detail Dokumen -->
                                    <div class="col-lg-5 info-left-col">
                                        <div class="info-field">
                                            <div class="info-label">PERIHAL</div>
                                            <div class="info-value fw-bold fs-6">{{ letter.subject }}</div>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-sm-6">
                                                <div class="info-field">
                                                    <div class="info-label">PENGIRIM</div>
                                                    <div class="info-value">{{ letter.sender_name || letter.sender_unit || '-' }}</div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="info-field">
                                                    <div class="info-label">TANGGAL MASUK</div>
                                                    <div class="info-value">{{ formatDate(letter.received_date || letter.created_at) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="info-field">
                                            <div class="info-label">UNIT KERJA</div>
                                            <div class="info-value">{{ letter.sender_unit || letter.destination || '-' }}</div>
                                        </div>
                                        <div class="row g-3">
                                            <div v-if="letter.letter_number" class="col-sm-6">
                                                <div class="info-field">
                                                    <div class="info-label">NOMOR SURAT</div>
                                                    <div class="info-value font-monospace text-primary fw-semibold">{{ letter.letter_number }}</div>
                                                </div>
                                            </div>
                                            <div v-if="letter.priority" class="col-sm-6">
                                                <div class="info-field">
                                                    <div class="info-label">SIFAT NASKAH</div>
                                                    <div class="info-value">{{ letter.priority }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Lampiran Berkas Naskah Utama -->
                                        <div v-if="letter && isValidAttachment(letter.attachment_path)" class="attachment-main-wrap mt-3">
                                            <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                <i class="bi bi-file-earmark-text text-primary fs-5"></i>
                                                <span class="small text-truncate fw-semibold text-dark" style="max-width: 180px;" :title="letter.attachment_path?.split('/').pop() || 'Lampiran'">
                                                    {{ letter.attachment_path?.split('/').pop() || 'Lampiran' }}
                                                </span>
                                            </div>
                                            <a :href="`/lampiran/view/${letter.attachment_path}`" target="_blank"
                                                class="btn btn-sm btn-primary-blue d-inline-flex align-items-center gap-1 px-3 py-1 text-nowrap">
                                                <i class="bi bi-eye"></i> Unduh
                                            </a>
                                        </div>

                                        <!-- Admin Update button -->
                                        <div v-if="user && (letter.process_lane === 'signature' || !letter.process_lane)" class="mt-4 pt-2">
                                            <Link :href="`/scan-status?tracking=${letter.tracking_code}`"
                                                class="btn btn-sm btn-primary-blue shadow-sm d-inline-flex align-items-center gap-1.5 px-3 py-2 rounded-pill">
                                                <i class="bi bi-pencil-square"></i> Update Status (Admin)
                                            </Link>
                                        </div>
                                    </div>

                                    <!-- RIGHT COLUMN: Status Saat Ini + Riwayat Perjalanan (Runtut Alur) -->
                                    <div class="col-lg-7 right-timeline-col">
                                        <!-- Status Saat Ini Header -->
                                        <div class="status-current-section pb-3 mb-3 border-bottom">
                                            <div class="info-label mb-2">STATUS SAAT INI</div>
                                            <div class="status-current-row">
                                                <span class="status-dot"></span>
                                                <div>
                                                    <div class="status-current-text">{{ letter.status }}</div>
                                                    <div class="status-current-position mt-1">
                                                        <i class="bi bi-geo-alt-fill text-primary me-1"></i>
                                                        Posisi: <strong class="text-dark">{{ letter.current_position }}</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Riwayat Perjalanan Dokumen Timeline (Alur Runtut & Status Pending) -->
                                        <div class="perjalanan-section">
                                            <div class="info-label mb-3 d-flex align-items-center gap-1.5">
                                                <i class="bi bi-clock-history text-primary"></i> RIWAYAT PERJALANAN DOKUMEN
                                            </div>
                                            
                                            <div class="perjalanan-timeline">
                                                <div v-for="(item, idx) in orderedTimeline" :key="item.id" 
                                                    class="perjalanan-item" 
                                                    :class="{ 
                                                        'is-completed': item.isCompleted, 
                                                        'is-current': item.isCurrent, 
                                                        'is-pending': item.isPending, 
                                                        'is-exception': item.isException,
                                                        'is-last': idx === orderedTimeline.length - 1 
                                                    }">
                                                    
                                                    <!-- Left line & indicator column -->
                                                    <div class="perjalanan-line-col">
                                                        <!-- Completed Dot: Checked Blue -->
                                                        <div v-if="item.isCompleted && !item.isException" class="perjalanan-dot is-completed" :class="{ 'is-current-glow': item.isCurrent }">
                                                            <i class="bi bi-check-lg"></i>
                                                        </div>
                                                        <!-- Exception Dot: Warning (Amber) or Danger (Red) -->
                                                        <div v-else-if="item.isException" class="perjalanan-dot is-exception" :class="item.exceptionType === 'warning' ? 'dot-warning' : 'dot-danger'">
                                                            <i :class="item.exceptionType === 'warning' ? 'bi bi-exclamation-triangle-fill' : 'bi bi-x-circle-fill'"></i>
                                                        </div>
                                                        <!-- Pending Dot: Gray with clock -->
                                                        <div v-else class="perjalanan-dot is-pending">
                                                            <i class="bi bi-clock"></i>
                                                        </div>
                                                        
                                                        <!-- Connector Line -->
                                                        <div v-if="idx < orderedTimeline.length - 1" 
                                                            class="perjalanan-connector" 
                                                            :class="{ 'is-pending': item.isPending, 'is-completed': item.isCompleted && orderedTimeline[idx + 1]?.isCompleted }">
                                                        </div>
                                                    </div>

                                                    <!-- Right Body -->
                                                    <div class="perjalanan-body">
                                                        <div class="perjalanan-top-row">
                                                            <div class="perjalanan-title-wrap d-flex align-items-center gap-2 flex-wrap">
                                                                <span class="perjalanan-title" :class="{ 'text-muted-title': item.isPending, 'text-danger': item.isException && item.exceptionType === 'danger', 'text-warning-dark': item.isException && item.exceptionType === 'warning' }">
                                                                    {{ item.title }}
                                                                </span>
                                                                <!-- Status Tag -->
                                                                <span v-if="item.isPending" class="badge-pending-tag">
                                                                    Menunggu
                                                                </span>
                                                                <span v-else-if="item.isCurrent && !item.isException" class="badge-current-tag">
                                                                    Sedang Berjalan
                                                                </span>
                                                                <span v-else-if="item.isException" :class="item.exceptionType === 'warning' ? 'badge-warning-tag' : 'badge-danger-tag'">
                                                                    {{ item.exceptionType === 'warning' ? 'Perlu Revisi' : 'Ditolak' }}
                                                                </span>
                                                            </div>
                                                            
                                                            <div v-if="item.dateTime" class="perjalanan-date">
                                                                {{ item.dateTime }}
                                                            </div>
                                                            <div v-else-if="item.isPending" class="perjalanan-date-pending">
                                                                &mdash;
                                                            </div>
                                                        </div>

                                                        <div v-if="item.note" class="perjalanan-note" :class="{ 'text-muted-pending': item.isPending }">
                                                            {{ item.note }}
                                                        </div>
                                                        
                                                        <div v-if="item.position" class="perjalanan-position" :class="{ 'text-muted-pending': item.isPending }">
                                                            <i class="bi bi-geo-alt-fill me-1" :class="item.isPending ? 'text-muted' : 'text-primary'"></i>
                                                            {{ item.position }}
                                                        </div>
                                                        
                                                        <div v-if="item.changedBy" class="perjalanan-by">
                                                            Oleh: {{ item.changedBy }}
                                                        </div>

                                                        <!-- Lampiran per log -->
                                                        <div v-if="isValidAttachment(item.attachmentPath)" class="perjalanan-attachment">
                                                            <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                                <i class="bi bi-paperclip text-primary"></i>
                                                                <span class="small text-truncate fw-semibold text-dark" style="max-width: 220px;" :title="item.attachmentName || item.attachmentPath?.split('/').pop() || 'Lampiran'">
                                                                    {{ item.attachmentName || item.attachmentPath?.split('/').pop() || 'Lampiran' }}
                                                                </span>
                                                            </div>
                                                            <a :href="`/lampiran/view/${item.attachmentPath}`" target="_blank"
                                                                class="btn btn-sm btn-outline-primary py-1 px-2 text-nowrap d-inline-flex align-items-center gap-1 small">
                                                                <i class="bi bi-eye"></i> Buka
                                                            </a>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>

                                            <!-- Cetak Lembar Pendamping Button (muncul jika siap diambil) -->
                                            <div v-if="(letter.process_lane === 'signature' || !letter.process_lane) && isReadyForPickup" class="cetak-pendamping-wrap mt-3 pt-3 border-top">
                                                <a :href="`/cetak/pendamping/${letter.id}`" target="_blank" class="btn-cetak-pendamping">
                                                    <i class="bi bi-printer-fill me-2"></i> Cetak Lembar Pendamping
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Not Found State -->
                <div v-else-if="searchCode" class="text-center py-5 animate-fade-up">
                    <div class="glass-result-card p-5 d-inline-block mx-auto border-0 shadow-lg bg-white"
                        style="max-width: 500px;">
                        <i class="bi bi-search text-muted display-4 mb-3 d-block opacity-20"></i>
                        <h4 class="fw-bold text-dark">Data Tidak Ditemukan</h4>
                        <p class="text-muted small mb-0">Kode <strong>"{{ searchCode }}"</strong> tidak terdaftar.</p>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>


<style scoped>
.tracking-viewport {
    background: #03205A;
    min-height: 100%;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.tracking-viewport.has-results {
    background: #03205A;
}

/* ============================================================
   RESI HEADER CARD (Institutional Gradient)
   ============================================================ */
.resi-header-card {
    background: linear-gradient(135deg, #03205A 0%, #1C386F 50%, #2743AF 100%);
    border-radius: 1.25rem 1.25rem 0 0;
    padding: 1.75rem 2rem;
    color: #fff;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-bottom: none;
}

.resi-header-card::before {
    content: '';
    position: absolute;
    top: -40%;
    right: -10%;
    width: 340px;
    height: 340px;
    background: radial-gradient(circle, rgba(61, 165, 249, 0.18) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.resi-header-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    position: relative;
    z-index: 1;
}

.resi-label {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #B5CCE3;
    margin-bottom: 0.25rem;
}

.resi-code {
    font-size: 1.65rem;
    font-weight: 800;
    letter-spacing: -0.01em;
    color: #ffffff;
    font-family: 'Plus Jakarta Sans', monospace;
}

.resi-lane-badge {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.28);
    backdrop-filter: blur(8px);
    padding: 0.5rem 1.15rem;
    border-radius: 999px;
    font-size: 0.82rem;
    font-weight: 700;
    color: #ffffff;
    white-space: nowrap;
}

/* ============================================================
   UNIFIED SINGLE TRACKING CARD
   ============================================================ */
.tracking-unified-card {
    background: #ffffff;
    border-radius: 1.25rem;
    box-shadow: 0 16px 40px rgba(3, 32, 90, 0.14);
    border: 1px solid #E2E8F0;
    overflow: hidden;
    margin-bottom: 2rem;
}

.tracking-card-body {
    background: #ffffff;
}

.info-left-col {
    padding: 2rem 2.25rem;
    border-right: 1px solid #F1F5F9;
    background: #ffffff;
}

.right-timeline-col {
    padding: 2rem 2.25rem;
    background: #FAFBFD;
}

.info-field {
    margin-bottom: 1.25rem;
}

.info-label {
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #2743AF;
    margin-bottom: 0.35rem;
}

.info-value {
    font-size: 0.95rem;
    color: #0F172A;
    line-height: 1.45;
}

/* Status current */
.status-current-section {
    position: relative;
}

.status-current-row {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
}

.status-dot {
    width: 14px;
    height: 14px;
    min-width: 14px;
    border-radius: 50%;
    background: #2743AF;
    margin-top: 5px;
    box-shadow: 0 0 0 4px rgba(39, 67, 175, 0.18);
    animation: pulseGlow 2s infinite ease-in-out;
}

@keyframes pulseGlow {
    0%, 100% {
        box-shadow: 0 0 0 4px rgba(39, 67, 175, 0.2);
    }
    50% {
        box-shadow: 0 0 0 8px rgba(61, 165, 249, 0.3);
    }
}

.status-current-text {
    font-size: 1.25rem;
    font-weight: 800;
    color: #0F172A;
    line-height: 1.3;
}

.status-current-position {
    font-size: 0.88rem;
    color: #536B88;
}

/* ============================================================
   PERJALANAN TIMELINE (Inside Single Card)
   ============================================================ */
.perjalanan-section {
    position: relative;
}

.perjalanan-timeline {
    position: relative;
}

.perjalanan-item {
    display: flex;
    gap: 1.25rem;
    position: relative;
    padding-bottom: 1.75rem;
}

.perjalanan-item.is-last {
    padding-bottom: 0;
}

.perjalanan-line-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 36px;
    min-width: 36px;
    position: relative;
}

.perjalanan-dot {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    position: relative;
    z-index: 2;
    flex-shrink: 0;
    transition: all 0.25s ease;
}

.perjalanan-dot.is-completed {
    background: #2743AF;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(39, 67, 175, 0.35);
}

.perjalanan-dot.is-current-glow {
    box-shadow: 0 0 0 4px rgba(39, 67, 175, 0.22), 0 4px 14px rgba(39, 67, 175, 0.35);
    animation: dotPulse 2s infinite ease-in-out;
}

@keyframes dotPulse {
    0%, 100% {
        box-shadow: 0 0 0 4px rgba(39, 67, 175, 0.22), 0 4px 14px rgba(39, 67, 175, 0.35);
    }
    50% {
        box-shadow: 0 0 0 7px rgba(61, 165, 249, 0.35), 0 4px 14px rgba(39, 67, 175, 0.4);
    }
}

.perjalanan-dot.is-pending {
    background: #F8FAFC;
    border: 2px solid #CBD5E1;
    color: #94A3B8;
    font-size: 0.8rem;
    box-shadow: none;
}

.perjalanan-dot.is-exception.dot-warning {
    background: #F59E0B;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35);
}

.perjalanan-dot.is-exception.dot-danger {
    background: #EF4444;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35);
}

.perjalanan-connector {
    width: 2px;
    flex: 1;
    background: #CBD5E1;
    margin-top: 4px;
    position: relative;
    z-index: 1;
    min-height: 28px;
}

.perjalanan-connector.is-completed {
    background: #2743AF;
}

.perjalanan-connector.is-pending {
    background: transparent;
    border-left: 2px dashed #CBD5E1;
    width: 0;
}

.perjalanan-body {
    flex: 1;
    min-width: 0;
    padding-top: 2px;
}

.perjalanan-top-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.perjalanan-title {
    font-size: 1.02rem;
    font-weight: 700;
    color: #0F172A;
    line-height: 1.4;
}

.text-muted-title {
    color: #64748B !important;
    font-weight: 600;
}

.text-warning-dark {
    color: #B45309 !important;
}

.badge-pending-tag {
    background: #F1F5F9;
    color: #64748B;
    border: 1px solid #E2E8F0;
    font-size: 0.68rem;
    font-weight: 600;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
    letter-spacing: 0.02em;
}

.badge-current-tag {
    background: rgba(39, 67, 175, 0.1);
    color: #2743AF;
    border: 1px solid rgba(39, 67, 175, 0.25);
    font-size: 0.68rem;
    font-weight: 700;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
    letter-spacing: 0.02em;
}

.badge-warning-tag {
    background: rgba(245, 158, 11, 0.12);
    color: #B45309;
    border: 1px solid rgba(245, 158, 11, 0.3);
    font-size: 0.68rem;
    font-weight: 700;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
}

.badge-danger-tag {
    background: rgba(239, 68, 68, 0.12);
    color: #DC2626;
    border: 1px solid rgba(239, 68, 68, 0.3);
    font-size: 0.68rem;
    font-weight: 700;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
}

.perjalanan-date {
    font-size: 0.82rem;
    color: #2743AF;
    font-weight: 700;
    font-family: 'Plus Jakarta Sans', monospace;
    white-space: nowrap;
    flex-shrink: 0;
}

.perjalanan-date-pending {
    font-size: 0.8rem;
    color: #94A3B8;
}

.perjalanan-note {
    font-size: 0.88rem;
    color: #475569;
    margin-top: 0.35rem;
    line-height: 1.5;
}

.perjalanan-position {
    font-size: 0.82rem;
    color: #2743AF;
    font-weight: 600;
    margin-top: 0.25rem;
}

.text-muted-pending {
    color: #94A3B8 !important;
}

.perjalanan-by {
    font-size: 0.8rem;
    color: #64748B;
    margin-top: 0.2rem;
    font-style: italic;
}

.perjalanan-attachment {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    margin-top: 0.6rem;
}

/* Cetak Lembar Pendamping */
.cetak-pendamping-wrap {
    margin-top: 1.5rem;
}

.btn-cetak-pendamping {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.75rem;
    background: #2743AF;
    color: #fff;
    font-weight: 700;
    font-size: 0.95rem;
    border: none;
    border-radius: 999px;
    text-decoration: none;
    box-shadow: 0 4px 16px rgba(39, 67, 175, 0.3);
    transition: transform 0.2s, background-color 0.2s, box-shadow 0.2s;
    cursor: pointer;
}

.btn-cetak-pendamping:hover {
    background: #1f37a0;
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(39, 67, 175, 0.4);
    color: #fff;
}

/* Attachment Main */
.attachment-main-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
}

/* Not found */
.glass-result-card {
    border-radius: 2rem;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25) !important;
}

.animate-fade-up {
    animation: fadeUp 0.5s ease-out forwards;
}

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(24px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 991.98px) {
    .info-left-col {
        border-right: none;
        border-bottom: 1px solid #E2E8F0;
        padding: 1.5rem 1.5rem 1.75rem;
    }

    .right-timeline-col {
        padding: 1.5rem 1.5rem 1.75rem;
    }
}

@media (max-width: 767.98px) {
    #results {
        margin-top: -1rem !important;
        padding-top: 1.5rem !important;
        padding-bottom: 2.5rem !important;
    }

    .resi-header-card {
        padding: 1.25rem 1.25rem;
        border-radius: 1rem 1rem 0 0;
    }

    .resi-code {
        font-size: 1.2rem;
    }

    .resi-lane-badge {
        font-size: 0.72rem;
        padding: 0.4rem 0.85rem;
    }

    .tracking-unified-card {
        border-radius: 1rem;
    }

    .info-left-col,
    .right-timeline-col {
        padding: 1.25rem 1.25rem;
    }

    .perjalanan-item {
        gap: 0.85rem;
        padding-bottom: 1.25rem;
    }

    .perjalanan-line-col {
        width: 28px;
        min-width: 28px;
    }

    .perjalanan-dot {
        width: 26px;
        height: 26px;
        font-size: 0.75rem;
    }

    .perjalanan-title {
        font-size: 0.92rem;
    }

    .perjalanan-date {
        font-size: 0.75rem;
    }

    .perjalanan-note,
    .perjalanan-by {
        font-size: 0.78rem;
    }

    .btn-cetak-pendamping {
        width: 100%;
        font-size: 0.88rem;
        padding: 0.65rem 1.25rem;
    }

    /* Not found card */
    .glass-result-card.p-5 {
        padding: 2rem 1.25rem !important;
        width: 100%;
        max-width: none !important;
    }
}
</style>