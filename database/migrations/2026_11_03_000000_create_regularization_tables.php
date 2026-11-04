<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regularization_causes', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique();
            // Causes par défaut : non supprimables.
            $table->boolean('is_protected')->default(false);
            $table->timestamps();
        });

        // Journal des régularisations (la présence elle-même est écrite dans
        // daily_attendance, lue par tous les rapports).
        Schema::create('attendance_regularizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cause_id')->constrained('regularization_causes')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date');
            $table->time('check_in');
            $table->time('check_out');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_regularizations');
        Schema::dropIfExists('regularization_causes');
    }
};
