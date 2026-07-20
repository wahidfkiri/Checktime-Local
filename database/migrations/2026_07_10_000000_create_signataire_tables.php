<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Deux tables pour le cartouche de signatures des rapports :
     *  - signataire_postes : les postes / colonnes (ex. Rédacteur, Vérificateur, Approbateur)
     *  - signataires       : les responsables rattachés à un poste (Nom complet + fonction).
     *    Un poste possède plusieurs responsables.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('signataire_postes', function (Blueprint $table) {
            $table->id();
            $table->string('name');            // Ex : Rédacteur, Vérificateur, Approbateur
            $table->unsignedInteger('position')->default(0); // Ordre d'affichage des colonnes
            $table->timestamps();

            $table->index('position');
        });

        Schema::create('signataires', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('poste_id');
            $table->string('full_name');       // Nom et prénom
            $table->string('fonction')->nullable();
            $table->unsignedInteger('position')->default(0); // Ordre au sein du poste
            $table->timestamps();

            $table->index('poste_id');
            $table->foreign('poste_id')->references('id')->on('signataire_postes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('signataires');
        Schema::dropIfExists('signataire_postes');
    }
};
