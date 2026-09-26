<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

interface NotificationItem {
    id: number;
    letter_id: number | null;
    type: string;
    title: string;
    message: string;
    data: {
        tracking_code?: string;
        agenda_number?: string;
        sender_unit?: string;
        sender_name?: string;
        destination?: string;
        subject?: string;
        priority?: string;
        created_at?: string;
    } | null;
    is_read: boolean;
    created_at: string;
}

const isOpen = ref(false);
const unreadCount = ref(0);
const notifications = ref<NotificationItem[]>([]);
const isLoading = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);
const desktopPermission = ref<NotificationPermission>('default');
let pollInterval: any = null;

const checkNotificationPermission = () => {
    if (typeof window !== 'undefined' && 'Notification' in window) {
        desktopPermission.value = Notification.permission;
    }
};

const requestNotificationPermission = async () => {
    playChimeSound();
    if (typeof window !== 'undefined' && 'Notification' in window) {
        try {
            let perm = Notification.permission;
            if (perm !== 'granted') {
                const res = await Notification.requestPermission();
                perm = res || Notification.permission;
            }
            desktopPermission.value = perm;
            if (perm === 'granted') {
                new Notification('SiTrack - Notifikasi Aktif', {
                    body: 'Notifikasi browser dan desktop pengajuan surat masuk telah aktif!',
                    icon: '/favicon.ico',
                });
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: {
                        message: 'Notifikasi desktop dan suara telah berhasil diaktifkan!',
                        type: 'success'
                    }
                }));
            } else if (perm === 'denied') {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: {
                        message: 'Izin notifikasi diblokir browser. Silakan klik ikon gembok / setelan situs di address bar browser untuk mengizinkan.',
                        type: 'warning'
                    }
                }));
            }
        } catch (e) {
            console.error('Error requesting notification permission:', e);
        }
    } else {
        if (typeof window !== 'undefined') {
            (window as any).dispatchEvent(new CustomEvent('toast', {
                detail: {
                    message: 'Browser Anda tidak mendukung Web Notifications API.',
                    type: 'warning'
                }
            }));
        }
    }
};

const navigateToDetail = (item: NotificationItem) => {
    const data = getNotifData(item);
    const letterId = item.letter_id || data?.letter_id || data?.id;
    if (letterId) {
        router.get(`/tindak-lanjut/${letterId}/edit`);
    } else if (data?.tracking_code) {
        router.get('/tindak-lanjut', { search: data.tracking_code });
    } else {
        router.get('/tindak-lanjut');
    }
};

const showDesktopNotification = (item: NotificationItem) => {
    if (typeof window !== 'undefined' && 'Notification' in window && Notification.permission === 'granted') {
        try {
            const letterData = getNotifData(item);
            const title = 'Pengajuan Surat Masuk Baru!';
            const body = letterData?.sender_unit 
                ? `${letterData.sender_unit}: ${letterData.subject || item.message}`
                : item.message;
            
            const notif = new Notification(title, {
                body: body,
                icon: '/favicon.ico',
                badge: '/favicon.ico',
                tag: letterData?.tracking_code || `letter-${item.id}`,
            });

            notif.onclick = () => {
                window.focus();
                navigateToDetail(item);
            };
        } catch (e) {
            // ignore
        }
    }
};

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value && notifications.value.length === 0) {
        fetchNotifications();
    }
};

const formatTimeAgo = (dateString: string) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);

    if (diffSec < 60) return 'Baru saja';
    if (diffMin < 60) return `${diffMin} mnt lalu`;
    if (diffHour < 24) return `${diffHour} jam lalu`;
    if (diffDay === 1) return 'Kemarin';
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
};

const playChimeSound = () => {
    try {
        const audioCtx = new (window.AudioContext || (window as any).webkitAudioContext)();
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        const now = audioCtx.currentTime;

        // Tone 1 (High bell)
        const osc1 = audioCtx.createOscillator();
        const gain1 = audioCtx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(659.25, now); // E5
        gain1.gain.setValueAtTime(0.2, now);
        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
        osc1.connect(gain1);
        gain1.connect(audioCtx.destination);
        osc1.start(now);
        osc1.stop(now + 0.35);

        // Tone 2 (Harmonic bell chime)
        const osc2 = audioCtx.createOscillator();
        const gain2 = audioCtx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(987.77, now + 0.12); // B5
        gain2.gain.setValueAtTime(0.25, now + 0.12);
        gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.6);
        osc2.connect(gain2);
        gain2.connect(audioCtx.destination);
        osc2.start(now + 0.12);
        osc2.stop(now + 0.6);
    } catch (e) {
        // AudioContext might be blocked before first user gesture
    }
};

const fetchNotifications = async (isPolling = false) => {
    try {
        if (!isPolling) isLoading.value = true;
        const res = await axios.get('/notifications');
        if (res.data?.ok) {
            const previousCount = unreadCount.value;
            const newCount = res.data.unread_count || 0;
            const newNotifs: NotificationItem[] = res.data.notifications || [];

            unreadCount.value = newCount;
            notifications.value = newNotifs;

            // Trigger chime & desktop toast if new unread notification arrived
            if (isPolling && newCount > previousCount && previousCount >= 0) {
                playChimeSound();

                // Trigger Desktop Notification for newest item
                if (newNotifs.length > 0) {
                    showDesktopNotification(newNotifs[0]);
                }
            }
        }
    } catch (err) {
        // Silent error on polling
    } finally {
        if (!isPolling) isLoading.value = false;
    }
};

const getNotifData = (item: NotificationItem) => {
    if (!item?.data) return {};
    if (typeof item.data === 'string') {
        try {
            return JSON.parse(item.data);
        } catch (e) {
            return {};
        }
    }
    return item.data || {};
};

const markAsRead = async (item: NotificationItem) => {
    try {
        if (!item.is_read) {
            item.is_read = true;
            unreadCount.value = Math.max(0, unreadCount.value - 1);
            await axios.post(`/notifications/${item.id}/read`);
        }
    } catch (e) {
        // ignore
    }

    isOpen.value = false;
    navigateToDetail(item);
};

const markAllAsRead = async () => {
    try {
        unreadCount.value = 0;
        notifications.value.forEach(n => n.is_read = true);
        await axios.post('/notifications/read-all');
    } catch (e) {
        // ignore
    }
};

const handleClickOutside = (e: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    checkNotificationPermission();
    fetchNotifications();
    document.addEventListener('click', handleClickOutside);
    // Poll every 20 seconds
    pollInterval = setInterval(() => {
        fetchNotifications(true);
    }, 20000);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
    <div class="notification-wrapper position-relative" ref="dropdownRef">
        <!-- Bell Trigger Button -->
        <button
            type="button"
            class="btn-notification-trigger"
            :class="{ 'has-unread': unreadCount > 0, 'is-active': isOpen }"
            @click="toggleDropdown"
            title="Notifikasi Surat Masuk Baru"
        >
            <i class="bi bi-bell-fill"></i>
            <span v-if="unreadCount > 0" class="notification-badge">
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
            <span v-if="unreadCount > 0" class="notification-pulse"></span>
        </button>

        <!-- Notification Dropdown Panel -->
        <transition name="notif-fade">
            <div v-if="isOpen" class="notification-dropdown shadow-lg">
                <!-- Dropdown Header -->
                <div class="notif-header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="notif-header-title">Notifikasi Pengajuan</span>
                        <span v-if="unreadCount > 0" class="badge rounded-pill bg-danger-subtle text-danger fw-bold px-2 py-0.5" style="font-size: 0.72rem;">
                            {{ unreadCount }} Baru
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-1.5">
                        <button
                            type="button"
                            class="btn-icon-action"
                            @click="playChimeSound"
                            title="Tes Bunyi Notifikasi"
                        >
                            <i class="bi bi-volume-up"></i>
                        </button>
                        <button
                            v-if="unreadCount > 0"
                            type="button"
                            class="btn-mark-all"
                            @click="markAllAsRead"
                            title="Tandai semua telah dibaca"
                        >
                            <i class="bi bi-check2-all me-1"></i>Tandai Dibaca
                        </button>
                    </div>
                </div>

                <!-- Desktop Permission Banner -->
                <div v-if="desktopPermission !== 'granted'" class="notif-desktop-banner" :class="{ 'is-denied': desktopPermission === 'denied' }">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-1.5 small fw-medium" :class="desktopPermission === 'denied' ? 'text-danger' : 'text-primary'" style="font-size: 0.74rem;">
                            <i :class="desktopPermission === 'denied' ? 'bi bi-exclamation-triangle-fill text-danger' : 'bi bi-display'"></i>
                            <span v-if="desktopPermission === 'denied'">Izin notifikasi diblokir browser</span>
                            <span v-else>Munculkan notif di layar/desktop</span>
                        </div>
                        <button
                            type="button"
                            class="btn-enable-desktop"
                            :class="{ 'btn-denied-help': desktopPermission === 'denied' }"
                            @click="requestNotificationPermission"
                        >
                            {{ desktopPermission === 'denied' ? 'Buka Izin' : 'Aktifkan' }}
                        </button>
                    </div>
                </div>
                <div v-else class="notif-desktop-banner is-active">
                    <div class="d-flex align-items-center gap-1.5 small text-success fw-semibold" style="font-size: 0.74rem;">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Notifikasi desktop aktif</span>
                    </div>
                </div>

                <!-- Notification Body / List -->
                <div class="notif-body">
                    <div v-if="isLoading" class="p-4 text-center text-muted small">
                        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                        <div>Memuat notifikasi...</div>
                    </div>

                    <div v-else-if="notifications.length === 0" class="p-4 text-center text-muted">
                        <i class="bi bi-inbox fs-2 text-muted opacity-50 d-block mb-1"></i>
                        <span class="small fw-semibold">Belum ada pengajuan surat masuk baru</span>
                    </div>

                    <div v-else class="notif-list">
                        <div
                            v-for="item in notifications"
                            :key="item.id"
                            class="notif-item"
                            :class="{ 'is-unread': !item.is_read }"
                            @click="markAsRead(item)"
                        >
                            <div class="notif-icon-col">
                                <span class="notif-icon-circle" :class="getNotifData(item).priority === 'Segera' ? 'is-urgent' : (['Selesai dan Siap Untuk diambil', 'Selesai dan Siap Untuk Diambil', 'Dokumen selesai dan sudah bisa diambil', 'Surat Selesai di Paraf/TTD dan bisa diambil'].includes(getNotifData(item).status) ? 'is-ready' : 'is-normal')">
                                    <i :class="['Selesai dan Siap Untuk diambil', 'Selesai dan Siap Untuk Diambil', 'Dokumen selesai dan sudah bisa diambil', 'Surat Selesai di Paraf/TTD dan bisa diambil'].includes(getNotifData(item).status) ? 'bi bi-box-seam-fill' : 'bi bi-file-earmark-arrow-down-fill'"></i>
                                </span>
                            </div>
                            <div class="notif-content-col">
                                <div class="d-flex align-items-center justify-content-between gap-1 mb-0.5">
                                    <span class="notif-unit text-truncate" :title="getNotifData(item).sender_unit || 'Unit Pengusul'">
                                        {{ getNotifData(item).sender_unit || 'Unit Pengusul' }}
                                    </span>
                                    <span class="notif-time">{{ formatTimeAgo(item.created_at) }}</span>
                                </div>
                                <div class="notif-subject text-truncate" :title="getNotifData(item).subject || item.message">
                                    {{ getNotifData(item).subject || item.message }}
                                </div>
                                <div class="d-flex align-items-center gap-1.5 mt-1 flex-wrap">
                                    <span v-if="getNotifData(item).tracking_code" class="notif-chip font-monospace">
                                        {{ getNotifData(item).tracking_code }}
                                    </span>
                                    <span v-if="getNotifData(item).sender_name" class="notif-sender text-truncate">
                                        <i class="bi bi-person me-0.5"></i>{{ getNotifData(item).sender_name }}
                                    </span>
                                    <span v-if="getNotifData(item).priority === 'Segera'" class="badge bg-danger-subtle text-danger px-1.5 py-0.5" style="font-size: 0.65rem;">
                                        Segera
                                    </span>
                                </div>
                            </div>
                            <div v-if="!item.is_read" class="notif-dot-col">
                                <span class="unread-dot"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dropdown Footer -->
                <div class="notif-footer">
                    <button
                        type="button"
                        class="btn-view-all"
                        @click="router.get('/tindak-lanjut'); isOpen = false;"
                    >
                        Lihat Semua Berkas Tindak Lanjut <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.notification-wrapper {
    position: relative;
    display: inline-block;
}

/* Trigger Button */
.btn-notification-trigger {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--st-surface, #ffffff);
    border: 1px solid var(--st-border, #E2E8F0);
    color: var(--st-slate-muted, #64748B);
    display: grid;
    place-items: center;
    position: relative;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-notification-trigger:hover,
.btn-notification-trigger.is-active {
    background: var(--st-powder-cyan, #EEF7FC);
    border-color: var(--st-teal, #167992);
    color: var(--st-teal, #167992);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(22, 121, 146, 0.15);
}

.btn-notification-trigger.has-unread {
    color: var(--st-navy, #03205A);
}

/* Counter Badge */
.notification-badge {
    position: absolute;
    top: -3px;
    right: -3px;
    background: #EF4444;
    color: #ffffff;
    font-size: 0.65rem;
    font-weight: 800;
    padding: 2px 6px;
    border-radius: 999px;
    border: 2px solid #ffffff;
    line-height: 1.1;
    z-index: 2;
    box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
}

/* Pulse animation */
.notification-pulse {
    position: absolute;
    top: -3px;
    right: -3px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: rgba(239, 68, 68, 0.6);
    animation: notif-ping 1.8s cubic-bezier(0, 0, 0.2, 1) infinite;
    pointer-events: none;
    z-index: 1;
}

@keyframes notif-ping {
    0% {
        transform: scale(0.9);
        opacity: 0.8;
    }
    70%, 100% {
        transform: scale(2.2);
        opacity: 0;
    }
}

/* Dropdown Panel */
.notification-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 380px;
    max-width: calc(100vw - 32px);
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid rgba(22, 121, 146, 0.15);
    z-index: 2000;
    overflow: hidden;
    transform-origin: top right;
}

/* Header */
.notif-header {
    padding: 0.9rem 1.1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
}

.notif-header-title {
    font-weight: 700;
    font-size: 0.88rem;
    color: #03205A;
}

.btn-icon-action {
    background: transparent;
    border: 1px solid #E2E8F0;
    color: #64748B;
    font-size: 0.85rem;
    cursor: pointer;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: 0.2s;
}

.btn-icon-action:hover {
    background: #EEF7FC;
    color: #167992;
    border-color: #167992;
}

.btn-mark-all {
    background: transparent;
    border: none;
    color: #167992;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 6px;
    transition: 0.2s;
}

.btn-mark-all:hover {
    background: rgba(22, 121, 146, 0.1);
    color: #0e5b6f;
}

/* Desktop Banner */
.notif-desktop-banner {
    background: #EEF7FC;
    border-bottom: 1px solid #E0F0F8;
    padding: 0.5rem 1.1rem;
}

.btn-enable-desktop {
    background: #167992;
    color: #ffffff;
    border: none;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 10px;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-enable-desktop:hover {
    background: #0e5b6f;
}

/* Body & List */
.notif-body {
    max-height: 380px;
    overflow-y: auto;
}

.notif-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.85rem 1.1rem;
    border-bottom: 1px solid #F1F5F9;
    cursor: pointer;
    transition: background 0.15s ease;
}

.notif-item:hover {
    background: #F8FAFC;
}

.notif-item.is-unread {
    background: #EEF7FC;
}

.notif-item.is-unread:hover {
    background: #E2F0F9;
}

.notif-icon-col {
    flex: none;
    margin-top: 2px;
}

.notif-icon-circle {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    font-size: 0.95rem;
}

.notif-icon-circle.is-normal {
    background: rgba(22, 121, 146, 0.12);
    color: #167992;
}

.notif-icon-circle.is-ready {
    background: rgba(234, 179, 8, 0.2);
    color: #b45309;
}

.notif-icon-circle.is-urgent {
    background: rgba(239, 68, 68, 0.12);
    color: #EF4444;
}

.notif-content-col {
    flex: 1;
    min-width: 0;
}

.notif-unit {
    font-size: 0.8rem;
    font-weight: 700;
    color: #03205A;
}

.notif-time {
    font-size: 0.7rem;
    color: #94A3B8;
    flex: none;
}

.notif-subject {
    font-size: 0.78rem;
    color: #475569;
    margin-top: 1px;
    line-height: 1.35;
}

.notif-chip {
    background: #ffffff;
    border: 1px solid #CBD5E1;
    border-radius: 4px;
    padding: 1px 5px;
    font-size: 0.68rem;
    font-weight: 600;
    color: #1E293B;
}

.notif-sender {
    font-size: 0.7rem;
    color: #64748B;
    max-width: 130px;
}

.notif-dot-col {
    flex: none;
    padding-top: 6px;
}

.unread-dot {
    display: block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #167992;
    box-shadow: 0 0 0 2px rgba(22, 121, 146, 0.25);
}

/* Footer */
.notif-footer {
    padding: 0.65rem 1rem;
    background: #F8FAFC;
    border-top: 1px solid #E2E8F0;
    text-align: center;
}

.btn-view-all {
    background: transparent;
    border: none;
    color: #167992;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: 0.2s;
}

.btn-view-all:hover {
    color: #0e5b6f;
}

/* Transitions */
.notif-fade-enter-active,
.notif-fade-leave-active {
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.notif-fade-enter-from,
.notif-fade-leave-to {
    opacity: 0;
    transform: translateY(-8px) scale(0.96);
}
</style>
