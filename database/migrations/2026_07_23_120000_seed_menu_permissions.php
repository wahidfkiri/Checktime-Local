<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Une permission par lien de menu (voir resources/views/components/side-bar.blade.php).
     * Le nom de page décrit ce que verrait un administrateur dans le formulaire d'assignation.
     */
    public static function menuPermissions(): array
    {
        return [
            'menu.employees' => 'Employés — Liste des employés',
            'menu.departments' => 'Employés — Départements',
            'menu.areas' => 'Employés — Zones',
            'menu.work-hours' => 'Plannings — Types d\'horaires',
            'menu.employee-schedules' => 'Plannings — Plannings employés',
            'menu.schedules' => 'Plannings — Calendrier',
            'menu.employee-permissions' => 'Autorisations — Permissions',
            'menu.missions' => 'Autorisations — Missions',
            'menu.leaves' => 'Autorisations — Congés',
            'menu.daily-attendance' => 'Historique des pointages',
            'menu.reports' => 'Rapports des présences',
            'menu.devices' => 'Appareils',
            'menu.settings' => 'Paramètres',
        ];
    }

    public function up(): void
    {
        foreach (self::menuPermissions() as $name => $label) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Rôle admin : garanti d'exister (déjà créé par l'installateur en temps normal).
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', array_keys(self::menuPermissions()))
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
