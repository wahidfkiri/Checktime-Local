<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Permission dédiée à la sous-page « Tableau de Suivi de la Ponctualité »
     * du menu Rapports des Présences. menu.reports reste le raccourci
     * « accès à tous les rapports ».
     */
    public static function permissions(): array
    {
        return [
            'menu.reports-suivi-ponctualite' => 'Rapports — Tableau de Suivi de la Ponctualité',
        ];
    }

    public function up(): void
    {
        foreach (array_keys(self::permissions()) as $name) {
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
