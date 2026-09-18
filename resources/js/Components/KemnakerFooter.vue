<script setup lang="ts">
// Kemnaker Official Footer Component
</script>

<template>
    <footer class="kemnaker-footer text-white mt-auto no-print">
        <div class="container py-4 py-md-5">
            <div class="row g-4 align-items-start justify-content-between">
                <!-- Info Instansi & Alamat sesuai referensi gambar -->
                <div class="col-lg-8">
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
                    <div class="d-flex flex-wrap align-items-center gap-4 text-white-50 kemnaker-contact small pt-1">
                        <span>Telp: <strong class="text-white">021-5255733</strong></span>
                        <span>Call Center: <strong class="text-white">1500630</strong></span>
                    </div>
                </div>

                <!-- Kolom Kanan: Media Sosial Resmi -->
                <div class="col-lg-4 text-lg-end">
                    <div class="d-flex gap-2 justify-content-lg-end align-items-center mb-3">
                        <a
                            href="https://www.facebook.com/KemnakerRI"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-circle-btn"
                            title="Facebook Kemnaker"
                            aria-label="Facebook"
                        >
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a
                            href="https://twitter.com/KemnakerRI"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-circle-btn"
                            title="Twitter / X Kemnaker"
                            aria-label="Twitter"
                        >
                            <i class="bi bi-twitter-x"></i>
                        </a>
                        <a
                            href="https://www.instagram.com/kemnaker"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-circle-btn"
                            title="Instagram Kemnaker"
                            aria-label="Instagram"
                        >
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a
                            href="https://www.youtube.com/user/kemnaker"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="social-circle-btn"
                            title="YouTube Kemnaker"
                            aria-label="YouTube"
                        >
                            <i class="bi bi-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Bottom Copyright & SiTrack Credit -->
            <div class="pt-3 mt-4 border-top border-white border-opacity-10 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 text-white-50 small">
                <div style="font-size: 11.5px;">
                    &copy; 2026 Sekretariat Jenderal Kementerian Ketenagakerjaan Republik Indonesia &bull; Hak Cipta Dilindungi Undang-Undang.
                </div>
                <div class="d-flex align-items-center gap-2" style="font-size: 11.5px;">
                    <img src="/images/sitrack_logo.svg" alt="SiTrack" width="14" height="14" />
                    <span>SiTrack &bull; Sistem Tracking Persuratan TU SEKJEN</span>
                </div>
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
    max-width: 520px;
    line-height: 1.5;
}

.kemnaker-contact {
    font-size: 0.9rem;
    letter-spacing: 0.2px;
}

.social-circle-btn {
    width: 38px;
    height: 38px;
    background: #ffffff;
    color: #193e6b;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    text-decoration: none;
    transition: all 0.25s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.social-circle-btn:hover {
    background: #3DA5F9;
    color: #ffffff;
    transform: translateY(-3px);
    box-shadow: 0 6px 14px rgba(61, 165, 249, 0.35);
}
</style>
