<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_sn');
            $table->string('alias')->nullable();
            $table->string('ip')->nullable();
            $table->dateTime('last_sync')->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('devices');
    }
};
