<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stocke, par commande de rapport, le HTML personnalisé édité via l'éditeur
 * Vvveb (bouton "Template" de /settings). Une ligne absente ou avec `html`
 * vide = aucune personnalisation, l'email par défaut (vues Blade existantes)
 * est utilisé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('command')->unique();
            $table->longText('html')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
