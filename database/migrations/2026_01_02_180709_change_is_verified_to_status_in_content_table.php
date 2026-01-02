<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the old column and add the new ENUM column
        Schema::table('content', function (Blueprint $table) {
            $table->dropColumn('is_verified');
        });

        Schema::table('content', function (Blueprint $table) {
            $table->enum('status', ['Pending', 'Accept', 'Reject'])->default('Pending')->after('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('content', function (Blueprint $table) {
            $table->date('is_verified')->nullable()->after('date');
        });
    }
};
