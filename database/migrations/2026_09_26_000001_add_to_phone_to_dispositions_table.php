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
        Schema::table('dispositions', function (Blueprint $table) {
            if (!Schema::hasColumn('dispositions', 'to_phone')) {
                $table->string('to_phone', 50)->nullable()->after('to_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dispositions', function (Blueprint $table) {
            if (Schema::hasColumn('dispositions', 'to_phone')) {
                $table->dropColumn('to_phone');
            }
        });
    }
};
