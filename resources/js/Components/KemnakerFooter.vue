<script setup lang="ts">
// Kemnaker Official Footer Component
</script>

<template>
    <footer class="kemnaker-footer text-white mt-auto no-print">
        <div class="container py-4 py-md-5">
            <!-- Row Atas: Informasi Instansi & Kontak yang Seimbang -->
            <div class="row align-items-center justify-content-between g-4">
                <!-- Kolom Kiri: Logo Kemnaker & Nama Unit Kerja -->
                <div class="col-lg-7 col-md-6">
                    <!-- Logo SiTrack | Logo Kemnaker + Text Kemnaker -->
                    <div class="d-flex align-items-center gap-2 gap-sm-3 mb-2 mb-sm-3">
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            <!-- Logo SiTrack -->
                            <img
                                src="/images/sitrack_logo.svg"
                                alt="Logo SiTrack"
                                width="36"
                                height="36"
                                class="rounded-3 shadow-sm"
                            />
                            <span class="text-white-50 opacity-40 fs-5 fw-light">|</span>
                            <!-- Logo Kemnaker -->
                            <img
                                src="/images/kemnaker_logo.png"
                                alt="Logo Kemnaker"
                                width="32"
                                height="32"
                                class="kemnaker-logo-white"
                            />
                        </div>
                        <div class="kemnaker-brand-text text-uppercase fw-bold">
                            <div class="lh-sm">KEMENTERIAN</div>
                            <div class="lh-sm">KETENAGAKERJAAN</div>
                            <div class="lh-sm">REPUBLIK INDONESIA</div>
                        </div>
                    </div>

                    <h5 class="fw-bold text-white mb-0 kemnaker-unit-title">
                        Sekretariat Jenderal Kementerian Ketenagakerjaan Republik Indonesia
                    </h5>
                </div>

                <!-- Kolom Kanan: Alamat & Kontak Kantor -->
                <div class="col-lg-5 col-md-6 text-md-end">
                    <p class="text-white-50 mb-3 kemnaker-address ms-md-auto d-flex align-items-start align-items-md-center justify-content-md-end gap-1">
                        <i class="bi bi-geo-alt-fill text-teal-accent flex-shrink-0 mt-1 mt-md-0"></i>
                        <span>Jl. Jendral Gatot Subroto Kav. 51, Jakarta Selatan 12950</span>
                    </p>

                    <div class="d-flex flex-wrap align-items-center justify-content-start justify-content-md-end gap-2 text-white-50 kemnaker-contact small">
                        <a href="tel:0215255733" class="contact-chip">
                            <i class="bi bi-telephone-fill"></i>
                            <span>Telp: <strong>021-5255733</strong></span>
                        </a>
                        <a href="tel:1500630" class="contact-chip">
                            <i class="bi bi-headset"></i>
                            <span>Call Center: <strong>1500630</strong></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</template>

<style scoped>
.kemnaker-footer {
    background: #03205A;
    color: #ffffff;
    font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    border-top: 1px solid rgba(181, 204, 227, 0.15);
}

.kemnaker-logo-white {
    filter: brightness(0) invert(1);
    display: inline-block;
    vertical-align: middle;
}

.kemnaker-brand-text {
    font-size: 10px;
    letter-spacing: 0.5px;
    line-height: 1.25;
    color: #ffffff;
}

@media (min-width: 576px) {
    .kemnaker-brand-text {
        font-size: 11px;
    }
}

.kemnaker-unit-title {
    font-size: 0.95rem;
    line-height: 1.35;
    letter-spacing: -0.2px;
    color: #EEF7FC;
}

@media (min-width: 576px) {
    .kemnaker-unit-title {
        font-size: 1.05rem;
        line-height: 1.4;
    }
}

.kemnaker-address {
    font-size: 0.82rem;
    max-width: 460px;
    line-height: 1.45;
    color: #B5CCE3 !important;
}

@media (min-width: 576px) {
    .kemnaker-address {
        font-size: 0.875rem;
    }
}

.text-teal-accent {
    color: #2dd4bf;
}

.contact-chip {
    background: rgba(255, 255, 255, 0.07);
    border: 1px solid rgba(255, 255, 255, 0.12);
    padding: 4px 10px;
    border-radius: 50px;
    color: #B5CCE3;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    transition: all 0.2s ease;
}

.contact-chip strong {
    color: #ffffff;
}

.contact-chip:hover {
    background: rgba(255, 255, 255, 0.14);
    color: #ffffff;
    border-color: rgba(45, 212, 191, 0.4);
}
</style>
