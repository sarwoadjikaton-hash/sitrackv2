<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('letter_number_types')->where('type_code', 'IZIN')->orWhere('workbook_name', 'IZIN')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('letter_number_types')->insertOrIgnore([
            'type_code' => 'IZIN',
            'type_name' => 'Surat Izin',
            'workbook_name' => 'IZIN',
            'uses_security_access' => false,
            'extra_field' => 'scan_result',
            'number_pattern' => '{sequence}/{classification}/{month_roman}/{year}',
            'sequence_padding' => 4,
            'default_signer_code' => '1',
            'display_order' => 16,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
