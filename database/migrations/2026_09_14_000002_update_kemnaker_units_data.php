<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Unit;

return new class extends Migration {
    public function up(): void
    {
        // Deactivate / remove leadership roles from units
        Unit::whereIn('unit_name', [
            'Mentri Ketenagakerjaan',
            'Menteri Ketenagakerjaan',
            'Wakil Mentri Ketenagakerjaan',
            'Wakil Menteri Ketenagakerjaan',
            'Sekretariat Jenderal',
            'Sekretaris Jenderal',
        ])->delete();

        // Rename legacy names
        $legacyRenames = [
            'Biro Perencanaan' => 'Biro Perencanaan dan Manajemen Kinerja',
            'Biro Keuangan' => 'Biro Keuangan dan Barang Milik Negara',
            'Biro OSDMA' => 'Biro Organisasi dan Sumber Daya Manusia Aparatur',
            'Biro Kerjasama' => 'Biro Kerja Sama',
            'PPSDM' => 'Pusat Pengembangan Sumber Daya Manusia Ketenagakerjaan',
            'Inspektur Jendral' => 'Inspektorat Jenderal',
        ];

        foreach ($legacyRenames as $oldName => $newName) {
            Unit::where('unit_name', $oldName)->update(['unit_name' => $newName]);
        }

        $units = [
            ['unit_name' => 'Biro Perencanaan dan Manajemen Kinerja', 'pic_name' => 'Tata Usaha Biro Perencanaan dan Manajemen Kinerja', 'phone' => '081234567806', 'email' => 'biro.perencanaan@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI Gedung B Lantai 2'],
            ['unit_name' => 'Biro Keuangan dan Barang Milik Negara', 'pic_name' => 'Tata Usaha Biro Keuangan dan BMN', 'phone' => '081234567804', 'email' => 'biro.keuangan@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI Gedung B Lantai 4'],
            ['unit_name' => 'Biro Hukum', 'pic_name' => 'Tata Usaha Biro Hukum', 'phone' => '081234567803', 'email' => 'biro.hukum@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI Gedung B Lantai 5'],
            ['unit_name' => 'Biro Kerja Sama', 'pic_name' => 'Tata Usaha Biro Kerja Sama', 'phone' => '081234567802', 'email' => 'biro.kerjasama@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI Gedung A Lantai 2'],
            ['unit_name' => 'Biro Organisasi dan Sumber Daya Manusia Aparatur', 'pic_name' => 'Tata Usaha Biro OSDMA', 'phone' => '081234567805', 'email' => 'biro.osdma@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI Gedung A Lantai 3'],
            ['unit_name' => 'Biro Komunikasi Publik', 'pic_name' => 'Tata Usaha Biro Komunikasi Publik', 'phone' => '081234567801', 'email' => 'biro.humas@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI Gedung A Lantai 1'],
            ['unit_name' => 'Biro Umum', 'pic_name' => 'Tata Usaha Biro Umum', 'phone' => '081234567805', 'email' => 'biro.umum@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI Gedung B Lantai 3'],
            ['unit_name' => 'Inspektorat Jenderal', 'pic_name' => 'Tata Usaha Inspektorat Jenderal', 'phone' => '081234567807', 'email' => 'itjen@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI Gedung C'],
            ['unit_name' => 'Pusat Pasar Kerja', 'pic_name' => 'Tata Usaha Pusat Pasar Kerja', 'phone' => '081234567812', 'email' => 'pusatpasarkerja@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI'],
            ['unit_name' => 'Pusat Pengembangan Sumber Daya Manusia Ketenagakerjaan', 'pic_name' => 'Tata Usaha PPSDM', 'phone' => '081234567808', 'email' => 'ppsdm@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI'],
            ['unit_name' => 'Pusat Data dan Teknologi Informasi Ketenagakerjaan', 'pic_name' => 'Tata Usaha Pusdatin', 'phone' => '081234567811', 'email' => 'pusdatin@kemnaker.go.id', 'address' => 'Kementerian Ketenagakerjaan RI'],
            ['unit_name' => 'Politeknik Ketenagakerjaan', 'pic_name' => 'Tata Usaha Polteknaker', 'phone' => '081234567813', 'email' => 'tu@polteknaker.ac.id', 'address' => 'Jl. Pengesahan No. 1, Ciracas, Jakarta Timur'],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['unit_name' => $unit['unit_name']],
                array_merge($unit, ['is_active' => true])
            );
        }
    }

    public function down(): void
    {
    }
};
