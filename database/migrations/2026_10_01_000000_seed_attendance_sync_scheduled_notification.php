<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ajoute la synchronisation horaire des pointages à la liste des tâches
 * planifiées affichées dans /settings. Réutilise la commande `attendance:sync`
 * existante (celle que le bouton "Synchroniser" de /admin/daily-attendance
 * déclenche déjà) : elle récupère les pointages depuis l'API du boîtier
 * biométrique (AttendanceSyncService::syncAll()) puis reconstruit les
 * résumés quotidiens (daily_attendance) pour la période synchronisée.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('scheduled_notifications')->updateOrInsert(
            ['command' => 'attendance:sync'],
            [
                'label'               => 'Synchronisation horaire des pointages',
                'description'         => 'Récupère les pointages depuis le boîtier biométrique et met à jour les présences du jour.',
                'supports_recipients' => false,
                'is_active'           => true,
                'frequency'           => 'custom',
                'time'                => '00:00',
                'day_of_week'         => null,
                'day_of_month'        => null,
                'cron_expression'     => '0 * * * *',
                'recipients'          => null,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('scheduled_notifications')->where('command', 'attendance:sync')->delete();
    }
};
