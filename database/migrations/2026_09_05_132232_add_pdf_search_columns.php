<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            // attachment_path sudah ada — tambah kolom teks hasil ekstraksi PDF
            $table->text('pdf_content')->nullable()->after('attachment_path');
        });

        Schema::table('letter_numbers', function (Blueprint $table) {
            // letter_numbers belum punya field upload sama sekali — tambah keduanya
            $table->string('attachment_path', 255)->nullable()->after('nd_pengantar');
            $table->text('pdf_content')->nullable()->after('attachment_path');
        });
    }

    public function down(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->dropColumn('pdf_content');
        });

        Schema::table('letter_numbers', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'pdf_content']);
        });
    }
};