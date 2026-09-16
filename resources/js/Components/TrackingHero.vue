<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import * as THREE from 'three';
import { RoundedBoxGeometry } from 'three/examples/jsm/geometries/RoundedBoxGeometry.js';

const model = defineModel<string>({ required: true });

const emit = defineEmits<{
    (e: 'search'): void;
}>();

const handleSubmit = () => {
    if (!model.value) return;
    emit('search');
};

// ===== 3D Hero: Smart Futuristic Postal Hub & Floating Documents =====
const threeContainer = ref<HTMLDivElement | null>(null);
let renderer: THREE.WebGLRenderer | null = null;
let scene: THREE.Scene | null = null;
let camera: THREE.PerspectiveCamera | null = null;
let animationId: number | null = null;
let resizeObserver: ResizeObserver | null = null;
const floaters: { mesh: THREE.Object3D; speed: number; offset: number; baseY: number; rotSpeed: number }[] = [];
let mailboxGroup: THREE.Group | null = null;
let laserBeam: THREE.Mesh | null = null;
let particles: THREE.Points | null = null;

// Mouse tracking for subtle interactive parallax
let mouseX = 0;
let mouseY = 0;
let targetMouseX = 0;
let targetMouseY = 0;

const onMouseMove = (e: MouseEvent) => {
    const { innerWidth, innerHeight } = window;
    targetMouseX = (e.clientX / innerWidth - 0.5) * 2;
    targetMouseY = (e.clientY / innerHeight - 0.5) * 2;
};

function metallicMaterial(color: number, roughness = 0.25, metalness = 0.6) {
    return new THREE.MeshStandardMaterial({
        color,
        roughness,
        metalness,
    });
}

function glowingMaterial(color: number, intensity = 0.6) {
    return new THREE.MeshStandardMaterial({
        color,
        emissive: color,
        emissiveIntensity: intensity,
        roughness: 0.2,
        metalness: 0.1,
    });
}

function glassMaterial(color: number, opacity = 0.85) {
    return new THREE.MeshPhysicalMaterial({
        color,
        transparent: true,
        opacity,
        roughness: 0.15,
        metalness: 0.1,
        clearcoat: 1.0,
        clearcoatRoughness: 0.1,
        transmission: 0.3,
        ior: 1.4,
    });
}

// Objek hero utama: Smart Cyber Mailbox / Postal Vault
function createMailbox(): THREE.Group {
    const group = new THREE.Group();

    // 1. Magnetic Floating Base Pedestal (Glowing Cyber Ring)
    const baseRing = new THREE.Mesh(
        new THREE.CylinderGeometry(1.6, 1.8, 0.18, 32),
        metallicMaterial(0x182b78, 0.3, 0.8)
    );
    baseRing.position.y = -1.45;
    group.add(baseRing);

    const baseGlowRing = new THREE.Mesh(
        new THREE.TorusGeometry(1.5, 0.05, 16, 40),
        glowingMaterial(0x3DA5F9, 1.2)
    );
    baseGlowRing.rotation.x = Math.PI / 2;
    baseGlowRing.position.y = -1.35;
    group.add(baseGlowRing);

    // 2. Main Body Chassis - Deep Indigo Navy with Glossy Chamfers
    const chassis = new THREE.Mesh(
        new RoundedBoxGeometry(2.3, 2.7, 2.1, 8, 0.4),
        metallicMaterial(0x2743AF, 0.2, 0.35)
    );
    chassis.position.y = 0.15;
    group.add(chassis);

    // 3. Front Faceplate - Frosted High-Tech Glass Panel
    const frontPanel = new THREE.Mesh(
        new RoundedBoxGeometry(1.9, 2.3, 0.12, 6, 0.2),
        glassMaterial(0xf8fafc, 0.92)
    );
    frontPanel.position.set(0, 0.15, 1.05);
    group.add(frontPanel);

    // 4. Inset Mail Intake Slot (Metallic Frame + Dark Chasm)
    const slotFrame = new THREE.Mesh(
        new RoundedBoxGeometry(1.35, 0.32, 0.08, 4, 0.08),
        metallicMaterial(0x182b78, 0.2, 0.7)
    );
    slotFrame.position.set(0, 0.65, 1.12);
    group.add(slotFrame);

    const slotOpening = new THREE.Mesh(
        new RoundedBoxGeometry(1.18, 0.14, 0.15, 3, 0.04),
        new THREE.MeshStandardMaterial({ color: 0x070d1e, roughness: 0.9 })
    );
    slotOpening.position.set(0, 0.65, 1.13);
    group.add(slotOpening);

    // 5. Laser Scanning Light Beam
    const beamGeo = new THREE.PlaneGeometry(1.15, 0.04);
    const beamMat = new THREE.MeshBasicMaterial({
        color: 0x3DA5F9,
        transparent: true,
        opacity: 0.9,
        side: THREE.DoubleSide,
    });
    laserBeam = new THREE.Mesh(beamGeo, beamMat);
    laserBeam.position.set(0, 0.65, 1.15);
    group.add(laserBeam);

    // 6. Letter entering the slot halfway (Glowing Paper with Seal)
    const activeLetter = new THREE.Mesh(
        new RoundedBoxGeometry(0.85, 0.55, 0.03, 3, 0.02),
        glassMaterial(0xffffff, 0.95)
    );
    activeLetter.rotation.x = -0.35;
    activeLetter.position.set(0, 0.72, 1.05);
    group.add(activeLetter);

    const activeLetterSeal = new THREE.Mesh(
        new THREE.CylinderGeometry(0.08, 0.08, 0.04, 16),
        glowingMaterial(0x3DA5F9, 0.9)
    );
    activeLetterSeal.rotation.x = -0.35;
    activeLetterSeal.position.set(0, 0.78, 1.18);
    group.add(activeLetterSeal);

    // 7. Interactive Digital LED Status Display on Lower Panel
    const ledScreen = new THREE.Mesh(
        new RoundedBoxGeometry(1.4, 0.65, 0.04, 4, 0.06),
        new THREE.MeshStandardMaterial({
            color: 0x091436,
            emissive: 0x182b78,
            emissiveIntensity: 0.4,
            roughness: 0.1,
            metalness: 0.8,
        })
    );
    ledScreen.position.set(0, -0.35, 1.12);
    group.add(ledScreen);

    // HUD Indicator Bar
    const hudBar = new THREE.Mesh(
        new RoundedBoxGeometry(0.9, 0.08, 0.03, 2, 0.02),
        glowingMaterial(0x3DA5F9, 1.1)
    );
    hudBar.position.set(0, -0.35, 1.15);
    group.add(hudBar);

    return group;
}

// 3D Glass Floating Envelope with Seal
function createEnvelope(bodyColor: number, sealColor: number): THREE.Group {
    const group = new THREE.Group();
    const body = new THREE.Mesh(
        new RoundedBoxGeometry(0.95, 0.65, 0.06, 4, 0.06),
        glassMaterial(bodyColor, 0.92)
    );
    group.add(body);

    const flapShape = new THREE.Shape();
    flapShape.moveTo(-0.47, 0.32);
    flapShape.lineTo(0.47, 0.32);
    flapShape.lineTo(0, -0.06);
    flapShape.lineTo(-0.47, 0.32);
    const flap = new THREE.Mesh(
        new THREE.ExtrudeGeometry(flapShape, { depth: 0.025, bevelEnabled: false }),
        metallicMaterial(bodyColor, 0.25, 0.4)
    );
    flap.position.z = 0.031;
    group.add(flap);

    const seal = new THREE.Mesh(
        new THREE.CylinderGeometry(0.09, 0.09, 0.04, 20),
        glowingMaterial(sealColor, 0.9)
    );
    seal.rotation.x = Math.PI / 2;
    seal.position.set(0, -0.05, 0.055);
    group.add(seal);

    return group;
}

// Floating Official Document Stack
function createDocumentStack(): THREE.Group {
    const group = new THREE.Group();
    const colors = [0x2743AF, 0x4A9CF0, 0xffffff];
    for (let i = 0; i < 3; i++) {
        const sheet = new THREE.Mesh(
            new RoundedBoxGeometry(0.75, 0.95, 0.025, 3, 0.03),
            glassMaterial(colors[i], 0.94)
        );
        sheet.position.set(i * 0.04, -i * 0.03, i * 0.035);
        sheet.rotation.z = (i - 1) * 0.08;
        group.add(sheet);
    }
    return group;
}

// Security Shield / Verification Badge
function createSecurityShield(): THREE.Group {
    const group = new THREE.Group();
    const shieldShape = new THREE.Shape();
    shieldShape.moveTo(0, 0.5);
    shieldShape.lineTo(0.35, 0.35);
    shieldShape.lineTo(0.35, -0.15);
    shieldShape.quadraticCurveTo(0.2, -0.45, 0, -0.55);
    shieldShape.quadraticCurveTo(-0.2, -0.45, -0.35, -0.15);
    shieldShape.lineTo(-0.35, 0.35);
    shieldShape.lineTo(0, 0.5);

    const shield = new THREE.Mesh(
        new THREE.ExtrudeGeometry(shieldShape, { depth: 0.08, bevelEnabled: true, bevelSegments: 3, steps: 1, bevelSize: 0.03, bevelThickness: 0.03 }),
        metallicMaterial(0x2743AF, 0.2, 0.7)
    );
    group.add(shield);

    const core = new THREE.Mesh(
        new THREE.SphereGeometry(0.16, 20, 20),
        glowingMaterial(0x3DA5F9, 1.2)
    );
    core.position.z = 0.08;
    group.add(core);

    return group;
}

// Particle Constellation Effect
function createParticles(): THREE.Points {
    const particleCount = 45;
    const geometry = new THREE.BufferGeometry();
    const positions = new Float32Array(particleCount * 3);

    for (let i = 0; i < particleCount * 3; i += 3) {
        positions[i] = (Math.random() - 0.3) * 12;
        positions[i + 1] = (Math.random() - 0.5) * 8;
        positions[i + 2] = (Math.random() - 0.5) * 6;
    }

    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    const material = new THREE.PointsMaterial({
        color: 0x3DA5F9,
        size: 0.08,
        transparent: true,
        opacity: 0.65,
        blending: THREE.AdditiveBlending,
    });

    return new THREE.Points(geometry, material);
}

let isVisible = true;
let intersectionObserver: IntersectionObserver | null = null;

const onVisibilityChange = () => {
    isVisible = !document.hidden;
};

function initThree() {
    if (!threeContainer.value) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    window.addEventListener('mousemove', onMouseMove, { passive: true });
    document.addEventListener('visibilitychange', onVisibilityChange, { passive: true });

    const el = threeContainer.value;
    const width = el.clientWidth || 1;
    const height = el.clientHeight || 1;

    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(36, width / height, 0.1, 100);
    camera.position.set(2.2, 1.4, 9.2);
    camera.lookAt(2, 0, 0);

    renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true, powerPreference: 'default' });
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.15;
    el.appendChild(renderer.domElement);

    // Studio Lighting Setup (Cyan, Azure, Deep Blue)
    const keyLight = new THREE.DirectionalLight(0xffffff, 2.2);
    keyLight.position.set(5, 7, 6);
    scene.add(keyLight);

    const cyanRimLight = new THREE.DirectionalLight(0x3DA5F9, 1.8);
    cyanRimLight.position.set(-6, 2, 4);
    scene.add(cyanRimLight);

    const azureFillLight = new THREE.DirectionalLight(0x4A9CF0, 1.2);
    azureFillLight.position.set(2, -4, 3);
    scene.add(azureFillLight);

    const ambientLight = new THREE.AmbientLight(0x182b78, 1.0);
    scene.add(ambientLight);

    // Hero Object: Mailbox Group
    mailboxGroup = createMailbox();
    mailboxGroup.position.set(3, -0.2, 0);
    mailboxGroup.rotation.y = -0.38;
    scene.add(mailboxGroup);

    // Particle Cloud
    particles = createParticles();
    scene.add(particles);

    // Satellite Floating Objects in Orbit
    const smallObjects: { build: () => THREE.Group; pos: [number, number, number]; scale: number }[] = [
        { build: () => createEnvelope(0xffffff, 0x3DA5F9), pos: [0.1, 1.9, 1.6], scale: 1.0 },
        { build: () => createEnvelope(0x2743AF, 0x4A9CF0), pos: [-1.2, -1.3, 0.8], scale: 0.9 },
        { build: () => createDocumentStack(), pos: [5.2, 1.7, -0.4], scale: 0.95 },
        { build: () => createSecurityShield(), pos: [1.3, -1.7, 1.9], scale: 0.9 },
    ];

    smallObjects.forEach((item, i) => {
        const obj = item.build();
        obj.position.set(...item.pos);
        obj.scale.setScalar(item.scale);
        obj.rotation.set(Math.random() * 0.3, Math.random() * 0.8, Math.random() * 0.2);
        scene!.add(obj);
        floaters.push({
            mesh: obj,
            speed: 0.32 + i * 0.08,
            offset: i * 1.6,
            baseY: item.pos[1],
            rotSpeed: 0.003 + i * 0.001,
        });
    });

    const clock = new THREE.Clock();
    let lastFrameTime = 0;
    const frameInterval = 1000 / 60;

    const animate = (now = 0) => {
        animationId = requestAnimationFrame(animate);
        if (!isVisible) return;
        if (now - lastFrameTime < frameInterval) return;
        lastFrameTime = now;
        const t = clock.getElapsedTime();

        // Smooth mouse parallax lerp
        mouseX += (targetMouseX - mouseX) * 0.05;
        mouseY += (targetMouseY - mouseY) * 0.05;

        if (mailboxGroup) {
            // Elegant floating & breathing motion with parallax tilt
            mailboxGroup.position.y = -0.2 + Math.sin(t * 0.8) * 0.08;
            mailboxGroup.rotation.y = -0.38 + Math.sin(t * 0.4) * 0.08 + mouseX * 0.15;
            mailboxGroup.rotation.x = mouseY * 0.08;
        }

        // Laser scanner pulsation
        if (laserBeam) {
            laserBeam.position.y = 0.65 + Math.sin(t * 3.5) * 0.04;
            (laserBeam.material as THREE.MeshBasicMaterial).opacity = 0.6 + Math.sin(t * 6) * 0.35;
        }

        // Floating objects orbit
        floaters.forEach((f) => {
            f.mesh.position.y = f.baseY + Math.sin(t * f.speed + f.offset) * 0.16;
            f.mesh.rotation.y += f.rotSpeed;
            f.mesh.rotation.x = Math.sin(t * 0.5 + f.offset) * 0.1;
        });

        // Slow particle drift
        if (particles) {
            particles.rotation.y = t * 0.02;
        }

        renderer!.render(scene!, camera!);
    };
    animate();

    intersectionObserver = new IntersectionObserver((entries) => {
        if (entries[0]) {
            isVisible = entries[0].isIntersecting && !document.hidden;
        }
    }, { threshold: 0.05 });
    intersectionObserver.observe(el);

    resizeObserver = new ResizeObserver(() => {
        if (!renderer || !camera || !el) return;
        const w = el.clientWidth || 1;
        const h = el.clientHeight || 1;
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        renderer.setSize(w, h);
    });
    resizeObserver.observe(el);
}

function disposeThree() {
    window.removeEventListener('mousemove', onMouseMove);
    document.removeEventListener('visibilitychange', onVisibilityChange);
    if (animationId !== null) cancelAnimationFrame(animationId);
    intersectionObserver?.disconnect();
    resizeObserver?.disconnect();
    if (scene) {
        scene.traverse((obj) => {
            const mesh = obj as THREE.Mesh;
            mesh.geometry?.dispose?.();
            const mat = mesh.material as THREE.Material | THREE.Material[] | undefined;
            if (Array.isArray(mat)) mat.forEach((m) => m.dispose());
            else mat?.dispose?.();
        });
    }
    renderer?.dispose();
    if (renderer && threeContainer.value?.contains(renderer.domElement)) {
        threeContainer.value.removeChild(renderer.domElement);
    }
    renderer = null;
    scene = null;
    camera = null;
    mailboxGroup = null;
    laserBeam = null;
    particles = null;
    floaters.length = 0;
}

onMounted(initThree);
onUnmounted(disposeThree);
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

        <!-- Mesh gradient background -->
        <div class="hero-mesh">
            <div class="mesh-blob mesh-blob-1"></div>
            <div class="mesh-blob mesh-blob-2"></div>
            <div class="mesh-blob mesh-blob-3"></div>
            <div class="mesh-blob mesh-blob-4"></div>
            <div class="mesh-sheen"></div>
            <div class="mesh-grid-overlay"></div>
        </div>

        <!-- Layer 3D ringan: amplop melayang -->
        <div ref="threeContainer" class="hero-three-layer" aria-hidden="true"></div>

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
                <div class="hero-badge">
                    <span class="hero-badge-dot"></span>
                    <span>Terverifikasi &bull; Realtime &bull; Log Transparan</span>
                </div>

                <h1 class="hero-heading">
                    <span class="heading-kicker">SiTrack &mdash; Portal Resmi</span>
                    <span class="heading-main">Lacak Naskah</span>
                    <span class="heading-sub">Dinas Anda</span>
                </h1>

                <p class="hero-desc">
                    Pantau posisi surat Anda secara realtime dengan memasukkan Nomor Resi
                </p>

                <div class="search-wrapper">
                    <form @submit.prevent="handleSubmit" class="search-glass-box">
                        <input v-model="model" type="text" placeholder="Contoh: ND-20260906-001" required />
                        <button type="submit" class="btn-track">
                            <span class="d-none d-sm-inline">Lacak Sekarang</span>
                            <i class="bi bi-search d-sm-none"></i>
                        </button>
                    </form>
                    <small class="d-flex align-items-center gap-1 mt-2" style="color: rgba(255,255,255,0.55);">
                        <i class="bi bi-info-circle"></i>
                        Format: <span class="fw-semibold">[KODE-JENIS]-YYYYMMDD-XXX</span>
                    </small>
                </div>

                <div class="hero-cta-row">
                    <Link href="/ajukan-surat" class="gooey-cta-wrapper" style="filter: url(#gooey-filter);">
                        <span class="gooey-arrow">
                            <i class="bi bi-arrow-up-right"></i>
                        </span>
                        <span class="gooey-label">
                            <i class="bi bi-pen-fill me-2"></i>Ajukan Permohonan Paraf
                        </span>
                    </Link>
                </div>
            </div>
        </div>

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
/* Palet lokal "Dark Ocean + Ice + Orange" — sengaja TIDAK pakai var(--st-*) global,
   supaya halaman publik ini punya identitas sendiri, lepas dari tema hijau dashboard admin. */
.hero-section {
    --ocean-primary: #2743AF;
    --ocean-primary-dark: #182b78;
    --ocean-primary-light: #4A9CF0;
    --ocean-accent: #3DA5F9;
}

.sr-only-defs {
    position: absolute;
    width: 0;
    height: 0;
}

.hero-section {
    position: relative;
    min-height: calc(100vh - 90px);
    width: 100%;
    display: flex;
    align-items: center;
    overflow: hidden;
    background: var(--ocean-primary-dark);
    margin-top: -88px;
    padding-top: 88px;
}

.hero-mesh {
    position: absolute;
    inset: 0;
    overflow: hidden;
}

.hero-three-layer {
    position: absolute;
    inset: 0;
    z-index: 2;
    pointer-events: none;
    opacity: 0.85;
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
    background: radial-gradient(circle, #4A9CF0 0%, transparent 70%);
    opacity: .7;
}

.mesh-blob-2 {
    width: 45vw;
    height: 45vw;
    max-width: 560px;
    max-height: 560px;
    top: 10%;
    right: -12%;
    background: radial-gradient(circle, #3DA5F9 0%, transparent 70%);
    opacity: .6;
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
    background: radial-gradient(circle, #2743AF 0%, transparent 70%);
    opacity: .7;
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
    background: radial-gradient(circle, #3DA5F9 0%, transparent 70%);
    opacity: .55;
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
    background: conic-gradient(from 0deg, transparent 0%, rgba(19, 78, 74, .06) 20%, transparent 35%, rgba(45, 212, 191, .07) 55%, transparent 75%);
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

    .hero-three-layer {
        display: none;
    }
}

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
    background: rgba(255, 255, 255, .1);
    border: 1px solid rgba(255, 255, 255, .22);
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
    background: var(--ocean-accent);
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
    background: linear-gradient(135deg, #ffffff 0%, #eaf8ff 40%, #5b96b8 70%, #ffffff 100%);
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
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    box-shadow: 0 8px 32px rgba(0, 0, 0, .35);
    padding: 10px;
    border-radius: 60px;
    display: flex;
    width: 100%;
    max-width: 560px;
    position: relative;
    z-index: 10;
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
    background: var(--ocean-accent);
    color: #fff;
    transform: translateY(-2px);
}

.hero-cta-row {
    display: flex;
    position: relative;
    z-index: 10;
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
    background: linear-gradient(135deg, var(--ocean-primary-light), var(--ocean-primary));
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
    background: conic-gradient(from 0deg, var(--ocean-primary-light), #003a63, var(--ocean-primary-dark), var(--ocean-accent), var(--ocean-primary-light));
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
    color: var(--ocean-accent);
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

    .hero-three-layer {
        opacity: 0.5;
    }
}
</style>