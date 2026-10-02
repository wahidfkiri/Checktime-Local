<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Permission dédiée à l'éditeur du contenu des emails de rapports
     * (/settings/email-templates). Comme menu.report-templates, elle permet de
     * donner accès à cette seule sous-page sans ouvrir tous les Paramètres ;
     * menu.settings continue de donner accès à tout.
     */
    public static function permissions(): array
    {
        return [
            'menu.email-templates' => 'Paramètres — Contenu des emails',
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
