<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LetterNumberType;

class LetterNumberTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'type_code' => 'ND_MEMO',
                'type_name' => 'Nota Dinas / Memorandum',
                'workbook_name' => 'NODIN-MEMORANDUM',
                'uses_security_access' => true,
                'extra_field' => 'nd_pengantar',
                'number_pattern' => '{security}-{signer}/{sequence}/{classification}/{month_roman}/{year}',
                'sequence_padding' => 4,
                'default_signer_code' => '1',
                'display_order' => 1,
            ],
            [
                'type_code' => 'BIASA_UND',
                'type_name' => 'Biasa / Undangan',
                'workbook_name' => 'BIASA-UNDANGAN',
                'uses_security_access' => true,
                'extra_field' => 'scan_result',
                'number_pattern' => '{security}-{signer}/{sequence}/{classification}/{month_roman}/{year}',
                'sequence_padding' => 4,
                'default_signer_code' => '1',
                'display_order' => 2,
            ],
            [
                'type_code' => 'KEPUTUSAN',
                'type_name' => 'Keputusan',
                'workbook_name' => 'KEPUTUSAN',
                'uses_security_access' => true,
                'extra_field' => 'scan_result',
                'number_pattern' => '{security}-{signer}/{sequence}/{classification}/{month_roman}/{year}',
                'sequence_padding' => 4,
                'default_signer_code' => '1',
                'display_order' => 3,
            ],
            [
                'type_code' => 'TUGAS_PERINTAH',
                'type_name' => 'Surat Tugas / Surat Perintah',
                'workbook_name' => 'SURAT TUGAS-SURAT PERINTAH',
                'uses_security_access' => true,
                'extra_field' => 'scan_result',
                'number_pattern' => '{security}-{signer}/{sequence}/{classification}/{month_roman}/{year}',
                'sequence_padding' => 4,
                'default_signer_code' => '1',
                'display_order' => 4,
            ],
            [
                'type_code' => 'KET_PERNYATAAN',
                'type_name' => 'Keterangan / Pernyataan / Kuasa',
                'workbook_name' => 'KETERANGAN-PERNYATAAN-KUASA',
                'uses_security_access' => true,
                'extra_field' => 'scan_result',
                'number_pattern' => '{security}-{signer}/{sequence}/{classification}/{month_roman}/{year}',
                'sequence_padding' => 4,
                'default_signer_code' => '1',
                'display_order' => 5,
            ],
            [
                'type_code' => 'FAKSIMILI',
                'type_name' => 'Berita Faksimili',
                'workbook_name' => 'BERITA FAKSIMILI',
                'uses_security_access' => false,
                'extra_field' => 'scan_result',
                'number_pattern' => '{sequence}/{classification}/{month_roman}/{year}',
                'sequence_padding' => 4,
                'default_signer_code' => '1',
                'display_order' => 6,
            ],
            [
                'type_code' => 'EDARAN',
                'type_name' => 'Surat Edaran',
                'workbook_name' => 'EDARAN',
                'uses_security_access' => false,
                'extra_field' => 'scan_result',
                'number_pattern' => '{sequence}/{classification}/{month_roman}/{year}',
                'sequence_padding' => 4,
                'default_signer_code' => '1',
                'display_order' => 7,
            ],
            [
                'type_code' => 'BERITA_ACARA',
                'type_name' => 'Berita Acara',
                'workbook_name' => 'BERITA ACARA',
                'uses_security_access' => false,
                'extra_field' => 'scan_result',
                'number_pattern' => '{sequence}/{classification}/{month_roman}/{year}',
                'sequence_padding' => 4,
                'default_signer_code' => '1',
                'display_order' => 8,
            ],
            [
                'type_code' => 'SERTIFIKAT_PIAGAM',
                'type_name' => 'Ijazah / Sertifikat / Piagam',
                'workbook_name' => 'IJAZAH-SERTIFIKAT-PIAGAM',
                'uses_security_access' => false,
                'extra_field' => 'scan_result',
                'number_pattern' => '{sequence}/{classification}/{month_roman}/{year}',
                'sequence_padding' => 4,
                'default_signer_code' => '1',
                'display_order' => 9,
            ],
            [
                'type_code' => 'PENGUMUMAN',
                'type_name' => 'Pengumuman',
                'workbook_name' => 'PENGUMUMAN',
                'uses_security_access' => false,
                'extra_field' => 'scan_result',
                'number_pattern' => '{sequence}/{classification}/{month_roman}/{year}',
                'sequence_padding' => 4,
                'default_signer_code' => '1',
                'display_order' => 10,
            ],
            [
                'type_code' => 'PENGANTAR',
                'type_name' => 'Surat Pengantar',
                'workbook_name' => 'PENGANTAR',
                'uses_security_access' => false,
                'extra_field' => 'scan_result',
                'number_pattern' => '{sequence}/{classification}/{month_roman}/{year}',
                'sequence_padding' => 4,
                'default_signer_code' => '1',
                'display_order' => 11,
            ],
            [
                'type_code' => 'PKS',
                'type_name' => 'Perjanjian Kerja Sama',
                'workbook_name' => 'PERJANJIAN KERJA SAMA',
                'uses_security_access' => false,
                'extra_field' => 'scan_result',
                'number_pattern' => '{sequence}/{classification}/{month_roman}/{year}',
                'sequence_padding' => 4,
                'default_signer_code' => '1',
                'display_order' => 12,
            ],
            [
                'type_code' => 'SIARAN_PERS',
                'type_name' => 'Siaran Pers',
                'workbook_name' => 'SIARAN PERS',
                'uses_security_access' => false,
                'extra_field' => 'scan_result',
                'number_pattern' => '{sequence}/{classification}/{month_roman}/{year}',
                'sequence_padding' => 4,
                'default_signer_code' => '1',
                'display_order' => 13,
            ],
            [
                'type_code' => 'NOTULA',
                'type_name' => 'Notula Rapat',
                'workbook_name' => 'NOTULA',
                'uses_security_access' => false,
                'extra_field' => 'scan_result',
                'number_pattern' => '{sequence}/{classification}/{month_roman}/{year}',
                'sequence_padding' => 4,
                'default_signer_code' => '1',
                'display_order' => 14,
            ],
            [
                'type_code' => 'SOP',
                'type_name' => 'Standar Operasional Prosedur',
                'workbook_name' => 'SOP',
                'uses_security_access' => false,
                'extra_field' => 'scan_result',
                'number_pattern' => '{sequence}/{classification}/{month_roman}/{year}',
                'sequence_padding' => 4,
                'default_signer_code' => '1',
                'display_order' => 15,
            ],
        ];

        foreach ($types as $type) {
            LetterNumberType::updateOrCreate(
                ['type_code' => $type['type_code']],
                array_merge($type, ['is_active' => true])
            );
        }
    }
}
