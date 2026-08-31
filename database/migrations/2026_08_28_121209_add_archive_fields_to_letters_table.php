<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->string('archive_classification_code', 100)->nullable();
            $table->string('signatory_name', 150)->nullable();
            $table->string('technical_officer', 150)->nullable();
            $table->string('cover_letter_number', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->dropColumn([
                'archive_classification_code',
                'signatory_name',
                'technical_officer',
                'cover_letter_number',
            ]);
        });
    }
};