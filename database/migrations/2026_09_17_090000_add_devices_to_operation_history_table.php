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
        Schema::table('operation_history', function (Blueprint $table) {
            // Device names at the time the command was sent, so renaming or
            // deleting a device later doesn't change what history shows.
            $table->json('devices')->nullable()->after('succeeded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operation_history', function (Blueprint $table) {
            $table->dropColumn('devices');
        });
    }
};
