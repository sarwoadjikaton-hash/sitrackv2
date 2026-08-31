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
        Schema::create('letters', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_code', 50)->unique();
            $table->string('agenda_number', 50)->nullable()->index();
            $table->string('letter_number', 150)->nullable();
            $table->string('letter_type', 20)->default('in'); // 'in', 'out'
            $table->string('letter_source', 50)->default('Manual'); // 'Manual', 'SRIKANDI'
            $table->string('process_lane', 30)->default('signature'); // 'signature', 'disposition'
            $table->string('sender_unit', 150)->nullable();
            $table->foreignId('category_id')->nullable()->constrained('letter_categories')->nullOnDelete();
            $table->string('sender_name', 150)->nullable();
            $table->string('sender_phone', 50)->nullable();
            $table->foreignId('recipient_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->text('subject');
            $table->date('letter_date')->nullable();
            $table->date('received_date')->nullable();
            $table->string('priority', 30)->default('normal'); // 'urgent', 'high', 'normal', 'low'
            $table->string('security_level', 30)->default('Biasa'); // 'Sangat Rahasia', 'Rahasia', 'Biasa'
            $table->string('status', 100)->default('Dokumen Diterima dan Diinput');
            $table->string('current_position', 150)->default('Tata Usaha');
            $table->text('requested_actions')->nullable(); // Paraf, Tanda Tangan, dll
            $table->text('notes')->nullable();
            $table->string('attachment_path', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['process_lane', 'status']);
            $table->index('tracking_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letters');
    }
};
