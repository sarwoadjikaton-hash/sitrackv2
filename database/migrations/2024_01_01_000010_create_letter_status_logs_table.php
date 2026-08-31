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
        Schema::create('letter_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_id')->constrained('letters')->cascadeOnDelete();
            $table->string('status', 100);
            $table->string('position', 150)->nullable();
            $table->text('note')->nullable();
            $table->string('changed_by', 100)->default('Sistem');
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamps();

            $table->index('letter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_status_logs');
    }
};
