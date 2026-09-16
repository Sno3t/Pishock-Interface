<?php

use App\Enums\ControlTypes;
use App\Models\User;
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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->enum('operation', ControlTypes::$types);
            $table->enum('type', ['duration', 'intensity']);
            $table->integer('max_value');
            $table->timestamp('created_at');
            // todo Add updated by user id later
        });

        Schema::create('operation_history', function (Blueprint $table) {
            $table->id();
            $table->enum('operation', ControlTypes::$types);
            $table->enum('type', ['duration', 'intensity']);
            $table->integer('value');
            $table->timestamp('created_at');
//            $table->foreignIdFor(User::class)->nullable();  // todo Add updated by user id later
        });

        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_name');
            $table->string('share_code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('operation_history');
        Schema::dropIfExists('devices');
    }
};
