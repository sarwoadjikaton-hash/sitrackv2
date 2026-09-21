<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import ToastNotification from '@/Components/ToastNotification.vue';
import KemnakerFooter from '@/Components/KemnakerFooter.vue';
import { PageProps } from '@/types';

const page = usePage<PageProps>();
const isLoggedIn = computed(() => !!page.props.auth.user);

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
                <div class="d-flex align-items-center justify-content-between w-100">

                    <!-- Brand Section: Logo SiTrack | Logo Kemnaker + Text Kemnaker (Sama seperti Footer) -->
                    <Link href="/tracking" class="brand-link d-flex align-items-center gap-2 text-decoration-none">
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            <!-- Logo SiTrack -->
                            <img src="/images/sitrack_logo.svg" alt="SiTrack" width="36" height="36"
                                class="app-logo-header shadow-sm rounded-3">
                            <span class="brand-pipe-divider text-white-50 opacity-40 fs-5 fw-light">|</span>
                            <!-- Logo Kemnaker -->
                            <img src="/images/kemnaker_logo.png" alt="Kemnaker" width="32" height="32"
                                class="kemnaker-logo-header kemnaker-logo-light">
                        </div>

                        <!-- Text Kemnaker (Identik dengan Footer) -->
                        <div class="kemnaker-brand-text text-uppercase fw-bold d-none d-sm-block">
                            <div class="lh-sm">KEMENTERIAN</div>
                            <div class="lh-sm">KETENAGAKERJAAN</div>
                            <div class="lh-sm">REPUBLIK INDONESIA</div>
                        </div>
                    </Link>

                    <!-- Nav Actions -->
                    <div class="d-flex align-items-center gap-1 gap-md-2">
                        <Link href="/tracking" class="nav-pill-btn" :class="{ 'active': $page.url === '/tracking' }">
                            <i class="bi bi-search"></i>
                            <span class="d-none d-md-inline ms-1">Lacak</span>
                        </Link>

                        <Link href="/ajukan-surat" class="nav-pill-btn"
                            :class="{ 'active': $page.url === '/ajukan-surat' }">
                            <i class="bi bi-send-fill"></i>
                            <span class="d-none d-md-inline ms-1">Ajukan</span>
                        </Link>

                        <div class="vr mx-1 opacity-25 text-white"></div>

                        <Link v-if="isLoggedIn" href="/dashboard"
                            class="btn btn-primary-blue rounded-pill px-3 px-md-4 shadow-sm fw-semibold">
                            <i class="bi bi-grid-fill me-1"></i>
                            <span class="small">Dashboard</span>
                        </Link>
                        <Link v-else href="/login" class="btn-login-epic rounded-pill px-3 px-md-4 fw-semibold">
                            <i class="bi bi-person-lock me-1"></i>
                            <span class="small">Login Staf</span>
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
    background: var(--st-navy);
}

.public-page main {
    background: var(--st-navy);
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
    padding: 0.75rem 1rem 0;
}

.navbar-island {
    transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}

/* 1. Kondisi Atas / Menempel (100% Transparan / Tanpa Background & Tanpa Garis Bawah) */
.nav-attached {
    background: transparent !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    border: none !important;
    border-radius: 0 !important;
    padding: 1.1rem 1.5rem;
    box-shadow: none !important;
}

@media (min-width: 992px) {
    .nav-attached {
        padding: 1.1rem 3rem;
    }
}

/* 2. Kondisi Mengambang (Solid Navy Melayang Elegan saat Scroll) */
.nav-floating {
    background: #03205A;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid #1C386F !important;
    border-radius: 1.25rem !important;
    padding: 0.65rem 1.25rem;
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.35) !important;
}

@media (min-width: 768px) {
    .nav-floating {
        padding: 0.65rem 1.75rem;
    }
}

/* Logo Kemnaker */
.kemnaker-logo-light {
    filter: brightness(0) invert(1);
    opacity: 0.95;
    vertical-align: middle;
}

/* Kemnaker Brand Text (Identik dengan Footer) */
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
    padding: 8px 18px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s ease;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid transparent;
}

.nav-pill-btn:hover {
    background: rgba(255, 255, 255, 0.16);
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.2);
}

.nav-pill-btn.active {
    background: #167992;
    border: 1px solid #167992;
    color: #ffffff;
    font-weight: 700;
}

/* Login Button Solid */
.btn-login-epic {
    background: #167992;
    color: #fff;
    border: 1px solid #167992;
    padding: 8px 22px;
    font-size: 14px;
    font-weight: 700;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}

.btn-login-epic:hover {
    background: #126277;
    border-color: #126277;
    color: white;
}
</style>
