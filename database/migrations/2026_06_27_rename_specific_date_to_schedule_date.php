<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent : sur un environnement où specific_date a déjà été
        // renommée (ou n'a jamais existé sous ce nom), le ALTER TABLE brut
        // plantait avec "Unknown column 'specific_date'" et bloquait TOUTES
        // les migrations suivantes (elles s'exécutent dans l'ordre et
        // s'arrêtent à la première erreur).
        if (Schema::hasColumn('employee_schedules', 'specific_date')
            && !Schema::hasColumn('employee_schedules', 'schedule_date')) {
            DB::statement('ALTER TABLE employee_schedules CHANGE specific_date schedule_date DATE NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employee_schedules', 'schedule_date')
            && !Schema::hasColumn('employee_schedules', 'specific_date')) {
            DB::statement('ALTER TABLE employee_schedules CHANGE schedule_date specific_date DATE NULL');
        }
    }
};
