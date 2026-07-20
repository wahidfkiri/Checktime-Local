<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ajoute une plage de dates (date_debut / date_fin) aux permissions employés.
     * Le champ `date` existant est conservé et gardé synchronisé sur date_debut
     * pour la rétro-compatibilité des consommateurs qui le lisent encore.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_permissions', function (Blueprint $table) {
            $table->date('date_debut')->nullable()->after('employee_id');
            $table->date('date_fin')->nullable()->after('date_debut');
            $table->index(['date_debut', 'date_fin']);
        });

        // Backfill : pour les permissions existantes, la plage vaut la date unique.
        DB::table('employee_permissions')->update([
            'date_debut' => DB::raw('`date`'),
            'date_fin'   => DB::raw('`date`'),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_permissions', function (Blueprint $table) {
            $table->dropIndex(['date_debut', 'date_fin']);
            $table->dropColumn(['date_debut', 'date_fin']);
        });
    }
};
