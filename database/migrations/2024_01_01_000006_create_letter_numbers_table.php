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
        Schema::create('letter_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id')->constrained('letter_number_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('number_year');
            $table->unsignedInteger('sequence_number');
            $table->string('status', 30)->default('available'); // 'available', 'reserved', 'used'
            $table->string('signer_code', 20)->default('1');
            $table->string('security_access', 20)->nullable();
            $table->string('classification_code', 80)->nullable();
            $table->unsignedTinyInteger('month_number')->nullable();
            $table->string('number_text', 255)->nullable();
            $table->date('incoming_date')->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('processing_unit_text', 150)->nullable();
            $table->string('signatory', 150)->nullable();
            $table->string('request_type', 100)->nullable();
            $table->string('destination', 255)->nullable();
            $table->date('letter_date')->nullable();
            $table->text('subject')->nullable();
            $table->string('technical_officer', 150)->nullable();
            $table->string('scan_result', 255)->nullable();
            $table->string('nd_pengantar', 255)->nullable();
            $table->unsignedBigInteger('linked_letter_id')->nullable();
            $table->string('reserved_for', 150)->nullable();
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['type_id', 'number_year', 'sequence_number'], 'uq_type_year_sequence');
            $table->index(['type_id', 'number_year', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_numbers');
    }
};
