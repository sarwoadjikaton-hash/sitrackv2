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

    <div class="auth-wrapper">
        <!-- Ambient decorative shapes in background (matching reference design) -->
        <div class="ambient-backdrop">
            <div class="ambient-blob-pink"></div>
            <div class="ambient-dot-grid"></div>
        </div>

        <!-- Main Card Container -->
        <div class="auth-card-container">
            
            <!-- LEFT PANEL: Gambar Ilustrasi & Identitas Aplikasi -->
            <div class="auth-panel-left">
                <!-- Top Brand -->
                <div class="left-brand-row">
                    <div class="brand-badge-white">
                        <img src="/images/sitrack_logo.svg" alt="SiTrack Logo" width="32" height="32" />
                    </div>
                    <div class="brand-text">
                        <div class="brand-name">SiTrack</div>
                        <div class="brand-tagline">Tata Usaha Sekjen Kemnaker</div>
                    </div>
                </div>

                <!-- 3D Illustration Display -->
                <div class="illustration-display-wrap">
                    <img src="/images/login-illustration.png" alt="Ilustrasi Persuratan" class="illustration-img animate-float" />
                </div>

                <!-- Left Footer Info -->
                <div class="left-footer-text">
                    <p class="mb-0">&copy; 2026 SiTrack &bull; Kementerian Ketenagakerjaan RI</p>
                </div>
            </div>

            <!-- RIGHT PANEL: Form Login dengan Pemisah Melengkung (Curved Separator) -->
            <div class="auth-panel-right">
                <div class="form-content-wrap" :class="{ 'has-errors': form.errors.username || form.errors.password }">
                    
                    <!-- Form Header -->
                    <div class="form-header text-start mb-4">
                        <span class="staff-access-pill mb-2">
                            <i class="bi bi-shield-lock-fill me-1"></i> Area Khusus Staf
                        </span>
                        <h2 class="form-title">Selamat Datang</h2>
                        <p class="form-subtitle">Masukkan akun Anda untuk mengelola persuratan dan tindak lanjut naskah dinas.</p>
                    </div>

                    <form @submit.prevent="submit" novalidate>
                        <!-- Username Input -->
                        <div class="field-float mb-3"
                            :class="{ 'is-filled': form.username, 'is-invalid': form.errors.username }">
                            <i class="bi bi-person field-icon"></i>
                            <input v-model="form.username" type="text" id="username" placeholder=" " autofocus
                                autocomplete="username" />
                            <label for="username">Username Staf</label>
                        </div>
                        <div v-if="form.errors.username" class="field-error">{{ form.errors.username }}</div>

                        <!-- Password Input -->
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

                        <!-- Remember Me Switch -->
                        <div class="d-flex align-items-center justify-content-between my-3">
                            <label class="switch-label">
                                <input v-model="form.remember" type="checkbox" class="switch-input" />
                                <div class="switch-button">
                                    <div class="switch-circle"></div>
                                </div>
                                <span class="ms-2 small text-muted fw-semibold">Ingat sesi saya</span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button ref="submitBtn" type="submit" class="btn-primary-action w-100" :disabled="form.processing"
                            @click="captureOrigin">
                            <span v-if="form.processing" class="btn-loading">
                                <span class="btn-loading-ring"></span>
                                <span>Memverifikasi akun...</span>
                            </span>
                            <span v-else class="d-inline-flex align-items-center justify-content-center gap-2">
                                <span>Masuk ke Sistem</span>
                                <i class="bi bi-arrow-right"></i>
                            </span>
                        </button>
                    </form>

                    <!-- Kembali ke Publik Link -->
                    <div class="text-center mt-4 pt-3 border-top">
                        <Link href="/tracking" class="back-link">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Portal Lacak Publik
                        </Link>
                    </div>

                    <!-- Footer Meta Bantuan -->
                    <div class="form-bottom-meta">
                        <div class="d-flex align-items-center justify-content-center gap-3 text-muted">
                            <span class="small"><i class="bi bi-headset me-1 text-primary"></i> Bantuan TU Sekjen</span>
                            <span class="opacity-30">&bull;</span>
                            <span class="small"><i class="bi bi-shield-check me-1 text-success"></i> Sistem Terproteksi</span>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- Ripple Login Transition Overlay -->
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
                    <p class="login-transition-text">Memverifikasi kredensial staf...</p>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped lang="scss">
$navy-dark: #1E2338;
$navy-panel: #2A314E;
$blue-primary: #4F6AF4;
$blue-vibrant: #435FE8;
$blue-dark: #3750D6;
$pink-coral: #FF5A87;
$soft-bg: #F5F7FC;
$text-dark: #1E293B;
$text-muted: #64748B;
$border-light: #E2E8F0;

.auth-wrapper {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: $navy-dark;
    padding: 2rem 1.25rem;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

/* ===== Ambient Decorative Backdrop ===== */
.ambient-backdrop {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
    z-index: 0;
}

.ambient-blob-pink {
    position: absolute;
    top: -10%;
    right: -10%;
    width: 600px;
    height: 600px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 90, 135, 0.75) 0%, rgba(255, 90, 135, 0.15) 60%, transparent 80%);
    filter: blur(40px);
}

.ambient-dot-grid {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1.5px, transparent 1.5px);
    background-size: 28px 28px;
    opacity: 0.6;
}

/* ===== Main Split Card Container ===== */
.auth-card-container {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 1020px;
    min-height: 600px;
    background: transparent;
    border-radius: 36px;
    display: flex;
    box-shadow: 0 30px 90px -20px rgba(10, 18, 45, 0.7), 0 0 0 1px rgba(255, 255, 255, 0.08);
    overflow: hidden;
    animation: cardFadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}

/* ===== LEFT PANEL (Ilustrasi) ===== */
.auth-panel-left {
    flex: 1.1;
    background: linear-gradient(145deg, #637DF5 0%, #4D6BF0 50%, #3B58E9 100%);
    padding: 2.5rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;

    &::after {
        content: '';
        position: absolute;
        bottom: -20%;
        left: -20%;
        width: 400px;
        height: 400px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
        pointer-events: none;
    }
}

.left-brand-row {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    position: relative;
    z-index: 2;
}

.brand-badge-white {
    width: 48px;
    height: 48px;
    background: #ffffff;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
}

.brand-text {
    .brand-name {
        font-size: 1.25rem;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: -0.01em;
        line-height: 1.2;
    }
    .brand-tagline {
        font-size: 0.72rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.85);
        letter-spacing: 0.02em;
    }
}

.illustration-display-wrap {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem 0;
}

.illustration-img {
    max-width: 100%;
    max-height: 380px;
    object-fit: contain;
    filter: drop-shadow(0 18px 35px rgba(25, 45, 140, 0.3));
    user-select: none;
}

.animate-float {
    animation: floatSmooth 6s ease-in-out infinite;
}

@keyframes floatSmooth {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-8px);
    }
}

.left-footer-text {
    position: relative;
    z-index: 2;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
}

/* ===== RIGHT PANEL (Form Login with Curved Separator) ===== */
.auth-panel-right {
    flex: 1;
    background: #ffffff;
    border-top-left-radius: 54px; /* Curved inward shape matching reference */
    padding: 3rem 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 2;
    box-shadow: -15px 0 35px rgba(0, 0, 0, 0.06);
}

.form-content-wrap {
    width: 100%;
    max-width: 380px;

    &.has-errors {
        animation: shake 0.4s ease-in-out;
    }
}

.staff-access-pill {
    display: inline-flex;
    align-items: center;
    font-size: 0.72rem;
    font-weight: 700;
    color: $blue-primary;
    background: rgba(79, 106, 244, 0.1);
    padding: 0.3rem 0.85rem;
    border-radius: 999px;
    letter-spacing: 0.02em;
}

.form-title {
    font-size: 1.65rem;
    font-weight: 800;
    color: $text-dark;
    letter-spacing: -0.02em;
    margin-bottom: 0.4rem;
}

.form-subtitle {
    font-size: 0.85rem;
    color: $text-muted;
    line-height: 1.5;
    margin-bottom: 0;
}

/* Floating Input Fields */
.field-float {
    position: relative;

    input {
        width: 100%;
        height: 54px;
        border: 1.5px solid #CBD5E1;
        border-radius: 14px;
        padding: 0 1rem 0 3rem;
        font-size: 0.95rem;
        background: #F8FAFC;
        color: $text-dark;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);

        &:focus {
            outline: none;
            border-color: $blue-primary;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(79, 106, 244, 0.15);
        }
    }

    label {
        position: absolute;
        left: 3rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94A3B8;
        pointer-events: none;
        transition: all 0.2s ease;
        font-size: 0.9rem;
    }

    .field-icon {
        position: absolute;
        left: 1.15rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94A3B8;
        font-size: 1.15rem;
    }

    input:focus + label,
    &.is-filled label {
        top: 0;
        font-size: 0.74rem;
        font-weight: 700;
        color: $blue-primary;
        background: #ffffff;
        padding: 0 6px;
    }

    .field-suffix-btn {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        color: #94A3B8;
        cursor: pointer;
        padding: 4px;

        &:hover {
            color: $text-dark;
        }
    }

    &.is-invalid input {
        border-color: #EF4444;
    }
}

.field-error {
    color: #EF4444;
    font-size: 0.75rem;
    margin-top: 4px;
    font-weight: 600;
}

/* Switch Toggle */
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
        width: 42px;
        height: 22px;
        background-color: #CBD5E1;
        border-radius: 20px;
        transition: all 0.3s ease;
    }

    .switch-circle {
        position: absolute;
        top: 2px;
        left: 2px;
        width: 18px;
        height: 18px;
        background-color: white;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }

    .switch-input:checked + .switch-button {
        background-color: $blue-primary;
        box-shadow: 0 3px 8px rgba(79, 106, 244, 0.3);
    }

    .switch-input:checked + .switch-button .switch-circle {
        transform: translateX(20px);
    }
}

/* Primary Action Button */
.btn-primary-action {
    height: 52px;
    border: none;
    border-radius: 14px;
    color: #ffffff;
    font-weight: 700;
    font-size: 0.95rem;
    background: linear-gradient(135deg, $blue-primary 0%, $blue-vibrant 100%);
    box-shadow: 0 6px 18px rgba(79, 106, 244, 0.35);
    transition: all 0.25s ease;

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        background: linear-gradient(135deg, $blue-vibrant 0%, $blue-dark 100%);
        box-shadow: 0 8px 24px rgba(79, 106, 244, 0.45);
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
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.back-link {
    color: #64748B;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.2s;

    &:hover {
        color: $blue-primary;
    }
}

.form-bottom-meta {
    margin-top: 1.5rem;
}

@keyframes cardFadeUp {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes shake {
    0%, 100% {
        transform: translateX(0);
    }
    25% {
        transform: translateX(-6px);
    }
    75% {
        transform: translateX(6px);
    }
}

/* ===== Ripple Login Transition Overlay ===== */
.login-transition-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: $navy-dark;
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
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.08);
    }
}

/* Responsive adjustments */
@media (max-width: 991px) {
    .auth-card-container {
        flex-direction: column;
        max-width: 480px;
        border-radius: 28px;
    }

    .auth-panel-left {
        padding: 2rem 1.75rem 1.5rem;
    }

    .illustration-display-wrap {
        padding: 1rem 0;
    }

    .illustration-img {
        max-height: 220px;
    }

    .auth-panel-right {
        border-top-left-radius: 36px;
        border-top-right-radius: 36px;
        padding: 2.5rem 1.75rem;
        margin-top: -24px;
    }
}
</style>