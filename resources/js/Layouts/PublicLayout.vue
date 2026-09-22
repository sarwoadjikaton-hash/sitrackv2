<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import ToastNotification from '@/Components/ToastNotification.vue';
import KemnakerFooter from '@/Components/KemnakerFooter.vue';
import { PageProps } from '@/types';

const page = usePage<PageProps>();
const isLoggedIn = computed(() => !!page.props.auth?.user);

const isScrolled = ref(false);

const handleScroll = () => {
    isScrolled.value = window.scrollY > 15;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <div class="d-flex flex-column min-vh-100 public-page">
        <ToastNotification />

        <!-- Scroll-Detached Transparent & Glass Floating Navbar -->
        <header
            class="sticky-top header-wrapper"
            :class="isScrolled ? 'is-scrolled' : 'is-top'"
        >
            <nav
                class="navbar-island"
                :class="isScrolled ? 'container nav-floating' : 'w-100 nav-attached'"
            >
                <div class="d-flex align-items-center justify-content-between w-100 gap-2">

                    <!-- Brand Section: Logo SiTrack | Logo Kemnaker + Text Kemnaker -->
                    <Link href="/tracking" class="brand-link d-flex align-items-center gap-2 text-decoration-none flex-shrink-0">
                        <div class="d-flex align-items-center gap-1 gap-sm-2 flex-shrink-0">
                            <!-- Logo SiTrack -->
                            <img src="/images/sitrack_logo.svg" alt="SiTrack"
                                class="app-logo-header shadow-sm rounded-3">
                            <span class="brand-pipe-divider text-white-50 opacity-40 fw-light">|</span>
                            <!-- Logo Kemnaker -->
                            <img src="/images/kemnaker_logo.png" alt="Kemnaker"
                                class="kemnaker-logo-header kemnaker-logo-light">
                        </div>

                        <!-- Text Kemnaker -->
                        <div class="kemnaker-brand-text text-uppercase fw-bold d-none d-md-block">
                            <div class="lh-sm">KEMENTERIAN</div>
                            <div class="lh-sm">KETENAGAKERJAAN</div>
                            <div class="lh-sm">REPUBLIK INDONESIA</div>
                        </div>
                    </Link>

                    <!-- Nav Actions -->
                    <div class="d-flex align-items-center gap-1 gap-sm-2 ms-auto">
                        <Link href="/tracking" class="nav-pill-btn" :class="{ 'active': $page.url === '/tracking' || $page.url.startsWith('/tracking?') }" title="Lacak Naskah">
                            <i class="bi bi-search"></i>
                            <span class="d-none d-sm-inline ms-1">Lacak</span>
                        </Link>

                        <Link href="/ajukan-surat" class="nav-pill-btn"
                            :class="{ 'active': $page.url === '/ajukan-surat' }" title="Ajukan Surat">
                            <i class="bi bi-send-fill"></i>
                            <span class="d-none d-sm-inline ms-1">Ajukan</span>
                        </Link>

                        <div class="vr mx-1 opacity-25 text-white d-none d-xs-block"></div>

                        <Link v-if="isLoggedIn" href="/dashboard"
                            class="btn btn-primary-blue rounded-pill px-2 px-sm-3 py-1 py-sm-2 shadow-sm fw-semibold d-inline-flex align-items-center gap-1 text-nowrap">
                            <i class="bi bi-grid-fill"></i>
                            <span class="small d-none d-sm-inline">Dashboard</span>
                        </Link>
                        <Link v-else href="/login" class="btn-login-epic rounded-pill px-2 px-sm-3 py-1 py-sm-2 fw-semibold d-inline-flex align-items-center gap-1 text-nowrap">
                            <i class="bi bi-person-lock"></i>
                            <span class="small d-none d-sm-inline">Login Staf</span>
                        </Link>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="flex-grow-1">
            <slot></slot>
        </main>

        <!-- Official Kemnaker Footer -->
        <KemnakerFooter />
    </div>
</template>

<style scoped>
/* Background halus untuk seluruh halaman */
.public-page {
    background: var(--st-navy, #03205A);
}

.public-page main {
    background: var(--st-navy, #03205A);
}

/* ==========================================================================
   SCROLL-DETACHED TRANSPARENT GLASS NAVBAR
   ========================================================= */

.header-wrapper {
    z-index: 1030;
    transition: padding 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}

.header-wrapper.is-top {
    padding: 0;
}

.header-wrapper.is-scrolled {
    padding: 0.5rem 0.75rem 0;
}

@media (min-width: 768px) {
    .header-wrapper.is-scrolled {
        padding: 0.75rem 1rem 0;
    }
}

.navbar-island {
    transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}

/* 1. Kondisi Atas / Menempel */
.nav-attached {
    background: transparent !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    border: none !important;
    border-radius: 0 !important;
    padding: 0.85rem 1rem;
    box-shadow: none !important;
}

@media (min-width: 768px) {
    .nav-attached {
        padding: 1.1rem 1.75rem;
    }
}

@media (min-width: 992px) {
    .nav-attached {
        padding: 1.1rem 3rem;
    }
}

/* 2. Kondisi Mengambang */
.nav-floating {
    background: rgba(3, 32, 90, 0.92);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(28, 56, 111, 0.8) !important;
    border-radius: 1.25rem !important;
    padding: 0.5rem 0.85rem;
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.4) !important;
}

@media (min-width: 768px) {
    .nav-floating {
        padding: 0.65rem 1.75rem;
    }
}

/* Logos */
.app-logo-header {
    width: 32px;
    height: 32px;
}

@media (min-width: 576px) {
    .app-logo-header {
        width: 36px;
        height: 36px;
    }
}

.kemnaker-logo-light {
    width: 28px;
    height: 28px;
    filter: brightness(0) invert(1);
    opacity: 0.95;
    vertical-align: middle;
}

@media (min-width: 576px) {
    .kemnaker-logo-light {
        width: 32px;
        height: 32px;
    }
}

.brand-pipe-divider {
    font-size: 1.1rem;
}

/* Kemnaker Brand Text */
.kemnaker-brand-text {
    font-size: 10px;
    letter-spacing: 0.5px;
    line-height: 1.25;
    color: #ffffff;
    font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

@media (min-width: 992px) {
    .kemnaker-brand-text {
        font-size: 11px;
    }
}

/* Nav Pill Buttons */
.nav-pill-btn {
    text-decoration: none;
    color: rgba(255, 255, 255, 0.85);
    padding: 6px 12px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.2s ease;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 34px;
}

@media (min-width: 576px) {
    .nav-pill-btn {
        padding: 7px 16px;
        font-size: 14px;
        min-height: 38px;
    }
}

.nav-pill-btn:hover {
    background: rgba(255, 255, 255, 0.18);
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.3);
}

.nav-pill-btn.active {
    background: #167992;
    border-color: #167992;
    color: #ffffff;
    font-weight: 700;
    box-shadow: 0 2px 10px rgba(22, 121, 146, 0.4);
}

/* Login Button Solid */
.btn-login-epic {
    background: #167992;
    color: #fff;
    border: 1px solid #167992;
    padding: 6px 14px;
    font-size: 13px;
    font-weight: 700;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 34px;
}

@media (min-width: 576px) {
    .btn-login-epic {
        padding: 7px 18px;
        font-size: 14px;
        min-height: 38px;
    }
}

.btn-login-epic:hover {
    background: #126277;
    border-color: #126277;
    color: white;
    box-shadow: 0 4px 14px rgba(22, 121, 146, 0.4);
}
</style>
