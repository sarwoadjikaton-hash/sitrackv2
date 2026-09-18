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
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img
                            src="/images/kemnaker_logo.png"
                            alt="Logo Kemnaker"
                            width="48"
                            height="48"
                            class="kemnaker-logo-white flex-shrink-0"
                        />
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

            <!-- Bottom SiTrack Identity (Persis seperti Gambar 3) -->
            <div class="pt-3 mt-4 border-top border-white border-opacity-10 d-flex flex-wrap align-items-center justify-content-center gap-2 text-white-50 small">
                <img src="/images/sitrack_logo.svg" alt="SiTrack" width="16" height="16" />
                <span class="fw-semibold text-white">SiTrack</span>
                <span class="opacity-40">|</span>
                <img src="/images/kemnaker_logo.png" alt="Kemnaker" width="15" height="15" class="kemnaker-logo-white" />
                <span>Kementerian Ketenagakerjaan RI &bull; TU SEKJEN &copy; 2026</span>
            </div>
        </div>
    </footer>
</template>

<style scoped>
.kemnaker-footer {
    background: #193e6b;
    color: #ffffff;
    font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.kemnaker-logo-white {
    filter: brightness(0) invert(1);
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
}

.kemnaker-contact {
    font-size: 0.875rem;
    letter-spacing: 0.2px;
}
</style>
