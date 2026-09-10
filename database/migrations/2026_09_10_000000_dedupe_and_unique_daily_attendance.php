<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Corrige les doublons dans daily_attendance : la synchronisation
 * (AttendanceSyncService) fait un updateOrCreate([employee_id,
 * attendance_date]) sans verrou ni contrainte d'unicité en base — deux
 * requêtes concurrentes (plusieurs onglets, rechargements rapides...) sur
 * la même date/employé pouvaient donc chacune insérer leur propre ligne,
 * d'où les lignes identiques constatées dans le tableau et les exports.
 *
 * Cette migration :
 *   1. Supprime les doublons déjà présents (ne garde que la ligne la plus
 *      récente par employé+date — perte de données non réversible, mais ce
 *      sont par construction des lignes strictement identiques ou
 *      obsolètes).
 *   2. Ajoute une contrainte d'unicité (employee_id, attendance_date) pour
 *      empêcher toute réapparition du problème, quelle que soit la
 *      concurrence des requêtes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Dédoublonnage : pour chaque (employee_id, attendance_date) en
        // double, ne garde que la ligne la plus récente (updated_at, puis id
        // en cas d'égalité) et supprime les autres.
        DB::statement(<<<'SQL'
            DELETE t1 FROM daily_attendance t1
            INNER JOIN daily_attendance t2
                ON t1.employee_id = t2.employee_id
               AND t1.attendance_date = t2.attendance_date
               AND (
                     t1.updated_at < t2.updated_at
                  OR (t1.updated_at = t2.updated_at AND t1.id < t2.id)
               )
        SQL);

        // 2) Contrainte d'unicité : empêche toute nouvelle insertion en
        // double, même en cas de requêtes concurrentes (le simple
        // updateOrCreate() PHP ne suffit pas, voir AttendanceSyncService).
        Schema::table('daily_attendance', function (Blueprint $table) {
            $table->unique(['employee_id', 'attendance_date'], 'daily_attendance_employee_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('daily_attendance', function (Blueprint $table) {
            $table->dropUnique('daily_attendance_employee_date_unique');
        });
        // Le dédoublonnage n'est pas réversible (les lignes supprimées
        // étaient des doublons identiques ou obsolètes, non restaurables).
    }
};
