# SiTrack — Sistem Elektronik Administrasi Persuratan
## Project Requirements Document (PRD) v14

**Instansi:** Tata Usaha Sekretariat Jenderal, Kementerian Ketenagakerjaan RI  
**Stack:** Laravel 11 · Vue 3 (Inertia.js) · PostgreSQL · Vite  
**Terakhir diperbarui:** September 2026

---

## Konsep Utama

SiTrack adalah sistem tracking dan pengelolaan persuratan digital yang dibangun untuk unit Tata Usaha Sekretariat Jenderal Kementerian Ketenagakerjaan RI. Aplikasi ini memiliki **dua workflow persuratan independen**:

1. **Lembar Tindak Lanjut / Penandatanganan** — untuk surat yang memerlukan paraf dan tanda tangan pimpinan.
2. **Lajur Disposisi** — untuk surat masuk yang memerlukan arahan disposisi dari pimpinan.

Keduanya menggunakan tabel surat yang sama (`letters`) sebagai metadata utama. Relasi lintas workflow disimpan pada `letter_relations`.

Setiap surat yang diinput mendapat **kode tracking unik** yang dapat dilacak oleh publik melalui halaman tracking tanpa perlu login.

---

## Fitur Publik (Tanpa Login)

### Halaman Tracking Surat
- Masyarakat atau pemohon dapat melacak status surat melalui halaman `/tracking`.
- Input kode tracking → tampil informasi lengkap: perihal, posisi berkas saat ini, progres penanganan (%), riwayat perjalanan dokumen (timeline), nomor surat, unit pengirim, unit tujuan, dan sifat naskah.
- Jika surat sudah selesai dan siap diambil, muncul tombol untuk mencetak **Lembar Pendamping** (kontrol fisik persuratan dengan barcode).
- Lampiran berkas naskah dapat dilihat/diunduh langsung dari halaman tracking.

### Halaman Ajukan Surat
- Form pengajuan surat baru dari publik melalui `/ajukan-surat`.
- Setelah berhasil, pemohon mendapat kode tracking di halaman `/surat-berhasil/{code}`.

---

## Sistem Login & Peran Pengguna

Login melalui halaman `/login` dengan username dan password. Terdapat 4 peran:

| Peran | Akses |
|-------|-------|
| **Super Admin** | Akses penuh ke semua fitur termasuk Master Data |
| **Admin Operator** | Penomoran, Tindak Lanjut, Disposisi, Data Surat |
| **Kasubbag TU** | Penomoran, Tindak Lanjut, Disposisi, Data Surat |
| **Sekretaris Jenderal** | Lajur Disposisi saja (read-only) |

Pengguna dapat mengubah password melalui modal di sidebar (klik profil pengguna).

---

## Dashboard

Halaman dashboard (`/dashboard`) menampilkan:
- **4 kartu statistik**: Tindak Lanjut/TTD (total, dalam proses, selesai), Lajur Disposisi (total, dalam proses, tuntas), Nomor Tersedia (stok tahun berjalan), dan Nomor Terpakai.
- **Ringkasan jenis naskah** per workbook.
- **Daftar surat terbaru** (Tindak Lanjut dan Disposisi).
- Shortcut button: *Surat Baru* dan *Disposisi*.

---

## Penomoran Surat

### Ketersediaan Nomor Surat (`/ketersediaan-nomor`)

Halaman manajemen stok nomor surat per jenis naskah (workbook). Fitur:

- **Dropdown navigasi workbook** untuk berpindah antar jenis naskah.
- Tiga jenis keperluan penomoran:
  - **Tersedia** — membuat slot nomor baru yang siap pakai.
  - **Pre-Order** — mengambil rentang kontinu dari stok yang masih Tersedia.
  - **Reservasi** — mengambil satu nomor spesifik dari stok Tersedia.
- Pre-Order dan Reservasi mengubah slot menjadi `reserved` secara transaksional.
- Alokasi yang belum digunakan dapat dibatalkan → nomor kembali menjadi `available`.
- Tombol **Tambah Jenis Naskah** tersedia di halaman ini untuk menambah workbook baru tanpa perlu ke Master Data.

### Data Surat / Laporan Data Surat (`/data-surat`)

Form Data Surat mengikuti struktur workbook **REKAP NOMOR 2026**:
- Tanggal Masuk, Unit Pengolah Arsip, Penandatangan Surat, Permohonan, Tujuan Surat, Tanggal Surat, Keamanan Akses (jika berlaku), Nomor Urut, Kode Klasifikasi Arsip, Bulan, Nomor Surat, Perihal Surat, Petugas Unit Teknis, serta ND Pengantar atau Hasil Pindai (sesuai profil workbook).

Aturan penting:
- Nomor urut **tidak dapat diketik bebas**. Pilihan berasal dari `letter_numbers` yang masih `available` atau `reserved`.
- Saat disimpan, sistem menggunakan **transaksi dan row locking** untuk mencegah double-use nomor.
- Data Surat mengubah slot `available` atau `reserved` menjadi `used` dan membentuk nomor final.
- Kombinasi `type_id + number_year + sequence_number` bersifat **unik**.

**Filter Workbook**: Dropdown di atas card statistik menyaring secara server-side: statistik Data Surat, Nomor Tersedia, Reservasi, dan tabel Data Surat.

**Fitur tambahan**: Import data via Excel template dan Export ke spreadsheet.

---

## Tindak Lanjut / Penandatanganan (`/tindak-lanjut`)

Workflow untuk surat yang memerlukan paraf dan tanda tangan pimpinan:

- **Data Tindak Lanjut** (`/tindak-lanjut`) — daftar semua surat dalam workflow TTD.
- **Form Input** (`/tindak-lanjut/create`) — input surat baru ke alur tindak lanjut.
- Setiap surat mendapat kode tracking unik.
- Status surat mengikuti alur yang diatur di **Alur Status**.
- Admin dapat meng-update status via halaman **Scan QR / Update Status**.

---

## Lajur Disposisi (`/disposisi`)

Workflow untuk surat masuk yang memerlukan arahan disposisi pimpinan:

- **Daftar Disposisi** (`/disposisi`) — semua surat disposisi beserta statusnya.
- **Input Disposisi** (`/disposisi/create`) — input surat masuk baru untuk disposisi.
- **Detail Disposisi** (`/disposisi/{id}`) — detail lengkap termasuk instruksi disposisi bertingkat.
- Disposisi mendukung **instruksi bertingkat** (multi-level disposition).
- **Cetak Disposisi** (`/cetak/disposisi/{id}`) — lembar disposisi untuk cetak fisik.

---

## Update Status Surat / Scan QR (`/scan-status`)

Fitur update status surat untuk Admin/Operator:
- Input kode tracking secara manual atau **scan barcode/QR** menggunakan kamera perangkat.
- Scan juga mendukung upload foto QR dari galeri.
- Setelah surat ditemukan, Admin dapat mengubah status sesuai alur yang berlaku.
- Setiap perubahan status dicatat dalam log riwayat perjalanan dokumen.

---

## Cetak

- **Cetak Lembar Disposisi** (`/cetak/disposisi/{id}`) — format cetak dengan kop Kemnaker.
- **Cetak Lembar Pendamping** (`/cetak/pendamping/{id}`) — lembar kontrol fisik dengan barcode tracking.

---

## Master Data (Super Admin Only)

| Menu | Keterangan |
|------|-----------|
| **Unit Kerja** (`/master/units`) | Kelola daftar unit kerja/organisasi |
| **Jenis Naskah** (`/master/number-types`) | Kelola jenis naskah penomoran / workbook |
| **Kategori Surat** (`/master/categories`) | Kelola kategori surat |
| **User & Akses** (`/master/users`) | Kelola akun pengguna dan peran akses |
| **Alur Status** (`/alur-status`) | Konfigurasi alur status tracking surat |
| **Rekap Master** (`/master/rekap`) | Rekap dan export data master, dengan fitur export ke Excel |

---

## Profil Workbook (Jenis Naskah)

16 workbook utama:

| No | Workbook | Pola Nomor |
|----|----------|-----------|
| 1 | NODIN / Memorandum | Prefiks keamanan, misal `B-1/0758/KS.06/VIII/2026` |
| 2 | Biasa / Undangan | Prefiks keamanan |
| 3 | Keputusan | Prefiks keamanan |
| 4 | Surat Tugas / Surat Perintah | Prefiks keamanan |
| 5 | Keterangan / Pernyataan / Kuasa | Prefiks keamanan |
| 6 | Berita Faksimili | Tanpa prefiks, misal `1/0001/KP.09.03/II/2026` |
| 7 | Edaran | Tanpa prefiks |
| 8 | Berita Acara | Tanpa prefiks |
| 9 | Ijazah / Sertifikat / Piagam | Tanpa prefiks |
| 10 | Pengumuman | Tanpa prefiks |
| 11 | Pengantar | Tanpa prefiks |
| 12 | Perjanjian Kerja Sama | Tanpa prefiks |
| 13 | Siaran Pers | Tanpa prefiks |
| 14 | Notula | Tanpa prefiks |
| 15 | SOP | Tanpa prefiks |
| 16 | Izin | Tanpa prefiks |

- NODIN/Memorandum menggunakan field akhir **ND Pengantar**; workbook lain menggunakan **Hasil Pindai**.
- Jenis naskah baru dapat ditambahkan melalui tombol **Tambah Jenis Naskah** di halaman Ketersediaan Nomor Surat.

---

## Sidebar v14

Struktur sidebar navigasi:

```
Menu Utama
├── Dashboard

Penomoran Surat (tersembunyi untuk Sekjen)
├── Ketersediaan Nomor

Tindak Lanjut / TTD (tersembunyi untuk Sekjen)
├── Data Tindak Lanjut
├── Laporan Data Surat

Lajur Disposisi
├── Input Disposisi (tersembunyi untuk Sekjen)
├── Lajur Disposisi

Master Data (Super Admin only)
├── Unit Kerja
├── Jenis Naskah
├── Kategori Surat
├── User & Akses
├── Alur Status
├── Rekap Master
```

Menu yang **tidak ditampilkan** di sidebar:
- Register Semua Surat
- Jenis Surat
- Jenis & Format Nomor

---

## Database

- `database.sql` adalah satu-satunya file instalasi database.
- Data operasional sengaja kosong (fresh install).
- Seed hanya mencakup: akun awal, master unit, kategori surat, dan 16 jenis naskah penomoran.
- Komponen legacy (`letter_number_requests`) tidak digunakan lagi.

---

## Desain & Branding

- **Warna utama**: Deep Navy `#03205A`, Trust Blue `#1C386F`, Teal Accent `#167992`
- **Logo**: Envelope + Location Pin + Checkmark Badge (SVG, tanpa gradasi)
- **UI**: Clean, solid colors, tanpa gradient. Navbar transparan saat di atas, solid navy saat scroll.
- **3D Scene**: Halaman tracking menggunakan Three.js untuk elemen 3D dekoratif (mailbox, floating documents).
- **Responsive**: Mendukung desktop dan mobile.

---

## Tech Stack Detail

| Layer | Teknologi |
|-------|----------|
| Backend | Laravel 11 (PHP 8.x) |
| Frontend | Vue 3 + TypeScript |
| Bridge | Inertia.js |
| Database | PostgreSQL |
| Bundler | Vite 5 |
| CSS | Bootstrap 5 + SCSS + Custom CSS |
| Icons | Bootstrap Icons |
| 3D | Three.js (halaman Tracking) |
| QR/Barcode | html5-qrcode |
| Container | Docker Compose |
