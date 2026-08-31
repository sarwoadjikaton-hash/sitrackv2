# Changelog v13 - Filter Workbook Data Surat

## Data Surat

- Menambahkan dropdown **Filter Workbook / Jenis Naskah** tepat sebelum tiga card statistik.
- Filter tersedia untuk seluruh workbook aktif dan opsi **Semua Workbook**.
- Filter bekerja secara server-side sehingga statistik dan tabel menggunakan scope workbook yang sama.
- Card **Data Surat**, **Nomor Tersedia**, dan **Reservasi** dihitung ulang sesuai workbook yang dipilih.
- Tabel Data Surat hanya menampilkan surat dari workbook yang dipilih.
- Filter menggunakan prepared statement saat `type_id` dipakai pada query.
- Tampilan filter dibuat responsif untuk desktop dan perangkat kecil.

## Database

Tidak ada perubahan schema database dari v12. `database.sql` tetap merupakan database fresh yang dapat diimport dari nol.
