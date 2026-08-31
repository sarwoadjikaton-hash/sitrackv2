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
        Schema::create('dispositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_id')->constrained('letters')->cascadeOnDelete();
            $table->foreignId('parent_disposition_id')->nullable()->constrained('dispositions')->cascadeOnDelete();
            $table->string('from_name', 150)->default('Sekretaris Jenderal');
            $table->foreignId('to_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('to_name', 150)->nullable();
            $table->text('instruction');
            $table->date('due_date')->nullable();
            $table->string('status', 50)->default('Didisposisikan'); // 'Didisposisikan', 'Dalam Tindak Lanjut', 'Selesai', 'Dikembalikan'
            $table->text('follow_up_note')->nullable();
            $table->boolean('is_koordinator')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disposition_date')->useCurrent();
            $table->timestamps();

            $table->index(['letter_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispositions');
    }
};
