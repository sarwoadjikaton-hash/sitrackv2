<script setup lang="ts">
// Kemnaker Official Footer Component
</script>

<template>
    <footer class="kemnaker-footer text-white mt-auto no-print">
        <div class="container py-5">
            <!-- Row 1: Brand / Logo & Alamat + Social Media -->
            <div class="row align-items-start justify-content-between g-4 mb-4">
                <!-- Col Kiri: Logo & Info Instansi -->
                <div class="col-lg-7">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img
                            src="/images/kemnaker_logo.png"
                            alt="Logo Kemnaker"
                            width="48"
                            height="48"
                            class="kemnaker-logo-invert flex-shrink-0"
                        />
                        <div class="d-flex flex-column">
                            <span class="kemnaker-brand-title fw-bold text-uppercase">
                                Kementerian Ketenagakerjaan
                            </span>
                            <span class="kemnaker-brand-subtitle text-white-50">
                                Republik Indonesia
                            </span>
                        </div>
                    </div>
                    <div class="kemnaker-desc text-white-50">
                        <p class="fw-semibold text-white mb-1">
                            Kementerian Ketenagakerjaan Republik Indonesia
                        </p>
                        <p class="small mb-0" style="max-width: 460px; line-height: 1.5;">
                            Jl. Jendral Gatot Subroto Kav. 51, Daerah Khusus Ibukota Jakarta 12950
                        </p>
                    </div>
                </div>

                <!-- Col Kanan: Social Media Circles -->
                <div class="col-lg-5 text-lg-end">
                    <div class="d-flex gap-2 justify-content-lg-end align-items-center">
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

            <!-- Row 2: Kontak & Navigasi Link -->
            <div class="row align-items-center justify-content-between g-3 pt-4 border-top border-white border-opacity-15 small">
                <!-- Kontak -->
                <div class="col-lg-5 col-md-6">
                    <div class="d-flex flex-wrap gap-3 text-white-50">
                        <span><strong>Telp:</strong> 021-5255733</span>
                        <span class="opacity-40">&bull;</span>
                        <span><strong>Call Center:</strong> 1500630</span>
                    </div>
                </div>

                <!-- Link Menu -->
                <div class="col-lg-7 col-md-6">
                    <div class="d-flex flex-wrap gap-3 justify-content-md-end text-white-50">
                        <a href="https://kemnaker.go.id" target="_blank" rel="noopener" class="footer-nav-link">Tentang Kami</a>
                        <a href="https://kemnaker.go.id" target="_blank" rel="noopener" class="footer-nav-link">Hubungi Kami</a>
                        <a href="https://kemnaker.go.id" target="_blank" rel="noopener" class="footer-nav-link">Peta Situs</a>
                        <a href="https://elhkpn.kpk.go.id" target="_blank" rel="noopener" class="footer-nav-link">ELHKPN</a>
                        <a href="https://kemnaker.go.id" target="_blank" rel="noopener" class="footer-nav-link">Kebijakan Privasi</a>
                        <a href="https://kemnaker.go.id" target="_blank" rel="noopener" class="footer-nav-link">Syarat &amp; Ketentuan</a>
                    </div>
                </div>
            </div>

            <!-- Row 3: Copyright -->
            <div class="pt-3 mt-3 border-top border-white border-opacity-10 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 text-white-50 small">
                <div style="font-size: 11.5px;">
                    Kementerian Ketenagakerjaan Republik Indonesia &copy; 2018 - 2026 &bull; Hak Cipta Dilindungi Undang-Undang.
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
    background: #133863;
    color: #ffffff;
    font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.kemnaker-logo-invert {
    filter: brightness(0) invert(1);
}

.kemnaker-brand-title {
    font-size: 12.5px;
    letter-spacing: 0.6px;
    line-height: 1.2;
}

.kemnaker-brand-subtitle {
    font-size: 11px;
    letter-spacing: 0.5px;
}

.social-circle-btn {
    width: 38px;
    height: 38px;
    background: #ffffff;
    color: #133863;
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

.footer-nav-link {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 12px;
    transition: color 0.2s ease;
}

.footer-nav-link:hover {
    color: #ffffff;
    text-decoration: underline;
}
</style>
