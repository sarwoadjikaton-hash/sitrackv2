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
            'Direktorat Jenderal Pembinaan Pelatihan Vokasi & Produktivitas',
            'Direktorat Jenderal Pembinaan Penempatan Tenaga Kerja & PKK',
            'Direktorat Jenderal Pembinaan Hubungan Industrial & Jamsosnaker',
            'Direktorat Jenderal Pembinaan Pengawasan Ketenagakerjaan & K3',
            'Inspektorat Jenderal',
            'Badan Perencanaan Pengembangan Ketenagakerjaan',
            'Staf Ahli Menteri Bidang Ekonomi Ketenagakerjaan',
            'Staf Ahli Menteri Bidang Hubungan Internasional',
            'Staf Ahli Menteri Bidang Hubungan Antarlembaga',
            'Staff Ahli Menteri Sosial, Politik, dan Kebijakan Publik',
            'PPSDM Ketenagakerjaan',
            'Pusat Pasar Kerja',
            'Biro Perencanaan & Manj. Kinerja',
            'Biro Keuangan & BMN',
            'Biro Organisasi & SDM Aparatur',
            'Biro Hukum',
            'Biro Umum',
            'Biro Kerja Sama',
            'Biro Hubungan Masyarakat',
            'Politeknik Ketenagakerjaan',
            'Bagian TU Pimpinan dan Protokol',
            'Subbagian TU Sekjen, SAM dan SKM',
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
