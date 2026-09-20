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
    // beri jeda kecil supaya animasi ripple sempat "membuka" sebelum request jalan
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

    <Head title="Masuk ke Sistem Staf" />
    <ToastNotification />

    <div class="auth-shell">
        <!-- Ambient background: tema dokumen & surat, blue-teal -->
        <div class="mail-bg">
            <div class="grid-overlay"></div>

            <svg class="mail-icon icon-envelope-a" viewBox="0 0 64 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="2" y="2" width="60" height="40" rx="4" stroke="white" stroke-width="2.2" />
                <path d="M4 4L32 26L60 4" stroke="white" stroke-width="2.2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>

            <svg class="mail-icon icon-envelope-b" viewBox="0 0 64 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="2" y="2" width="60" height="40" rx="4" stroke="white" stroke-width="2.2" />
                <path d="M4 4L32 26L60 4" stroke="white" stroke-width="2.2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>

            <svg class="mail-icon icon-paper" viewBox="0 0 48 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8 2H32L40 10V58H8V2Z" stroke="white" stroke-width="2.2" stroke-linejoin="round" />
                <path d="M32 2V10H40" stroke="white" stroke-width="2.2" stroke-linejoin="round" />
                <line x1="14" y1="24" x2="34" y2="24" stroke="white" stroke-width="2" stroke-linecap="round" />
                <line x1="14" y1="32" x2="34" y2="32" stroke="white" stroke-width="2" stroke-linecap="round" />
                <line x1="14" y1="40" x2="26" y2="40" stroke="white" stroke-width="2" stroke-linecap="round" />
            </svg>

            <svg class="mail-icon icon-stamp" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="30" cy="30" r="26" stroke="white" stroke-width="2.2" stroke-dasharray="4 4" />
                <circle cx="30" cy="30" r="16" stroke="white" stroke-width="2.2" />
                <path d="M22 30L28 36L40 24" stroke="white" stroke-width="2.4" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>

            <svg class="mail-icon icon-clip" viewBox="0 0 24 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M6 10V28C6 32 9 35 13 35C17 35 20 32 20 28V8C20 5 17.5 3 15 3C12.5 3 10 5 10 8V26C10 27.5 11 28.5 12.5 28.5C14 28.5 15 27.5 15 26V12"
                    stroke="white" stroke-width="2.2" stroke-linecap="round" />
            </svg>
        </div>

        <div class="auth-center">
            <!-- Card Login di Tengah -->
            <div class="auth-card" :class="{ 'has-errors': form.errors.username || form.errors.password }">
                <!-- Brand Header -->
                <div class="text-center mb-4">
                    <div class="d-flex align-items-center justify-content-center gap-3 mb-2">
                        <div class="brand-logo-ring shadow-sm">
                            <img src="/images/sitrack_logo.svg" alt="SiTrack Logo" width="46" height="46" />
                        </div>
                        <span class="fs-4 text-muted opacity-50 fw-light">|</span>
                        <div class="brand-logo-ring brand-logo-kemnaker shadow-sm">
                            <img src="/images/kemnaker_logo.png" alt="Kemnaker Logo" width="40" height="40" />
                        </div>
                    </div>
                    <h2 class="fw-bold mb-0 mt-2 text-dark">SiTrack</h2>
                    <p class="text-muted small mb-0 mt-1">Sistem Elektronik Administrasi Persuratan TU SEKJEN</p>
                </div>

                <span class="eyebrow d-block text-center mb-3">STAFF ACCESS ONLY</span>

                <form @submit.prevent="submit" novalidate>
                    <!-- Username Field -->
                    <div class="field-float mb-3"
                        :class="{ 'is-filled': form.username, 'is-invalid': form.errors.username }">
                        <i class="bi bi-person field-icon"></i>
                        <input v-model="form.username" type="text" id="username" placeholder=" " autofocus
                            autocomplete="username" />
                        <label for="username">Username Staf</label>
                    </div>
                    <div v-if="form.errors.username" class="field-error">{{ form.errors.username }}</div>

                    <!-- Password Field -->
                    <div class="field-float mb-2"
                        :class="{ 'is-filled': form.password, 'is-invalid': form.errors.password }">
                        <i class="bi bi-lock field-icon"></i>
                        <input v-model="form.password" :type="showPassword ? 'text' : 'password'" id="password"
                            placeholder=" " autocomplete="current-password" />
                        <label for="password">Kata Sandi</label>
                        <button type="button" class="field-suffix-btn" @click="togglePassword" tabindex="-1">
                            <i :class="['bi', showPassword ? 'bi-eye-slash' : 'bi-eye']"></i>
                        </button>
                    </div>
                    <div v-if="form.errors.password" class="field-error mb-2">{{ form.errors.password }}</div>

                    <!-- Remember Me & Switch -->
                    <div class="d-flex align-items-center justify-content-between my-4">
                        <label class="switch-label">
                            <input v-model="form.remember" type="checkbox" class="switch-input" />
                            <div class="switch-button">
                                <div class="switch-circle"></div>
                            </div>
                            <span class="ms-2 small text-muted fw-semibold">Ingat sesi saya</span>
                        </label>
                    </div>

                    <!-- Submit Button with Glow -->
                    <button ref="submitBtn" type="submit" class="btn-glow w-100" :disabled="form.processing"
                        @click="captureOrigin">
                        <span v-if="form.processing" class="btn-loading">
                            <span class="btn-loading-ring"></span>
                            <span>Memeriksa akun...</span>
                        </span>
                        <span v-else>Masuk ke Sistem</span>
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <Link href="/tracking" class="back-link">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Portal Lacak Publik
                    </Link>
                </div>
            </div>

            <!-- Footer Statis 2026 -->
            <p class="auth-footer-note">
                <i class="bi bi-c-circle"></i> 2026 SiTrack &bull; Kementerian Ketenagakerjaan Republik Indonesia
            </p>
        </div>

        <Transition name="ripple">
            <div v-if="transitioning" class="login-transition-overlay" :style="overlayStyle">
                <div class="login-transition-content">
                    <div class="d-flex align-items-center justify-content-center gap-3 mb-2">
                        <div class="login-transition-logo-badge">
                            <img src="/images/sitrack_logo.svg" alt="SiTrack" width="40" height="40"
                                class="login-transition-logo" />
                        </div>
                        <span class="text-white opacity-40 fs-5">|</span>
                        <div class="login-transition-logo-badge">
                            <img src="/images/kemnaker_logo.png" alt="Kemnaker" width="36" height="36"
                                class="login-transition-logo" />
                        </div>
                    </div>
                    <p class="login-transition-text">Memverifikasi kredensial...</p>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped lang="scss">
$blue-900: #03205A;
$blue-800: #1C386F;
$blue-600: #1C386F;
$blue-500: #395ba0;
$teal-500: #167992;
$teal-400: #167992;
$teal-300: #3a9cb5;
$soft-blue: #B5CCE3;
$powder-cyan: #E4F5F9;
$ice-white: #EEF7FC;

.auth-shell {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    /* Gradient Kemnaker: Deep Navy -> Trust Blue -> Teal Accent */
    background: linear-gradient(160deg, $blue-900 0%, $blue-800 45%, $teal-500 100%);
    padding: 2rem 1rem;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

/* ===== Ambient background: dokumen & surat ===== */
.mail-bg {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
    z-index: 0;
}

.grid-overlay {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255, 255, 255, .05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, .05) 1px, transparent 1px);
    background-size: 44px 44px;
    mask-image: radial-gradient(circle at 50% 45%, #000 0%, transparent 80%);
}

.mail-icon {
    position: absolute;
    opacity: .16;
    filter: drop-shadow(0 8px 20px rgba(2, 8, 30, .25));
    animation: mailFloat 14s ease-in-out infinite;
}

.icon-envelope-a {
    --r: -12deg;
    width: 130px;
    top: 8%;
    left: 6%;
    animation-duration: 16s;
}

.icon-envelope-b {
    --r: 8deg;
    width: 90px;
    bottom: 12%;
    left: 12%;
    opacity: .12;
    animation-duration: 19s;
    animation-delay: -6s;
}

.icon-paper {
    --r: 10deg;
    width: 100px;
    top: 14%;
    right: 10%;
    animation-duration: 17s;
    animation-delay: -3s;
}

.icon-stamp {
    --r: 0deg;
    width: 150px;
    bottom: 6%;
    right: 8%;
    opacity: .14;
    animation-duration: 20s;
    animation-delay: -9s;
}

.icon-clip {
    --r: -6deg;
    width: 46px;
    top: 45%;
    left: 45%;
    opacity: .1;
    animation-duration: 15s;
    animation-delay: -2s;
}

@keyframes mailFloat {

    0%,
    100% {
        transform: translate(0, 0) rotate(var(--r, 0deg));
    }

    50% {
        transform: translate(16px, -20px) rotate(calc(var(--r, 0deg) + 4deg));
    }
}

@media (prefers-reduced-motion: reduce) {
    .mail-icon {
        animation: none;
    }
}

/* ===== Kartu login (di atas background, WAJIB solid) ===== */
.auth-center {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 440px;
}

.auth-card {
    position: relative;
    z-index: 10;
    background: #fff;
    border-radius: 28px;
    padding: 3rem 2.5rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 32px 80px -20px rgba(3, 32, 90, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.8);
    animation: fadeUp .6s ease-out both;

    &.has-errors {
        animation: shake 0.4s ease-in-out;
    }
}

.brand-logo-ring {
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 18px;
    background: linear-gradient(135deg, rgba(3, 32, 90, 0.08), rgba(22, 121, 146, 0.12));
    border: 1px solid rgba(22, 121, 146, 0.2);
}

.eyebrow {
    font-size: .65rem;
    font-weight: 800;
    letter-spacing: .15em;
    color: $teal-500;
    background: $powder-cyan;
    padding: 4px 12px;
    border-radius: 50px;
    display: inline-block;
}

.auth-footer-note {
    position: relative;
    z-index: 10;
    text-align: center;
    margin-top: 2rem;
    font-size: .75rem;
    color: rgba(238, 247, 252, 0.85);
    letter-spacing: 0.5px;
}

/* Floating label inputs */
.field-float {
    position: relative;

    input {
        width: 100%;
        height: 56px;
        border: 1.5px solid $soft-blue;
        border-radius: 14px;
        padding: 0 1rem 0 3rem;
        font-size: .95rem;
        background: $ice-white;
        color: $blue-900;
        transition: all 0.25s ease;

        &:focus {
            outline: none;
            border-color: $teal-500;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(22, 121, 146, 0.15);
        }
    }

    label {
        position: absolute;
        left: 3rem;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        pointer-events: none;
        transition: all 0.2s ease;
    }

    .field-icon {
        position: absolute;
        left: 1.2rem;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        font-size: 1.1rem;
    }

    input:focus+label,
    &.is-filled label {
        top: 0;
        font-size: 0.75rem;
        font-weight: 700;
        color: $teal-500;
        background: #fff;
        padding: 0 5px;
    }

    .field-suffix-btn {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        color: #64748b;
    }

    &.is-invalid input {
        border-color: #ef4444;
    }
}

.field-error {
    color: #ef4444;
    font-size: .75rem;
    margin-top: 4px;
    font-weight: 500;
}

/* Modern Switch Toggle */
.switch-label {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    user-select: none;

    .switch-input {
        display: none;
    }

    .switch-button {
        position: relative;
        width: 44px;
        height: 24px;
        background-color: $soft-blue;
        border-radius: 20px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .switch-circle {
        position: absolute;
        top: 3px;
        left: 3px;
        width: 16px;
        height: 16px;
        background-color: white;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .switch-input:checked+.switch-button {
        background-color: $teal-500;
        box-shadow: 0 4px 10px rgba(22, 121, 146, 0.25);
    }

    .switch-input:checked+.switch-button .switch-circle {
        transform: translateX(20px);
        box-shadow: -2px 0 4px rgba(0, 0, 0, 0.05);
    }

    &:hover .switch-button {
        filter: brightness(0.95);
    }
}

/* Button with Gradient & Glow */
.btn-glow {
    height: 56px;
    border: none;
    border-radius: 14px;
    color: #fff;
    font-weight: 700;
    background: linear-gradient(135deg, $blue-800, $teal-500);
    box-shadow: 0 10px 20px -5px rgba(22, 121, 146, 0.35);
    transition: 0.3s ease;

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 15px 30px -5px rgba(22, 121, 146, 0.45);
        filter: brightness(1.05);
    }

    &:active {
        transform: translateY(0);
    }
}

.btn-loading {
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-loading-ring {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    flex-shrink: 0;
    background: conic-gradient(from 0deg, rgba(255, 255, 255, 0) 0%, #fff 100%);
    -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 3px), #000 calc(100% - 3px));
    mask: radial-gradient(farthest-side, transparent calc(100% - 3px), #000 calc(100% - 3px));
    animation: btnRingSpin 0.8s linear infinite;
}

.btn-glow:disabled {
    cursor: wait;
    animation: btnPulse 1.6s ease-in-out infinite;
}

@keyframes btnRingSpin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes btnPulse {

    0%,
    100% {
        box-shadow: 0 10px 20px -5px rgba(22, 121, 146, 0.35);
    }

    50% {
        box-shadow: 0 14px 30px -5px rgba(22, 121, 146, 0.55);
    }
}

.back-link {
    color: #64748b;
    font-size: .85rem;
    font-weight: 600;
    text-decoration: none;

    &:hover {
        color: $teal-500;
    }
}

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes shake {

    0%,
    100% {
        transform: translateX(0);
    }

    25% {
        transform: translateX(-6px);
    }

    75% {
        transform: translateX(6px);
    }
}

.login-transition-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(circle at var(--origin-x) var(--origin-y), $teal-400 0%, $blue-600 45%, $blue-900 100%);
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
    letter-spacing: 0.02em;
}

@keyframes transitionFadeIn {
    to {
        opacity: 1;
    }
}

@keyframes transitionLogoPulse {

    0%,
    100% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.08);
    }
}

@media (prefers-reduced-motion: reduce) {

    .ripple-enter-active,
    .ripple-leave-active {
        transition: opacity 0.3s ease;
    }

    .ripple-enter-from,
    .ripple-leave-to {
        opacity: 0;
        clip-path: none;
    }

    .login-transition-overlay {
        clip-path: none;
    }

    .login-transition-logo {
        animation: none;
    }
}
</style>