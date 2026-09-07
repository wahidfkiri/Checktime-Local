<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('scheduled_notifications')->updateOrInsert(
            ['command' => 'backup:send'],
            [
                'label'               => 'Sauvegarde de la base de données',
                'description'         => 'Génère une sauvegarde complète de la base de données (SQL + CSV) et l\'envoie par email.',
                'supports_recipients' => true,
                'is_active'           => false,
                'frequency'           => 'weekly',
                'time'                => '02:00',
                'day_of_week'         => 7, // Dimanche
                'day_of_month'        => null,
                'cron_expression'     => null,
                'recipients'          => null,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('scheduled_notifications')->where('command', 'backup:send')->delete();
    }
};
