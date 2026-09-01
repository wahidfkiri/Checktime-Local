<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename');                 // nom du fichier .zip
            $table->string('path');                     // chemin relatif (disque local)
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('tables_count')->default(0);
            $table->unsignedBigInteger('rows_count')->default(0);
            $table->string('status')->default('completed'); // completed | failed
            $table->text('error')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // user id
            $table->string('created_by_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_backups');
    }
};
