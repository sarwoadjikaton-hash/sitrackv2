<script setup lang="ts">
import { Link, usePage, router, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import ToastNotification from '@/Components/ToastNotification.vue';
import type { PageProps } from '@/types';

const props = defineProps<{
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

const expandedGroups = ref<Set<string>>(
    new Set(['Menu Utama', 'Penomoran Surat', 'Tindak Lanjut / TTD', 'Lajur Disposisi', 'Master Data'])
);

const toggleGroup = (title: string) => {
    if (expandedGroups.value.has(title)) {
        expandedGroups.value.delete(title);
    } else {
        expandedGroups.value.add(title);
    }
};

const logout = () => router.post('/logout');

// ===== Topbar User Dropdown =====
const showUserDropdown = ref(false);
const toggleUserDropdown = () => {
    showUserDropdown.value = !showUserDropdown.value;
};
const closeUserDropdown = () => {
    showUserDropdown.value = false;
};

// ===== Command Palette / Global Search (Ctrl + K) =====
const showSearchModal = ref(false);
const searchQuery = ref('');

const openSearchModal = () => {
    searchQuery.value = '';
    showSearchModal.value = true;
};

const handleGlobalKeydown = (e: KeyboardEvent) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        showSearchModal.value = !showSearchModal.value;
    } else if (e.key === 'Escape') {
        showSearchModal.value = false;
        showUserDropdown.value = false;
    }
};

const executeSearch = () => {
    if (!searchQuery.value.trim()) return;
    const q = searchQuery.value.trim();
    showSearchModal.value = false;
    router.get('/data-surat', { search: q });
};

const navigateTo = (url: string) => {
    showSearchModal.value = false;
    router.visit(url);
};

onMounted(() => {
    window.addEventListener('keydown', handleGlobalKeydown);
    window.addEventListener('click', (e) => {
        const target = e.target as HTMLElement;
        if (!target.closest('.topbar-user-pill-container')) {
            closeUserDropdown();
        }
    });
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown);
});

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
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const openPasswordModal = () => {
    passwordForm.reset();
    passwordForm.clearErrors();
    showCurrentPassword.value = false;
    showNewPassword.value = false;
    showConfirmPassword.value = false;
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

const breadcrumb = computed(() => {
    const url = currentUrl.value;
    if (url.startsWith('/dashboard')) return { group: 'Menu Utama', page: 'Dashboard' };
    if (url.startsWith('/ketersediaan-nomor')) return { group: 'Penomoran Surat', page: 'Ketersediaan Nomor' };
    if (url.startsWith('/tindak-lanjut')) return { group: 'Tindak Lanjut / TTD', page: 'Data Tindak Lanjut' };
    if (url.startsWith('/data-surat')) return { group: 'Tindak Lanjut / TTD', page: 'Laporan Data Surat' };
    if (url.startsWith('/disposisi/create')) return { group: 'Lajur Disposisi', page: 'Input Disposisi' };
    if (url.startsWith('/disposisi')) return { group: 'Lajur Disposisi', page: 'Lajur Disposisi' };
    if (url.startsWith('/scan-status') || url.startsWith('/scan-qr')) return { group: 'Layanan', page: 'Scan & Update Status' };
    if (url.startsWith('/master/units')) return { group: 'Master Data', page: 'Unit Kerja' };
    if (url.startsWith('/master/number-types')) return { group: 'Master Data', page: 'Jenis Naskah' };
    if (url.startsWith('/master/categories')) return { group: 'Master Data', page: 'Kategori Surat' };
    if (url.startsWith('/master/users')) return { group: 'Master Data', page: 'User & Akses' };
    if (url.startsWith('/alur-status')) return { group: 'Master Data', page: 'Alur Status' };
    if (url.startsWith('/master/rekap')) return { group: 'Master Data', page: 'Rekap Master' };
    return { group: 'Menu Utama', page: props.title || 'Dashboard' };
});
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
                <!-- Brand Header (Dual Logo) -->
                <div class="sidebar-header">
                    <Link href="/dashboard" class="brand-wrapper" @click="closeMobileNav">
                        <div class="brand-logos-pair">
                            <span class="brand-logo-ring" title="SiTrack">
                                <img src="/images/sitrack_logo.svg" alt="SiTrack" width="28" height="28" />
                            </span>
                            <span class="brand-pipe-divider">|</span>
                            <span class="brand-logo-ring brand-kemnaker-ring" title="Kementerian Ketenagakerjaan RI">
                                <img src="/images/kemnaker_logo.png" alt="Kemnaker" width="26" height="26" class="brand-kemnaker-img" />
                            </span>
                        </div>
                        <span class="brand-text">
                            <span class="brand-title">SiTrack</span>
                            <small class="brand-subtitle">TU SEKRETARIAT JENDERAL</small>
                        </span>
                    </Link>
                    <button type="button" class="sidebar-close d-lg-none" @click="closeMobileNav">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Quick Action Button -->
                <div class="sidebar-quick-action px-3 mb-2">
                    <Link href="/scan-status" class="sidebar-scan-btn" :class="{ active: isActive('/scan-status') || isActive('/scan-qr') }" @click="closeMobileNav" data-tooltip="Scan & Update Status">
                        <i class="bi bi-qr-code-scan"></i>
                        <span class="nav-label">Scan &amp; Update Status</span>
                    </Link>
                </div>

                <div class="sidebar-scroll">
                    <!-- Menu Utama -->
                    <div class="sidebar-group-item">
                        <button type="button" class="sidebar-caption-btn" @click="toggleGroup('Menu Utama')">
                            <span>Menu Utama</span>
                            <i class="bi bi-chevron-down group-chevron" :class="{ 'rotate-minus-90': !expandedGroups.has('Menu Utama') }"></i>
                        </button>
                        <div v-show="expandedGroups.has('Menu Utama')" class="sidebar-nav">
                            <Link href="/dashboard" class="nav-link-item" :class="{ active: isActive('/dashboard') }"
                                @click="closeMobileNav" data-tooltip="Dashboard">
                                <i class="bi bi-grid-1x2-fill"></i>
                                <span class="nav-label">Dashboard</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Penomoran Surat -->
                    <template v-if="!isSekjen">
                        <div class="sidebar-group-item">
                            <button type="button" class="sidebar-caption-btn" @click="toggleGroup('Penomoran Surat')">
                                <span>Penomoran Surat</span>
                                <i class="bi bi-chevron-down group-chevron" :class="{ 'rotate-minus-90': !expandedGroups.has('Penomoran Surat') }"></i>
                            </button>
                            <div v-show="expandedGroups.has('Penomoran Surat')" class="sidebar-nav">
                                <Link href="/ketersediaan-nomor" class="nav-link-item"
                                    :class="{ active: isActive('/ketersediaan-nomor') }" @click="closeMobileNav"
                                    data-tooltip="Ketersediaan Nomor">
                                    <i class="bi bi-hash"></i>
                                    <span class="nav-label">Ketersediaan Nomor</span>
                                </Link>
                            </div>
                        </div>
                    </template>

                    <!-- Tindak Lanjut / TTD -->
                    <template v-if="!isSekjen">
                        <div class="sidebar-group-item">
                            <button type="button" class="sidebar-caption-btn" @click="toggleGroup('Tindak Lanjut / TTD')">
                                <span>Tindak Lanjut / TTD</span>
                                <i class="bi bi-chevron-down group-chevron" :class="{ 'rotate-minus-90': !expandedGroups.has('Tindak Lanjut / TTD') }"></i>
                            </button>
                            <div v-show="expandedGroups.has('Tindak Lanjut / TTD')" class="sidebar-nav">
                                <Link href="/tindak-lanjut" class="nav-link-item"
                                    :class="{ active: isActive('/tindak-lanjut') && !isActive('/tindak-lanjut/create', true) }" @click="closeMobileNav"
                                    data-tooltip="Data Tindak Lanjut">
                                    <i class="bi bi-list-task"></i>
                                    <span class="nav-label">Data Tindak Lanjut</span>
                                </Link>
                                <Link href="/data-surat" class="nav-link-item" :class="{ active: isActive('/data-surat') }"
                                    @click="closeMobileNav" data-tooltip="Laporan Data Surat">
                                    <i class="bi bi-book"></i>
                                    <span class="nav-label">Laporan Data Surat</span>
                                </Link>
                            </div>
                        </div>
                    </template>

                    <!-- Lajur Disposisi -->
                    <div class="sidebar-group-item">
                        <button type="button" class="sidebar-caption-btn" @click="toggleGroup('Lajur Disposisi')">
                            <span>Lajur Disposisi</span>
                            <i class="bi bi-chevron-down group-chevron" :class="{ 'rotate-minus-90': !expandedGroups.has('Lajur Disposisi') }"></i>
                        </button>
                        <div v-show="expandedGroups.has('Lajur Disposisi')" class="sidebar-nav">
                            <Link v-if="!isSekjen" href="/disposisi/create" class="nav-link-item"
                                :class="{ active: isActive('/disposisi/create', true) }" @click="closeMobileNav"
                                data-tooltip="Input Disposisi">
                                <i class="bi bi-envelope"></i>
                                <span class="nav-label">Input Disposisi</span>
                            </Link>
                            <Link href="/disposisi" class="nav-link-item"
                                :class="{ active: isActive('/disposisi') && !isActive('/disposisi/create', true) }"
                                @click="closeMobileNav" data-tooltip="Lajur Disposisi">
                                <i class="bi bi-send-fill"></i>
                                <span class="nav-label">Lajur Disposisi</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Master Data -->
                    <template v-if="isSuperAdmin">
                        <div class="sidebar-group-item">
                            <button type="button" class="sidebar-caption-btn" @click="toggleGroup('Master Data')">
                                <span>Master Data</span>
                                <i class="bi bi-chevron-down group-chevron" :class="{ 'rotate-minus-90': !expandedGroups.has('Master Data') }"></i>
                            </button>
                            <div v-show="expandedGroups.has('Master Data')" class="sidebar-nav">
                                <Link href="/master/units" class="nav-link-item"
                                    :class="{ active: isActive('/master/units') }" @click="closeMobileNav"
                                    data-tooltip="Unit Kerja">
                                    <i class="bi bi-building"></i> <span class="nav-label">Unit Kerja</span>
                                </Link>
                                <Link href="/master/number-types" class="nav-link-item"
                                    :class="{ active: isActive('/master/number-types') }" @click="closeMobileNav"
                                    data-tooltip="Jenis Naskah">
                                    <i class="bi bi-tag"></i> <span class="nav-label">Jenis Naskah</span>
                                </Link>
                                <Link href="/master/categories" class="nav-link-item"
                                    :class="{ active: isActive('/master/categories') }" @click="closeMobileNav"
                                    data-tooltip="Kategori Surat">
                                    <i class="bi bi-folder"></i> <span class="nav-label">Kategori Surat</span>
                                </Link>
                                <Link href="/master/users" class="nav-link-item"
                                    :class="{ active: isActive('/master/users') }" @click="closeMobileNav"
                                    data-tooltip="User & Akses">
                                    <i class="bi bi-people"></i> <span class="nav-label">User &amp; Akses</span>
                                </Link>
                                <Link href="/alur-status" class="nav-link-item"
                                    :class="{ active: isActive('/alur-status', true) }" @click="closeMobileNav"
                                    data-tooltip="Alur Status">
                                    <i class="bi bi-activity"></i> <span class="nav-label">Alur Status</span>
                                </Link>
                                <Link href="/master/rekap" class="nav-link-item"
                                    :class="{ active: isActive('/master/rekap') }" @click="closeMobileNav"
                                    data-tooltip="Rekap Master">
                                    <i class="bi bi-bar-chart"></i> <span class="nav-label">Rekap Master</span>
                                </Link>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- FOOTER AREA (Figma Prototype Model) -->
                <div class="sidebar-footer-container">
                    <!-- User Profile -->
                    <div class="sidebar-user-card" @click="openPasswordModal" data-tooltip="Ubah Password">
                        <div class="user-avatar-circle">
                            {{ (user?.name || user?.username || 'D').substring(0, 1).toUpperCase() }}
                        </div>
                        <div class="user-info">
                            <div class="user-name">{{ user?.name || user?.username }}</div>
                            <div class="user-role-label">{{ roleLabel }}</div>
                        </div>
                    </div>

                    <!-- Footer Action Links -->
                    <div class="sidebar-footer-links">
                        <button type="button" class="sidebar-footer-link-btn" @click="openPasswordModal" data-tooltip="Ganti Password">
                            <i class="bi bi-lock"></i>
                            <span class="nav-label">Ganti Password</span>
                        </button>
                        <button type="button" class="sidebar-footer-link-btn" @click="logout" data-tooltip="Keluar">
                            <i class="bi bi-box-arrow-right"></i>
                            <span class="nav-label">Keluar</span>
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Floating Tooltip for Collapsed Sidebar -->
        <Teleport to="body">
            <transition name="tooltip-fade">
                <div v-if="tooltip.visible && tooltip.text" class="sidebar-tooltip-floating"
                    :style="{ top: `${tooltip.top}px`, left: `${tooltip.left}px` }">
                    {{ tooltip.text }}
                </div>
            </transition>
        </Teleport>

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
                        <div class="d-none d-sm-flex align-items-center gap-1.5 small">
                            <span class="text-secondary">{{ breadcrumb.group }}</span>
                            <span class="text-muted opacity-50">/</span>
                            <span class="text-dark fw-bold font-display">{{ breadcrumb.page }}</span>
                        </div>
                        <h2 class="topbar-title d-sm-none">{{ title || 'Dashboard' }}</h2>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <!-- Search Input (Figma model - click or Ctrl+K opens Command Palette) -->
                    <div class="topbar-search-box d-none d-md-flex align-items-center cursor-pointer" @click="openSearchModal" title="Tekan Ctrl+K untuk pencarian cepat">
                        <i class="bi bi-search text-muted me-2" style="font-size: 0.85rem;"></i>
                        <input type="text" placeholder="Cari surat..." class="topbar-search-input pointer-events-none" readonly />
                        <span class="topbar-kbd">⌘K</span>
                    </div>

                    <NotificationBell />

                    <!-- User Pill Badge (Figma model with Dropdown) -->
                    <div class="topbar-user-pill-container position-relative">
                        <button type="button" class="topbar-user-pill d-none d-lg-flex align-items-center gap-2 border-0 bg-transparent" @click="toggleUserDropdown">
                            <div class="user-avatar-circle user-avatar-topbar">
                                {{ (user?.name || user?.username || 'A').substring(0, 1).toUpperCase() }}
                            </div>
                            <div class="text-start leading-tight">
                                <div class="fw-bold text-dark" style="font-size: 0.82rem; line-height: 1.1;">{{ user?.name || user?.username }}</div>
                                <div class="text-muted" style="font-size: 0.68rem;">{{ roleLabel }}</div>
                            </div>
                            <i class="bi bi-chevron-down text-muted ms-1" style="font-size: 0.75rem;"></i>
                        </button>

                        <!-- User Dropdown Menu -->
                        <transition name="fade">
                            <div v-if="showUserDropdown" class="topbar-user-menu-dropdown shadow-lg rounded-3 border bg-white position-absolute end-0 mt-2 py-2" style="width: 240px; z-index: 1060;">
                                <div class="px-3 py-2 border-bottom">
                                    <div class="fw-bold text-dark small">{{ user?.name || user?.username }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ user?.email || user?.username + '@kemnaker.go.id' }}</div>
                                    <span class="badge bg-primary-subtle text-primary mt-1" style="font-size: 0.68rem;">{{ roleLabel }}</span>
                                </div>
                                <div class="py-1">
                                    <button type="button" class="dropdown-item px-3 py-2 small d-flex align-items-center gap-2" @click="openPasswordModal(); closeUserDropdown();">
                                        <i class="bi bi-key text-primary"></i> Ganti Password
                                    </button>
                                    <Link href="/alur-status" class="dropdown-item px-3 py-2 small d-flex align-items-center gap-2" @click="closeUserDropdown">
                                        <i class="bi bi-bezier2 text-info"></i> Panduan Alur SOP
                                    </Link>
                                    <div class="dropdown-divider my-1"></div>
                                    <button type="button" class="dropdown-item px-3 py-2 small text-danger d-flex align-items-center gap-2" @click="logout(); closeUserDropdown();">
                                        <i class="bi bi-box-arrow-right"></i> Keluar
                                    </button>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>
            </header>

            <main class="app-main-body">
                <slot></slot>
            </main>

            <footer class="app-page-footer no-print">
                <div class="d-flex flex-wrap align-items-center justify-content-center gap-2">
                    <div class="d-inline-flex align-items-center gap-2">
                        <img src="/images/sitrack_logo.svg" alt="SiTrack" width="16" height="16" />
                        <span class="opacity-40">|</span>
                        <img src="/images/kemnaker_logo.png" alt="Kemnaker" width="16" height="16" />
                    </div>
                    <span>SiTrack &bull; Kementerian Ketenagakerjaan RI &bull; TU SEKJEN &copy; 2026</span>
                </div>
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

        <!-- Command Palette / Global Search Modal (Ctrl + K) -->
        <Modal :show="showSearchModal" max-width="lg" @close="showSearchModal = false">
            <div class="p-0 overflow-hidden">
                <!-- Search Input Header -->
                <div class="p-3 border-bottom d-flex align-items-center gap-3 bg-light">
                    <i class="bi bi-search fs-5 text-primary"></i>
                    <input
                        v-model="searchQuery"
                        type="text"
                        class="form-control border-0 bg-transparent fs-6 shadow-none px-0"
                        placeholder="Ketik nomor surat, nomor agenda, perihal, atau kode tracking..."
                        autofocus
                        @keyup.enter="executeSearch"
                    />
                    <button v-if="searchQuery" class="btn btn-sm btn-link text-muted p-0 text-decoration-none" @click="searchQuery = ''">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                    <span class="badge bg-secondary-subtle text-secondary font-monospace px-2 py-1">ESC</span>
                </div>

                <!-- Navigation Quick Links / Results -->
                <div class="p-3" style="max-height: 380px; overflow-y: auto;">
                    <div v-if="searchQuery.trim()" class="mb-3">
                        <div class="text-uppercase fw-bold small text-muted mb-2 px-2" style="font-size: 0.72rem;">Hasil Pencarian</div>
                        <button
                            type="button"
                            class="w-100 text-start btn btn-light border-0 d-flex align-items-center justify-content-between p-2.5 rounded-3 mb-1"
                            @click="executeSearch"
                        >
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-search text-primary"></i>
                                <span>Cari "<strong>{{ searchQuery }}</strong>" di semua Data Surat & Register</span>
                            </div>
                            <span class="badge bg-primary text-white">Enter ↵</span>
                        </button>
                    </div>

                    <!-- Quick Navigation -->
                    <div class="text-uppercase fw-bold small text-muted mb-2 px-2" style="font-size: 0.72rem;">Navigasi Cepat</div>
                    <div class="list-group list-group-flush border-0">
                        <button type="button" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-2 rounded-2 border-0 mb-1" @click="navigateTo('/dashboard')">
                            <div class="d-flex align-items-center gap-2.5">
                                <span class="badge bg-primary-subtle text-primary p-2 rounded-2"><i class="bi bi-grid-1x2-fill"></i></span>
                                <div>
                                    <div class="fw-semibold small text-dark">Dashboard</div>
                                    <small class="text-muted" style="font-size: 0.72rem;">Ringkasan eksekutif dan statistik persuratan</small>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </button>

                        <button type="button" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-2 rounded-2 border-0 mb-1" @click="navigateTo('/ketersediaan-nomor')">
                            <div class="d-flex align-items-center gap-2.5">
                                <span class="badge bg-success-subtle text-success p-2 rounded-2"><i class="bi bi-hash"></i></span>
                                <div>
                                    <div class="fw-semibold small text-dark">Ketersediaan Nomor</div>
                                    <small class="text-muted" style="font-size: 0.72rem;">Cek stok nomor, reservasi nomor dan batch</small>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </button>

                        <button type="button" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-2 rounded-2 border-0 mb-1" @click="navigateTo('/tindak-lanjut')">
                            <div class="d-flex align-items-center gap-2.5">
                                <span class="badge bg-primary-subtle text-primary p-2 rounded-2"><i class="bi bi-list-task"></i></span>
                                <div>
                                    <div class="fw-semibold small text-dark">Data Tindak Lanjut / TTD</div>
                                    <small class="text-muted" style="font-size: 0.72rem;">Lajur penandatanganan dan paraf naskah dinas</small>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </button>

                        <button type="button" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-2 rounded-2 border-0 mb-1" @click="navigateTo('/data-surat')">
                            <div class="d-flex align-items-center gap-2.5">
                                <span class="badge bg-info-subtle text-info p-2 rounded-2"><i class="bi bi-table"></i></span>
                                <div>
                                    <div class="fw-semibold small text-dark">Laporan Data Surat</div>
                                    <small class="text-muted" style="font-size: 0.72rem;">Buku register penomoran dan ekspor data</small>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </button>

                        <button type="button" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-2 rounded-2 border-0 mb-1" @click="navigateTo('/disposisi')">
                            <div class="d-flex align-items-center gap-2.5">
                                <span class="badge bg-warning-subtle text-warning p-2 rounded-2"><i class="bi bi-send-fill"></i></span>
                                <div>
                                    <div class="fw-semibold small text-dark">Lajur Disposisi</div>
                                    <small class="text-muted" style="font-size: 0.72rem;">Arahan pimpinan dan penerusan lembar disposisi</small>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </button>

                        <button type="button" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-2 rounded-2 border-0 mb-1" @click="navigateTo('/scan-status')">
                            <div class="d-flex align-items-center gap-2.5">
                                <span class="badge bg-danger-subtle text-danger p-2 rounded-2"><i class="bi bi-qr-code-scan"></i></span>
                                <div>
                                    <div class="fw-semibold small text-dark">Scan & Update Status</div>
                                    <small class="text-muted" style="font-size: 0.72rem;">Pindai QR fisik surat untuk update status instan</small>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </button>
                    </div>
                </div>

                <div class="p-2.5 bg-light border-top d-flex align-items-center justify-content-between text-muted small px-3" style="font-size: 0.75rem;">
                    <span>Tip: Gunakan <kbd class="bg-white border text-dark px-1.5 py-0.5 rounded">Ctrl</kbd> + <kbd class="bg-white border text-dark px-1.5 py-0.5 rounded">K</kbd> kapan saja</span>
                    <span>Tekan <kbd class="bg-white border text-dark px-1.5 py-0.5 rounded">ESC</kbd> untuk tutup</span>
                </div>
            </div>
        </Modal>

        <!-- Modal Ubah Password -->
        <Modal :show="showPasswordModal" @close="showPasswordModal = false">
            <div class="bg-white rounded-2xl p-4 sm:p-5">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                    <h5 class="fw-bold text-dark m-0" style="font-size: 1.05rem;">Ganti Password</h5>
                    <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none" @click="showPasswordModal = false">
                        <i class="bi bi-x-lg fs-6"></i>
                    </button>
                </div>
                <form @submit.prevent="submitPasswordChange">
                    <!-- Password Lama -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Password Lama</label>
                        <div class="input-group">
                            <input
                                v-model="passwordForm.current_password"
                                :type="showCurrentPassword ? 'text' : 'password'"
                                class="form-control rounded-start-lg border-end-0"
                                required
                                autofocus
                                placeholder="Masukkan password lama"
                            />
                            <button
                                type="button"
                                class="btn btn-outline-secondary border border-start-0 text-muted bg-white rounded-end-lg"
                                @click="showCurrentPassword = !showCurrentPassword"
                                tabindex="-1"
                                title="Lihat/Sembunyikan password"
                            >
                                <i class="bi" :class="showCurrentPassword ? 'bi-eye-slash-fill' : 'bi-eye-fill'"></i>
                            </button>
                        </div>
                        <div v-if="passwordForm.errors.current_password" class="text-danger small mt-1">
                            {{ passwordForm.errors.current_password }}
                        </div>
                    </div>

                    <!-- Password Baru -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Password Baru</label>
                        <div class="input-group">
                            <input
                                v-model="passwordForm.password"
                                :type="showNewPassword ? 'text' : 'password'"
                                class="form-control rounded-start-lg border-end-0"
                                required
                                minlength="8"
                                placeholder="Masukkan password baru"
                            />
                            <button
                                type="button"
                                class="btn btn-outline-secondary border border-start-0 text-muted bg-white rounded-end-lg"
                                @click="showNewPassword = !showNewPassword"
                                tabindex="-1"
                                title="Lihat/Sembunyikan password"
                            >
                                <i class="bi" :class="showNewPassword ? 'bi-eye-slash-fill' : 'bi-eye-fill'"></i>
                            </button>
                        </div>
                        <div v-if="passwordForm.errors.password" class="text-danger small mt-1">
                            {{ passwordForm.errors.password }}
                        </div>
                        <small class="text-muted" style="font-size: 0.72rem;">Minimal 8 karakter.</small>
                    </div>

                    <!-- Konfirmasi Password Baru -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary mb-1">Konfirmasi Password Baru</label>
                        <div class="input-group">
                            <input
                                v-model="passwordForm.password_confirmation"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                class="form-control rounded-start-lg border-end-0"
                                required
                                minlength="8"
                                placeholder="Konfirmasi password baru"
                            />
                            <button
                                type="button"
                                class="btn btn-outline-secondary border border-start-0 text-muted bg-white rounded-end-lg"
                                @click="showConfirmPassword = !showConfirmPassword"
                                tabindex="-1"
                                title="Lihat/Sembunyikan password"
                            >
                                <i class="bi" :class="showConfirmPassword ? 'bi-eye-slash-fill' : 'bi-eye-fill'"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex gap-2 pt-2 border-top">
                        <button type="button" class="btn btn-light border flex-grow-1 text-secondary font-medium"
                            @click="showPasswordModal = false">Batal</button>
                        <button type="submit" class="btn text-white flex-grow-1 font-medium" style="background-color: #2743AF;" :disabled="passwordForm.processing">
                            <span v-if="passwordForm.processing" class="spinner-border spinner-border-sm me-1" role="status"></span>
                            Simpan
                        </button>
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
    background: #EEF7FC;
    --sidebar-width: 260px;
    --sidebar-collapsed-width: 85px;
    --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ===== SIDEBAR ===== */
.app-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: var(--sidebar-width);
    z-index: 1050;
    background: #1a2d7a;
    color: #fff;
    border-radius: 0;
    border-right: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 2px 0 16px rgba(3, 32, 90, 0.18);
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
        top: 0;
        left: 0;
        bottom: 0;
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

/* TOGGLE BUTTON */
.sidebar-toggle-btn {
    position: absolute;
    right: -13px;
    top: 20px;
    width: 26px;
    height: 26px;
    background: #2743AF;
    color: #fff;
    border: 2px solid #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 1100;
    box-shadow: 0 2px 8px rgba(3, 32, 90, 0.25);
    transition: background 0.2s ease, transform 0.2s ease;
}

.sidebar-toggle-btn:hover {
    background: #1f37a0;
    transform: scale(1.1);
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
    padding: 1.1rem 1.25rem 0.85rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.brand-wrapper {
    display: flex;
    align-items: center;
    gap: .75rem;
    text-decoration: none;
    color: #fff;
}

.brand-logos-pair {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex: none;
}

.brand-logo-ring {
    width: 32px;
    height: 32px;
    display: grid;
    place-items: center;
    background: rgba(61, 165, 249, 0.25);
    border: 1px solid rgba(61, 165, 249, 0.4);
    border-radius: 8px;
    flex: none;
    transition: background 0.2s;
}

.brand-kemnaker-img {
    filter: brightness(0) invert(1);
}

.brand-pipe-divider {
    color: rgba(255, 255, 255, 0.35);
    font-size: 0.95rem;
    font-weight: 300;
}

.brand-text {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.brand-title {
    font-weight: 800;
    font-size: 0.95rem;
    letter-spacing: -0.01em;
}

.brand-subtitle {
    font-size: 0.65rem;
    color: #93c5fd;
    font-weight: 500;
}

.sidebar-scan-btn {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.5rem 0.75rem;
    background: transparent;
    border-radius: 8px;
    color: #dbeafe;
    font-size: 0.85rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.15s ease;
}

.sidebar-scan-btn:hover {
    background: rgba(255, 255, 255, 0.08);
    color: #ffffff;
}

.sidebar-scan-btn i {
    font-size: 1rem;
    color: #93c5fd;
}

.sidebar-scroll {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 0.5rem 0 1rem;
}

.sidebar-group-item {
    margin-bottom: 0.35rem;
}

.sidebar-caption-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: rgba(147, 197, 253, 0.7);
    padding: 0.45rem 0.85rem 0.25rem;
    background: transparent;
    border: none;
    cursor: pointer;
    text-align: left;
    transition: color 0.15s ease;
}

.sidebar-caption-btn:hover {
    color: #ffffff;
}

.group-chevron {
    font-size: 10px;
    transition: transform 0.2s ease;
}

.rotate-minus-90 {
    transform: rotate(-90deg);
}

.sidebar-caption {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: rgba(147, 197, 253, 0.7);
    padding: 0.75rem 0.85rem 0.25rem;
}

/* NAV ITEMS */
.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    padding: 0 0.5rem;
}

.nav-link-item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.5rem 0.75rem;
    color: #dbeafe;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 400;
    border-radius: 8px;
    transition: all 0.15s ease;
}

.nav-link-item i {
    font-size: 0.95rem;
    width: 18px;
    text-align: center;
    color: #93c5fd;
    transition: color 0.15s ease;
}

.nav-link-item:hover:not(.active) {
    color: #fff;
    background: rgba(255, 255, 255, 0.08);
}

.nav-link-item:hover:not(.active) i {
    color: #fff;
}

/* Active State */
.nav-link-item.active {
    background: rgba(61, 165, 249, 0.18);
    border-left: 3px solid #3DA5F9;
    border-radius: 8px;
    color: #ffffff;
    font-weight: 500;
    padding-left: calc(0.75rem - 3px);
}

.nav-link-item.active i {
    color: #3DA5F9;
}

/* FOOTER AREA (Figma Model) */
.sidebar-footer-container {
    margin-top: auto;
    padding: 0.75rem;
    display: flex;
    flex-direction: column;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-user-card {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.5rem 0.65rem;
    background: transparent;
    border: none;
    border-radius: 8px;
    margin-bottom: 0.25rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.sidebar-user-card:hover {
    background: rgba(255, 255, 255, 0.08);
}

.user-avatar-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(61, 165, 249, 0.3);
    display: grid;
    place-items: center;
    font-weight: 700;
    color: #fff;
    flex: none;
    font-size: 0.85rem;
}

.user-info {
    flex: 1;
    overflow: hidden;
}

.user-name {
    font-size: 0.75rem;
    font-weight: 600;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}

.user-role-label {
    font-size: 10px;
    color: #93c5fd;
    font-weight: 400;
    margin-top: 2px;
}

.sidebar-footer-links {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.sidebar-footer-link-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.5rem;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: #bfdbfe;
    font-size: 0.75rem;
    font-weight: 400;
    cursor: pointer;
    text-align: left;
    transition: all 0.15s ease;
}

.sidebar-footer-link-btn:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.08);
}

.sidebar-footer-link-btn:last-child:hover {
    color: #fca5a5;
    background: rgba(239, 68, 68, 0.12);
}

.sidebar-footer-link-btn i {
    font-size: 0.85rem;
}

.sidebar-copyright {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0 1rem;
    opacity: 0.6;
    font-size: 0.7rem;
    color: #B5CCE3;
}

.user-profile-hint {
    font-size: 0.75rem;
    opacity: 0.6;
    color: #B5CCE3;
    flex: none;
}

.app-page-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    padding: 1.5rem 0 1rem;
    opacity: 0.75;
    font-size: 0.75rem;
    color: var(--st-slate-muted);
}

/* ===== FLOATING TOOLTIP (Teleport ke body) ===== */
.sidebar-tooltip-floating {
    position: fixed;
    transform: translateY(-50%);
    background: #03205A;
    color: #fff;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
    box-shadow: 0 4px 14px rgba(3, 32, 90, 0.35);
    border: 1px solid rgba(181, 204, 227, 0.3);
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

    .app-sidebar.is-collapsed {
        width: var(--sidebar-collapsed-width);
    }

    .app-sidebar.is-collapsed .sidebar-header {
        justify-content: center;
        padding: 1.1rem 0.5rem;
    }

    .app-sidebar.is-collapsed .brand-wrapper {
        justify-content: center;
        gap: 0;
    }

    .app-sidebar.is-collapsed .brand-logos-pair {
        justify-content: center;
    }

    .app-sidebar.is-collapsed .brand-text,
    .app-sidebar.is-collapsed .brand-pipe-divider,
    .app-sidebar.is-collapsed .brand-kemnaker-ring,
    .app-sidebar.is-collapsed .sidebar-caption,
    .app-sidebar.is-collapsed .sidebar-caption-btn,
    .app-sidebar.is-collapsed .nav-label,
    .app-sidebar.is-collapsed .user-info,
    .app-sidebar.is-collapsed .user-profile-hint,
    .app-sidebar.is-collapsed .group-chevron {
        display: none !important;
    }

    .app-sidebar.is-collapsed .sidebar-group-item {
        margin-bottom: 0.25rem;
    }

    .app-sidebar.is-collapsed .sidebar-group-item:not(:first-child)::before {
        content: '';
        display: block;
        width: 32px;
        height: 1px;
        background: rgba(255, 255, 255, 0.12);
        margin: 0.35rem auto;
    }

    .app-sidebar.is-collapsed .sidebar-quick-action {
        padding: 0 !important;
        display: flex;
        justify-content: center;
    }

    .app-sidebar.is-collapsed .sidebar-scan-btn {
        width: 44px;
        height: 44px;
        padding: 0;
        justify-content: center;
        margin: 0 auto;
        border-radius: 10px;
    }

    .app-sidebar.is-collapsed .sidebar-nav {
        padding: 0 0.5rem;
        align-items: center;
        display: flex !important;
        gap: 0.35rem;
    }

    .app-sidebar.is-collapsed .nav-link-item {
        justify-content: center;
        width: 44px;
        height: 44px;
        padding: 0;
        margin: 0 auto;
        border-radius: 10px;
        border-left: none !important;
    }

    .app-sidebar.is-collapsed .nav-link-item.active {
        border-left: none !important;
        background: rgba(61, 165, 249, 0.25);
        border: 1px solid rgba(61, 165, 249, 0.5);
    }

    .app-sidebar.is-collapsed .nav-link-item:hover:not(.active) {
        transform: none;
    }

    .app-sidebar.is-collapsed .sidebar-footer-container {
        padding: 0.65rem 0.35rem;
        align-items: center;
    }

    .app-sidebar.is-collapsed .sidebar-user-card {
        justify-content: center;
        padding: 0;
        width: 44px;
        height: 44px;
        margin: 0 auto 0.4rem;
    }

    .app-sidebar.is-collapsed .sidebar-footer-links {
        width: 100%;
        align-items: center;
        gap: 0.35rem;
    }

    .app-sidebar.is-collapsed .sidebar-footer-link-btn {
        width: 44px;
        height: 44px;
        padding: 0;
        justify-content: center;
        margin: 0 auto;
        border-radius: 10px;
    }

    .app-sidebar.is-collapsed .sidebar-footer-link-btn i {
        font-size: 1.15rem;
    }

    .app-sidebar.is-collapsed .sidebar-copyright {
        display: none;
    }
}

/* MAIN CONTENT */
.app-content {
    flex: 1;
    min-width: 0;
    margin-left: var(--sidebar-width);
    transition: margin-left var(--transition);
}

.app-content.content-expanded {
    margin-left: var(--sidebar-collapsed-width);
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
    margin: 0;
    padding: 0.85rem 2rem;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.topbar-heading {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.topbar-title {
    margin: 0;
    font-weight: 800;
    font-size: 1.25rem;
    letter-spacing: -0.02em;
    color: var(--st-navy);
}

.topbar-search-box {
    display: flex;
    align-items: center;
    background: #f8fafc;
    border: 1px solid var(--st-border, #e2e8f0);
    border-radius: 9999px;
    padding: 0.35rem 0.85rem;
    width: 220px;
    transition: all 0.2s ease;
}

.topbar-search-box:focus-within {
    border-color: #2563eb;
    background: #fff;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
}

.topbar-search-input {
    border: none;
    background: transparent;
    outline: none;
    font-size: 0.82rem;
    color: #1e293b;
    width: 100%;
}

.topbar-kbd {
    font-size: 0.65rem;
    font-weight: 700;
    color: #94a3b8;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    padding: 0.1rem 0.35rem;
    line-height: 1;
}

.topbar-user-pill {
    padding: 0.25rem 0.65rem 0.25rem 0.35rem;
    border-radius: 9999px;
    background: #ffffff;
    border: 1px solid var(--st-border, #e2e8f0);
}

.user-avatar-topbar {
    width: 30px !important;
    height: 30px !important;
    font-size: 0.82rem !important;
    flex-shrink: 0;
}

.hamburger-btn {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    background: var(--st-surface);
    border: 1px solid var(--st-border);
    border-radius: var(--st-radius-sm);
    color: var(--st-navy);
    transition: background-color 0.2s var(--st-ease), border-color 0.2s var(--st-ease), transform 0.15s var(--st-ease);
}

.hamburger-btn:hover {
    background: var(--st-powder-cyan);
    border-color: var(--st-teal);
    color: var(--st-teal);
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
    background: var(--st-powder-cyan);
    color: var(--st-teal);
}

.btn-topbar:hover {
    border-color: var(--st-teal);
    color: var(--st-teal);
    transform: translateY(-1px);
    box-shadow: var(--st-shadow-low);
}

.btn-topbar-primary {
    background: #03205A;
    border-color: #03205A;
    color: #fff;
    box-shadow: 0 4px 12px rgba(3, 32, 90, 0.2);
}

.btn-topbar-primary .btn-topbar-icon {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}

.btn-topbar-primary:hover {
    color: #fff;
    background: #1C386F;
    border-color: #1C386F;
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(3, 32, 90, 0.35);
}

.btn-topbar-accent {
    background: var(--st-teal);
    border-color: var(--st-teal);
    color: #fff;
    box-shadow: var(--st-shadow-teal);
}

.btn-topbar-accent .btn-topbar-icon {
    background: rgba(255, 255, 255, 0.22);
    color: #fff;
}

.btn-topbar-accent:hover {
    color: #fff;
    background: var(--st-teal-hover);
    border-color: var(--st-teal-hover);
    transform: translateY(-2px);
    box-shadow: 0 14px 26px -8px rgba(var(--st-teal-rgb), 0.6);
}

.app-main-body {
    padding: 1.75rem 2rem 2.5rem;
    background-color: #F8FAFC;
    min-height: calc(100vh - 65px);
}

@media (max-width: 991px) {
    .app-topbar {
        padding: 0.75rem 1rem;
    }

    .app-main-body {
        padding: 1rem 0.85rem 2rem;
    }
}

@media (max-width: 576px) {
    .app-topbar {
        padding: 0.65rem 0.85rem;
    }

    .app-main-body {
        padding: 0.85rem 0.65rem 1.75rem;
    }
}

.sidebar-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(3, 32, 90, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1040;
}
</style>