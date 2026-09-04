<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable();
            $table->string('action', 50)->index();          // login, logout, create, update, delete, export…
            $table->text('description')->nullable();
            $table->string('subject_type')->nullable();      // classe du modèle concerné
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('method', 10)->nullable();        // GET/POST…
            $table->text('url')->nullable();
            $table->string('route')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('properties')->nullable();          // détails (colonnes modifiées, etc.)
            $table->timestamps();

            $table->index('created_at');
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
