<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Tambah jenis naskah "Lainnya" untuk database yang sudah berjalan.
     */
    public function up(): void
    {
        DB::table('letter_number_types')->insertOrIgnore([
            'type_code' => 'LAINNYA',
            'type_name' => 'Lainnya',
            'workbook_name' => 'LAINNYA',
            'uses_security_access' => false,
            'extra_field' => 'scan_result',
            'number_pattern' => '{sequence}/{classification}/{month_roman}/{year}',
            'sequence_padding' => 4,
            'default_signer_code' => '1',
            'display_order' => 99,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Hapus lagi hanya kalau belum dipakai surat mana pun.
     */
    public function down(): void
    {
        $id = DB::table('letter_number_types')->where('type_code', 'LAINNYA')->value('id');

        if (
            $id
            && ! DB::table('letters')->where('letter_number_type_id', $id)->exists()
            && ! DB::table('letter_numbers')->where('type_id', $id)->exists()
        ) {
            DB::table('letter_number_types')->where('id', $id)->delete();
        }
    }
};
