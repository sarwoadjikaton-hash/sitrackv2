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
            ['unit_name' => 'Mentri Ketenagakerjaan', 'pic_name' => 'Tata Usaha Pimpinan', 'phone' => '081234567810', 'email' => 'tu.pimpinan@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI'],
            ['unit_name' => 'Wakil Mentri Ketenagakerjaan', 'pic_name' => 'Tata Usaha Pimpinan', 'phone' => '081234567810', 'email' => 'tu.pimpinan@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI'],
            ['unit_name' => 'Sekretariat Jenderal', 'pic_name' => 'Drs. H. Mulyadi, M.M.', 'phone' => '081234567809', 'email' => 'sekjen@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI'],
            ['unit_name' => 'Inspektur Jendral', 'pic_name' => 'Inspektur Jendral', 'phone' => '081234567807', 'email' => 'irjen@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI'],
            ['unit_name' => 'Biro Umum', 'pic_name' => 'Tata Usaha Biro Umum', 'phone' => '081234567805', 'email' => 'biro.umum@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI Gedung B Lantai 3'],
            ['unit_name' => 'Biro Perencanaan', 'pic_name' => 'Tata Usaha Biro Perencanaan', 'phone' => '081234567806', 'email' => 'biro.perencanaan.adm@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI'],
            ['unit_name' => 'Biro Keuangan', 'pic_name' => 'Tata Usaha Biro Keuangan', 'phone' => '081234567804', 'email' => 'biro.keuangan@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI'],
            ['unit_name' => 'Biro Hukum', 'pic_name' => 'Tata Usaha Biro Hukum', 'phone' => '081234567803', 'email' => 'biro.hukum@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI'],
            ['unit_name' => 'Biro Kerjasama', 'pic_name' => 'Tata Usaha Biro Kerjasama', 'phone' => '081234567802', 'email' => 'biro.kerjasama@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI'],
            ['unit_name' => 'Biro OSDMA', 'pic_name' => 'Tata Usaha Biro OSDMA', 'phone' => '081234567805', 'email' => 'biro.osdma@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI'],
            ['unit_name' => 'PPSDM', 'pic_name' => 'Tata Usaha PPSDM', 'phone' => '081234567808', 'email' => 'ppsdm@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI'],
            ['unit_name' => 'Pusat Pasar Kerja', 'pic_name' => 'Tata Usaha Pusat Pasar Kerja', 'phone' => '081234567812', 'email' => 'pusatpasarkerja@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI'],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['unit_name' => $unit['unit_name']],
                array_merge($unit, ['is_active' => true])
            );
        }
    }
}
