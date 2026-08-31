<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['unit_name' => 'Biro Umum dan Keuangan', 'pic_name' => 'Dr. H. Ahmad Fauzi', 'phone' => '081234567801', 'email' => 'biro.umum@instansi.go.id', 'address' => 'Gedung A Lantai 2'],
            ['unit_name' => 'Biro Kepegawaian dan Organisasi', 'pic_name' => 'Siti Rahmawati, M.Si', 'phone' => '081234567802', 'email' => 'biro.kepegawaian@instansi.go.id', 'address' => 'Gedung A Lantai 3'],
            ['unit_name' => 'Biro Hukum dan Kerjasama', 'pic_name' => 'Bambang Sudiro, S.H., M.H.', 'phone' => '081234567803', 'email' => 'biro.hukum@instansi.go.id', 'address' => 'Gedung A Lantai 4'],
            ['unit_name' => 'Pusat Data dan Informasi (Pusdatin)', 'pic_name' => 'Ir. Eko Prasetyo, M.Kom', 'phone' => '081234567804', 'email' => 'pusdatin@instansi.go.id', 'address' => 'Gedung B Lantai 1'],
            ['unit_name' => 'Inspektorat Jenderal', 'pic_name' => 'Drs. Agus Wijaya, Ak., CA', 'phone' => '081234567805', 'email' => 'itjen@instansi.go.id', 'address' => 'Gedung C Lantai 5'],
            ['unit_name' => 'Direktorat Jenderal Administrasi', 'pic_name' => 'Prof. Dr. Hendra Gunawan', 'phone' => '081234567806', 'email' => 'ditjen.adm@instansi.go.id', 'address' => 'Gedung D Lantai 2'],
            ['unit_name' => 'Direktorat Jenderal Pelayanan Teknis', 'pic_name' => 'Ir. Tri Haryanto, M.T.', 'phone' => '081234567807', 'email' => 'ditjen.teknis@instansi.go.id', 'address' => 'Gedung D Lantai 3'],
            ['unit_name' => 'Badan Kebijakan dan Pengembangan', 'pic_name' => 'Dr. Rina Anggraini', 'phone' => '081234567808', 'email' => 'bkp@instansi.go.id', 'address' => 'Gedung B Lantai 4'],
            ['unit_name' => 'Sekretariat Jenderal', 'pic_name' => 'Drs. H. Mulyadi, M.M.', 'phone' => '081234567809', 'email' => 'sekjen@instansi.go.id', 'address' => 'Gedung Utama Lantai 3'],
            ['unit_name' => 'Subbagian Tata Usaha Pimpinan', 'pic_name' => 'Nurul Hidayati, S.AP', 'phone' => '081234567810', 'email' => 'tu.pimpinan@instansi.go.id', 'address' => 'Gedung Utama Lantai 2'],
            ['unit_name' => 'Subbagian Kearsipan', 'pic_name' => 'Joko Susilo, S.Sos', 'phone' => '081234567811', 'email' => 'arsip@instansi.go.id', 'address' => 'Gedung A Lantai 1'],
            ['unit_name' => 'Bagian Rumah Tangga dan Protokol', 'pic_name' => 'Dedi Kurniawan, S.E.', 'phone' => '081234567812', 'email' => 'rumahtangga@instansi.go.id', 'address' => 'Gedung B Lantai 2'],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['unit_name' => $unit['unit_name']],
                array_merge($unit, ['is_active' => true])
            );
        }
    }
}
