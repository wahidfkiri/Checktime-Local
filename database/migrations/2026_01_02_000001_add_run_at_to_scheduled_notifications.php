<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_notifications', function (Blueprint $table) {
            // Date+heure exacte pour une exécution unique (frequency = "once")
            $table->timestamp('run_at')->nullable()->after('cron_expression');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_notifications', function (Blueprint $table) {
            $table->dropColumn('run_at');
        });
    }
};
