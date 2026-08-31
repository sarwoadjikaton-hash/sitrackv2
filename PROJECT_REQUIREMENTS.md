# Project tus Tracking Surat v13

## Konsep utama

Aplikasi memiliki dua workflow persuratan yang independen: **Lembar Tindak Lanjut/Penandatanganan** dan **Lajur Disposisi**. Keduanya menggunakan tabel surat yang sama sebagai metadata utama, sementara relasi lintas workflow disimpan pada `letter_relations`.

## Penomoran Surat

Penomoran memiliki dua menu utama di sidebar:

1. **Ketersediaan Nomor Surat** untuk mengelola stok dan alokasi nomor.
2. **Data Surat** untuk mencatat surat final yang menggunakan nomor tersebut.

### Aturan stok

- Nomor baru hanya dapat dibuat melalui Keperluan **Tersedia**.
- **Pre-Order** wajib mengambil rentang kontinu dari stok yang masih Tersedia.
- **Reservasi** wajib mengambil satu nomor dari stok yang masih Tersedia.
- Pre-Order dan Reservasi mengubah slot menjadi `reserved` secara transaksional.
- Alokasi yang belum digunakan dapat dibatalkan/dihapus sehingga nomor kembali menjadi `available`.
- Data Surat mengubah slot `available` atau `reserved` menjadi `used` dan membentuk nomor final.
- Kombinasi `type_id + number_year + sequence_number` bersifat unik.

## Data Surat

Form Data Surat mengikuti struktur workbook `REKAP NOMOR 2026`: Tanggal Masuk, Unit Pengolah Arsip, Penandatangan Surat, Permohonan, Tujuan Surat, Tanggal Surat, Keamanan Akses jika workbook menggunakannya, Nomor Urut, Kode Klasifikasi Arsip, Bulan, Nomor Surat, Perihal Surat, Petugas Unit Teknis, serta ND Pengantar atau Hasil Pindai sesuai profil workbook.

Nomor urut tidak dapat diketik bebas. Pilihan nomor berasal dari `letter_numbers` yang masih `available` atau `reserved`. Saat Data Surat disimpan, sistem menggunakan transaksi dan row locking untuk mencegah nomor yang sama dipakai bersamaan.

### Filter Workbook

Halaman **Data Surat** memiliki dropdown **Filter Workbook / Jenis Naskah** tepat sebelum card statistik. Pilihan ini menyaring secara server-side tiga statistik **Data Surat**, **Nomor Tersedia**, dan **Reservasi**, sekaligus membatasi isi tabel Data Surat ke workbook yang dipilih. Opsi **Semua Workbook** mengembalikan tampilan agregat seluruh jenis naskah.

## Profil workbook

Jenis naskah awal mengikuti 16 workbook utama: NODIN-MEMORANDUM, BIASA-UNDANGAN, KEPUtusAN, SURAT TUGAS-SURAT PERINTAH, KETERANGAN-PERNYATAAN-KUASA, BERITA FAKSIMILI, EDARAN, BERITA ACARA, IJAZAH-SERTIFIKAT-PIAGAM, PENGUMUMAN, PENGANTAR, PERJANJIAN KERJA SAMA, SIARAN PERS, NOTULA, SOP, dan IZIN.

Kelompok NODIN/Memorandum, Biasa/Undangan, Keputusan, Surat Tugas/Surat Perintah, dan Keterangan/Pernyataan/Kuasa menggunakan pola dengan prefiks keamanan seperti `B-1/0758/KS.06/VIII/2026`. Kelompok lain menggunakan pola `1/0001/KP.09.03/II/2026` sesuai referensi Excel. NODIN memakai field akhir `ND Pengantar`; workbook lain memakai `Hasil Pindai`.

## Database fresh

`database.sql` adalah satu-satunya file instalasi database. Data operasional sengaja kosong. Seed hanya mencakup akun awal, master unit, kategori surat, dan 16 jenis naskah penomoran.

Komponen legacy dari versi lama seperti `letter_number_requests` tidak digunakan lagi.

## Sidebar v13

Sidebar tidak lagi menampilkan:

- Register Semua Surat.
- Jenis Surat.
- Jenis & Format Nomor.

Jenis naskah penomoran tetap dapat dikelola melalui tombol **Tambah Jenis Naskah** pada halaman Ketersediaan Nomor Surat agar fungsi input jenis naskah tetap tersedia tanpa memenuhi sidebar.
