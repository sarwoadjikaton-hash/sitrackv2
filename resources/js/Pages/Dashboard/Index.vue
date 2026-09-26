<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Letter, Disposition, LetterNumberType, PageProps } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    stats: {
        signature: { total: number; in_progress: number; completed: number };
        disposition: { total: number; in_progress: number; completed: number };
        stock: { year: number; used: number; available: number; reserved: number; total: number };
    };
    recentLetters: Letter[];
    recentDispositions: Disposition[];
    typesSummary: LetterNumberType[];
}>();

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);

const greeting = () => {
    const h = new Date().getHours();
    if (h < 11) return 'Selamat pagi';
    if (h < 15) return 'Selamat siang';
    if (h < 18) return 'Selamat sore';
    return 'Selamat malam';
};

const firstName = computed(() => {
    const rawName = user.value?.name || user.value?.username || 'Dyah Permatasari';
    return rawName.split(',')[0].split(' ').slice(-2).join(' ');
});

const todayDateFormatted = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(new Date());
});

const isSekjen = computed(() => {
    const r = user.value?.roles?.[0] || user.value?.role || '';
    return r === 'sekjen' || r === 'Sekretaris Jenderal';
});

// Workbook preview list
const workbookColors = ['#3DA5F9', '#10B981', '#F59E0B', '#8B5CF6', '#06B6D4', '#EC4899'];
const previewWorkbooks = computed(() => {
    if (!props.typesSummary || props.typesSummary.length === 0) {
        return [
            { id: 1, name: 'NODIN', available: 45, total: 247, color: '#3DA5F9' },
            { id: 2, name: 'Surat Tugas', available: 60, total: 292, color: '#10B981' },
            { id: 3, name: 'Undangan', available: 30, total: 130, color: '#F59E0B' },
            { id: 4, name: 'SK', available: 20, total: 53, color: '#8B5CF6' },
        ];
    }
    return props.typesSummary.slice(0, 4).map((t, idx) => ({
        id: t.id,
        name: t.workbook_name || t.type_name,
        available: t.available_slots || 0,
        total: (t.total_slots && t.total_slots > 0) ? t.total_slots : ((t.available_slots || 0) + (t.used_slots || 0) || 100),
        color: workbookColors[idx % workbookColors.length]
    }));
});
</script>

<template>
    <AppLayout title="Dashboard">
        <Head title="Dashboard" />

        <div class="w-full space-y-6">
            <!-- Greeting & Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="font-display font-bold text-xl text-slate-900">
                        {{ greeting() }}, {{ firstName }} 👋
                    </h1>
                    <p class="text-sm text-slate-500 mt-0.5">
                        {{ todayDateFormatted }} · Sistem berjalan normal
                    </p>
                </div>
                <div class="flex gap-2.5 flex-wrap">
                    <Link href="/tindak-lanjut/create"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-all hover:opacity-90 shadow-sm"
                        style="background: linear-gradient(135deg, #2743AF, #1a2d7a);">
                        <i class="bi bi-plus-lg text-xs"></i>
                        <span>Surat Baru</span>
                    </Link>
                    <Link v-if="!isSekjen" href="/disposisi/create"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors bg-white"
                        style="color: #2743AF; border: 1px solid #C7D2FE;">
                        <i class="bi bi-send text-xs"></i>
                        <span>Input Disposisi</span>
                    </Link>
                    <Link href="/scan-status"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-600 border border-slate-200 hover:bg-slate-50 transition-colors bg-white">
                        <i class="bi bi-qr-code-scan text-xs"></i>
                        <span>Scan QR</span>
                    </Link>
                </div>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Tindak Lanjut -->
                <Link href="/tindak-lanjut"
                    class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 text-left hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group block text-decoration-none">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-sm"
                            style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                            <i class="bi bi-file-earmark-text text-white text-lg"></i>
                        </div>
                        <i class="bi bi-arrow-right text-slate-300 group-hover:text-slate-400 group-hover:translate-x-0.5 transition-all text-sm"></i>
                    </div>
                    <div class="text-2xl font-bold font-display mb-1 text-slate-900">
                        {{ stats.signature.total.toLocaleString('id-ID') }}
                    </div>
                    <div class="text-xs font-medium text-slate-600 mb-2 leading-tight">Tindak Lanjut / TTD</div>
                    <div class="flex gap-3 text-xs">
                        <span class="text-amber-600 font-medium">{{ stats.signature.in_progress }} Dalam Proses</span>
                        <span class="text-emerald-600 font-medium">{{ stats.signature.completed }} Selesai</span>
                    </div>
                </Link>

                <!-- Card 2: Lajur Disposisi -->
                <Link href="/disposisi"
                    class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 text-left hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group block text-decoration-none">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-sm"
                            style="background: linear-gradient(135deg, #6366f1, #7c3aed);">
                            <i class="bi bi-send text-white text-base"></i>
                        </div>
                        <i class="bi bi-arrow-right text-slate-300 group-hover:text-slate-400 group-hover:translate-x-0.5 transition-all text-sm"></i>
                    </div>
                    <div class="text-2xl font-bold font-display mb-1 text-slate-900">
                        {{ stats.disposition.total.toLocaleString('id-ID') }}
                    </div>
                    <div class="text-xs font-medium text-slate-600 mb-2 leading-tight">Lajur Disposisi</div>
                    <div class="flex gap-3 text-xs">
                        <span class="text-amber-600 font-medium">{{ stats.disposition.in_progress }} Dalam Proses</span>
                        <span class="text-emerald-600 font-medium">{{ stats.disposition.completed }} Tuntas</span>
                    </div>
                </Link>

                <!-- Card 3: Nomor Tersedia -->
                <Link href="/ketersediaan-nomor"
                    class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 text-left hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group block text-decoration-none">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-sm"
                            style="background: linear-gradient(135deg, #14b8a6, #10b981);">
                            <i class="bi bi-hash text-white text-xl"></i>
                        </div>
                        <i class="bi bi-arrow-right text-slate-300 group-hover:text-slate-400 group-hover:translate-x-0.5 transition-all text-sm"></i>
                    </div>
                    <div class="text-2xl font-bold font-display mb-1" style="color: #2743AF;">
                        {{ stats.stock.available.toLocaleString('id-ID') }}
                    </div>
                    <div class="text-xs font-medium text-slate-600 mb-2 leading-tight">Nomor Tersedia</div>
                    <div class="flex gap-3 text-xs">
                        <span class="text-slate-500">Seluruh workbook</span>
                        <span class="text-slate-400">Stok {{ stats.stock.year }}</span>
                    </div>
                </Link>

                <!-- Card 4: Nomor Terpakai -->
                <Link href="/data-surat"
                    class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 text-left hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group block text-decoration-none">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-sm"
                            style="background: linear-gradient(135deg, #f97316, #f59e0b);">
                            <i class="bi bi-book text-white text-base"></i>
                        </div>
                        <i class="bi bi-arrow-right text-slate-300 group-hover:text-slate-400 group-hover:translate-x-0.5 transition-all text-sm"></i>
                    </div>
                    <div class="text-2xl font-bold font-display mb-1 text-slate-900">
                        {{ stats.stock.used.toLocaleString('id-ID') }}
                    </div>
                    <div class="text-xs font-medium text-slate-600 mb-2 leading-tight">Nomor Terpakai</div>
                    <div class="flex gap-3 text-xs">
                        <span class="font-medium" style="color: #2743AF;">{{ stats.stock.used }} Bulan ini</span>
                        <span class="text-slate-400">Tahun {{ stats.stock.year }}</span>
                    </div>
                </Link>
            </div>

            <!-- Main Columns Grid (5 Columns: 3 Left, 2 Right) -->
            <div class="grid lg:grid-cols-5 gap-6">
                <!-- Left: Recent Tindak Lanjut (3 Cols) -->
                <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h2 class="font-display font-semibold text-slate-900 text-sm">Tindak Lanjut Terbaru</h2>
                            <p class="text-xs text-slate-400 mt-0.5">5 naskah terakhir masuk</p>
                        </div>
                        <Link href="/tindak-lanjut" class="text-xs hover:underline font-medium flex items-center gap-1 text-decoration-none"
                            style="color: #2743AF;">
                            <span>Lihat semua</span>
                            <i class="bi bi-arrow-right text-xs"></i>
                        </Link>
                    </div>

                    <div class="divide-y divide-slate-100 flex-1">
                        <Link v-for="l in recentLetters" :key="l.id" :href="`/tindak-lanjut/${l.id}/edit`"
                            class="px-5 py-3 hover:bg-slate-50 transition-colors cursor-pointer block text-decoration-none">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-mono text-[11px] text-slate-400 tracking-tight">{{ l.tracking_code || l.agenda_number || '-' }}</span>
                                        <span v-if="(l.priority || '').toLowerCase() === 'urgent' || (l.priority || '').toLowerCase() === 'segera'"
                                            class="text-[10px] font-bold px-1.5 py-0.5 rounded text-orange-700 bg-orange-50 border border-orange-200">
                                            SEGERA
                                        </span>
                                    </div>
                                    <p class="text-sm text-slate-800 font-medium line-clamp-1 leading-snug mb-0.5">
                                        {{ l.subject }}
                                    </p>
                                    <p class="text-xs text-slate-400 mt-0.5 mb-0">
                                        {{ l.sender_unit || l.sender_name }} &bull; {{ l.received_date || l.letter_date || '-' }}
                                    </p>
                                </div>
                                <div class="flex-shrink-0 pt-0.5">
                                    <StatusBadge :status="l.status" />
                                </div>
                            </div>
                        </Link>

                        <div v-if="recentLetters.length === 0" class="text-center py-8 text-slate-400 text-sm">
                            Belum ada naskah tindak lanjut.
                        </div>
                    </div>
                </div>

                <!-- Right Column (2 Cols: Disposisi + Workbook Stock) -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Recent Disposisi -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h2 class="font-display font-semibold text-slate-900 text-sm">Disposisi Masuk</h2>
                            <Link href="/disposisi" class="text-xs hover:underline font-medium flex items-center gap-1 text-decoration-none"
                                style="color: #2743AF;">
                                <span>Semua</span>
                                <i class="bi bi-arrow-right text-xs"></i>
                            </Link>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <Link v-for="d in recentDispositions" :key="d.id" :href="`/disposisi/${d.letter_id || d.id}`"
                                class="px-5 py-3 hover:bg-slate-50 transition-colors cursor-pointer block text-decoration-none">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                                        style="background: #EEF1FB;">
                                        <i class="bi bi-send text-xs" style="color: #2743AF;"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-semibold text-slate-800 line-clamp-2 leading-snug mb-1">
                                            {{ d.letter?.subject || d.instruction }}
                                        </p>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[11px] text-slate-400 text-truncate">
                                                {{ d.from_name || d.to_name || 'Instansi Terkait' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </Link>

                            <div v-if="recentDispositions.length === 0" class="text-center py-8 text-slate-400 text-sm">
                                Belum ada instruksi disposisi.
                            </div>
                        </div>
                    </div>

                    <!-- Quick Workbook Stock Stats -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100">
                            <h2 class="font-display font-semibold text-slate-900 text-sm">Stok Nomor per Workbook</h2>
                        </div>
                        <div class="p-4 space-y-3">
                            <div v-for="wb in previewWorkbooks" :key="wb.id">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs font-semibold text-slate-700">{{ wb.name }}</span>
                                    <span class="text-[11px] text-slate-400">{{ wb.available }} tersedia</span>
                                </div>
                                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-300"
                                        :style="{ width: `${Math.min(100, Math.max(0, (wb.available / wb.total) * 100))}%`, background: wb.color }">
                                    </div>
                                </div>
                            </div>
                            <Link href="/ketersediaan-nomor"
                                class="w-full mt-2 text-xs hover:underline text-center font-medium py-1.5 block text-decoration-none"
                                style="color: #2743AF;">
                                Kelola semua nomor &rarr;
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
