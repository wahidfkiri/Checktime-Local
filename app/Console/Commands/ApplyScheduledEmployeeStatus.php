<?php

namespace App\Console\Commands;

use App\Models\EmployeeScheduledStatusChange;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ApplyScheduledEmployeeStatus extends Command
{
    protected $signature = 'employees:apply-scheduled-status
                            {--dry-run : Afficher les opérations dues sans les appliquer}';

    protected $description = 'Applique les désactivations / activations d\'employés programmées à une date future';

    public function handle(): int
    {
        $due = EmployeeScheduledStatusChange::due()->with('employee')->orderBy('effective_date')->orderBy('id')->get();

        if ($due->isEmpty()) {
            $this->info('Aucune opération programmée à appliquer.');
            return 0;
        }

        $applied = 0;

        foreach ($due as $change) {
            $label = $change->status === 'inactive' ? 'désactivation' : 'activation';
            $name  = $change->employee ? $change->employee->full_name : ('#' . $change->employee_id);
            $date  = $change->effective_date->format('d/m/Y');

            if ($this->option('dry-run')) {
                $this->line("[dry-run] {$label} de {$name} au {$date}");
                continue;
            }

            try {
                if ($change->employee) {
                    $change->employee->applyStatus($change->status, $change->effective_date, $change->created_by);
                }

                $change->update(['applied_at' => now()]);
                $applied++;

                $this->info("✅ {$label} de {$name} au {$date}");
                Log::info("Opération programmée appliquée : {$label} de {$name} au {$date}");
            } catch (\Throwable $e) {
                // L'opération reste en attente : elle sera retentée à la prochaine heure.
                $this->error("❌ {$label} de {$name} : " . $e->getMessage());
                Log::error("Opération programmée en échec ({$label} de {$name}) : " . $e->getMessage());
            }
        }

        $this->info("{$applied} opération(s) appliquée(s) sur {$due->count()} due(s).");

        return 0;
    }
}
