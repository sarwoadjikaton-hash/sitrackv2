<script setup lang="ts">
import { Link, usePage, router, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
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

// ===== Floating Tooltip (Teleport-based) =====
const sidebarRef = ref<HTMLElement | null>(null);
const tooltip = ref({ visible: false, text: '', top: 0, left: 0 });

const showTooltip = (e: MouseEvent) => {
    if (!isSidebarCollapsed.value || window.innerWidth < 992) return;
    const target = (e.target as HTMLElement).closest('[data-tooltip]') as HTMLElement | null;
    if (!target || !sidebarRef.value) return;

    const itemRect = target.getBoundingClientRect();
    const sidebarRect = sidebarRef.value.getBoundingClientRect();

    tooltip.value = {
        visible: true,
        text: target.dataset.tooltip || '',
        top: itemRect.top + itemRect.height / 2,
        left: sidebarRect.right + 12,
    };
};

const hideTooltip = (e: MouseEvent) => {
    const target = (e.target as HTMLElement).closest('[data-tooltip]');
    const related = e.relatedTarget as HTMLElement | null;
    if (target && related && target.contains(related)) return;
    tooltip.value.visible = false;
};

const showPasswordModal = ref(false);
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const openPasswordModal = () => {
    passwordForm.reset();
    passwordForm.clearErrors();
    showPasswordModal.value = true;
};

const submitPasswordChange = () => {
    passwordForm.put('/password', {
        preserveScroll: true,
        onSuccess: () => {
            showPasswordModal.value = false;
            passwordForm.reset();
        },
    });
};

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
        <aside class="app-sidebar" ref="sidebarRef"
            :class="{ 'is-open': isMobileNavOpen, 'is-collapsed': isSidebarCollapsed }">
            <!-- Toggle Button (Floating on Edge) -->
            <button type="button" class="sidebar-toggle-btn d-none d-lg-flex" @click="toggleSidebarCollapse">
                <i class="bi" :class="isSidebarCollapsed ? 'bi-chevron-right' : 'bi-chevron-left'"></i>
            </button>

            <div class="sidebar-inner" @mouseover="showTooltip" @mouseout="hideTooltip">
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
                            @click="closeMobileNav" data-tooltip="Dashboard">
                            <i class="bi bi-grid-1x2-fill"></i>
                            <span class="nav-label">Dashboard</span>
                        </Link>
                    </nav>

                    <template v-if="!isSekjen">
                        <div class="sidebar-caption">Penomoran Surat</div>
                        <nav class="sidebar-nav">
                            <Link href="/ketersediaan-nomor" class="nav-link-item"
                                :class="{ active: isActive('/ketersediaan-nomor') }" @click="closeMobileNav"
                                data-tooltip="Ketersediaan Nomor">
                                <i class="bi bi-123"></i>
                                <span class="nav-label">Ketersediaan Nomor</span>
                            </Link>
                        </nav>
                    </template>

                    <template v-if="!isSekjen">
                        <div class="sidebar-caption">Tindak Lanjut / TTD</div>
                        <nav class="sidebar-nav">
                            <Link href="/tindak-lanjut" class="nav-link-item"
                                :class="{ active: isActive('/tindak-lanjut', true) }" @click="closeMobileNav"
                                data-tooltip="Data Tindak Lanjut">
                                <i class="bi bi-pen-fill"></i>
                                <span class="nav-label">Data Tindak Lanjut</span>
                            </Link>
                            <Link href="/data-surat" class="nav-link-item" :class="{ active: isActive('/data-surat') }"
                                @click="closeMobileNav" data-tooltip="Laporan Data Surat">
                                <i class="bi bi-table"></i>
                                <span class="nav-label">Laporan Data Surat</span>
                            </Link>
                        </nav>
                    </template>

                    <div class="sidebar-caption">Lajur Disposisi</div>
                    <nav class="sidebar-nav">
                        <Link v-if="!isSekjen" href="/disposisi/create" class="nav-link-item"
                            :class="{ active: isActive('/disposisi/create', true) }" @click="closeMobileNav"
                            data-tooltip="Input Disposisi">
                            <i class="bi bi-file-earmark-arrow-down-fill"></i>
                            <span class="nav-label">Input Disposisi</span>
                        </Link>
                        <Link href="/disposisi" class="nav-link-item"
                            :class="{ active: isActive('/disposisi') && !isActive('/disposisi/create', true) }"
                            @click="closeMobileNav" data-tooltip="Lajur Disposisi">
                            <i class="bi bi-diagram-3-fill"></i>
                            <span class="nav-label">Lajur Disposisi</span>
                        </Link>
                    </nav>

                    <template v-if="isSuperAdmin">
                        <div class="sidebar-caption">Master Data</div>
                        <nav class="sidebar-nav">
                            <Link href="/master/units" class="nav-link-item"
                                :class="{ active: isActive('/master/units') }" @click="closeMobileNav"
                                data-tooltip="Unit Kerja">
                                <i class="bi bi-building"></i> <span class="nav-label">Unit Kerja</span>
                            </Link>
                            <Link href="/master/number-types" class="nav-link-item"
                                :class="{ active: isActive('/master/number-types') }" @click="closeMobileNav"
                                data-tooltip="Jenis Naskah">
                                <i class="bi bi-file-code"></i> <span class="nav-label">Jenis Naskah</span>
                            </Link>
                            <Link href="/master/categories" class="nav-link-item"
                                :class="{ active: isActive('/master/categories') }" @click="closeMobileNav"
                                data-tooltip="Kategori Surat">
                                <i class="bi bi-tags-fill"></i> <span class="nav-label">Kategori Surat</span>
                            </Link>
                            <Link href="/master/users" class="nav-link-item"
                                :class="{ active: isActive('/master/users') }" @click="closeMobileNav"
                                data-tooltip="User & Akses">
                                <i class="bi bi-person-gear"></i> <span class="nav-label">User &amp; Akses</span>
                            </Link>
                            <Link href="/alur-status" class="nav-link-item"
                                :class="{ active: isActive('/alur-status', true) }" @click="closeMobileNav"
                                data-tooltip="Alur Status">
                                <i class="bi bi-bezier2"></i> <span class="nav-label">Alur Status</span>
                            </Link>
                            <Link href="/master/rekap" class="nav-link-item"
                                :class="{ active: isActive('/master/rekap') }" @click="closeMobileNav"
                                data-tooltip="Rekap Master">
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
                        <button type="button" class="logout-btn" @click="logout" data-tooltip="Keluar Sistem">
                            <i class="bi bi-box-arrow-right"></i>
                            <span class="nav-label">Keluar Sistem</span>
                        </button>
                    </div>

                    <!-- User Profile -->
                    <button type="button" class="sidebar-user border-0 w-100 text-start" data-tooltip="Ubah Password"
                        @click="openPasswordModal">
                        <div class="user-avatar-circle">
                            {{ (user?.name || user?.username || 'A').substring(0, 1).toUpperCase() }}
                        </div>
                        <div class="user-info">
                            <div class="user-name">{{ user?.name || user?.username }}</div>
                            <div class="user-role-badge">{{ roleLabel }}</div>
                        </div>
                        <i class="bi bi-key-fill user-profile-hint"></i>
                    </button>
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
                    <div class="topbar-heading">
                        <span class="topbar-heading-mark"></span>
                        <h2 class="topbar-title">{{ title || 'Sistem Tracking Persuratan' }}</h2>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <Link v-if="!isSekjen" href="/scan-status" class="btn-topbar btn-topbar-accent">
                        <span class="btn-topbar-icon"><i class="bi bi-qr-code-scan"></i></span>
                        <span class="d-none d-sm-inline">Scan QR</span>
                    </Link>
                    <Link href="/tracking" class="btn-topbar" target="_blank">
                        <span class="btn-topbar-icon"><i class="bi bi-box-arrow-up-right"></i></span>
                        <span class="d-none d-sm-inline">Portal Publik</span>
                    </Link>
                </div>
            </header>

            <main class="app-main-body">
                <slot></slot>
            </main>

            <footer class="app-page-footer no-print">
                <img src="/images/sitrack_logo.svg" alt="SiTrack" width="18" height="20" />
                <span>SiTrack &copy; TU SEKJEN</span>
            </footer>
        </div>

        <!-- Floating Tooltip (Teleport, lepas dari overflow sidebar) -->
        <Teleport to="body">
            <Transition name="tooltip-fade">
                <div v-if="tooltip.visible" class="sidebar-tooltip-floating"
                    :style="{ top: tooltip.top + 'px', left: tooltip.left + 'px' }">
                    {{ tooltip.text }}
                </div>
            </Transition>
        </Teleport>

        <!-- Modal Ubah Password -->
        <Modal :show="showPasswordModal" @close="showPasswordModal = false">
            <div class="p-4">
                <h5 class="fw-bold mb-3">Ubah Password</h5>
                <form @submit.prevent="submitPasswordChange">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password Saat Ini</label>
                        <input v-model="passwordForm.current_password" type="password" class="form-control" required
                            autofocus />
                        <div v-if="passwordForm.errors.current_password" class="text-danger small mt-1">{{
                            passwordForm.errors.current_password }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password Baru</label>
                        <input v-model="passwordForm.password" type="password" class="form-control" required
                            minlength="8" />
                        <div v-if="passwordForm.errors.password" class="text-danger small mt-1">{{
                            passwordForm.errors.password
                            }}</div>
                        <small class="text-muted">Minimal 8 karakter.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Konfirmasi Password Baru</label>
                        <input v-model="passwordForm.password_confirmation" type="password" class="form-control"
                            required minlength="8" />
                    </div>
                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <button type="button" class="btn btn-secondary"
                            @click="showPasswordModal = false">Batal</button>
                        <button type="submit" class="btn btn-primary-blue" :disabled="passwordForm.processing">Simpan
                            Password</button>
                    </div>
                </form>
            </div>
        </Modal>
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
    background: #1E416A;
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
    background: #3DA5F9;
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
    cursor: pointer;
    position: relative;
    transition: 0.2s;
}

.sidebar-user:hover {
    background: rgba(255, 255, 255, 0.1) !important;
}

.user-avatar-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #3DA5F9;
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

.user-profile-hint {
    font-size: 0.75rem;
    opacity: 0.4;
    flex: none;
}

.app-page-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    padding: 1.5rem 0 1rem;
    opacity: 0.5;
    font-size: 0.75rem;
    color: #64748b;
}

/* ===== FLOATING TOOLTIP (Teleport ke body) ===== */
.sidebar-tooltip-floating {
    position: fixed;
    transform: translateY(-50%);
    background: #0f172a;
    color: #fff;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 500;
    white-space: nowrap;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    pointer-events: none;
    z-index: 3000;
}

.tooltip-fade-enter-active,
.tooltip-fade-leave-active {
    transition: opacity 0.15s ease;
}

.tooltip-fade-enter-from,
.tooltip-fade-leave-to {
    opacity: 0;
}

/* COLLAPSED STATE ADJUSTMENTS */
@media (min-width: 992px) {

    .app-sidebar.is-collapsed .brand-text,
    .app-sidebar.is-collapsed .sidebar-caption,
    .app-sidebar.is-collapsed .nav-label,
    .app-sidebar.is-collapsed .user-info,
    .app-sidebar.is-collapsed .user-profile-hint {
        display: none;
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
    position: sticky;
    top: 0;
    z-index: 1000;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.1rem 1.5rem;
    background: linear-gradient(180deg, #ffffff 0%, var(--st-surface-sunken) 100%);
    border-bottom: 1px solid var(--st-border);
    box-shadow: var(--st-shadow-low);
}

.app-topbar {
    position: sticky;
    top: 1rem;
    z-index: 1000;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 1rem 1.5rem 1.5rem;
    padding: 1.1rem 1.5rem;
    border-radius: var(--st-radius);
    background: linear-gradient(180deg, #ffffff 0%, var(--st-surface-sunken) 100%);
    border: 1px solid var(--st-border);
    box-shadow: var(--st-shadow-med);
}

.topbar-heading {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.topbar-heading-mark {
    width: 4px;
    height: 22px;
    border-radius: 999px;
    flex-shrink: 0;
    background: linear-gradient(180deg, var(--st-primary) 0%, var(--st-accent) 100%);
}

.topbar-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.15rem;
    letter-spacing: -0.01em;
    color: var(--st-primary);
}

.hamburger-btn {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    background: var(--st-surface);
    border: 1px solid var(--st-border);
    border-radius: var(--st-radius-sm);
    color: var(--st-primary);
    transition: background-color 0.2s var(--st-ease), border-color 0.2s var(--st-ease), transform 0.15s var(--st-ease);
}

.hamburger-btn:hover {
    background: var(--st-primary-soft);
    border-color: var(--st-primary-light);
}

.hamburger-btn:active {
    transform: scale(0.94);
}

.btn-topbar {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.35rem 0.9rem 0.35rem 0.35rem;
    background: var(--st-surface);
    border: 1px solid var(--st-border);
    border-radius: 999px;
    text-decoration: none;
    color: var(--st-slate-muted);
    font-size: 0.85rem;
    font-weight: 600;
    transition: border-color 0.2s var(--st-ease), color 0.2s var(--st-ease),
        transform 0.2s var(--st-ease), box-shadow 0.2s var(--st-ease);
}

.btn-topbar-icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    font-size: 0.9rem;
    background: var(--st-primary-soft);
    color: var(--st-primary);
}

.btn-topbar:hover {
    border-color: var(--st-primary-light);
    color: var(--st-primary);
    transform: translateY(-1px);
    box-shadow: var(--st-shadow-low);
}

.btn-topbar-accent {
    background: var(--st-accent);
    border-color: var(--st-accent);
    color: #fff;
    box-shadow: 0 10px 22px -8px rgba(var(--st-accent-rgb), 0.5);
}

.btn-topbar-accent .btn-topbar-icon {
    background: rgba(255, 255, 255, 0.22);
    color: #fff;
}

.btn-topbar-accent:hover {
    color: #fff;
    border-color: var(--st-accent);
    transform: translateY(-2px);
    box-shadow: 0 14px 26px -8px rgba(var(--st-accent-rgb), 0.6);
}

.app-main-body {
    padding: 0.5rem 1.5rem 1.5rem;
}

.sidebar-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1040;
}
</style>