<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ToastNotification from '@/Components/ToastNotification.vue';

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
const togglePassword = () => (showPassword.value = !showPassword.value);

const submit = () => {
    transitioning.value = true;
    setTimeout(() => {
        form.post('/login', {
            onError: () => {
                transitioning.value = false;
            },
            onFinish: () => form.reset('password'),
        });
    }, 350);
};

const transitioning = ref(false);
const overlayOrigin = ref({ x: '50%', y: '50%' });
const submitBtn = ref<HTMLButtonElement | null>(null);

const captureOrigin = (e: MouseEvent) => {
    const x = (e.clientX / window.innerWidth) * 100;
    const y = (e.clientY / window.innerHeight) * 100;
    overlayOrigin.value = { x: `${x}%`, y: `${y}%` };
};

const overlayStyle = computed(() => ({
    '--origin-x': overlayOrigin.value.x,
    '--origin-y': overlayOrigin.value.y,
}));
</script>

<template>
    <Head title="Masuk ke Sistem Staf - SiTrack" />
    <ToastNotification />

    <div class="auth-shell">
        <!-- Main Card Container with Outer Radius -->
        <div class="auth-main-card">
            <!-- LEFT COLUMN: Brand, Hero Title & 3D Illustration -->
            <div class="auth-left-pane">
                <!-- Top Brand Header -->
                <div class="brand-top-section">
                    <div class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            <!-- Logo SiTrack -->
                            <img src="/images/sitrack_logo.svg" alt="SiTrack" class="app-logo-header shadow-sm rounded-3" />
                            <span class="brand-pipe-divider text-white-50 opacity-40 fw-light">|</span>
                            <!-- Logo Kemnaker -->
                            <img src="/images/kemnaker_logo.png" alt="Kemnaker" class="kemnaker-logo-header kemnaker-logo-light" />
                        </div>

                        <!-- Text Kemnaker -->
                        <div class="kemnaker-brand-text text-uppercase fw-bold">
                            <div class="lh-sm">KEMENTERIAN</div>
                            <div class="lh-sm">KETENAGAKERJAAN</div>
                            <div class="lh-sm">REPUBLIK INDONESIA</div>
                        </div>
                    </div>
                    <p class="brand-tagline-text mt-2 mb-0">
                        Tracking Online, Learn the Status.
                    </p>
                </div>

                <!-- Hero Big Headline -->
                <div class="hero-headline-wrap my-auto">
                    <h1 class="hero-headline-text">
                        Lacak Status Surat<br />
                        Anda Dari Mana<br />
                        Saja Di Dunia. <span class="globe-icon">🌍</span>
                    </h1>
                </div>

                <!-- 3D Illustration at Bottom Left -->
                <div class="illustration-container">
                    <img src="/images/login-illustration.png" alt="Ilustrasi Persuratan SiTrack" class="illustration-3d-img" />
                </div>
            </div>

            <!-- RIGHT COLUMN: White Rounded Pane & Login Form -->
            <div class="auth-right-pane">
                <!-- Top Actions (Portal Link / Language) -->
                <div class="right-pane-top-bar d-flex justify-content-end align-items-center">
                    <Link href="/tracking" class="top-portal-link d-inline-flex align-items-center gap-1">
                        <span>Portal Lacak Publik</span>
                        <i class="bi bi-chevron-down small opacity-75"></i>
                    </Link>
                </div>

                <div class="form-center-wrapper my-auto">
                    <!-- Title Section -->
                    <div class="form-header-section mb-4">
                        <h2 class="form-main-title fw-bold mb-1">Masuk ke Akun Anda</h2>
                        <p class="form-sub-title text-muted">Sistem Elektronik Administrasi Persuratan Staf</p>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submit" novalidate class="login-form-body">
                        <!-- Username Field -->
                        <div class="clean-input-group mb-3" :class="{ 'is-invalid': form.errors.username }">
                            <label for="username" class="clean-label">Username Staf</label>
                            <div class="clean-input-wrap">
                                <input
                                    v-model="form.username"
                                    type="text"
                                    id="username"
                                    class="clean-input"
                                    placeholder="Masukkan username"
                                    autofocus
                                    autocomplete="username"
                                />
                            </div>
                            <div v-if="form.errors.username" class="clean-error-text">{{ form.errors.username }}</div>
                        </div>

                        <!-- Password Field -->
                        <div class="clean-input-group mb-3" :class="{ 'is-invalid': form.errors.password }">
                            <label for="password" class="clean-label">Kata Sandi</label>
                            <div class="clean-input-wrap">
                                <input
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    id="password"
                                    class="clean-input"
                                    placeholder="Masukkan kata sandi"
                                    autocomplete="current-password"
                                />
                                <button type="button" class="password-toggle-btn" @click="togglePassword" tabindex="-1" aria-label="Toggle password visibility">
                                    <i :class="['bi', showPassword ? 'bi-eye-slash' : 'bi-eye']"></i>
                                </button>
                            </div>
                            <div v-if="form.errors.password" class="clean-error-text">{{ form.errors.password }}</div>
                        </div>

                        <!-- Remember Me -->
                        <div class="d-flex align-items-center justify-content-between mb-4 mt-2">
                            <label class="clean-checkbox-label">
                                <input v-model="form.remember" type="checkbox" class="clean-checkbox-input" />
                                <span class="clean-checkbox-text">Ingat sesi login saya</span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button
                            ref="submitBtn"
                            type="submit"
                            class="btn-primary-pill w-100"
                            :disabled="form.processing"
                            @click="captureOrigin"
                        >
                            <span v-if="form.processing" class="btn-loading">
                                <span class="btn-loading-ring"></span>
                                <span>Memeriksa akun...</span>
                            </span>
                            <span v-else>Masuk ke Sistem</span>
                        </button>
                    </form>
                </div>

                <!-- Footer / Return Link -->
                <div class="right-pane-footer text-center mt-3">
                    <p class="text-muted small mb-0">
                        Bukan staf?
                        <Link href="/tracking" class="footer-action-link fw-semibold">
                            Lacak Surat Publik
                        </Link>
                    </p>
                </div>
            </div>
        </div>

        <!-- Ripple Login Transition Animation -->
        <Transition name="ripple">
            <div v-if="transitioning" class="login-transition-overlay" :style="overlayStyle">
                <div class="login-transition-content">
                    <div class="d-flex align-items-center justify-content-center gap-3 mb-2">
                        <div class="login-transition-logo-badge">
                            <img src="/images/sitrack_logo.svg" alt="SiTrack" width="40" height="40" class="login-transition-logo" />
                        </div>
                        <span class="text-white opacity-40 fs-5">|</span>
                        <div class="login-transition-logo-badge">
                            <img src="/images/kemnaker_logo.png" alt="Kemnaker" width="36" height="36" class="login-transition-logo" />
                        </div>
                    </div>
                    <p class="login-transition-text">Memverifikasi kredensial...</p>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped lang="scss">
.auth-shell {
    position: relative;
    width: 100vw;
    min-height: 100vh;
    overflow-x: hidden;
    overflow-y: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #eef2f8;
    padding: 2.5rem 1.5rem;
    margin: 0;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

/* Main Card Container with Outer Radius */
.auth-main-card {
    position: relative;
    display: flex;
    width: 100%;
    max-width: 1040px;
    min-height: 610px;
    background: #6F81EB;
    border-radius: 36px;
    box-shadow: 0 24px 60px rgba(65, 84, 209, 0.2);
    margin: 0;
    overflow: hidden;
}

/* ========================================================
   LEFT PANE: Brand, Hero Headline & 3D Illustration
   ======================================================== */
.auth-left-pane {
    position: relative;
    flex: 1.15;
    background: #6F81EB;
    padding: 2.6rem 2.8rem 1.2rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    color: #ffffff;
    z-index: 1;
}

.brand-top-section {
    position: relative;
    z-index: 2;
}

.app-logo-header {
    width: 36px;
    height: 36px;
}

@media (min-width: 576px) {
    .app-logo-header {
        width: 38px;
        height: 38px;
    }
}

.kemnaker-logo-header {
    width: 30px;
    height: 30px;
}

.kemnaker-logo-light {
    width: 30px;
    height: 30px;
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
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.45);
    margin: 0 0.15rem;
    user-select: none;
}

.kemnaker-brand-text {
    font-size: 0.65rem;
    letter-spacing: 0.6px;
    line-height: 1.22;
    color: #ffffff;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

@media (min-width: 576px) {
    .kemnaker-brand-text {
        font-size: 0.72rem;
        letter-spacing: 0.65px;
    }
}

.brand-tagline-text {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.82);
    font-weight: 500;
    letter-spacing: 0.2px;
}

/* Hero Headline */
.hero-headline-wrap {
    margin: 1.8rem 0 1.2rem;
    position: relative;
    z-index: 2;
}

.hero-headline-text {
    font-size: 2.15rem;
    font-weight: 800;
    line-height: 1.22;
    color: #ffffff;
    letter-spacing: -0.025em;
}

.globe-icon {
    display: inline-block;
    font-size: 1.9rem;
    vertical-align: middle;
}

/* 3D Illustration Container */
.illustration-container {
    position: relative;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    width: 100%;
    margin-top: auto;
    z-index: 2;
}

.illustration-3d-img {
    position: relative;
    max-width: 100%;
    height: auto;
    max-height: 275px;
    object-fit: contain;
    animation: floatIllustration 8s ease-in-out infinite;
}

@keyframes floatIllustration {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-6px);
    }
}

/* ========================================================
   RIGHT PANE: White Rounded Card & Login Form
   ======================================================== */
.auth-right-pane {
    position: relative;
    flex: 1.08;
    background: #ffffff;
    border-radius: 36px;
    margin: 0;
    padding: 2.6rem 3.5rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    z-index: 2;
    box-shadow: -10px 0 28px rgba(0, 0, 0, 0.04);
}

.right-pane-top-bar {
    width: 100%;
}

.top-portal-link {
    font-size: 0.85rem;
    font-weight: 600;
    color: #374151;
    text-decoration: none;
    padding: 0.35rem 0.75rem;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;

    &:hover {
        color: #5B6DEE;
        background: #eef2ff;
        border-color: #c7d2fe;
    }
}

.form-center-wrapper {
    width: 100%;
    max-width: 380px;
    margin: 0 auto;
}

.form-main-title {
    font-size: 1.85rem;
    font-weight: 800;
    color: #111827;
    letter-spacing: -0.02em;
}

.form-sub-title {
    font-size: 0.88rem;
    color: #6B7280;
}

/* Clean Underline Input Form Style */
.clean-input-group {
    position: relative;

    .clean-label {
        display: block;
        font-size: 0.88rem;
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 0.35rem;
    }

    .clean-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .clean-input {
        width: 100%;
        padding: 0.65rem 0.2rem 0.65rem 0;
        font-size: 0.95rem;
        color: #111827;
        background: transparent;
        border: none;
        border-bottom: 1.5px solid #E5E7EB;
        outline: none;
        border-radius: 0;
        transition: border-color 0.2s, box-shadow 0.2s;

        &:focus {
            border-bottom-color: #5B6DEE;
            box-shadow: 0 1px 0 #5B6DEE;
        }

        &::placeholder {
            color: #9CA3AF;
            font-size: 0.9rem;
        }
    }

    &.is-invalid .clean-input {
        border-bottom-color: #EF4444;
        box-shadow: 0 1px 0 #EF4444;
    }

    .clean-error-text {
        color: #EF4444;
        font-size: 0.75rem;
        margin-top: 4px;
        font-weight: 500;
    }
}

.password-toggle-btn {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #9CA3AF;
    font-size: 1.15rem;
    cursor: pointer;
    padding: 4px;
    transition: color 0.2s ease;

    &:hover {
        color: #4B5563;
    }
}

.clean-checkbox-label {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    cursor: pointer;
    user-select: none;
}

.clean-checkbox-input {
    width: 16px;
    height: 16px;
    border-radius: 4px;
    border: 1.5px solid #CBD5E1;
    accent-color: #5B6DEE;
    cursor: pointer;
}

.clean-checkbox-text {
    font-size: 0.82rem;
    color: #4B5563;
    font-weight: 500;
}

/* Button Pill Submit */
.btn-primary-pill {
    height: 48px;
    background: #5B6DEE;
    color: #ffffff;
    border: none;
    border-radius: 50px;
    font-weight: 700;
    font-size: 0.98rem;
    letter-spacing: 0.2px;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 8px 22px rgba(91, 109, 238, 0.35);

    &:hover:not(:disabled) {
        background: #4B5EE3;
        transform: translateY(-2px);
        box-shadow: 0 12px 26px rgba(91, 109, 238, 0.45);
    }

    &:active:not(:disabled) {
        transform: translateY(0);
    }

    &:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
}

.btn-loading {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-loading-ring {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    flex-shrink: 0;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #ffffff;
    animation: btnRingSpin 0.8s linear infinite;
}

@keyframes btnRingSpin {
    to {
        transform: rotate(360deg);
    }
}

.footer-action-link {
    color: #5B6DEE;
    text-decoration: none;

    &:hover {
        text-decoration: underline;
    }
}

/* Ripple Overlay Animation */
.login-transition-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #1E3A8A;
    clip-path: circle(150% at var(--origin-x) var(--origin-y));
}

.ripple-enter-active,
.ripple-leave-active {
    transition: clip-path 0.6s cubic-bezier(0.22, 1, 0.36, 1);
}

.ripple-enter-from,
.ripple-leave-to {
    clip-path: circle(0% at var(--origin-x) var(--origin-y));
}

.login-transition-content {
    text-align: center;
    color: #fff;
    opacity: 0;
    animation: transitionFadeIn 0.4s ease 0.3s forwards;
}

.login-transition-logo-badge {
    width: 76px;
    height: 76px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 20px;
    background: #fff;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    animation: transitionLogoPulse 1.4s ease-in-out infinite;
}

.login-transition-logo {
    display: block;
}

.login-transition-text {
    margin-top: 0.85rem;
    font-size: 0.85rem;
    font-weight: 600;
}

@keyframes transitionFadeIn {
    to {
        opacity: 1;
    }
}

@keyframes transitionLogoPulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.08);
    }
}

/* ========================================================
   RESPONSIVE DESIGN (Mobile & Tablet)
   ======================================================== */
@media (max-width: 991.98px) {
    .auth-shell {
        padding: 1.5rem 1rem;
    }

    .auth-main-card {
        flex-direction: column;
        max-width: 480px;
        min-height: auto;
        border-radius: 28px;
    }

    .auth-left-pane {
        padding: 2rem 1.75rem 1.5rem;
        flex: none;
    }

    .hero-headline-text {
        font-size: 1.75rem;
    }

    .illustration-3d-img {
        max-height: 200px;
    }

    .auth-right-pane {
        border-radius: 28px;
        padding: 2.2rem 1.75rem;
    }
}
</style>