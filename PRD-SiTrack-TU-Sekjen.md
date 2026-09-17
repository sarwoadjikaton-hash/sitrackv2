# Product Requirement Document (PRD) & Technical Specification
## SiTrack — Sistem Informasi Tracking & Penatausahaan Naskah Dinas TU Sekjen Kemnaker

---

## 1. Ringkasan Eksekutif & Tujuan Proyek (Step 1 - PRD Lengkap)
**SiTrack** adalah aplikasi berbasis web modern yang dikembangkan untuk **Sub Bagian Tata Usaha Sekretariat Jenderal, Staf Ahli Menteri, dan Staf Khusus Menteri (TU SEKJEN)** Kementerian Ketenagakerjaan Republik Indonesia.

### Tujuan Utama:
1. **Pemisahan Alur Naskah Dinas 2 Lajur (Dual-Lane Architecture)**:
   - **Lajur Pertama (Tindak Lanjut / Penandatanganan)**: Registrasi surat masuk, proses paraf, verifikasi administrasi, penandatanganan pimpinan, hingga pengambilan dokumen.
   - **Lajur Kedua (Disposisi)**: Pengendalian surat masuk yang membutuhkan arahan berjenjang Sekretaris Jenderal kepada unit-unit kerja eselon I/II dengan penunjukan koordinator unit.
2. **Konektivitas Relasi Lintas Lajur (Cross-Lane Graph)**:
   - Menghubungkan naskah dinas antar lajur (mis. surat masuk disposisi menghasilkan tindak lanjut nota dinas keluar, balasan surat, dsb.).
3. **Manajemen Penomoran Otomatis (Format Baku 2026)**:
   - Otomasi formula penomoran 16 workbook naskah dinas (NODIN, UNDANGAN, KEPUTUSAN, SURAT TUGAS, dll.).
   - Pre-order nomor untuk unit kerja & reservasi nomor surat tertentu.
   - Sinkronisasi instan dua arah dengan **Google Spreadsheet**.
4. **Pelacakan Publik & Scan QR Cepat**:
   - Portal publik modern dengan animasi hero 3D untuk lacak resi (`ND-YYYYMMDD-XXX`).
   - Scan QR code via kamera smartphone untuk update status & posisi berkas instan tanpa ketik ulang.
5. **Cetak Standar Baku Kedinasan**:
   - Cetak Lembar Disposisi 1:1 format fisik Kementerian Ketenagakerjaan.
   - Cetak Lembar Pendamping Naskah Dinas dengan QR Verifikasi resmi TU Sekjen.

---

## 2. Desain Visual & Pengalaman Pengguna (Step 2 - UI & UX Brief)

### Palet Warna Resmi:
- **Primary Navy**: `#2743AF` (Elemen navigasi, header, tombol utama, brand emphasis)
- **Primary Accent / Sky Blue**: `#3DA5F9` (Highlight, active state, glow effect, badge)
- **Mid Azure Blue**: `#4A9CF0` (Gradien, hover state, secondary CTA)
- **Surface / Background**: `#F8FAFC` & `#FFFFFF` dengan subtle border `#E2E8F0`

### Prinsip Antarmuka (Aesthetics):
- **Glassmorphism**: Backdrop blur pada search box, hero navbar, dan modal header.
- **Micro-Interactions**: Hover elevation, smooth state transitions (`cubic-bezier(0.4, 0, 0.2, 1)`).
- **Prosedural 3D Mailbox**: Model hero 3D hemat daya menggunakan Three.js prosedural (< 800 vertex, 0% CPU saat idle atau out-of-viewport via `IntersectionObserver`).
- **Responsive Adaptive Filters**: Filter bar dinamis dengan `SearchableSelect` teleported dropdown.

---

## 3. Analisis & Standar Keamanan (Step 3 - Security Assessment)

| Kategori Keamanan | Implementasi di SiTrack | Status |
|---|---|---|
| **SQL Injection** | Menggunakan Eloquent ORM & Query Builder dengan parameterized PDO queries (`ILIKE ?`). | ✅ Sangat Aman |
| **Cross-Site Scripting (XSS)** | Vue 3 Reactive Binding meng-escape text secara default; sanitasi input pada semua controller. | ✅ Sangat Aman |
| **CSRF Protection** | Dilindungi middleware `VerifyCsrfToken` Laravel di seluruh endpoint `POST`/`PUT`/`DELETE`. | ✅ Sangat Aman |
| **File Upload Security** | Validasi tipe MIME (`pdf`, `docx`, `jpg`, `png`), batas ukuran 20MB, dan penyimpanan di `storage/app/public` terisolasi. | ✅ Sangat Aman |
| **Role-Based Access Control (RBAC)** | Menggunakan Spatie Laravel-Permission dengan 5 peran: `Super Admin`, `Admin TU`, `Staf Tata Usaha`, `Pimpinan`, `Operator Scanning`. | ✅ Sangat Aman |
| **Route Protection** | Semua rute staf berada di bawah `Route::middleware(['auth', 'permission:...'])`. | ✅ Sangat Aman |

---

## 4. Penanganan Error & Ketahanan Sistem (Step 4 - Error Handling)
- **Database Graceful Failures**: Menggunakan DB Transactions (`DB::transaction`) untuk mutasi multi-tabel (Letter, Logs, Relations).
- **Inertia Toast Feedback**: Notifikasi toast otomatis (`ToastNotification.vue`) menangani session flash `success`, `error`, dan `info`.
- **Validation Messages**: Validasi form real-time dengan pesan error berbahasa Indonesia yang informatif.

---

## 5. Pengujian & Verifikasi Kualitas (Step 5 - Testing & Quality Assurance)
- **Automated Verification**: Pengujian migrasi database, relasi model Eloquent, seeder, dan import spreadsheet.
- **Frontend Type Safety**: Menggunakan TypeScript (`vue-tsc --noEmit`) dan Vite bundle compiler.
- **Liveness & Health**: Endpoint publik `/tracking` dan API `/data-surat/slots` responsif dan terisolasi dari sesi autentikasi.

---

## 6. Pembersihan Kode & Refactoring (Step 6 - Dead Code Cleanup)
- **Deduplikasi Unit Master**: Pembersihan seluruh variasi duplikat unit kerja Kementerian Ketenagakerjaan dan normalisasi FK.
- **Pembersihan File Sampah**: Menghapus direktori `temp-build/` dan file dump sementara.
- **Optimasi Bundle**: Lazy-loading rute Vue dan modul Three.js hanya pada halaman hero.

---

## 7. Standarisasi Pesan Commit (Step 7 - Clean Git Commits)
Repositori menerapkan standar **Conventional Commits**:
- `feat:` Penambahan fitur baru (mis. filter, lajur, sync spreadsheet).
- `fix:` Perbaikan bug (mis. deduplikasi unit, border disposisi).
- `style:` Pembaruan styling UI/UX dan palet warna.
- `perf:` Optimasi performa dan rendering 3D.
- `docs:` Pembaruan dokumentasi teknis dan PRD.

---

## 8. Panduan Alur Kerja & Standardisasi (Step 8 - Workflow Standardization)
Alur pemrosesan dokumen di SiTrack distandarisasi ke dalam 2 lajur:
1. **Alur Lajur Pertama (Tindak Lanjut)**:
   `Registrasi Naskah` &rarr; `Pemeriksaan TU` &rarr; `Paraf Kasubag/Pimpinan` &rarr; `Tanda Tangan Pimpinan` &rarr; `Dokumen Selesai / Siap Diambil`.
2. **Alur Lajur Kedua (Disposisi)**:
   `Surat Masuk Diinput` &rarr; `Disposisi Sekjen Terbit` &rarr; `Unit Koordinator & Anggota Menerima Arahan` &rarr; `Tindak Lanjut & Lampiran Progres Diunggah` &rarr; `Disposisi Selesai`.

---

## 9. Optimasi Performa (Step 9 - Performance & Caching)
- **Database Indexing**: Indeks pada `tracking_code`, `agenda_number`, `(type_id, number_year, status)`, dan `(process_lane, status)`.
- **Eager Loading**: Mencegah N+1 query dengan `with(['category', 'recipientUnit', 'letterNumberType'])`.
- **Resource Cleanup**: Pause otomatis animasi 3D WebGL saat hero section tidak terlihat (`IntersectionObserver`).

---

## 10. Panduan Deployment & Produksi (Step 10 - Deployment Guide)

### Perintah Update di Server Produksi:
```bash
cd /opt/sitrack
git pull origin main
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

### Akses Server:
- **URL Produksi**: `http://192.168.223.103:8081`
- **Hak Cipta & Branding**: SiTrack &copy; TU SEKJEN Kementerian Ketenagakerjaan RI.
