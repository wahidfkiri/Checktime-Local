<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Permission dédiée à la page de gestion des modèles d'export PDF
     * (/settings/modeles-rapport). menu.settings reste un raccourci « accès
     * à toutes les pages de Paramètres » — elle n'est pas remplacée, cette
     * permission permet juste de donner accès à cette seule sous-page.
     */
    public static function permissions(): array
    {
        return [
            'menu.report-templates' => 'Paramètres — Modèles d\'export',
        ];
    }

    public function up(): void
    {
        foreach (self::permissions() as $name => $label) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', array_keys(self::permissions()))
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
