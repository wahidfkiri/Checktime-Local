<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dates d'activation / désactivation des employés.
     *
     *  - employees.deactivated_at : DERNIER jour pris en compte dans les rapports
     *    avant la désactivation (les jours suivants sont laissés vides).
     *  - employees.activated_at   : date de la dernière (ré)activation.
     *  - employee_inactive_periods : historique des périodes de désactivation,
     *    pour que les rapports laissent vide l'intervalle désactivé d'un
     *    employé réactivé ensuite.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('deactivated_at')->nullable()->after('status');
            $table->date('activated_at')->nullable()->after('deactivated_at');
        });

        Schema::create('employee_inactive_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            // Premier jour désactivé (lendemain de la date de désactivation).
            $table->date('start_date');
            // Dernier jour désactivé (veille de la réactivation) ; null = toujours désactivé.
            $table->date('end_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_inactive_periods');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['deactivated_at', 'activated_at']);
        });
    }
};
