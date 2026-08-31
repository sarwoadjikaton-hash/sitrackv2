# Changelog v12 Fresh Clean

## Database

- Database dibuat ulang sebagai fresh schema v12.
- Data operasional tidak disertakan.
- Master unit, kategori surat, akun awal, dan 16 profil jenis naskah dipertahankan.
- Tabel legacy `letter_number_requests` dihapus karena alur Permintaan Nomor sudah digantikan oleh Data Surat.
- Kolom legacy `disposition_to` diganti menjadi `requested_actions` agar sesuai makna data pada lajur Tindak Lanjut/TTD.
- Kolom redundant `uses_existing_slot` dan `request_id` dihapus dari batch ketersediaan nomor.
- Role user diperketat menjadi enum `super_admin` dan `admin`.
- Charset/collation diseragamkan ke utf8mb4/utf8mb4_unicode_ci.
- Zona waktu aplikasi dan sesi database diselaraskan ke UTC+07:00.

## Sidebar

- Menghapus Register Semua Surat.
- Menghapus Jenis Surat dari sidebar.
- Menghapus Jenis & Format Nomor dari sidebar.
- Pengelolaan jenis naskah penomoran tetap dapat dibuka melalui tombol Tambah Jenis Naskah pada halaman Ketersediaan Nomor Surat.

## Code cleanup

- Menghapus route/view Register Semua Surat.
- Menghapus view nomor surat legacy.
- Menghapus tabel dan kode kompatibilitas Permintaan Nomor versi lama.
- Memisahkan proses master jenis naskah ke `jenis_nomor_surat_process.php`.
- Menghapus controller/model lama yang tidak digunakan.
- Menambahkan konfigurasi Workbook, Keamanan Akses, dan Field Akhir saat mengelola jenis naskah.
