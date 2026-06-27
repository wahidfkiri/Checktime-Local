<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            // Key-value store columns (for company info, installer, etc.)
            $table->string('key')->nullable()->index();
            $table->text('value')->nullable();
            $table->string('group')->nullable()->index();

            // Email/SMS configuration columns
            $table->string('email')->nullable();
            $table->boolean('email_employees_is_active')->default(true);
            $table->boolean('email_is_active')->default(true);
            $table->boolean('sms_is_active')->default(true);
            $table->integer('sms_credit')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};