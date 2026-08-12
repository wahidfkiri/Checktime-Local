<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AttendanceSyncService::updateEmployeeDailySummary() écrit déjà
     * processed_at (horodatage du traitement) depuis sa création, mais la
     * colonne n'a jamais été créée en base : chaque synchronisation de
     * pointages échouait avec "Unknown column 'processed_at'" juste après
     * avoir enregistré le résumé du jour, empêchant les transactions
     * d'être marquées comme traitées.
     */
    public function up(): void
    {
        Schema::table('attendance_transactions', function (Blueprint $table) {
            $table->timestamp('processed_at')->nullable()->after('processed');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_transactions', function (Blueprint $table) {
            $table->dropColumn('processed_at');
        });
    }
};
