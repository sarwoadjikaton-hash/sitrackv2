# 📘 Buku Panduan Pengguna SiTrack
### Sistem Elektronik Administrasi Persuratan — TU Sekretariat Jenderal Kemnaker RI

---

> **Selamat datang di SiTrack!** 👋
>
> Panduan ini ditulis untuk membantu kamu memahami dan menggunakan SiTrack dengan mudah. Tidak perlu bingung — kami jelaskan langkah demi langkah, dengan bahasa yang sederhana dan to the point.

---

## Daftar Isi

1. [Apa itu SiTrack?](#1-apa-itu-sitrack)
2. [Memulai: Login ke Sistem](#2-memulai-login-ke-sistem)
3. [Mengenal Dashboard](#3-mengenal-dashboard)
4. [Tracking Surat (Untuk Publik)](#4-tracking-surat-untuk-publik)
5. [Penomoran Surat](#5-penomoran-surat)
   - [Ketersediaan Nomor Surat](#51-ketersediaan-nomor-surat)
   - [Laporan Data Surat](#52-laporan-data-surat)
6. [Tindak Lanjut / Penandatanganan](#6-tindak-lanjut--penandatanganan)
7. [Lajur Disposisi](#7-lajur-disposisi)
8. [Scan QR & Update Status](#8-scan-qr--update-status)
9. [Cetak Dokumen](#9-cetak-dokumen)
10. [Master Data (Super Admin)](#10-master-data-super-admin)
11. [Tips & Trik](#11-tips--trik)
12. [FAQ — Pertanyaan yang Sering Ditanyakan](#12-faq--pertanyaan-yang-sering-ditanyakan)

---

## 1. Apa itu SiTrack?

SiTrack adalah **sistem tracking persuratan digital** yang dibuat khusus untuk unit Tata Usaha Sekretariat Jenderal Kementerian Ketenagakerjaan RI.

Dengan SiTrack, kamu bisa:
- 📬 **Melacak surat** — cukup masukkan kode tracking, langsung tahu posisi surat ada di mana.
- 🔢 **Mengelola penomoran surat** — stok nomor surat tersedia, terpakai, atau sudah dipesan? Semua terlihat jelas.
- ✍️ **Mengelola tindak lanjut tanda tangan** — surat yang perlu paraf dan TTD pimpinan, alurnya tercatat rapi.
- 📋 **Mengatur disposisi** — arahan pimpinan untuk surat masuk, lengkap dan terstruktur.
- 📱 **Scan barcode** — update status surat cukup scan QR dari HP atau komputer.

**Sederhananya:** SiTrack memastikan tidak ada surat yang tercecer atau statusnya tidak jelas. Semuanya tercatat, bisa dilacak, dan transparan.

---

## 2. Memulai: Login ke Sistem

### Cara Login

1. Buka browser, akses alamat SiTrack yang diberikan oleh administrator.
2. Kamu akan melihat halaman login dengan logo SiTrack dan Kemnaker.
3. Masukkan **Username** dan **Password** yang sudah diberikan.
4. Klik tombol **Masuk**.

> **💡 Tips:** Kalau kamu lupa password, hubungi Super Admin untuk di-reset. Jangan coba-coba tebak ya, nanti terkunci!

### Peran Pengguna

Setiap akun punya peran (role) yang menentukan menu apa saja yang bisa diakses:

| Peran | Bisa Apa Saja? |
|-------|---------------|
| 🛡️ **Super Admin** | Segalanya — termasuk kelola user, unit kerja, dan konfigurasi sistem |
| 👨‍💼 **Admin Operator** | Penomoran surat, tindak lanjut, disposisi, data surat |
| 📝 **Kasubbag TU** | Sama seperti Admin Operator |
| 👔 **Sekretaris Jenderal** | Hanya melihat Lajur Disposisi |

### Mengubah Password

Ingin ganti password? Mudah:
1. Lihat bagian bawah sidebar (sebelah kiri layar).
2. Klik nama/profil kamu.
3. Muncul modal **Ubah Password** — isi password lama dan password baru.
4. Klik **Simpan**.

---

## 3. Mengenal Dashboard

Begitu login berhasil, kamu langsung masuk ke **Dashboard** — halaman utama yang menampilkan ringkasan semua aktivitas persuratan.

### Yang Terlihat di Dashboard:

| Kartu | Isinya |
|-------|--------|
| **Tindak Lanjut / TTD** | Berapa surat yang dalam proses paraf/TTD dan sudah selesai |
| **Lajur Disposisi** | Berapa surat disposisi yang sedang diproses dan tuntas |
| **Nomor Tersedia** | Stok nomor surat yang masih bisa dipakai tahun ini |
| **Nomor Terpakai** | Nomor yang sudah digunakan untuk surat final |

Di bawahnya ada:
- **Daftar surat terbaru** — surat-surat yang baru masuk atau di-update.
- **Tombol shortcut** — klik *Surat Baru* untuk langsung input surat baru ke Tindak Lanjut, atau *Disposisi* untuk input disposisi baru.

> **💡 Tips:** Dashboard ini ibarat "pusat komando" kamu. Biasakan cek dashboard setiap pagi untuk tahu apa yang perlu ditindaklanjuti hari ini.

---

## 4. Tracking Surat (Untuk Publik)

Halaman ini bisa diakses siapa saja, **tanpa perlu login**. Biasanya digunakan oleh pemohon surat yang ingin tahu sudah sampai mana prosesnya.

### Cara Melacak Surat:

1. Buka halaman tracking (biasanya halaman utama SiTrack).
2. Masukkan **kode tracking** di kolom pencarian. Contoh: `TRK-240901-001`
3. Klik **Lacak** atau tekan Enter.

### Informasi yang Ditampilkan:

Kalau kode tracking valid, kamu akan melihat:

- ✅ **Status surat** saat ini (misalnya "Dalam Proses Penandatanganan")
- 📍 **Posisi berkas sekarang** — surat sedang di meja siapa
- 📊 **Progres penanganan** — bar persentase, misalnya 60%
- 🔢 **Nomor surat** (jika sudah ada)
- 🏢 **Unit pengirim** dan **Unit tujuan**
- 📎 **Lampiran** — kalau ada dokumen terlampir, bisa langsung dilihat/diunduh
- 📜 **Timeline riwayat** — perjalanan lengkap surat dari awal sampai sekarang

### Lembar Pendamping

Kalau status surat sudah **"Selesai dan Siap Diambil"**, akan muncul tombol hijau untuk mencetak **Lembar Pendamping** — dokumen fisik yang berisi barcode tracking dan riwayat paraf pimpinan. Ini yang dibawa saat mengambil surat.

> **💡 Tips:** Simpan kode tracking yang diberikan saat mengajukan surat. Kode ini adalah "tiket" kamu untuk memantau progres.

---

## 5. Penomoran Surat

### 5.1 Ketersediaan Nomor Surat

**Lokasi:** Sidebar → Penomoran Surat → **Ketersediaan Nomor**

Ini adalah "gudang" nomor surat. Di sini kamu bisa melihat dan mengelola stok nomor untuk setiap jenis naskah (workbook).

#### Navigasi Workbook

Di bagian atas halaman ada **dropdown workbook** — klik untuk berpindah antar jenis naskah. Ada 16 jenis naskah bawaan (NODIN, Biasa/Undangan, Keputusan, dll.).

#### Tiga Jenis Keperluan

| Keperluan | Penjelasan |
|-----------|-----------|
| **Tersedia** | Membuat slot nomor baru yang siap digunakan siapa saja |
| **Pre-Order** | Memesan rentang nomor berurutan dari stok yang tersedia (misal nomor 10-15) |
| **Reservasi** | Memesan satu nomor spesifik dari stok tersedia untuk keperluan tertentu |

#### Cara Membuat Nomor Baru (Tersedia):

1. Pilih workbook/jenis naskah yang diinginkan.
2. Klik tombol **Tambah Nomor**.
3. Pilih keperluan **Tersedia**.
4. Isi jumlah nomor yang mau dibuat.
5. Klik **Simpan**.

Nomor-nomor baru akan langsung muncul di daftar dengan status **hijau (Available)**.

#### Cara Pre-Order:

1. Klik **Tambah Nomor** → pilih **Pre-Order**.
2. Tentukan rentang nomor yang mau dipesan (harus berurutan dan dari stok yang masih tersedia).
3. Isi informasi pemesanan.
4. Klik **Simpan** — nomor berubah status menjadi **Reserved** (kuning).

#### Cara Reservasi:

1. Klik **Tambah Nomor** → pilih **Reservasi**.
2. Pilih satu nomor dari daftar yang masih tersedia.
3. Isi keterangan reservasi.
4. Klik **Simpan** — nomor tersebut di-lock untuk reservasi.

> **⚠️ Penting:** Alokasi (Pre-Order/Reservasi) yang belum terpakai bisa dibatalkan — nomornya akan kembali jadi Available. Tapi kalau sudah jadi Data Surat, tidak bisa dikembalikan.

#### Menambah Jenis Naskah Baru

Perlu jenis naskah yang belum ada? Klik tombol **Tambah Jenis Naskah** di halaman ini — tidak perlu masuk ke Master Data.

---

### 5.2 Laporan Data Surat

**Lokasi:** Sidebar → Tindak Lanjut / TTD → **Laporan Data Surat**

Halaman ini untuk mencatat surat final yang sudah menggunakan nomor dari stok.

#### Cara Input Data Surat:

1. Klik tombol **Tambah Data Surat**.
2. Isi form sesuai struktur workbook REKAP NOMOR:
   - Tanggal Masuk
   - Unit Pengolah Arsip
   - Penandatangan Surat
   - Permohonan
   - Tujuan Surat
   - Tanggal Surat
   - Nomor Urut (pilih dari dropdown — bukan ketik bebas!)
   - Kode Klasifikasi Arsip
   - Perihal Surat
   - Petugas Unit Teknis
   - ND Pengantar atau Hasil Pindai (tergantung workbook)
3. Klik **Simpan**.

> **⚠️ Penting:** Nomor Urut harus dipilih dari daftar nomor yang Available atau Reserved. Sistem akan otomatis mengunci nomor saat kamu menyimpan, jadi tidak mungkin dua orang memakai nomor yang sama secara bersamaan.

#### Filter Workbook

Gunakan **dropdown Filter Workbook** di atas statistik untuk menyaring tampilan berdasarkan jenis naskah tertentu. Statistik dan tabel akan berubah sesuai filter.

#### Import & Export

- **Import:** Klik tombol Import untuk upload data dari template Excel.
- **Export:** Klik tombol Export untuk mengunduh data ke spreadsheet.

---

## 6. Tindak Lanjut / Penandatanganan

**Lokasi:** Sidebar → Tindak Lanjut / TTD → **Data Tindak Lanjut**

Ini untuk surat-surat yang membutuhkan proses **paraf dan tanda tangan pimpinan**.

### Alur Kerja:

```
Surat Masuk → Input ke Tindak Lanjut → Dapat Kode Tracking
→ Proses Paraf → Proses TTD → Selesai / Siap Diambil
```

### Cara Input Surat Baru:

1. Klik tombol **Tambah Surat** atau gunakan shortcut dari Dashboard.
2. Isi form: perihal, unit pengirim, unit tujuan, sifat naskah, lampiran, dll.
3. Klik **Simpan**.
4. Sistem otomatis memberikan **kode tracking unik** — catat atau berikan ke pemohon.

### Mengelola Surat:

- **Lihat daftar** — semua surat tindak lanjut tampil dalam tabel dengan status terkini.
- **Edit** — klik surat untuk mengubah informasi.
- **Update status** — bisa via halaman Scan QR atau langsung dari halaman tracking.

> **💡 Tips:** Setelah menginput surat baru, langsung berikan kode tracking ke pemohon. Mereka bisa memantau sendiri di halaman tracking publik.

---

## 7. Lajur Disposisi

**Lokasi:** Sidebar → Lajur Disposisi

Ini untuk surat masuk yang memerlukan **arahan/perintah dari pimpinan** (disposisi).

### Cara Input Disposisi Baru:

1. Klik **Input Disposisi** di sidebar atau dari Dashboard.
2. Isi form: asal surat, perihal, tanggal, nomor surat masuk, dll.
3. Klik **Simpan**.

### Halaman Detail Disposisi:

Klik surat di daftar disposisi untuk melihat detail lengkap:
- Informasi surat masuk
- **Instruksi disposisi** dari pimpinan — mendukung disposisi bertingkat
- Status tindak lanjut
- Tombol **Tambah Instruksi** untuk menambahkan arahan baru
- Update status per instruksi

### Cetak Disposisi:

Klik tombol **Cetak** untuk mencetak lembar disposisi format resmi Kemnaker.

> **💡 Tips:** Disposisi bertingkat artinya satu surat bisa punya beberapa instruksi dari pimpinan yang berbeda. Misalnya: Sekjen → Biro Umum → Sub Bagian TU. Semuanya tercatat rapi di SiTrack.

---

## 8. Scan QR & Update Status

**Lokasi:** Bisa diakses dari halaman tracking (tombol "Update Status (Admin)") atau URL `/scan-status`

Fitur ini untuk **update status surat secara cepat**, terutama saat surat berpindah posisi.

### Cara Menggunakan:

#### Opsi 1: Input Manual
1. Ketik kode tracking di kolom pencarian.
2. Klik **Cari**.

#### Opsi 2: Scan QR via Kamera
1. Klik tombol **Buka Kamera**.
2. Arahkan kamera ke barcode/QR yang tercetak di Lembar Pendamping.
3. Otomatis terbaca dan surat langsung muncul.

#### Opsi 3: Upload Foto QR
1. Klik ikon **Upload Foto**.
2. Pilih foto yang berisi QR code dari galeri.
3. Sistem membaca QR dari gambar.

### Setelah Surat Ditemukan:

1. Ubah **Status** ke status berikutnya sesuai alur.
2. Ubah **Posisi Berkas** (misal: "Meja Kasubbag TU" → "Meja Sekjen").
3. Tambahkan **Catatan** jika perlu.
4. Klik **Simpan** — perubahan langsung tercatat di timeline riwayat.

> **💡 Tips:** Fitur scan QR sangat berguna saat surat berpindah tangan. Cukup scan → pilih status baru → simpan. Dalam hitungan detik, status terbaru sudah bisa dilihat oleh pemohon di halaman tracking.

---

## 9. Cetak Dokumen

SiTrack menyediakan dua jenis dokumen cetak:

### Lembar Disposisi
- Akses: Klik **Cetak** dari halaman Detail Disposisi.
- Berisi: Kop Kemnaker, informasi surat masuk, instruksi disposisi, tanda tangan.
- Format: A4, siap cetak.

### Lembar Pendamping
- Akses: Klik **Cetak Lembar Pendamping** dari halaman Tracking (muncul saat surat sudah selesai).
- Berisi: Barcode tracking, informasi surat, riwayat paraf pimpinan.
- Fungsi: Kontrol fisik persuratan — dibawa saat mengambil surat.

> **💡 Tips:** Pastikan printer sudah terhubung dan kertas A4 tersedia sebelum mencetak. Kalau mau preview dulu, browser akan membuka halaman cetak di tab baru.

---

## 10. Master Data (Super Admin)

> **⚠️ Bagian ini hanya untuk Super Admin**

### Unit Kerja
Kelola daftar unit kerja/organisasi di lingkungan Kemnaker. Tambah, edit, atau hapus unit.

### Jenis Naskah
Kelola jenis naskah penomoran (workbook). 16 jenis naskah sudah ter-seed secara default.

### Kategori Surat
Kelola kategori untuk mengklasifikasikan surat.

### User & Akses
Kelola akun pengguna — tambah user baru, atur peran (role), atau nonaktifkan akun.

### Alur Status
Konfigurasi urutan status yang bisa dipilih saat update status surat. Misalnya:
```
Diterima → Proses Paraf → Proses TTD → Selesai → Siap Diambil → Sudah Diambil
```

### Rekap Master
Lihat ringkasan seluruh data master dan export ke Excel untuk pelaporan.

---

## 11. Tips & Trik

### 🔹 Sidebar Bisa Diciutkan
Klik tanda panah di tepi sidebar untuk menciutkannya. Layar kerja jadi lebih luas! Klik lagi untuk mengembalikan.

### 🔹 Keyboard Shortcut
Di halaman tracking, tekan **Enter** setelah mengetik kode tracking untuk langsung mencari.

### 🔹 Responsive / Mobile Friendly
SiTrack bisa diakses dari HP. Di layar kecil, sidebar berubah jadi menu hamburger yang bisa dibuka/tutup.

### 🔹 Warna Status
Perhatikan warna badge status:
- 🟢 **Hijau** — Selesai / Tersedia
- 🟡 **Kuning** — Dalam Proses / Reserved
- 🔵 **Biru** — Informasi / Baru
- 🔴 **Merah** — Urgent / Ditolak

### 🔹 Nomor Surat Aman
Sistem menggunakan **row locking** saat menyimpan Data Surat. Artinya, mustahil dua orang menggunakan nomor yang sama secara bersamaan — bahkan kalau klik Simpan di detik yang sama.

---

## 12. FAQ — Pertanyaan yang Sering Ditanyakan

### ❓ Saya lupa password, bagaimana?
Hubungi Super Admin untuk di-reset. Saat ini belum ada fitur "Lupa Password" otomatis.

### ❓ Kode tracking tidak ditemukan?
Pastikan kode tracking yang dimasukkan benar (perhatikan huruf besar/kecil dan tanda hubung). Kalau masih tidak ditemukan, kemungkinan surat belum diinput ke sistem.

### ❓ Nomor surat yang saya mau sudah terpakai?
Nomor bersifat first-come-first-served. Kalau nomor yang kamu incar sudah menjadi `used`, pilih nomor lain yang masih `available`. Atau minta Admin membuat stok nomor baru.

### ❓ Bisa tidak mengembalikan nomor yang sudah terpakai?
Tidak bisa. Nomor yang statusnya sudah `used` (sudah jadi Data Surat) bersifat final. Yang bisa dikembalikan hanya nomor yang statusnya `reserved` dan belum digunakan.

### ❓ Siapa yang bisa akses halaman tracking?
Semua orang — halaman tracking bersifat publik, tidak perlu login. Cukup punya kode tracking.

### ❓ Bagaimana cara menambah jenis naskah baru?
Ada dua cara:
1. **Cara cepat:** Klik tombol **Tambah Jenis Naskah** di halaman Ketersediaan Nomor.
2. **Via Master Data:** Buka Master Data → Jenis Naskah → Tambah.

### ❓ Scan QR tidak bisa di HP saya?
Scan QR membutuhkan **HTTPS** (koneksi aman). Jika akses via HTTP biasa, kamera tidak bisa diaktifkan karena kebijakan keamanan browser. Hubungi Admin untuk memastikan SiTrack diakses via HTTPS. Alternatif: gunakan fitur **Upload Foto QR** dari galeri.

### ❓ Bagaimana cara kerja Notifikasi WhatsApp otomatis?
SiTrack terhubung langsung dengan **WhatsApp Gateway (Meta WhatsApp Cloud API)**:
1. **Saat Pengajuan Berhasil**: Pemohon akan langsung menerima pesan WA berisi **Nomor Resi / Kode Tracking** dan link pelacakan langsung.
2. **Saat Status Diperbarui**: Setiap kali admin/petugas mengubah status dokumen (misal: "Diperiksa Oleh Sekjen", "Selesai dan Siap Untuk diambil", "Dokumen Sudah diambil", dll), sistem otomatis mengirim pesan pembaruan status terkini ke nomor WA pemohon.
3. **Konfigurasi Administrator**: Admin dapat mengatur token dan nomor pengirim di file `.env` melalui variabel `WHATSAPP_PHONE_NUMBER_ID` dan `WHATSAPP_API_TOKEN`, serta melakukan uji coba menggunakan perintah `php artisan wa:test <nomor_hp>`.

### ❓ Bagaimana deploy update ke server?
Untuk tim teknis, jalankan perintah berikut:
```bash
cd /opt/sitrack
git pull origin main
docker compose exec app php artisan optimize:clear
```

---

> **Butuh bantuan lebih lanjut?** Hubungi tim IT atau Super Admin di unit kerja kamu. Selamat menggunakan SiTrack! 🚀

