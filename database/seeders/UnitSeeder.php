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
        $targetUnits = [
            'Direktorat Jendral Pembinaan Pelatihan Vokasi & Produktivitas',
            'Direktorat Jendral Pembinaan Penempatan Tenaga Kerja & PKK',
            'Direktorat Jendral Pembinaan Hubungan Industrial & Jamsosnaker',
            'Direktorat Jendral Pembinaan Pengawasan Ketenagakerjaan & K3',
            'Inspektorat Jendral',
            'Badan Perencanaan Pengembangan Ketenagakerjaan',
            'Staff Ahli Menteri Bidang Ekonomi Ketenagakerjaan',
            'Staff Ahli Menteri Bidang Hubungan Internasional',
            'Staff Ahli Menteri Bidang Hubungan Antarlembaga',
            'Politeknik Ketenagakerjaan',
            'Pusat Pasar Kerja',
            'PPSDM Ketenagakerjaan',
            'Biro Perencanaan & Manj. Kinerja',
            'Biro Keuangan & BMN',
            'Biro Organisasi & SDM Aparatur',
            'Biro Hukum',
            'Biro Umum',
            'Biro Kerja Sama',
            'Biro Hubungan Masyarakat',
            'Subbagian TU Sekjen, SAM, dan SKM',
        ];

        // Deactivate units not in the list
        Unit::whereNotIn('unit_name', $targetUnits)->update(['is_active' => false]);

        foreach ($targetUnits as $unitName) {
            Unit::updateOrCreate(
                ['unit_name' => $unitName],
                [
                    'pic_name' => 'Tata Usaha ' . $unitName,
                    'phone' => '081234567800',
                    'email' => strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $unitName)) . '@kemnaker.go.id',
                    'address' => 'Kementerian Ketenagakerjaan RI',
                    'is_active' => true,
                ]
            );
        }
    }
}
