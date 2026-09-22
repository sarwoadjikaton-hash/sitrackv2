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
        metallicMaterial(0x03205A, 0.3, 0.8)
    );
    baseRing.position.y = -1.45;
    group.add(baseRing);

    const baseGlowRing = new THREE.Mesh(
        new THREE.TorusGeometry(1.5, 0.05, 16, 40),
        glowingMaterial(0x167992, 1.2)
    );
    baseGlowRing.rotation.x = Math.PI / 2;
    baseGlowRing.position.y = -1.35;
    group.add(baseGlowRing);

    // 2. Main Body Chassis - Trust Blue with Glossy Chamfers
    const chassis = new THREE.Mesh(
        new RoundedBoxGeometry(2.3, 2.7, 2.1, 8, 0.4),
        metallicMaterial(0x1C386F, 0.2, 0.35)
    );
    chassis.position.y = 0.15;
    group.add(chassis);

    // 3. Front Faceplate - Frosted High-Tech Glass Panel
    const frontPanel = new THREE.Mesh(
        new RoundedBoxGeometry(1.9, 2.3, 0.12, 6, 0.2),
        glassMaterial(0xEEF7FC, 0.92)
    );
    frontPanel.position.set(0, 0.15, 1.05);
    group.add(frontPanel);

    // 4. Inset Mail Intake Slot (Metallic Frame + Dark Chasm)
    const slotFrame = new THREE.Mesh(
        new RoundedBoxGeometry(1.35, 0.32, 0.08, 4, 0.08),
        metallicMaterial(0x03205A, 0.2, 0.7)
    );
    slotFrame.position.set(0, 0.65, 1.12);
    group.add(slotFrame);

    const slotOpening = new THREE.Mesh(
        new RoundedBoxGeometry(1.18, 0.14, 0.15, 3, 0.04),
        new THREE.MeshStandardMaterial({ color: 0x03173d, roughness: 0.9 })
    );
    slotOpening.position.set(0, 0.65, 1.13);
    group.add(slotOpening);

    // 5. Laser Scanning Light Beam
    const beamGeo = new THREE.PlaneGeometry(1.15, 0.04);
    const beamMat = new THREE.MeshBasicMaterial({
        color: 0x167992,
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
        glowingMaterial(0x167992, 1.1)
    );
    activeLetterSeal.rotation.x = -0.35;
    activeLetterSeal.position.set(0, 0.78, 1.18);
    group.add(activeLetterSeal);

    // 7. Interactive Digital LED Status Display on Lower Panel
    const ledScreen = new THREE.Mesh(
        new RoundedBoxGeometry(1.4, 0.65, 0.04, 4, 0.06),
        new THREE.MeshStandardMaterial({
            color: 0x03205A,
            emissive: 0x03205A,
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
        glowingMaterial(0x167992, 1.2)
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
    const colors = [0x03205A, 0x1C386F, 0xEEF7FC];
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
        metallicMaterial(0x1C386F, 0.2, 0.7)
    );
    group.add(shield);

    const core = new THREE.Mesh(
        new THREE.SphereGeometry(0.16, 20, 20),
        glowingMaterial(0x167992, 1.2)
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
        color: 0x167992,
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

function updateLayoutForViewport(width: number) {
    if (!mailboxGroup || !camera) return;
    const isMobile = width < 768;
    if (isMobile) {
        mailboxGroup.position.set(0, 0.4, -2.5);
        mailboxGroup.scale.setScalar(0.7);
        camera.position.set(0, 0.8, 8.5);
    } else {
        mailboxGroup.position.set(2.8, -0.15, 0);
        mailboxGroup.scale.setScalar(1.0);
        camera.position.set(0, 1.1, 9.6);
    }
}

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
    camera.position.set(0, 1.1, 9.6);
    camera.lookAt(0, 0, 0);

    renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true, powerPreference: 'default' });
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.15;
    el.appendChild(renderer.domElement);

    // Studio Lighting Setup (Teal, Soft Blue, Deep Navy)
    const keyLight = new THREE.DirectionalLight(0xffffff, 2.2);
    keyLight.position.set(6, 7, 6);
    scene.add(keyLight);

    const cyanRimLight = new THREE.DirectionalLight(0x167992, 1.8);
    cyanRimLight.position.set(-4, 2, 4);
    scene.add(cyanRimLight);

    const azureFillLight = new THREE.DirectionalLight(0xB5CCE3, 1.2);
    azureFillLight.position.set(3, -4, 3);
    scene.add(azureFillLight);

    const ambientLight = new THREE.AmbientLight(0x03205A, 1.0);
    scene.add(ambientLight);

    // Hero Object: Mailbox Group
    mailboxGroup = createMailbox();
    scene.add(mailboxGroup);

    // Particle Cloud
    particles = createParticles();
    scene.add(particles);

    // Satellite Floating Objects in Orbit
    const smallObjects: { build: () => THREE.Group; pos: [number, number, number]; scale: number }[] = [
        { build: () => createEnvelope(0xEEF7FC, 0x167992), pos: [1.8, 2.1, 1.5], scale: 1.0 },
        { build: () => createEnvelope(0x1C386F, 0x167992), pos: [1.4, -1.2, 1.0], scale: 0.9 },
        { build: () => createDocumentStack(), pos: [7.0, 1.8, -0.2], scale: 1.0 },
        { build: () => createEnvelope(0xEEF7FC, 0x167992), pos: [7.4, -0.5, 0.7], scale: 0.85 },
        { build: () => createSecurityShield(), pos: [3.1, -1.6, 1.8], scale: 0.95 },
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

    updateLayoutForViewport(width);

    const startTime = performance.now();
    let lastFrameTime = 0;
    const frameInterval = 1000 / 60;

    const animate = (now = 0) => {
        animationId = requestAnimationFrame(animate);
        if (!isVisible) return;
        if (now - lastFrameTime < frameInterval) return;
        lastFrameTime = now;
        const t = (now - startTime) * 0.001;

        // Smooth mouse parallax lerp
        mouseX += (targetMouseX - mouseX) * 0.05;
        mouseY += (targetMouseY - mouseY) * 0.05;

        if (mailboxGroup) {
            const isMobile = (el.clientWidth || window.innerWidth) < 768;
            const baseY = isMobile ? 0.3 : -0.2;
            const baseRotY = isMobile ? -0.15 : -0.38;

            mailboxGroup.position.y = baseY + Math.sin(t * 0.8) * 0.08;
            mailboxGroup.rotation.y = baseRotY + Math.sin(t * 0.4) * 0.08 + mouseX * 0.15;
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
        updateLayoutForViewport(w);
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

        <!-- Mesh background -->
        <div class="hero-mesh">
            <div class="mesh-blob mesh-blob-1"></div>
            <div class="mesh-blob mesh-blob-2"></div>
            <div class="mesh-blob mesh-blob-3"></div>
            <div class="mesh-blob mesh-blob-4"></div>
            <div class="mesh-sheen"></div>
        </div>

        <!-- Layer 3D: Mailbox & floating documents -->
        <div ref="threeContainer" class="hero-three-layer" aria-hidden="true"></div>

        <div class="container hero-container">
            <div class="hero-text-block">
                <!-- Badge -->
                <div class="hero-badge">
                    <span class="hero-badge-dot"></span>
                    <span>Terverifikasi &bull; Realtime &bull; Log Transparan</span>
                </div>

                <!-- Main Heading -->
                <div class="hero-heading-box">
                    <div class="heading-kicker">
                        SiTrack
                    </div>
                    <h1 class="hero-heading">
                        <span class="heading-main">Lacak Naskah</span>
                        <span class="heading-sub sheen-animated">Dinas Anda</span>
                    </h1>
                </div>

                <!-- Description -->
                <p class="hero-desc">
                    Pantau status perjalanan, verifikasi paraf pimpinan, dan posisi surat Anda secara realtime dan transparan.
                </p>

                <!-- Search Input Bar -->
                <div class="search-wrapper">
                    <form @submit.prevent="handleSubmit" class="search-glass-box">
                        <div class="search-icon-inside">
                            <i class="bi bi-search"></i>
                        </div>
                        <input
                            v-model="model"
                            type="text"
                            placeholder="Contoh: ND-20260906-001"
                            aria-label="Nomor Resi / Kode Tracking"
                            autocomplete="off"
                            required
                        />
                        <button type="submit" class="btn-track">
                            <span class="d-none d-sm-inline">Lacak Sekarang</span>
                            <span class="d-sm-none">Lacak</span>
                            <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </form>
                    <div class="search-hint d-flex align-items-center gap-1 mt-2">
                        <i class="bi bi-info-circle"></i>
                        <span>Format Resi: <strong>[KODE-JENIS]-YYYYMMDD-XXX</strong></span>
                    </div>
                </div>

                <!-- CTA Row dengan efek Gooey Animation -->
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

        <!-- Floating Spinning Orb Animation -->
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
.hero-section {
    --ocean-primary: #1C386F;
    --ocean-primary-dark: #03205A;
    --ocean-primary-light: #395ba0;
    --ocean-accent: #167992;
    --cyan-glow: #2dd4bf;

    position: relative;
    min-height: calc(100vh - 80px);
    width: 100%;
    display: flex;
    align-items: center;
    overflow: hidden;
    background: var(--ocean-primary-dark);
    margin-top: -72px;
    padding-top: 80px;
    padding-bottom: 2.5rem;
}

@media (min-width: 768px) {
    .hero-section {
        min-height: calc(100vh - 90px);
        margin-top: -88px;
        padding-top: 100px;
        padding-bottom: 4rem;
    }
}

.sr-only-defs {
    position: absolute;
    width: 0;
    height: 0;
}

.hero-mesh {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}

.hero-three-layer {
    position: absolute;
    inset: 0;
    z-index: 2;
    pointer-events: none;
    opacity: 0.85;
}

@media (max-width: 767px) {
    .hero-three-layer {
        opacity: 0.45;
    }
}

.mesh-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    mix-blend-mode: screen;
    will-change: transform;
    animation: meshDrift 22s ease-in-out infinite;
}

.mesh-blob-1 {
    width: 60vw;
    height: 60vw;
    max-width: 640px;
    max-height: 640px;
    top: -15%;
    left: -10%;
    background: radial-gradient(circle, #1C386F 0%, transparent 70%);
    opacity: .75;
}

.mesh-blob-2 {
    width: 50vw;
    height: 50vw;
    max-width: 560px;
    max-height: 560px;
    top: 10%;
    right: -12%;
    background: radial-gradient(circle, #167992 0%, transparent 70%);
    opacity: .65;
    animation-duration: 26s;
    animation-delay: -6s;
}

.mesh-blob-3 {
    width: 45vw;
    height: 45vw;
    max-width: 480px;
    max-height: 480px;
    bottom: -18%;
    left: 20%;
    background: radial-gradient(circle, #03205A 0%, transparent 70%);
    opacity: .75;
    animation-duration: 30s;
    animation-delay: -12s;
}

.mesh-blob-4 {
    width: 35vw;
    height: 35vw;
    max-width: 380px;
    max-height: 380px;
    bottom: -5%;
    right: 10%;
    background: radial-gradient(circle, #B5CCE3 0%, transparent 70%);
    opacity: .45;
    animation-duration: 19s;
    animation-delay: -3s;
}

@keyframes meshDrift {
    0%, 100% {
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
    background: conic-gradient(from 0deg, transparent 0%, rgba(22, 121, 146, .08) 20%, transparent 35%, rgba(45, 212, 191, .07) 55%, transparent 75%);
    animation: sheenSpin 40s linear infinite;
}

@keyframes sheenSpin {
    to {
        transform: rotate(360deg);
    }
}

.hero-container {
    position: relative;
    z-index: 10;
    width: 100%;
    padding-left: 1.25rem;
    padding-right: 1.25rem;
}

@media (min-width: 576px) {
    .hero-container {
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }
}

.hero-text-block {
    max-width: 600px;
}

/* Badge */
.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .38rem .9rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, .1);
    border: 1px solid rgba(255, 255, 255, .2);
    backdrop-filter: blur(10px);
    color: rgba(255, 255, 255, .9);
    font-size: .74rem;
    font-weight: 600;
    letter-spacing: .02em;
    margin-bottom: 1.1rem;
    animation: badgeFloat 4s ease-in-out infinite;
    max-width: 100%;
}

@media (min-width: 576px) {
    .hero-badge {
        font-size: .8rem;
        padding: .45rem 1.1rem;
        gap: .55rem;
        margin-bottom: 1.4rem;
    }
}

@keyframes badgeFloat {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-3px);
    }
}

.hero-badge-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #2dd4bf;
    box-shadow: 0 0 8px 2px rgba(45, 212, 191, .7);
    flex-shrink: 0;
}

/* Headings */
.hero-heading-box {
    margin-bottom: 1rem;
}

.heading-kicker {
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: .12em;
    color: #B5CCE3;
    margin-bottom: .4rem;
    text-transform: uppercase;
}

@media (min-width: 576px) {
    .heading-kicker {
        font-size: 0.85rem;
        letter-spacing: .15em;
    }
}

.hero-heading {
    display: flex;
    flex-direction: column;
    margin-bottom: 0;
    line-height: 1.12;
}

.heading-main {
    font-size: 2.1rem;
    font-weight: 900;
    color: #ffffff;
    letter-spacing: -0.02em;
}

@media (min-width: 576px) {
    .heading-main {
        font-size: 2.85rem;
    }
}

@media (min-width: 992px) {
    .heading-main {
        font-size: 3.6rem;
    }
}

.heading-sub {
    font-size: 1.8rem;
    font-weight: 400;
    font-style: italic;
    letter-spacing: -0.01em;
}

@media (min-width: 576px) {
    .heading-sub {
        font-size: 2.4rem;
    }
}

@media (min-width: 992px) {
    .heading-sub {
        font-size: 3rem;
    }
}

/* Shimmer animated text */
.sheen-animated {
    background: linear-gradient(135deg, #ffffff 0%, #eaf8ff 35%, #5b96b8 70%, #ffffff 100%);
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
    font-size: 0.92rem;
    font-weight: 400;
    color: rgba(255, 255, 255, .8);
    max-width: 520px;
    line-height: 1.55;
    margin-bottom: 1.5rem;
}

@media (min-width: 576px) {
    .hero-desc {
        font-size: 1.05rem;
        line-height: 1.6;
        margin-bottom: 1.75rem;
    }
}

/* Search Glass Box */
.search-wrapper {
    margin-bottom: 1.5rem;
    max-width: 560px;
}

.search-glass-box {
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.28);
    box-shadow: 0 10px 32px rgba(0, 0, 0, .4);
    padding: 6px 6px 6px 16px;
    border-radius: 60px;
    display: flex;
    align-items: center;
    width: 100%;
    position: relative;
    z-index: 10;
    transition: all 0.25s ease;
}

.search-glass-box:focus-within {
    border-color: #2dd4bf;
    box-shadow: 0 12px 36px rgba(45, 212, 191, 0.25), 0 0 0 2px rgba(45, 212, 191, 0.35);
    background: rgba(255, 255, 255, 0.16);
}

.search-icon-inside {
    color: rgba(255, 255, 255, 0.6);
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    flex-shrink: 0;
}

.search-glass-box input {
    background: transparent;
    border: none;
    padding: 0 10px 0 12px;
    color: #fff;
    flex: 1;
    outline: none;
    font-size: 0.95rem;
    min-width: 0;
    font-family: inherit;
}

@media (min-width: 576px) {
    .search-glass-box input {
        font-size: 1.05rem;
        padding: 0 16px;
    }
}

.search-glass-box input::placeholder {
    color: rgba(255, 255, 255, 0.45);
    font-size: 0.9rem;
}

.btn-track {
    background: linear-gradient(135deg, #ffffff 0%, #EEF7FC 100%);
    color: #03205A;
    border: none;
    padding: 10px 18px;
    border-radius: 50px;
    font-weight: 800;
    font-size: 0.88rem;
    flex-shrink: 0;
    transition: all 0.25s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

@media (min-width: 576px) {
    .btn-track {
        padding: 12px 24px;
        font-size: 0.95rem;
    }
}

.btn-track:hover {
    background: #167992;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(22, 121, 146, 0.5);
}

.btn-track:active {
    transform: translateY(0);
}

.search-hint {
    font-size: 0.78rem;
    color: rgba(255, 255, 255, 0.65);
    padding-left: 8px;
}

.search-hint strong {
    color: #B5CCE3;
}

/* Gooey CTA Button */
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
    background: var(--ocean-accent);
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

/* Floating Orb Animation */
.hero-orb {
    position: absolute;
    z-index: 10;
    bottom: 6%;
    right: 6%;
    width: 92px;
    height: 92px;
    display: none;
}

@media (min-width: 992px) {
    .hero-orb {
        display: block;
    }
}

.orb-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 2px solid var(--ocean-accent);
    background: transparent;
    animation: orbSpin 6s linear infinite;
    filter: blur(1px);
}

.orb-ring::after {
    content: '';
    position: absolute;
    inset: 5px;
    border-radius: 50%;
    background: #03205A;
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
    0%, 100% {
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

.text-cyan-accent {
    color: #2dd4bf;
}
</style>