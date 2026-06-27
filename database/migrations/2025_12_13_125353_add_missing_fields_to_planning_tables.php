<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter des champs à employee_schedules
        Schema::table('employee_schedules', function (Blueprint $table) {
            // Pour les horaires planifiés par jour spécifique
            $table->date('specific_date')->nullable()->after('employee_id');
            
            // Pour les horaires fixes (répétition)
            $table->boolean('repeat_weekly')->default(false)->after('day_of_week');
            $table->date('start_date')->nullable()->after('repeat_weekly');
            $table->date('end_date')->nullable()->after('start_date');
            
            // Pour les plannings personnalisés
            $table->json('custom_days')->nullable()->after('end_date'); // Ex: ["monday", "wednesday", "friday"]
            $table->text('notes')->nullable()->after('custom_days');
            
            // Statut
            $table->boolean('is_active')->default(true)->after('notes');
            
            // Index
            $table->index(['employee_id', 'specific_date']);
            $table->index(['employee_id', 'day_of_week', 'is_active']);
        });

        // Ajouter des champs à schedule_rotations
        Schema::table('schedule_rotations', function (Blueprint $table) {
            // Type de rotation (24h/48h, etc.)
            $table->string('rotation_type', 50)->default('24_48')->after('employee_id');
            
            // Cycle de travail (en heures)
            $table->integer('work_hours')->default(24)->after('rotation_type');
            
            // Cycle de repos (en heures)
            $table->integer('rest_hours')->default(48)->after('work_hours');
            
            // Récurrence
            $table->boolean('is_recurring')->default(true)->after('rest_hours');
            $table->date('recurrence_end_date')->nullable()->after('is_recurring');
            
            // Notes
            $table->text('description')->nullable()->after('recurrence_end_date');
            
            // Statut
            $table->boolean('is_active')->default(true)->after('description');
        });

        // Créer la table des types d'horaires (si elle n'existe pas)
        if (!Schema::hasTable('work_hour_types')) {
            Schema::create('work_hour_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->time('start_time');
                $table->time('end_time');
                $table->integer('break_minutes')->default(60);
                $table->boolean('is_overnight')->default(false);
                $table->boolean('has_break')->default(true);
                $table->json('break_times')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Créer la table des plannings journaliers (pour export PDF)
        if (!Schema::hasTable('daily_plannings')) {
            Schema::create('daily_plannings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->onDelete('cascade');
                $table->foreignId('schedule_id')->nullable()->constrained('employee_schedules')->onDelete('set null');
                $table->date('planning_date');
                $table->string('day_name');
                $table->time('planned_start_time');
                $table->time('planned_end_time');
                $table->integer('planned_break_minutes')->default(60);
                $table->boolean('is_working_day')->default(true);
                $table->boolean('is_holiday')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();
                
                $table->unique(['employee_id', 'planning_date']);
                $table->index(['planning_date', 'employee_id']);
            });
        }

        // Créer la table des jours fériés
        if (!Schema::hasTable('holidays')) {
            Schema::create('holidays', function (Blueprint $table) {
                $table->id();
                $table->date('holiday_date');
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_recurring')->default(true);
                $table->boolean('is_working_day')->default(false);
                $table->timestamps();
                
                $table->unique(['holiday_date']);
                $table->index(['holiday_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('employee_schedules', function (Blueprint $table) {
            $table->dropColumn([
                'specific_date',
                'repeat_weekly',
                'start_date',
                'end_date',
                'custom_days',
                'notes',
                'is_active'
            ]);
        });

        Schema::table('schedule_rotations', function (Blueprint $table) {
            $table->dropColumn([
                'rotation_type',
                'work_hours',
                'rest_hours',
                'is_recurring',
                'recurrence_end_date',
                'description',
                'is_active'
            ]);
        });

        Schema::dropIfExists('daily_plannings');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('work_hour_types');
    }
};