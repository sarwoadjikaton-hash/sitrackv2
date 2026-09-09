<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LetterCategory;

class LetterCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['category_name' => 'Permohonan Tanda Tangan', 'description' => 'Surat permohonan paraf atau tanda tangan pimpinan Sekretariat Jenderal.', 'is_active' => true],
            ['category_name' => 'Permohonan', 'description' => 'Surat permohonan layanan, izin, atau fasilitas.', 'is_active' => true],
            ['category_name' => 'Nota Dinas / Memorandum', 'description' => 'Naskah dinas berbentuk nota dinas atau memorandum untuk komunikasi kedinasan internal.', 'is_active' => true],
            ['category_name' => 'Arsip', 'description' => 'Surat terkait template Surat.', 'is_active' => true],
            ['category_name' => 'Undangan', 'description' => 'Naskah dinas undangan rapat, koordinasi, audiensi, atau kegiatan kedinasan.', 'is_active' => true],
            ['category_name' => 'Pemberitahuan', 'description' => 'Surat pemberitahuan resmi dari unit kerja Kementerian Ketenagakerjaan.', 'is_active' => true],
            ['category_name' => 'Disposisi Internal', 'description' => 'Surat yang membutuhkan tindak lanjut dari unit internal.', 'is_active' => true],
            ['category_name' => 'Edaran', 'description' => 'Naskah dinas edaran untuk penyampaian informasi, arahan, atau ketentuan kepada unit terkait.', 'is_active' => true],
        ];

        foreach ($categories as $cat) {
            LetterCategory::updateOrCreate(
                ['category_name' => $cat['category_name']],
                $cat
            );
        }
    }
}
