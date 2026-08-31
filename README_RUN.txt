tus TRACKING SURAT v13.0.0 - FILTER WORKBOOK DATA SURAT
=========================================

DATABASE
- Project ini menggunakan database fresh.
- Import satu file saja: database.sql.
- Tidak ada migration versi lama.
- Data operasional kosong.
- Master unit, kategori surat, akun awal, dan 16 jenis naskah tetap disediakan.

SIDEBAR
- Dashboard
- Penomoran Surat
  - Ketersediaan Nomor Surat
  - Data Surat
- Tindak Lanjut / Penandatanganan
  - Input Tindak Lanjut / TTD
  - Data Tindak Lanjut / TTD
- Disposisi
  - Input Surat Disposisi
  - Lajur Disposisi
- Master Data
  - Unit/Direktorat
  - Alur status
  - Rekap Master
  - User Staf (Super Admin)

Menu Register Semua Surat dihapus.
Menu Jenis Surat dan Jenis/Format Nomor tidak ditampilkan di sidebar. Pengelolaan jenis naskah penomoran tetap tersedia melalui tombol Tambah Jenis Naskah pada Ketersediaan Nomor Surat.

PENOMORAN
Tersedia -> Pre-Order/Reservasi -> Data Surat -> Terpakai
Pada menu Data Surat tersedia Filter Workbook yang menyaring statistik dan tabel secara bersamaan.
Pre-Order dan Reservasi tidak membuat slot baru.

AKUN AWAL
Super Admin  : superadmin / super123
Admin Operator: admin / admin123

TEKNOLOGI
PHP 8+, MySQL/MariaDB, Bootstrap 5, mysqli prepared statements, transaksi database, session authentication, CSRF token.
