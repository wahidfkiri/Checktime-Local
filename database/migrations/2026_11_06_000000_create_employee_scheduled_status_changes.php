<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Désactivations / activations programmées à une date FUTURE depuis
     * /employees, appliquées automatiquement par la tâche planifiée horaire
     * `employees:apply-scheduled-status` (visible et réglable dans /settings).
     */
    public function up(): void
    {
        Schema::create('employee_scheduled_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            // Statut visé : 'inactive' (désactivation) ou 'active' (activation).
            $table->string('status', 16);
            // Désactivation : dernier jour pris en compte ; activation : premier jour pris en compte.
            $table->date('effective_date');
            $table->timestamp('applied_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['applied_at', 'effective_date']);
        });

        $now = now();

        DB::table('scheduled_notifications')->updateOrInsert(
            ['command' => 'employees:apply-scheduled-status'],
            [
                'label'               => 'Désactivations / activations programmées des employés',
                'description'         => "Chaque heure, applique les désactivations et activations d'employés programmées à une date future.",
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
        DB::table('scheduled_notifications')->where('command', 'employees:apply-scheduled-status')->delete();

        Schema::dropIfExists('employee_scheduled_status_changes');
    }
};
