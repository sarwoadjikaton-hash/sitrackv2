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
        Schema::create('letter_number_availability_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id')->constrained('letter_number_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('number_year');
            $table->string('purpose', 30); // 'available', 'preorder', 'reservation'
            $table->unsignedInteger('start_sequence');
            $table->unsignedInteger('end_sequence');
            $table->date('period_month')->nullable();
            $table->date('letter_date')->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('unit_text', 150)->nullable();
            $table->string('pic_name', 150)->nullable();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type_id', 'number_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_number_availability_batches');
    }
};
