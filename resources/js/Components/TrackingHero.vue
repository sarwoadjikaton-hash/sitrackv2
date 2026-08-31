<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const model = defineModel<string>({ required: true });

const emit = defineEmits<{
    (e: 'search'): void;
}>();

const handleSubmit = () => {
    if (!model.value) return;
    emit('search');
};
</script>

<template>
    <section class="hero-section">
        <!-- Definisi filter SVG (gooey effect utk tombol) -->
        <svg class="sr-only-defs" width="0" height="0">
            <defs>
                <filter id="gooey-filter" x="-50%" y="-50%" width="200%" height="200%">
                    <feGaussianBlur in="SourceGraphic" stdDeviation="4" result="blur" />
                    <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 19 -9"
                        result="gooey" />
                    <feComposite in="SourceGraphic" in2="gooey" operator="atop" />
                </filter>
            </defs>
        </svg>

        <!-- Mesh gradient background (pengganti @paper-design/shaders-react) -->
        <div class="hero-mesh">
            <div class="mesh-blob mesh-blob-1"></div>
            <div class="mesh-blob mesh-blob-2"></div>
            <div class="mesh-blob mesh-blob-3"></div>
            <div class="mesh-blob mesh-blob-4"></div>
            <div class="mesh-sheen"></div>
            <div class="mesh-grid-overlay"></div>
        </div>

        <!-- Dekorasi tema dokumen & tracking -->
        <div class="hero-bg-icons">
            <svg class="hero-icon icon-pin" viewBox="0 0 40 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 2C10.6 2 3 9.6 3 19C3 32 20 54 20 54C20 54 37 32 37 19C37 9.6 29.4 2 20 2Z" stroke="white"
                    stroke-width="2" stroke-linejoin="round" />
                <circle cx="20" cy="19" r="7" stroke="white" stroke-width="2" />
            </svg>
            <svg class="hero-icon icon-route" viewBox="0 0 120 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2 30C25 30 25 6 48 6C71 6 71 34 94 34C105 34 110 30 118 22" stroke="white" stroke-width="2"
                    stroke-dasharray="5 6" stroke-linecap="round" />
                <circle cx="2" cy="30" r="3.5" fill="white" />
                <circle cx="118" cy="22" r="3.5" fill="white" />
            </svg>
        </div>

        <div class="container hero-container">
            <div class="hero-text-block">
                <!-- Badge melayang -->
                <div class="hero-badge">
                    <span class="hero-badge-dot"></span>
                    <span>Terverifikasi &bull; Realtime &bull; Log Transparan</span>
                </div>

                <!-- Tipografi bertingkat -->
                <h1 class="hero-heading">
                    <span class="heading-kicker">SiTrack &mdash; Portal Resmi</span>
                    <span class="heading-main">Lacak Naskah</span>
                    <span class="heading-sub">Dinas Anda</span>
                </h1>

                <p class="hero-desc">
                    Pantau posisi surat Anda secara realtime dengan memasukkan Nomor Resi
                    (contoh: <span class="fw-bold text-info">TUS-YYYYMMDD-XXX</span>)
                </p>

                <!-- Search box -->
                <div class="search-wrapper">
                    <form @submit.prevent="handleSubmit" class="search-glass-box">
                        <input v-model="model" type="text" placeholder="TUS-YYYYMMDD-XXX" required />
                        <button type="submit" class="btn-track">
                            <span class="d-none d-sm-inline">Lacak Sekarang</span>
                            <i class="bi bi-search d-sm-none"></i>
                        </button>
                    </form>
                </div>

                <!-- CTA sekunder, gooey button style -->
                <div class="hero-cta-row">
                    <Link href="/ajukan-surat" class="gooey-cta-wrapper" style="filter: url(#gooey-filter);">
                        <span class="gooey-arrow">
                            <i class="bi bi-arrow-up-right"></i>
                        </span>
                        <span class="gooey-label">
                            <i class="bi bi-send-fill me-2"></i>Ajukan Surat Baru
                        </span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Badge muter di pojok, pengganti PulsingBorder -->
        <div class="hero-orb">
            <div class="orb-ring"></div>
            <div class="orb-core"><i class="bi bi-shield-check"></i></div>
            <svg class="orb-text" viewBox="0 0 100 100">
                <defs>
                    <path id="orbCirclePath" d="M 50, 50 m -38, 0 a 38,38 0 1,1 76,0 a 38,38 0 1,1 -76,0" />
                </defs>
                <text>
                    <textPath href="#orbCirclePath" startOffset="0%">
                        SITRACK &bull; REALTIME TRACKING &bull; SITRACK &bull;
                    </textPath>
                </text>
            </svg>
        </div>
    </section>
</template>

<style scoped>
.sr-only-defs {
    position: absolute;
    width: 0;
    height: 0;
}

/* ============ HERO ============ */
.hero-section {
    position: relative;
    min-height: calc(100vh - 90px);
    width: 100%;
    display: flex;
    align-items: center;
    overflow: hidden;
    background: var(--st-primary-dark);
    margin-top: -88px;
    padding-top: 88px;
}

/* ---- Mesh gradient (pengganti MeshGradient shader) ---- */
.hero-mesh {
    position: absolute;
    inset: 0;
    overflow: hidden;
}

.mesh-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(90px);
    mix-blend-mode: screen;
    will-change: transform;
    animation: meshDrift 22s ease-in-out infinite;
}

.mesh-blob-1 {
    width: 55vw;
    height: 55vw;
    max-width: 640px;
    max-height: 640px;
    top: -15%;
    left: -10%;
    background: radial-gradient(circle,
            #5b96b8 0%,
            transparent 70%);
    opacity: .55;
}

.mesh-blob-2 {
    width: 45vw;
    height: 45vw;
    max-width: 560px;
    max-height: 560px;
    top: 10%;
    right: -12%;
    background: radial-gradient(circle,
            #003a63 0%,
            transparent 70%);
    opacity: .5;
    animation-duration: 26s;
    animation-delay: -6s;
}

.mesh-blob-3 {
    width: 40vw;
    height: 40vw;
    max-width: 480px;
    max-height: 480px;
    bottom: -18%;
    left: 20%;
    background: radial-gradient(circle,
            #002b4c 0%,
            transparent 70%);
    opacity: .6;
    animation-duration: 30s;
    animation-delay: -12s;
}

.mesh-blob-4 {
    width: 30vw;
    height: 30vw;
    max-width: 380px;
    max-height: 380px;
    bottom: -5%;
    right: 10%;
    background: radial-gradient(circle,
            #f59e71 0%,
            transparent 70%);
    opacity: .4;
    animation-duration: 19s;
    animation-delay: -3s;
}

@keyframes meshDrift {

    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }

    33% {
        transform: translate(4%, -6%) scale(1.08);
    }

    66% {
        transform: translate(-5%, 4%) scale(0.95);
    }
}

.mesh-sheen {
    position: absolute;
    inset: -20%;
    background: conic-gradient(from 0deg,
            transparent 0%,
            rgba(19, 78, 74, .06) 20%,
            transparent 35%,
            rgba(45, 212, 191, .07) 55%,
            transparent 75%);
    animation: sheenSpin 40s linear infinite;
}

@keyframes sheenSpin {
    to {
        transform: rotate(360deg);
    }
}

.mesh-grid-overlay {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255, 255, 255, .04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, .04) 1px, transparent 1px);
    background-size: 46px 46px;
    mask-image: radial-gradient(circle at 30% 50%, #000 0%, transparent 75%);
}

@media (prefers-reduced-motion: reduce) {

    .mesh-blob,
    .mesh-sheen,
    .hero-icon,
    .hero-badge,
    .orb-ring,
    .orb-text {
        animation: none !important;
    }
}

/* ---- Dekorasi dokumen & tracking ---- */
.hero-bg-icons {
    position: absolute;
    inset: 0;
    z-index: 1;
    overflow: hidden;
    pointer-events: none;
}

.hero-icon {
    position: absolute;
    opacity: .08;
    animation: heroIconFloat 18s ease-in-out infinite;
}

.icon-pin {
    width: 60px;
    bottom: 12%;
    left: 6%;
}

.icon-route {
    width: 200px;
    top: 14%;
    right: 6%;
    animation-delay: -8s;
}

@keyframes heroIconFloat {

    0%,
    100% {
        transform: translate(0, 0);
    }

    50% {
        transform: translate(10px, -12px);
    }
}

/* ---- Konten teks hero ---- */
.hero-container {
    position: relative;
    z-index: 10;
    width: 100%;
    padding-top: 3rem;
    padding-bottom: 3rem;
}

.hero-text-block {
    max-width: 640px;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .5rem 1.1rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, .06);
    border: 1px solid rgba(255, 255, 255, .14);
    backdrop-filter: blur(8px);
    color: rgba(255, 255, 255, .85);
    font-size: .78rem;
    font-weight: 600;
    letter-spacing: .02em;
    margin-bottom: 1.6rem;
    animation: badgeFloat 4s ease-in-out infinite;
}

@keyframes badgeFloat {

    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-4px);
    }
}

.hero-badge-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #f59e71;
    box-shadow: 0 0 8px 2px rgba(245, 158, 113, .55);
    flex: none;
}

.hero-heading {
    display: flex;
    flex-direction: column;
    margin-bottom: 1.25rem;
    line-height: 1.05;
}

.heading-kicker {
    font-size: clamp(1rem, 2vw, 1.35rem);
    font-weight: 300;
    letter-spacing: .04em;
    color: rgba(255, 255, 255, .65);
    margin-bottom: .3rem;
}

.heading-main {
    font-size: clamp(2.6rem, 6vw, 4.5rem);
    font-weight: 900;
    color: #fff;
    text-shadow: 0 6px 30px rgba(45, 212, 191, .25);
}

.heading-sub {
    font-size: clamp(1.8rem, 4vw, 3rem);
    font-weight: 300;
    font-style: italic;
    background: linear-gradient(135deg,
            #ffffff 0%,
            #eaf8ff 40%,
            #5b96b8 70%,
            #ffffff 100%);
    background-size: 200% auto;
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: sheenText 6s linear infinite;
}

@keyframes sheenText {
    to {
        background-position: 200% center;
    }
}

.hero-desc {
    font-size: 1.02rem;
    font-weight: 300;
    color: rgba(255, 255, 255, .7);
    max-width: 520px;
    line-height: 1.6;
    margin-bottom: 2rem;
}

.search-wrapper {
    margin-bottom: 1.5rem;
}

.search-glass-box {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 10px;
    border-radius: 60px;
    display: flex;
    width: 100%;
    max-width: 560px;
}

.search-glass-box input {
    background: transparent;
    border: none;
    padding: 0 26px;
    color: #fff;
    flex: 1;
    outline: none;
    font-size: 1.05rem;
    min-width: 0;
}

.search-glass-box input::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

.btn-track {
    background: #fff;
    color: #0f172a;
    border: none;
    padding: 12px 28px;
    border-radius: 50px;
    font-weight: 800;
    flex: none;
    transition: all 0.3s;
}

.btn-track:hover {
    background: var(--st-accent);
    color: #fff;
    transform: translateY(-2px);
}

/* ---- Tombol gooey (CSS murni, tanpa JS) ---- */
.hero-cta-row {
    display: flex;
}

.gooey-cta-wrapper {
    position: relative;
    display: inline-flex;
    align-items: center;
    height: 44px;
    text-decoration: none;
}

.gooey-arrow,
.gooey-label {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 44px;
    border-radius: 999px;
    background: linear-gradient(135deg,
            #5b96b8,
            #002b4c);
    color: #fff;
    font-weight: 700;
    font-size: .85rem;
    transition: transform .35s cubic-bezier(.4, 0, .2, 1);
}

.gooey-arrow {
    position: absolute;
    left: 0;
    width: 44px;
    transform: translateX(0);
    z-index: 0;
}

.gooey-label {
    position: relative;
    padding: 0 1.75rem 0 2.75rem;
    z-index: 1;
    transform: translateX(0);
}

.gooey-cta-wrapper:hover .gooey-arrow {
    transform: translateX(-14px);
}

.gooey-cta-wrapper:hover .gooey-label {
    transform: translateX(10px);
}

.gooey-cta-wrapper:active .gooey-label,
.gooey-cta-wrapper:active .gooey-arrow {
    filter: brightness(.95);
}

/* ---- Orb muter pojok bawah (pengganti PulsingBorder) ---- */
.hero-orb {
    position: absolute;
    z-index: 10;
    bottom: 6%;
    right: 6%;
    width: 92px;
    height: 92px;
    display: none;
}

@media (min-width: 768px) {
    .hero-orb {
        display: block;
    }
}

.orb-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: conic-gradient(from 0deg,
            #5b96b8,
            #003a63,
            #001a2e,
            #f59e71,
            #5b96b8);
    animation: orbSpin 6s linear infinite;
    filter: blur(1px);
}

.orb-ring::after {
    content: '';
    position: absolute;
    inset: 5px;
    border-radius: 50%;
    background: #0b1120;
}

@keyframes orbSpin {
    to {
        transform: rotate(360deg);
    }
}

.orb-core {
    position: absolute;
    inset: 18px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .06);
    display: grid;
    place-items: center;
    color: #f59e71;
    font-size: 1.15rem;
    box-shadow: 0 0 16px 2px rgba(45, 212, 191, .35);
    animation: orbPulse 2.4s ease-in-out infinite;
}

@keyframes orbPulse {

    0%,
    100% {
        box-shadow: 0 0 12px 1px rgba(45, 212, 191, .3);
    }

    50% {
        box-shadow: 0 0 20px 4px rgba(45, 212, 191, .55);
    }
}

.orb-text {
    position: absolute;
    inset: -14px;
    animation: orbTextSpin 18s linear infinite;
}

.orb-text text {
    font-size: 7.5px;
    fill: rgba(255, 255, 255, .55);
    letter-spacing: .5px;
}

@keyframes orbTextSpin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 767px) {
    .hero-section {
        min-height: auto;
        padding: 4rem 0 3rem;
    }
}
</style>