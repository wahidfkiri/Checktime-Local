<?php

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
        Schema::table('employee_schedules', function (Blueprint $table) {
            // Colonnes pour le type rotation
            $table->integer('work_days_count')->nullable()->after('repeat_weekly');
            $table->integer('rest_days_count')->nullable()->after('work_days_count');
            $table->decimal('daily_hours', 4, 2)->nullable()->after('rest_days_count');
            
            // Colonne pour l'utilisateur créateur
            $table->unsignedBigInteger('created_by')->nullable()->after('notes');
            
            // Index pour optimiser les requêtes
            $table->index(['schedule_type', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_schedules', function (Blueprint $table) {
            $table->dropColumn(['work_days_count', 'rest_days_count', 'daily_hours', 'created_by']);
            $table->dropIndex(['schedule_type', 'start_date', 'end_date']);
        });
    }
};