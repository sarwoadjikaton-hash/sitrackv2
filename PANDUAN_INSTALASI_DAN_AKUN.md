# Panduan Instalasi tus Tracking Surat v13 Fresh

## Penting

Versi ini dibuat untuk **database baru**. Tidak ada file migration dari versi lama karena database lama akan dihapus. `database.sql` berisi struktur fresh yang digunakan v13, akun awal, master unit, kategori surat, serta 16 jenis naskah penomoran. Seluruh data operasional dimulai kosong.

## Instalasi di XAMPP

1. Hentikan penggunaan database lama dan backup jika masih diperlukan sebagai arsip.
2. Hapus database lama `tus_tracking_surat` melalui phpMyAdmin bila Anda ingin memulai benar-benar dari nol.
3. Ekstrak folder project ke `C:\xampp\htdocs\`.
4. Jalankan Apache dan MySQL.
5. Buka phpMyAdmin, pilih menu **Import**, lalu import `database.sql` dari root project. File SQL akan membuat database `tus_tracking_surat` secara otomatis.
6. Buka `http://localhost/NAMA_FOLDER_PROJECT/public/`.

## Akun awal

- Super Admin: `superadmin` / `super123`
- Admin Operator: `admin` / `admin123`
- Sekertaris Jendral: `sekjen` / `sekjen123`

Ganti password akun awal setelah aplikasi mulai digunakan pada lingkungan operasional.

## Data awal yang dipertahankan

Database fresh tetap menyediakan master dasar yang sama dengan project sebelumnya:

- 12 unit/pejabat awal.
- 4 kategori surat awal untuk dua lajur persuratan.
- 16 jenis naskah penomoran yang mengikuti workbook `REKAP NOMOR 2026`.
- Format nomor, digit urut, keamanan akses, dan field akhir Data Surat sesuai profil jenis naskah.

Tidak ada surat contoh, disposisi contoh, stok nomor, Pre-Order, Reservasi, atau Data Surat contoh.

## Alur penomoran

1. Pada **Ketersediaan Nomor Surat**, buat stok dengan Keperluan **Tersedia**.
2. **Pre-Order** hanya dapat mengambil rentang kontinu dari stok Tersedia.
3. **Reservasi** hanya dapat mengambil satu nomor dari stok Tersedia.
4. Pada **Data Surat**, pilih nomor Tersedia atau nomor yang sudah dialokasikan, lengkapi metadata surat, lalu simpan.
5. Sistem membentuk nomor final dan mengubah slot menjadi **Terpakai**.
