<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Unit;

return new class extends Migration {
    public function up(): void
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
            'Staf Ahli Menteri Bidang Sosial, Politik dan Kebijakan Publik',
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

        // Rename legacy / alternate spellings if they exist
        $legacyRenames = [
            'Direktorat Jendral Pembinaan Pelatihan Vokasi & Produktivitas' => 'Direktorat Jenderal Pembinaan Pelatihan Vokasi & Produktivitas',
            'Direktorat Jendral Pembinaan Penempatan Tenaga Kerja & PKK' => 'Direktorat Jenderal Pembinaan Penempatan Tenaga Kerja & PKK',
            'Direktorat Jendral Pembinaan Hubungan Industrial & Jamsosnaker' => 'Direktorat Jenderal Pembinaan Hubungan Industrial & Jamsosnaker',
            'Direktorat Jendral Pembinaan Pengawasan Ketenagakerjaan & K3' => 'Direktorat Jenderal Pembinaan Pengawasan Ketenagakerjaan & K3',
            'Inspektur Jendral' => 'Inspektorat Jenderal',
            'Inspektur Jenderal' => 'Inspektorat Jenderal',
            'Staff Ahli Menteri Bidang Ekonomi Ketenagakerjaan' => 'Staf Ahli Menteri Bidang Ekonomi Ketenagakerjaan',
            'Staff Ahli Menteri Bidang Hubungan Internasional' => 'Staf Ahli Menteri Bidang Hubungan Internasional',
            'Staff Ahli Menteri Bidang Hubungan Antarlembaga' => 'Staf Ahli Menteri Bidang Hubungan Antarlembaga',
            'Biro Hubungan Masyarakat' => 'Biro Hubungan Masyarakat',
            'Biro Komunikasi Publik' => 'Biro Hubungan Masyarakat',
            'Biro Perencanaan dan Manajemen Kinerja' => 'Biro Perencanaan & Manj. Kinerja',
            'Biro Keuangan dan Barang Milik Negara' => 'Biro Keuangan & BMN',
            'Biro Organisasi dan Sumber Daya Manusia Aparatur' => 'Biro Organisasi & SDM Aparatur',
            'Pusat Pengembangan Sumber Daya Manusia Ketenagakerjaan' => 'PPSDM Ketenagakerjaan',
            'Subbagian TU Sekjen, SAM, dan SKM' => 'Subbagian TU Sekjen, SAM dan SKM',
            'Kabag TU Pimpinan dan Protokol' => 'Bagian TU Pimpinan dan Protokol',
            'Kasubbag TU Sekjen, SAM dan SKM' => 'Subbagian TU Sekjen, SAM dan SKM',
        ];

        foreach ($legacyRenames as $oldName => $newName) {
            Unit::where('unit_name', $oldName)->update(['unit_name' => $newName]);
        }

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

    public function down(): void
    {
    }
};
