<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Permissions plus fines pour les sous-liens de « Historique des
     * pointages » et « Rapports des présences ». Les permissions globales
     * menu.daily-attendance / menu.reports (voir 2026_07_23_120000) restent
     * un raccourci « accès à toutes les sous-pages » — elles ne sont pas
     * remplacées, seulement complétées.
     */
    public static function submenuPermissions(): array
    {
        return [
            'menu.attendance-history' => 'Historique des pointages — Historique complet',
            'menu.attendance-presence' => 'Historique des pointages — Liste des présences',
            'menu.attendance-absence' => 'Historique des pointages — Liste des absences',
            'menu.attendance-retards' => 'Historique des pointages — Liste des retards',
            'menu.reports-absences-delays' => "Rapports des présences — État de pointage (arrivées – départs)",
            'menu.reports-custom-presence' => "Rapports des présences — Rapport d'assiduité et de ponctualité",
        ];
    }

    public function up(): void
    {
        foreach (self::submenuPermissions() as $name => $label) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', array_keys(self::submenuPermissions()))
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
