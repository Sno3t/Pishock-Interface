<?php

use App\Models\OperatorToken;
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
            $table->foreignIdFor(OperatorToken::class)->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operation_history', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(OperatorToken::class);
        });
    }
};
