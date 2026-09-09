<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('letter_numbers', 'attachment_path')) {
            Schema::table('letter_numbers', function (Blueprint $table) {
                $table->string('attachment_path')->nullable()->after('nd_pengantar');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('letter_numbers', 'attachment_path')) {
            Schema::table('letter_numbers', function (Blueprint $table) {
                $table->dropColumn('attachment_path');
            });
        }
    }
};