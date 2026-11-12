<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * employees.first_name / area_name / dept_name sont chiffrés par le cast
     * `encrypted` du modèle Employee. Une valeur chiffrée fait environ 3 fois la
     * taille du texte clair (et dépasse 255 caractères dès ~60 caractères de
     * texte) : avec des colonnes VARCHAR(255), l'insertion de l'employé échouait
     * en « Data too long for column » et l'employé était silencieusement absent
     * après la synchronisation. TEXT lève cette limite.
     */
    public function up(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('ALTER TABLE `employees` MODIFY `first_name` TEXT NOT NULL');
        DB::statement('ALTER TABLE `employees` MODIFY `area_name` TEXT NULL');
        DB::statement('ALTER TABLE `employees` MODIFY `dept_name` TEXT NULL');
    }

    public function down(): void
    {
        // Pas de retour en arrière : réduire les colonnes tronquerait des données chiffrées.
    }
};
