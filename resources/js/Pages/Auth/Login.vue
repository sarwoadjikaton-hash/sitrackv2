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
        <!-- Edge-to-edge 2 Columns Container -->
        <div class="auth-main-card">
            <!-- LEFT COLUMN: Brand & 3D Illustration -->
            <div class="auth-left-pane">
                <div class="left-pane-content">
                    <!-- Top Logo Header -->
                    <div class="brand-top-row">
                        <div class="brand-logos-pill">
                            <div class="logo-icon-wrap">
                                <img src="/images/sitrack_logo.svg" alt="SiTrack Logo" width="34" height="34" />
                            </div>
                            <span class="logo-divider"></span>
                            <div class="logo-icon-wrap kemnaker-icon">
                                <img src="/images/kemnaker_logo.png" alt="Kemnaker Logo" width="28" height="28" />
                            </div>
                        </div>
                        <div class="brand-title-group">
                            <span class="brand-name">SiTrack</span>
                            <span class="brand-subtitle">TU SEKJEN</span>
                        </div>
                    </div>

                    <!-- Center 3D Illustration -->
                    <div class="illustration-container">
                        <img src="/images/login-illustration.png" alt="Ilustrasi Persuratan SiTrack" class="illustration-3d-img" />
                    </div>

                    <!-- Bottom Note -->
                    <div class="left-pane-footer">
                        <p class="footer-copy-text mb-0">
                            &copy; 2026 SiTrack &bull; Kementerian Ketenagakerjaan Republik Indonesia
                        </p>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Curved Divider & Login Form -->
            <div class="auth-right-pane">
                <!-- SVG Curved Shape Divider -->
                <div class="curved-divider-wrapper" aria-hidden="true">
                    <svg class="curved-divider-svg" viewBox="0 0 100 800" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 0 C40 0 0 80 0 200 L0 800 L100 800 Z" fill="#ffffff" />
                    </svg>
                </div>

                <div class="right-pane-content">
                    <!-- Login Form Box -->
                    <div class="login-inner-card" :class="{ 'has-errors': form.errors.username || form.errors.password }">
                        <div class="form-header-section mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge-staff-access">STAFF ACCESS ONLY</span>
                                <span class="portal-badge-text">Portal Staf</span>
                            </div>
                            <h2 class="form-title text-dark fw-bold mb-1">Masuk ke Sistem</h2>
                            <p class="text-muted small mb-0">Sistem Elektronik Administrasi Persuratan</p>
                        </div>

                        <form @submit.prevent="submit" novalidate>
                            <!-- Username Field -->
                            <div class="field-float mb-3" :class="{ 'is-filled': form.username, 'is-invalid': form.errors.username }">
                                <i class="bi bi-person field-icon"></i>
                                <input v-model="form.username" type="text" id="username" placeholder=" " autofocus autocomplete="username" />
                                <label for="username">Username Staf</label>
                            </div>
                            <div v-if="form.errors.username" class="field-error">{{ form.errors.username }}</div>

                            <!-- Password Field -->
                            <div class="field-float mb-2" :class="{ 'is-filled': form.password, 'is-invalid': form.errors.password }">
                                <i class="bi bi-lock field-icon"></i>
                                <input v-model="form.password" :type="showPassword ? 'text' : 'password'" id="password" placeholder=" " autocomplete="current-password" />
                                <label for="password">Kata Sandi</label>
                                <button type="button" class="field-suffix-btn" @click="togglePassword" tabindex="-1">
                                    <i :class="['bi', showPassword ? 'bi-eye-slash' : 'bi-eye']"></i>
                                </button>
                            </div>
                            <div v-if="form.errors.password" class="field-error mb-2">{{ form.errors.password }}</div>

                            <!-- Remember Me Toggle -->
                            <div class="d-flex align-items-center justify-content-between my-3.5">
                                <label class="switch-label">
                                    <input v-model="form.remember" type="checkbox" class="switch-input" />
                                    <div class="switch-button">
                                        <div class="switch-circle"></div>
                                    </div>
                                    <span class="ms-2 small text-muted fw-semibold">Ingat sesi saya</span>
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <button ref="submitBtn" type="submit" class="btn-glow-submit w-100 mt-2" :disabled="form.processing" @click="captureOrigin">
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
    overflow: hidden;
    display: flex;
    background: #ffffff;
    padding: 0;
    margin: 0;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

/* Edge-to-Edge 2-Column Container */
.auth-main-card {
    position: relative;
    display: flex;
    width: 100%;
    min-height: 100vh;
    background: #ffffff;
    margin: 0;
    padding: 0;
    overflow: hidden;
}

/* ========================================================
   LEFT PANE: 3D Illustration & Seamless Color Background
   ======================================================== */
.auth-left-pane {
    position: relative;
    flex: 1.15;
    background: #7383E2;
    min-height: 100vh;
    padding: 3rem 4rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
    z-index: 1;
}

.left-pane-content {
    position: relative;
    z-index: 2;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.brand-top-row {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.brand-logos-pill {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    background: rgba(255, 255, 255, 0.22);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.35);
    padding: 0.45rem 0.8rem;
    border-radius: 18px;
}

.logo-icon-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-divider {
    width: 1px;
    height: 20px;
    background: rgba(255, 255, 255, 0.4);
}

.brand-title-group {
    display: flex;
    flex-direction: column;
}

.brand-name {
    font-size: 1.35rem;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: -0.01em;
    line-height: 1.1;
}

.brand-subtitle {
    font-size: 0.7rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.85);
    letter-spacing: 0.08em;
}

.illustration-container {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem 0;
    flex: 1;
}

.illustration-3d-img {
    position: relative;
    z-index: 2;
    max-width: 100%;
    height: auto;
    max-height: 380px;
    object-fit: contain;
    animation: floatIllustration 8s ease-in-out infinite;
}

@keyframes floatIllustration {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-8px);
    }
}

.left-pane-footer {
    text-align: left;
}

.footer-copy-text {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
    letter-spacing: 0.02em;
}

/* ========================================================
   RIGHT PANE: Curved Divider & Login Form
   ======================================================== */
.auth-right-pane {
    position: relative;
    flex: 1;
    background: #ffffff;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem 4.5rem;
    z-index: 2;
}

/* SVG Curved Shape Divider */
.curved-divider-wrapper {
    position: absolute;
    top: 0;
    bottom: 0;
    left: -55px;
    width: 58px;
    height: 100%;
    z-index: 10;
    pointer-events: none;
}

.curved-divider-svg {
    width: 100%;
    height: 100%;
    display: block;
}

.right-pane-content {
    position: relative;
    z-index: 15;
    width: 100%;
    max-width: 400px;
}

.badge-staff-access {
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    color: #4F46E5;
    background: #EEF2FF;
    padding: 4px 12px;
    border-radius: 50px;
    display: inline-block;
}

.portal-badge-text {
    font-size: 0.75rem;
    font-weight: 600;
    color: #94A3B8;
}

.form-title {
    font-size: 1.65rem;
    letter-spacing: -0.02em;
}

/* Floating label inputs */
.field-float {
    position: relative;

    input {
        width: 100%;
        height: 54px;
        border: 1.5px solid #E2E8F0;
        border-radius: 14px;
        padding: 0 1rem 0 3rem;
        font-size: 0.95rem;
        background: #F8FAFC;
        color: #0F172A;
        transition: all 0.25s ease;

        &:focus {
            outline: none;
            border-color: #3A62E8;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(58, 98, 232, 0.12);
        }
    }

    label {
        position: absolute;
        left: 3rem;
        top: 50%;
        transform: translateY(-50%);
        color: #64748B;
        pointer-events: none;
        transition: all 0.2s ease;
    }

    .field-icon {
        position: absolute;
        left: 1.2rem;
        top: 50%;
        transform: translateY(-50%);
        color: #64748B;
        font-size: 1.15rem;
    }

    input:focus + label,
    &.is-filled label {
        top: 0;
        font-size: 0.72rem;
        font-weight: 700;
        color: #3A62E8;
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
        color: #64748B;
        cursor: pointer;
    }

    &.is-invalid input {
        border-color: #EF4444;
    }
}

.field-error {
    color: #EF4444;
    font-size: 0.75rem;
    margin-top: 4px;
    font-weight: 500;
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
        width: 40px;
        height: 22px;
        background-color: #CBD5E1;
        border-radius: 20px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .switch-circle {
        position: absolute;
        top: 3px;
        left: 3px;
        width: 16px;
        height: 16px;
        background-color: white;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .switch-input:checked + .switch-button {
        background-color: #3A62E8;
    }

    .switch-input:checked + .switch-button .switch-circle {
        transform: translateX(18px);
    }
}

/* Button Glow Submit */
.btn-glow-submit {
    height: 54px;
    border: none;
    border-radius: 14px;
    color: #ffffff;
    font-weight: 700;
    font-size: 0.95rem;
    background: linear-gradient(135deg, #4A72F5 0%, #3A62E8 100%);
    box-shadow: 0 4px 14px rgba(58, 98, 232, 0.35);
    transition: all 0.2s ease;
    cursor: pointer;

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        background: linear-gradient(135deg, #3A62E8 0%, #2544C0 100%);
        box-shadow: 0 8px 20px rgba(58, 98, 232, 0.45);
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
    border-top-color: #ffffff;
    animation: btnRingSpin 0.8s linear infinite;
}

@keyframes btnRingSpin {
    to {
        transform: rotate(360deg);
    }
}

.back-link {
    color: #64748B;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.2s ease;

    &:hover {
        color: #3A62E8;
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
    .auth-main-card {
        flex-direction: column;
        max-width: 500px;
        min-height: auto;
        border-radius: 28px;
    }

    .auth-left-pane {
        padding: 2rem 1.5rem;
        flex: none;
    }

    .illustration-container {
        padding: 1rem 0;
    }

    .illustration-3d-img {
        max-height: 200px;
    }

    .curved-divider-wrapper {
        display: none;
    }

    .auth-right-pane {
        padding: 2.5rem 1.75rem;
    }
}
</style>