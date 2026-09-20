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

                    <!-- Brand Section: Logo SiTrack | Logo Kemnaker -->
                    <Link href="/tracking" class="brand-link d-flex align-items-center gap-2 text-decoration-none">
                        <div class="d-flex align-items-center gap-2">
                            <!-- Logo SiTrack -->
                            <img src="/images/sitrack_logo.svg" alt="SiTrack" width="36" height="36"
                                class="app-logo-header shadow-sm rounded-3">
                            <span class="brand-pipe-divider text-white-50 opacity-50 fw-light">|</span>
                            <!-- Logo Kemnaker -->
                            <img src="/images/kemnaker_logo.png" alt="Kemnaker" width="32" height="32"
                                class="kemnaker-logo-header kemnaker-logo-light">
                        </div>

                        <div class="d-none d-sm-block ms-1">
                            <h5 class="fw-bold mb-0 text-white brand-title">SiTrack</h5>
                            <p class="text-white-50 mb-0 brand-sub" style="font-size: 10px; letter-spacing: 0.5px;">
                                Sistem Elektronik Administrasi & Tracking Persuratan
                            </p>
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
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
}

/* 1. Kondisi Atas / Menempel (Transparan Alami menyatu dengan Hero) */
.nav-attached {
    background: rgba(3, 32, 90, 0.45);
    border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    border-radius: 0 !important;
    padding: 0.9rem 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

@media (min-width: 992px) {
    .nav-attached {
        padding: 0.9rem 3rem;
    }
}

/* 2. Kondisi Mengambang (Deep Navy Glass Capsule yang Melayang Elegan) */
.nav-floating {
    background: rgba(3, 32, 90, 0.82);
    border: 1px solid rgba(181, 204, 227, 0.35) !important;
    border-radius: 1.25rem !important;
    padding: 0.65rem 1.25rem;
    box-shadow: 0 16px 42px rgba(0, 0, 0, 0.38), 0 2px 8px rgba(0, 0, 0, 0.2) !important;
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
}

/* Brand Typography */
.brand-title {
    color: #ffffff !important;
    font-weight: 800;
    font-size: 1.25rem;
    letter-spacing: -0.02em;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.brand-sub {
    color: rgba(255, 255, 255, 0.75) !important;
}

/* Nav Pill Buttons */
.nav-pill-btn {
    text-decoration: none;
    color: rgba(255, 255, 255, 0.85);
    padding: 8px 18px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.25s ease;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid transparent;
}

.nav-pill-btn:hover {
    background: rgba(255, 255, 255, 0.16);
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.2);
}

.nav-pill-btn.active {
    background: rgba(22, 121, 146, 0.5);
    border: 1px solid rgba(45, 212, 191, 0.4);
    color: #ffffff;
    font-weight: 700;
    box-shadow: 0 0 14px rgba(22, 121, 146, 0.4);
}

/* Login Button Epic */
.btn-login-epic {
    background: linear-gradient(135deg, #167992, #0e4d5d);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.25);
    padding: 8px 22px;
    font-size: 14px;
    font-weight: 700;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    box-shadow: 0 4px 16px rgba(22, 121, 146, 0.4);
}

.btn-login-epic:hover {
    background: linear-gradient(135deg, #1fa3c5, #167992);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(22, 121, 146, 0.6);
    color: white;
}
</style>
