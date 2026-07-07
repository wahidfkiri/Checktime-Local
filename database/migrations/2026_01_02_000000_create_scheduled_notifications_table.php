<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('command')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('supports_recipients')->default(false);
            $table->boolean('is_active')->default(false);
            $table->string('frequency')->default('weekly'); // daily|weekly|monthly|custom
            $table->string('time', 5)->default('09:00');     // HH:MM
            $table->unsignedTinyInteger('day_of_week')->nullable();  // 1 (Lundi) .. 7 (Dimanche)
            $table->unsignedTinyInteger('day_of_month')->nullable(); // 1 .. 31
            $table->string('cron_expression')->nullable();
            $table->json('recipients')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_status')->nullable();
            $table->timestamps();
        });

        // Seed les tâches de rapport (idempotent).
        $now = now();
        $jobs = [
            [
                'command'             => 'attendance:send-weekly-reports',
                'label'               => 'Rapport hebdomadaire de présence (employés)',
                'description'         => 'Envoie à chaque employé son relevé de présence de la semaine (Lun–Ven).',
                'supports_recipients' => false,
                'frequency'           => 'weekly',
                'time'                => '09:00',
                'day_of_week'         => 6, // Samedi
            ],
            [
                'command'             => 'attendance:send-weekly-rh-reports',
                'label'               => 'Rapport hebdomadaire RH',
                'description'         => 'Envoie le rapport de présence hebdomadaire consolidé aux destinataires RH.',
                'supports_recipients' => true,
                'frequency'           => 'weekly',
                'time'                => '09:00',
                'day_of_week'         => 6, // Samedi
            ],
            [
                'command'             => 'reports:send-monthly-rh',
                'label'               => 'Rapport mensuel RH (PDF)',
                'description'         => 'Génère et envoie le rapport mensuel RH en PDF aux destinataires RH.',
                'supports_recipients' => true,
                'frequency'           => 'monthly',
                'time'                => '09:00',
                'day_of_month'        => 31,
            ],
        ];

        foreach ($jobs as $job) {
            DB::table('scheduled_notifications')->updateOrInsert(
                ['command' => $job['command']],
                array_merge($job, [
                    'is_active'  => false,
                    'recipients' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_notifications');
    }
};
