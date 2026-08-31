<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('letter_number_types', function (Blueprint $table) {
            $table->id();
            $table->string('type_code', 50)->unique();
            $table->string('type_name', 150);
            $table->string('workbook_name', 120);
            $table->boolean('uses_security_access')->default(false);
            $table->string('extra_field', 50)->default('scan_result'); // 'scan_result' or 'nd_pengantar'
            $table->string('number_pattern', 255);
            $table->unsignedTinyInteger('sequence_padding')->default(4);
            $table->string('default_signer_code', 20)->default('1');
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_number_types');
    }
};
