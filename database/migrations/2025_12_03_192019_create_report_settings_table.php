<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('report_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('period', [
                'journalier','hebdomadaire','mensuel',
                'trimestriel','semestriel','annuel'
            ]);
            $table->json('email_list')->nullable();
            $table->boolean('sms_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('report_settings');
    }
};
