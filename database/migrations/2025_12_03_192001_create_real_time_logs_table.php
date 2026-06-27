<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('real_time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->dateTime('punch_time');
            $table->string('punch_state')->nullable();
            $table->string('device_sn')->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('real_time_logs');
    }
};
