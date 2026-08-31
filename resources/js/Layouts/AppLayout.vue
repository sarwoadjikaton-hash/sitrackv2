<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ToastNotification from '@/Components/ToastNotification.vue';
import type { PageProps } from '@/types';

defineProps<{
    title?: string;
}>();

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);
const role = computed(() => user.value?.roles?.[0] || user.value?.role || 'admin');

const isSuperAdmin = computed(() => role.value === 'super_admin');
const isSekjen = computed(() => role.value === 'sekjen');

const isMobileNavOpen = ref(false);
const toggleMobileNav = () => (isMobileNavOpen.value = !isMobileNavOpen.value);
const closeMobileNav = () => (isMobileNavOpen.value = false);

const isSidebarCollapsed = ref(
    typeof window !== 'undefined' && localStorage.getItem('sitrack:sidebar-collapsed') === '1'
);
const toggleSidebarCollapse = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem('sitrack:sidebar-collapsed', isSidebarCollapsed.value ? '1' : '0');
};

const logout = () => router.post('/logout');

const roleLabel = computed(() => {
    switch (role.value) {
        case 'super_admin': return 'Super Admin';
        case 'admin': return 'Admin Operator';
        case 'kasubbag': return 'Kasubbag TU';
        case 'sekjen': return 'Sekretaris Jenderal';
        default: return 'Staf Persuratan';
    }
});

const currentUrl = computed(() => page.url);
const isActive = (path: string, exact = false) =>
    exact ? currentUrl.value === path : currentUrl.value.startsWith(path);
</script>

<template>
    <div class="app-shell">
        <ToastNotification />

        <!-- Sidebar Navigation -->
        <aside class="app-sidebar" :class="{ 'is-open': isMobileNavOpen, 'is-collapsed': isSidebarCollapsed }">
            <div class="sidebar-blob sidebar-blob-a"></div>
            <div class="sidebar-blob sidebar-blob-b"></div>

            <!-- Toggle Button (Floating on Edge) -->
            <button type="button" class="sidebar-toggle-btn d-none d-lg-flex" @click="toggleSidebarCollapse">
                <i class="bi" :class="isSidebarCollapsed ? 'bi-chevron-right' : 'bi-chevron-left'"></i>
            </button>

            <div class="sidebar-inner">
                <!-- Brand Header -->
                <div class="sidebar-header">
                    <Link href="/dashboard" class="brand-wrapper" @click="closeMobileNav">
                        <span class="brand-logo-ring">
                            <img src="/images/sitrack_logo.svg" alt="SiTrack" width="32" height="32" />
                        </span>
                        <span class="brand-text">
                            <span class="brand-title">SiTrack</span>
                            <small>Sistem Persuratan</small>
                        </span>
                    </Link>
                    <button type="button" class="sidebar-close d-lg-none" @click="closeMobileNav">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="sidebar-scroll">
                    <div class="sidebar-caption">Menu Utama</div>
                    <nav class="sidebar-nav">
                        <Link href="/dashboard" class="nav-link-item" :class="{ active: isActive('/dashboard') }"
                            @click="closeMobileNav">
                            <i class="bi bi-grid-1x2-fill"></i>
                            <span class="nav-label">Dashboard</span>
                        </Link>
                    </nav>

                    <template v-if="!isSekjen">
                        <div class="sidebar-caption">Penomoran Surat</div>
                        <nav class="sidebar-nav">
                            <Link href="/ketersediaan-nomor" class="nav-link-item"
                                :class="{ active: isActive('/ketersediaan-nomor') }" @click="closeMobileNav">
                                <i class="bi bi-123"></i>
                                <span class="nav-label">Ketersediaan Nomor</span>
                            </Link>
                        </nav>
                    </template>

                    <template v-if="!isSekjen">
                        <div class="sidebar-caption">Tindak Lanjut / TTD</div>
                        <nav class="sidebar-nav">
                            <Link href="/tindak-lanjut/create" class="nav-link-item"
                                :class="{ active: isActive('/tindak-lanjut/create', true) }" @click="closeMobileNav">
                                <i class="bi bi-file-earmark-check-fill"></i>
                                <span class="nav-label">Input TTD Baru</span>
                            </Link>
                            <Link href="/tindak-lanjut" class="nav-link-item"
                                :class="{ active: isActive('/tindak-lanjut', true) }" @click="closeMobileNav">
                                <i class="bi bi-pen-fill"></i>
                                <span class="nav-label">Data Tindak Lanjut</span>
                            </Link>
                            <Link href="/data-surat" class="nav-link-item" :class="{ active: isActive('/data-surat') }"
                                @click="closeMobileNav">
                                <i class="bi bi-table"></i>
                                <span class="nav-label">Laporan Data Surat</span>
                            </Link>
                        </nav>
                    </template>

                    <div class="sidebar-caption">Lajur Disposisi</div>
                    <nav class="sidebar-nav">
                        <Link v-if="!isSekjen" href="/disposisi/create" class="nav-link-item"
                            :class="{ active: isActive('/disposisi/create', true) }" @click="closeMobileNav">
                            <i class="bi bi-file-earmark-arrow-down-fill"></i>
                            <span class="nav-label">Input Disposisi</span>
                        </Link>
                        <Link href="/disposisi" class="nav-link-item"
                            :class="{ active: isActive('/disposisi') && !isActive('/disposisi/create', true) }"
                            @click="closeMobileNav">
                            <i class="bi bi-diagram-3-fill"></i>
                            <span class="nav-label">Lajur Disposisi</span>
                        </Link>
                    </nav>

                    <template v-if="isSuperAdmin">
                        <div class="sidebar-caption">Master Data</div>
                        <nav class="sidebar-nav">
                            <Link href="/master/units" class="nav-link-item"
                                :class="{ active: isActive('/master/units') }" @click="closeMobileNav">
                                <i class="bi bi-building"></i> <span class="nav-label">Unit Kerja</span>
                            </Link>
                            <Link href="/master/number-types" class="nav-link-item"
                                :class="{ active: isActive('/master/number-types') }" @click="closeMobileNav">
                                <i class="bi bi-file-code"></i> <span class="nav-label">Jenis Naskah</span>
                            </Link>
                            <Link href="/master/categories" class="nav-link-item"
                                :class="{ active: isActive('/master/categories') }" @click="closeMobileNav">
                                <i class="bi bi-tags-fill"></i> <span class="nav-label">Kategori Surat</span>
                            </Link>
                            <Link href="/master/users" class="nav-link-item"
                                :class="{ active: isActive('/master/users') }" @click="closeMobileNav">
                                <i class="bi bi-person-gear"></i> <span class="nav-label">User &amp; Akses</span>
                            </Link>
                            <Link href="/alur-status" class="nav-link-item"
                                :class="{ active: isActive('/alur-status', true) }" @click="closeMobileNav">
                                <i class="bi bi-bezier2"></i> <span class="nav-label">Alur Status</span>
                            </Link>
                            <Link href="/master/rekap" class="nav-link-item"
                                :class="{ active: isActive('/master/rekap') }" @click="closeMobileNav">
                                <i class="bi bi-file-earmark-spreadsheet"></i> <span class="nav-label">Rekap
                                    Master</span>
                            </Link>
                        </nav>
                    </template>
                </div>

                <!-- FOOTER AREA -->
                <div class="sidebar-footer-container">
                    <!-- Logout Button (Positioned above user on collapse via CSS) -->
                    <div class="logout-wrapper">
                        <button type="button" class="logout-btn" @click="logout">
                            <i class="bi bi-box-arrow-right"></i>
                            <span class="nav-label">Keluar Sistem</span>
                        </button>
                    </div>

                    <!-- User Profile -->
                    <div class="sidebar-user">
                        <div class="user-avatar-circle">
                            {{ (user?.name || user?.username || 'A').substring(0, 1).toUpperCase() }}
                        </div>
                        <div class="user-info">
                            <div class="user-name">{{ user?.name || user?.username }}</div>
                            <div class="user-role-badge">{{ roleLabel }}</div>
                        </div>
                    </div>

                    <!-- Sidebar Logo Footer -->
                    <div class="sidebar-copyright">
                        <img src="/images/sitrack_logo.svg" alt="SiTrack" width="20" height="22" />
                        <span class="nav-label">SiTrack &copy; 2026</span>
                    </div>
                </div>
            </div>
        </aside>

        <transition name="fade">
            <div v-if="isMobileNavOpen" class="sidebar-backdrop d-lg-none" @click="closeMobileNav"></div>
        </transition>

        <!-- Main Workspace -->
        <div class="app-content" :class="{ 'content-expanded': isSidebarCollapsed }">
            <header class="app-topbar no-print">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="hamburger-btn d-lg-none" @click="toggleMobileNav">
                        <i class="bi bi-list"></i>
                    </button>
                    <h2 class="topbar-title">{{ title || 'Sistem Tracking Persuratan' }}</h2>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <Link v-if="!isSekjen" href="/scan-status" class="btn-topbar btn-topbar-accent">
                        <i class="bi bi-qr-code-scan"></i>
                        <span class="d-none d-sm-inline">Scan QR</span>
                    </Link>
                    <Link href="/tracking" class="btn-topbar" target="_blank">
                        <i class="bi bi-box-arrow-up-right"></i>
                        <span class="d-none d-sm-inline">Portal Publik</span>
                    </Link>
                </div>
            </header>

            <main class="app-main-body">
                <slot></slot>
            </main>
        </div>
    </div>
</template>

<style scoped>
.app-shell {
    display: flex;
    min-height: 100vh;
    background: #f1f5f9;
    --sidebar-width: 260px;
    --sidebar-collapsed-width: 85px;
    --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ===== SIDEBAR ===== */
.app-sidebar {
    position: fixed;
    inset: 1rem auto 1rem 1rem;
    width: var(--sidebar-width);
    z-index: 1050;
    background: linear-gradient(175deg, #0b1739 0%, #0f2b6b 45%, #1d4ed8 110%);
    color: #fff;
    border-radius: 2rem;
    transition: width var(--transition), transform var(--transition);
}

@media (min-width: 992px) {
    .app-sidebar {
        transform: translateX(0);
        overflow: visible;
    }

    .app-sidebar.is-collapsed {
        width: var(--sidebar-collapsed-width);
    }
}

@media (max-width: 991px) {
    .app-sidebar {
        inset: 0.75rem auto 0.75rem 0.75rem;
        transform: translateX(-110%);
        width: 280px;
    }

    .app-sidebar.is-open {
        transform: translateX(0);
    }
}

.sidebar-inner {
    height: 100%;
    display: flex;
    flex-direction: column;
    position: relative;
    z-index: 2;
}

/* FLOATING TOGGLE BUTTON */
.sidebar-toggle-btn {
    position: absolute;
    right: -14px;
    top: 50px;
    width: 28px;
    height: 28px;
    background: #2563eb;
    color: #fff;
    border: 3px solid #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 1100;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.sidebar-close {
    width: 32px;
    height: 32px;
    display: grid;
    place-items: center;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    color: #fff;
}

.sidebar-header {
    padding: 1.5rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.brand-wrapper {
    display: flex;
    align-items: center;
    gap: .7rem;
    text-decoration: none;
    color: #fff;
}

.brand-logo-ring {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    flex: none;
}

.brand-text {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.brand-title {
    font-weight: 800;
    font-size: 1rem;
}

.brand-text small {
    font-size: 0.65rem;
    opacity: 0.6;
}

.sidebar-scroll {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding-bottom: 1rem;
}

.sidebar-caption {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.3);
    padding: 1.2rem 1.5rem 0.5rem;
    letter-spacing: 0.5px;
}

/* NAV ITEMS & NOTCH EFFECT */
.sidebar-nav {
    display: flex;
    flex-direction: column;
    padding-left: 0.75rem;
}

.nav-link-item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1.25rem;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 0.85rem;
    border-radius: 1.5rem 0 0 1.5rem;
    transition: all 0.2s;
}

.nav-link-item i {
    font-size: 1.1rem;
    width: 24px;
    text-align: center;
}

.nav-link-item:hover:not(.active) {
    transform: translateX(4px);
    color: #fff;
}

/* Active Notch Effect */
.nav-link-item.active {
    background: #f1f5f9;
    color: #0f172a;
    font-weight: 700;
}

.nav-link-item.active::before,
.nav-link-item.active::after {
    content: '';
    position: absolute;
    right: 0;
    width: 20px;
    height: 20px;
    background: transparent;
    pointer-events: none;
}

.nav-link-item.active::before {
    top: -20px;
    border-radius: 0 0 20px 0;
    box-shadow: 10px 10px 0 10px #f1f5f9;
}

.nav-link-item.active::after {
    bottom: -20px;
    border-radius: 0 20px 0 0;
    box-shadow: 10px -10px 0 10px #f1f5f9;
}

/* FOOTER AREA */
.sidebar-footer-container {
    margin-top: auto;
    padding: 0.5rem 0.75rem 1.5rem;
    display: flex;
    flex-direction: column;
}

.logout-wrapper {
    padding: 0 0.5rem 0.5rem;
}

.logout-btn {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 0.65rem 1rem;
    background: rgba(239, 68, 68, 0.1);
    border: none;
    border-radius: 12px;
    color: #fca5a5;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}

.logout-btn:hover {
    background: rgba(239, 68, 68, 0.2);
    color: #fff;
}

.sidebar-user {
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 1.2rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #38bdf8, #2563eb);
    display: grid;
    place-items: center;
    font-weight: 700;
    flex: none;
}

.user-info {
    flex: 1;
    overflow: hidden;
}

.user-name {
    font-size: 0.8rem;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-role-badge {
    font-size: 0.65rem;
    color: rgba(255, 255, 255, 0.5);
}

.sidebar-copyright {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0 1rem;
    opacity: 0.4;
    font-size: 0.7rem;
}

/* COLLAPSED STATE ADJUSTMENTS */
@media (min-width: 992px) {

    .app-sidebar.is-collapsed .brand-text,
    .app-sidebar.is-collapsed .sidebar-caption,
    .app-sidebar.is-collapsed .nav-label,
    .app-sidebar.is-collapsed .user-info {
        display: none;
    }

    .app-sidebar.is-collapsed .nav-link-item {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }

    /* Logout button above user on collapsed */
    .app-sidebar.is-collapsed .sidebar-footer-container {
        flex-direction: column-reverse;
        align-items: center;
    }

    .app-sidebar.is-collapsed .logout-wrapper {
        padding: 0;
        margin-top: 1rem;
        margin-bottom: 0;
    }

    .app-sidebar.is-collapsed .logout-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        justify-content: center;
        padding: 0;
    }

    .app-sidebar.is-collapsed .sidebar-user {
        background: transparent;
        justify-content: center;
        padding: 0;
        margin-bottom: 0;
    }

    .app-sidebar.is-collapsed .sidebar-copyright {
        display: none;
    }
}

/* MAIN CONTENT */
.app-content {
    flex: 1;
    min-width: 0;
    margin-left: calc(var(--sidebar-width) + 1.5rem);
    transition: margin-left var(--transition);
}

.app-content.content-expanded {
    margin-left: calc(var(--sidebar-collapsed-width) + 1.5rem);
}

@media (max-width: 991px) {
    .app-content {
        margin-left: 0;
    }
}

.app-topbar {
    padding: 1rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.topbar-title {
    font-weight: 800;
    color: #1e293b;
    font-size: 1.1rem;
}

.hamburger-btn {
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    color: #1e293b;
}

.btn-topbar {
    padding: 0.5rem 1rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    text-decoration: none;
    color: #64748b;
    font-size: 0.85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-topbar-accent {
    background: #2563eb;
    color: #fff;
    border: none;
}

.app-main-body {
    padding: 1.5rem;
}

.sidebar-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1040;
}

.sidebar-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(50px);
    opacity: .25;
    pointer-events: none;
}

.sidebar-blob-a {
    width: 150px;
    height: 150px;
    background: #38bdf8;
    top: 0;
    right: 0;
}

.sidebar-blob-b {
    width: 120px;
    height: 120px;
    background: #2563eb;
    bottom: 10%;
    left: 0;
}
</style>