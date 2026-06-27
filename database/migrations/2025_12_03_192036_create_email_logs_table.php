<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('receiver');
            $table->string('subject');
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('email_logs');
    }
};
