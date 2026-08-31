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
        Schema::create('letter_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_letter_id')->constrained('letters')->cascadeOnDelete();
            $table->foreignId('target_letter_id')->constrained('letters')->cascadeOnDelete();
            $table->string('relation_type', 50)->default('TERKAIT_DENGAN'); // 'TINDAK_LANJUT_DARI', 'BALASAN_DARI', 'HASIL_DISPOSISI_DARI', 'TERKAIT_DENGAN'
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['source_letter_id', 'target_letter_id', 'relation_type'], 'uq_source_target_relation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_relations');
    }
};
