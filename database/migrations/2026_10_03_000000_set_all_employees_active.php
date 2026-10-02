<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Le statut actif/inactif des employés devient une décision locale (bouton
 * dans /employees) : un employé inactif est masqué de tous les rapports,
 * statistiques et synchronisations. On repart d'une base saine en mettant
 * tous les employés existants à « active » ; auparavant le statut était
 * recalculé à chaque synchronisation depuis enable_att de l'appareil.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('employees')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'active');
            })
            ->update(['status' => 'active']);
    }

    public function down(): void
    {
        // Les anciens statuts ne sont pas conservés : rien à restaurer.
    }
};
