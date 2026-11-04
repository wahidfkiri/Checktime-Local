<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Permission du sous-menu « Autre » (Gestion des autorisations) :
     * régularisation manuelle de pointages.
     */
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'menu.regularizations', 'guard_name' => 'web']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'menu.regularizations')->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
