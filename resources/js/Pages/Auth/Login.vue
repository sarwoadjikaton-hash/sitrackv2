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

    <div class="login-split-page">
        
        <!-- ================= LEFT PANEL: ILUSTRASI SEAMLESS ================= -->
        <div class="login-left-pane">
            <!-- Header Brand -->
            <div class="left-brand">
                <div class="brand-badge-white">
                    <img src="/images/sitrack_logo.svg" alt="SiTrack Logo" width="36" height="36" />
                </div>
                <div class="brand-info">
                    <h1 class="brand-title">SiTrack</h1>
                    <p class="brand-subtitle">Tata Usaha Sekjen Kemnaker</p>
                </div>
            </div>

            <!-- Center Illustration (Blends 100% with #7e8fe9) -->
            <div class="left-illustration-container">
                <img src="/images/login-illustration.png" alt="Ilustrasi Persuratan" class="illustration-image" />
            </div>

            <!-- Footer Copyright -->
            <div class="left-footer">
                <p class="mb-0">&copy; 2026 SiTrack &bull; Kementerian Ketenagakerjaan RI</p>
            </div>
        </div>

        <!-- ================= RIGHT PANEL: FORM LOGIN WITH CURVED SEPARATOR ================= -->
        <div class="login-right-pane">
            <div class="form-wrapper" :class="{ 'has-errors': form.errors.username || form.errors.password }">
                
                <!-- Header Form -->
                <div class="form-header text-start mb-4">
                    <div class="badge-staff-pill mb-2">
                        <i class="bi bi-shield-lock-fill me-1"></i> Area Khusus Staf
                    </div>
                    <h2 class="form-title">Selamat Datang</h2>
                    <p class="form-subtitle">Silakan masukkan username dan kata sandi Anda untuk mengakses sistem persuratan.</p>
                </div>

                <!-- Form Login -->
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

                    <!-- Remember Me Toggle -->
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
                    <button ref="submitBtn" type="submit" class="btn-login-action w-100" :disabled="form.processing"
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

                <!-- Kembali ke Lacak Publik -->
                <div class="text-center mt-4 pt-3 border-top">
                    <Link href="/tracking" class="back-link">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Portal Lacak Publik
                    </Link>
                </div>

                <!-- Footer Meta -->
                <div class="form-footer-meta mt-4 text-center">
                    <div class="d-flex align-items-center justify-content-center gap-3 text-muted small">
                        <span><i class="bi bi-headset me-1 text-primary"></i> Bantuan TU</span>
                        <span class="opacity-30">&bull;</span>
                        <span><i class="bi bi-shield-check me-1 text-success"></i> Sistem Terproteksi</span>
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
$theme-blue: #7e8fe9;
$theme-dark-blue: #556be8;
$theme-darker-blue: #4459d4;
$text-dark: #0F172A;
$text-muted: #64748B;
$border-color: #CBD5E1;

/* Full screen direct split page without any outer container/background */
.login-split-page {
    min-height: 100vh;
    width: 100vw;
    display: flex;
    background: $theme-blue;
    overflow-x: hidden;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

/* ===== LEFT PANE (Illustration Side) ===== */
.login-left-pane {
    flex: 1.15;
    background: $theme-blue;
    min-height: 100vh;
    padding: 3rem 3.5rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
}

.left-brand {
    display: flex;
    align-items: center;
    gap: 1rem;
    position: relative;
    z-index: 2;
}

.brand-badge-white {
    width: 52px;
    height: 52px;
    background: #ffffff;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
}

.brand-info {
    .brand-title {
        font-size: 1.45rem;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: -0.01em;
        line-height: 1.15;
        margin: 0;
    }
    .brand-subtitle {
        font-size: 0.8rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.85);
        margin: 0;
    }
}

.left-illustration-container {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 1;
    padding: 1rem 0;
}

.illustration-image {
    width: 100%;
    max-width: 540px;
    height: auto;
    max-height: 70vh;
    object-fit: contain;
    user-select: none;
    filter: drop-shadow(0 20px 40px rgba(45, 65, 160, 0.25));
}

.left-footer {
    position: relative;
    z-index: 2;
    font-size: 0.78rem;
    color: rgba(255, 255, 255, 0.85);
}

/* ===== RIGHT PANE (White Login Form with Curved Border) ===== */
.login-right-pane {
    flex: 0.95;
    background: #ffffff;
    min-height: 100vh;
    border-top-left-radius: 64px;
    border-bottom-left-radius: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3.5rem 4.5rem;
    position: relative;
    z-index: 5;
    box-shadow: -20px 0 60px rgba(0, 0, 0, 0.08);
}

.form-wrapper {
    width: 100%;
    max-width: 400px;

    &.has-errors {
        animation: shake 0.4s ease-in-out;
    }
}

.badge-staff-pill {
    display: inline-flex;
    align-items: center;
    font-size: 0.74rem;
    font-weight: 700;
    color: $theme-dark-blue;
    background: rgba(126, 143, 233, 0.15);
    padding: 0.32rem 0.85rem;
    border-radius: 999px;
    letter-spacing: 0.02em;
}

.form-title {
    font-size: 1.85rem;
    font-weight: 800;
    color: $text-dark;
    letter-spacing: -0.02em;
    margin-bottom: 0.35rem;
}

.form-subtitle {
    font-size: 0.88rem;
    color: $text-muted;
    line-height: 1.5;
    margin-bottom: 0;
}

/* Floating Input Fields */
.field-float {
    position: relative;

    input {
        width: 100%;
        height: 56px;
        border: 1.5px solid $border-color;
        border-radius: 14px;
        padding: 0 1rem 0 3rem;
        font-size: 0.95rem;
        background: #F8FAFC;
        color: $text-dark;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);

        &:focus {
            outline: none;
            border-color: $theme-dark-blue;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(126, 143, 233, 0.25);
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
        font-size: 0.92rem;
    }

    .field-icon {
        position: absolute;
        left: 1.15rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94A3B8;
        font-size: 1.2rem;
    }

    input:focus + label,
    &.is-filled label {
        top: 0;
        font-size: 0.74rem;
        font-weight: 700;
        color: $theme-dark-blue;
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
        width: 44px;
        height: 24px;
        background-color: #CBD5E1;
        border-radius: 20px;
        transition: all 0.3s ease;
    }

    .switch-circle {
        position: absolute;
        top: 3px;
        left: 3px;
        width: 18px;
        height: 18px;
        background-color: white;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }

    .switch-input:checked + .switch-button {
        background-color: $theme-dark-blue;
        box-shadow: 0 3px 10px rgba(85, 107, 232, 0.35);
    }

    .switch-input:checked + .switch-button .switch-circle {
        transform: translateX(20px);
    }
}

/* Primary Action Button */
.btn-login-action {
    height: 54px;
    border: none;
    border-radius: 14px;
    color: #ffffff;
    font-weight: 700;
    font-size: 0.98rem;
    background: linear-gradient(135deg, $theme-dark-blue 0%, $theme-darker-blue 100%);
    box-shadow: 0 6px 18px rgba(85, 107, 232, 0.35);
    transition: all 0.25s ease;

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        background: linear-gradient(135deg, $theme-blue 0%, $theme-dark-blue 100%);
        box-shadow: 0 8px 24px rgba(85, 107, 232, 0.45);
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
    font-size: 0.86rem;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.2s;

    &:hover {
        color: $theme-dark-blue;
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
    background: $theme-darker-blue;
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

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .login-left-pane {
        padding: 2.5rem 2rem;
    }
    .login-right-pane {
        padding: 3rem 2.5rem;
    }
}

@media (max-width: 991px) {
    .login-split-page {
        flex-direction: column;
    }

    .login-left-pane {
        min-height: auto;
        padding: 2.5rem 2rem 3rem;
    }

    .illustration-image {
        max-width: 320px;
        max-height: 240px;
    }

    .login-right-pane {
        min-height: auto;
        border-top-left-radius: 40px;
        border-top-right-radius: 40px;
        border-bottom-left-radius: 0;
        margin-top: -32px;
        padding: 3rem 2rem;
    }
}
</style>