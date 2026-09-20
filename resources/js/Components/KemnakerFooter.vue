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
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            <!-- Logo SiTrack -->
                            <img
                                src="/images/sitrack_logo.svg"
                                alt="Logo SiTrack"
                                width="44"
                                height="44"
                                class="rounded-3 shadow-sm"
                            />
                            <span class="text-white-50 opacity-40 fs-4 fw-light">|</span>
                            <!-- Logo Kemnaker -->
                            <img
                                src="/images/kemnaker_logo.png"
                                alt="Logo Kemnaker"
                                width="40"
                                height="40"
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
                    <p class="text-white-50 mb-2 kemnaker-address ms-md-auto">
                        Jl. Jendral Gatot Subroto Kav. 51, Daerah Khusus Ibukota Jakarta 12950
                    </p>

                    <div class="d-flex flex-wrap align-items-center justify-content-md-end gap-3 text-white-50 kemnaker-contact small">
                        <span>Telp: <strong class="text-white">021-5255733</strong></span>
                        <span class="opacity-40">&bull;</span>
                        <span>Call Center: <strong class="text-white">1500630</strong></span>
                    </div>
                </div>
            </div>

            <!-- Bottom SiTrack Identity -->
            <div class="pt-3 mt-4 border-top border-white border-opacity-10 d-flex flex-wrap align-items-center justify-content-center gap-2 text-white-50 small">
                <div class="d-inline-flex align-items-center gap-2">
                    <img src="/images/sitrack_logo.svg" alt="SiTrack" width="18" height="18" class="footer-app-logo" />
                    <span class="text-white-50 opacity-40 fw-light">|</span>
                    <img src="/images/kemnaker_logo.png" alt="Kemnaker" width="16" height="16" class="kemnaker-logo-white" />
                </div>
                <span>SiTrack &bull; Kementerian Ketenagakerjaan RI &bull; TU SEKJEN &copy; 2026</span>
            </div>
        </div>
    </footer>
</template>

<style scoped>
.kemnaker-footer {
    background: #03205A;
    color: #ffffff;
    font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    border-top: 1px solid rgba(181, 204, 227, 0.2);
}

.kemnaker-logo-white {
    filter: brightness(0) invert(1);
    display: inline-block;
    vertical-align: middle;
}

.footer-app-logo {
    display: inline-block;
    vertical-align: middle;
    border-radius: 4px;
}

.kemnaker-brand-text {
    font-size: 11.5px;
    letter-spacing: 0.6px;
    line-height: 1.25;
    color: #ffffff;
}

.kemnaker-unit-title {
    font-size: 1.05rem;
    line-height: 1.4;
    letter-spacing: -0.2px;
}

.kemnaker-address {
    font-size: 0.875rem;
    max-width: 460px;
    line-height: 1.5;
    color: #B5CCE3 !important;
}

.kemnaker-contact {
    font-size: 0.875rem;
    letter-spacing: 0.2px;
    color: #B5CCE3 !important;
}
</style>
