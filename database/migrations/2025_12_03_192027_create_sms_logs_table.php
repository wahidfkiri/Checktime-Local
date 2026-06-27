<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->dateTime('sent_at')->nullable();
            $table->enum('status', ['sent','failed']);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('sms_logs');
    }
};
