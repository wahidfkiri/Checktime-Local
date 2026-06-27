<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pdf_export_params', function (Blueprint $table) {
            $table->id();
            $table->string('request_id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('emp_code');
            $table->timestamps();
            
            // IMPORTANT: Assurez-vous que request_id a la même longueur que dans pdf_exports
            $table->index('request_id');
            
            // La clé étrangère doit référencer une colonne avec le même type et la même longueur
            $table->foreign('request_id')
                  ->references('request_id')
                  ->on('pdf_exports')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pdf_export_params');
    }
};