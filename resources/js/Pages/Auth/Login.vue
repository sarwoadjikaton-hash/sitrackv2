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

    <div class="auth-page-wrapper">
        <!-- LEFT HALF: Blue Gradient Area with 3D Illustration -->
        <div class="auth-left-section">
            <!-- Top Logo Header -->
            <div class="left-top-brand">
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
            </div>

            <!-- 3D Illustration Center -->
            <div class="left-illustration-center">
                <img src="/images/login-illustration.png" alt="Ilustrasi Persuratan SiTrack" class="illustration-3d-img" />
            </div>

            <!-- Bottom Copyright -->
            <div class="left-bottom-footer">
                <p class="copyright-text mb-0">
                    Copyright &copy; 2026 SiTrack. All rights reserved.
                </p>
            </div>
        </div>

        <!-- RIGHT HALF: Curved White Area with Floating Login Card -->
        <div class="auth-right-section">
            <div class="right-content-container">
                <!-- Floating Login Card -->
                <div class="floating-login-card" :class="{ 'has-errors': form.errors.username || form.errors.password }">
                    <!-- Card Tabs Header -->
                    <div class="card-header-tabs mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge-staff-access">STAFF ACCESS ONLY</span>
                            <span class="portal-badge-text">Portal Staf</span>
                        </div>
                        <div class="tab-title-row">
                            <h2 class="active-tab-title mb-0">Masuk ke Sistem</h2>
                        </div>
                        <p class="text-muted small mt-1 mb-0">Sistem Elektronik Administrasi Persuratan</p>
                    </div>

                    <!-- Form -->
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
                            <button type="button" class="field-suffix-btn" @click="togglePassword" tabindex="-1" aria-label="Toggle password">
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

                    <!-- Bottom Link inside card -->
                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="text-muted small mb-0">
                            Bukan Staff?
                            <Link href="/tracking" class="back-link fw-semibold ms-1">
                                Buka Portal Publik
                            </Link>
                        </p>
                    </div>
                </div>

                <!-- Footer under card -->
                <div class="right-bottom-subfooter text-center mt-4">
                    <p class="text-muted small mb-0">
                        Kementerian Ketenagakerjaan Republik Indonesia
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
.auth-page-wrapper {
    position: relative;
    display: flex;
    width: 100vw;
    min-height: 100vh;
    overflow-x: hidden;
    overflow-y: auto;
    background: linear-gradient(145deg, #0e347e 0%, #03205A 50%, #02163f 100%);
    margin: 0;
    padding: 0;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

/* ========================================================
   LEFT SECTION: Deep Navy System & 3D Illustration
   ======================================================== */
.auth-left-section {
    flex: 1.05;
    padding: 3.5rem 4rem 2.5rem 4.5rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 100vh;
    position: relative;
    z-index: 1;
}

.left-top-brand {
    position: relative;
    z-index: 2;
}

.app-logo-header {
    width: 38px;
    height: 38px;
}

.kemnaker-logo-header {
    width: 32px;
    height: 32px;
    vertical-align: middle;
}

.kemnaker-logo-light {
    filter: brightness(0) invert(1);
    opacity: 0.95;
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

.left-illustration-center {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 0;
    flex: 1;
}

.illustration-3d-img {
    max-width: 100%;
    height: auto;
    max-height: 390px;
    object-fit: contain;
}

.left-bottom-footer {
    text-align: left;
}

.copyright-text {
    font-size: 0.78rem;
    color: rgba(255, 255, 255, 0.75);
    font-weight: 500;
    letter-spacing: 0.02em;
}

/* ========================================================
   RIGHT SECTION: Curved Soft White Area with Floating Card
   ======================================================== */
.auth-right-section {
    flex: 1.15;
    background: #F8FAFD;
    border-top-left-radius: 90px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3.5rem 3rem;
    position: relative;
    z-index: 2;
    box-shadow: -18px 0 45px rgba(0, 0, 0, 0.08);
}

.right-content-container {
    width: 100%;
    max-width: 440px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.floating-login-card {
    width: 100%;
    background: #ffffff;
    border-radius: 32px;
    padding: 3rem 2.8rem 2.5rem;
    box-shadow: 0 20px 45px rgba(3, 32, 90, 0.08), 0 6px 18px rgba(0, 0, 0, 0.03);
    border: 1px solid rgba(226, 232, 240, 0.9);
}

.badge-staff-access {
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    color: #1C386F;
    background: #EEF7FC;
    padding: 4px 12px;
    border-radius: 50px;
    display: inline-block;
}

.portal-badge-text {
    font-size: 0.75rem;
    font-weight: 600;
    color: #94A3B8;
}

.active-tab-title {
    font-size: 1.65rem;
    font-weight: 800;
    color: #03205A;
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
            border-color: #1C386F;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(28, 56, 111, 0.12);
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
        color: #1C386F;
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
        background-color: #1C386F;
    }

    .switch-input:checked + .switch-button .switch-circle {
        transform: translateX(18px);
    }
}

/* Button Glow Submit */
.btn-glow-submit {
    height: 52px;
    border: none;
    border-radius: 50px;
    color: #ffffff;
    font-weight: 700;
    font-size: 0.98rem;
    letter-spacing: 0.2px;
    background: linear-gradient(135deg, #1C386F 0%, #03205A 100%);
    box-shadow: 0 8px 22px rgba(3, 32, 90, 0.35);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    cursor: pointer;

    &:hover:not(:disabled) {
        transform: translateY(-2px);
        background: linear-gradient(135deg, #142850 0%, #02163f 100%);
        box-shadow: 0 12px 26px rgba(3, 32, 90, 0.45);
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
        color: #1C386F;
    }
}

.right-bottom-subfooter {
    p {
        color: #94A3B8;
        font-size: 0.78rem;
        font-weight: 500;
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
    .auth-page-wrapper {
        flex-direction: column;
    }

    .auth-left-section {
        padding: 2.5rem 1.75rem 2rem;
        min-height: auto;
        flex: none;
    }

    .illustration-3d-img {
        max-height: 220px;
    }

    .auth-right-section {
        border-top-left-radius: 40px;
        border-top-right-radius: 40px;
        padding: 2.5rem 1.5rem;
    }

    .floating-login-card {
        padding: 2.2rem 1.75rem;
        border-radius: 24px;
    }
}
</style>