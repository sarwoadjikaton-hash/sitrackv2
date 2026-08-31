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
            ['category_name' => 'Surat Masuk', 'description' => 'Surat masuk dari instansi luar atau masyarakat', 'is_active' => true],
            ['category_name' => 'Surat Keluar', 'description' => 'Surat keluar resmi ke instansi luar', 'is_active' => true],
            ['category_name' => 'Nota Dinas', 'description' => 'Komunikasi kedinasan internal antar unit kerja', 'is_active' => true],
            ['category_name' => 'Disposisi Pimpinan', 'description' => 'Lembar tindak lanjut dan arahan pimpinan', 'is_active' => true],
        ];

        foreach ($categories as $cat) {
            LetterCategory::updateOrCreate(
                ['category_name' => $cat['category_name']],
                $cat
            );
        }
    }
}
