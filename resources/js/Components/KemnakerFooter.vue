<script setup lang="ts">
// Kemnaker Official Footer Component
</script>

<template>
    <footer class="kemnaker-footer text-white mt-auto no-print">
        <div class="container py-4 py-md-5">
            <!-- Header Logo + Text Kemnaker -->
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

            <!-- Judul Unit Kerja -->
            <h5 class="fw-bold text-white mb-2 kemnaker-unit-title">
                Sekretariat Jenderal Kementerian Ketenagakerjaan Republik Indonesia
            </h5>

            <!-- Alamat Kantor -->
            <p class="text-white-50 mb-3 kemnaker-address">
                Jl. Jendral Gatot Subroto Kav. 51, Daerah Khusus Ibukota Jakarta 12950
            </p>

            <!-- Kontak Telp & Call Center -->
            <div class="d-flex flex-wrap align-items-center gap-4 text-white-50 kemnaker-contact small">
                <span>Telp: <strong class="text-white">021-5255733</strong></span>
                <span>Call Center: <strong class="text-white">1500630</strong></span>
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
    font-size: 0.9rem;
    max-width: 560px;
    line-height: 1.5;
}

.kemnaker-contact {
    font-size: 0.9rem;
    letter-spacing: 0.2px;
}
</style>
