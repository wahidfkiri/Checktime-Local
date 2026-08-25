<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modèles d'édition des rapports PDF : un modèle retient les colonnes qu'un
 * admin veut voir sortir sur le PDF, ainsi que quelques options de mise en
 * page. `report_key` permet d'étendre le mécanisme à d'autres rapports sans
 * nouvelle table.
 */
return new class extends Migration {
    public function up() {
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('report_key', 64)->default('presence-ponctualite');
            $table->string('name', 150);
            $table->string('description', 255)->nullable();

            // Clés de colonnes retenues, dans l'ordre d'affichage.
            $table->json('columns');
            // Orientation, ligne de totaux, cartouche de signatures.
            $table->json('options')->nullable();

            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['report_key', 'name'], 'report_templates_key_name_unique');
        });
    }

    public function down() {
        Schema::dropIfExists('report_templates');
    }
};
