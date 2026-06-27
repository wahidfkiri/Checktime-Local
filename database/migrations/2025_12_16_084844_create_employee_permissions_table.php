<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_permissions', function (Blueprint $table) {
            $table->id();
            
            // Clés étrangères
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            
            // Champs pour la permission
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->text('raison'); // ou 'reason' en anglais
            $table->enum('status', ['pending', 'approved', 'rejected', 'canceled'])->default('pending');
            
            // Si vous avez besoin de la durée
            $table->integer('duration_minutes')->nullable();
            
            
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index(['employee_id', 'date']);
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_permissions');
    }
};