<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ToastNotification from '@/Components/ToastNotification.vue';
import { PageProps } from '@/types';

const page = usePage<PageProps>();
const isLoggedIn = computed(() => !!page.props.auth.user);
</script>

<template>
    <div class="d-flex flex-column min-vh-100 public-page">
        <ToastNotification />

        <!-- Epic Floating Navbar -->
        <header class="sticky-top pt-3 px-3">
            <nav class="container nav-glass rounded-4 shadow-sm border py-2 px-3 px-md-4 backdrop-blur">
                <div class="d-flex align-items-center justify-content-between">

                    <!-- Brand Section -->
                    <Link href="/tracking" class="brand-link d-flex align-items-center gap-2 text-decoration-none">
                        <!-- Logo 38px -->
                        <img src="/images/sitrack_logo.svg" alt="SiTrack" width="38" height="38"
                            class="app-logo-header shadow-sm rounded-3">

                        <div class="d-none d-sm-block">
                            <h5 class="fw-bold mb-0 text-primary-dark">SiTrack</h5>
                            <p class="text-muted mb-0" style="font-size: 10px; letter-spacing: 0.5px;">Sistem Elektronik
                                Administrasi & Tracking Persuratan
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

                        <div class="vr mx-1 opacity-25"></div>

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

        <!-- Minimalist Footer -->
        <footer class="bg-white border-top py-5 mt-auto">
            <div class="container text-center">
                <!-- Logo Section di Footer -->
                <div class="mb-3 d-flex align-items-center justify-content-center gap-2">
                    <img src="/images/sitrack_logo.svg" alt="SiTrack" width="24" height="24" class="app-logo-footer opacity-75">

                    <span class="fw-bold text-dark" style="letter-spacing: 2px; font-size: 0.9rem;">SITRACK</span>
                </div>

                <!-- Info Hak Cipta -->
                <p class="text-muted small mb-1">Sistem Elektronik Administrasi & Tracking Persuratan</p>
                <p class="text-secondary mb-0" style="font-size: 12px;">
                    &copy; 2026 TU SEKJEN · SiTrack. All rights reserved.
                </p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Background halus untuk seluruh halaman */
.public-page {
    background: var(--st-primary-dark);
}

.public-page main {
    background: var(--st-primary-dark);
}

/* Glassmorphism Effect */
.nav-glass {
    background: rgba(248, 252, 255, 0.88);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.55) !important;
    box-shadow: 0 8px 30px rgba(0, 43, 76, 0.12);
}

.nav-glass:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05) !important;
}

/* Logo Styling */
.brand-logo-container {
    background: linear-gradient(135deg, #2743AF, #3DA5F9);
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(39, 67, 175, 0.25);
}

.text-primary-dark {
    color: #2743AF;
    font-size: 1.2rem;
}

/* Nav Pill Buttons */
.nav-pill-btn {
    text-decoration: none;
    color: #475569;
    padding: 8px 16px;
    border-radius: 50px;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.2s ease;
}

.nav-pill-btn:hover {
    background: #edf5fd;
    color: #2743AF;
}

.nav-pill-btn.active {
    background: #dbeafe;
    color: #2743AF;
}

/* Login Button Epic */
.btn-login-epic {
    background: linear-gradient(135deg, #2743AF, #4A9CF0);
    color: #fff;
    border: none;
    padding: 8px 20px;
    font-size: 14px;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    box-shadow: 0 4px 14px rgba(39, 67, 175, 0.3);
}

.btn-login-epic:hover {
    background: linear-gradient(135deg, #1f3693, #3DA5F9);
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(39, 67, 175, 0.4);
    color: white;
}

/* Utilitas tambahan */
.backdrop-blur {
    backdrop-filter: blur(8px);
}
</style>
