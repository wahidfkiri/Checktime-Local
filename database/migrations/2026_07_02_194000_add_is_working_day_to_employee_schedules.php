<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('employee_schedules', 'is_working_day')) {
            Schema::table('employee_schedules', function (Blueprint $table) {
                $table->boolean('is_working_day')->default(true)->after('day_of_week');
            });
        }
        if (!Schema::hasColumn('employee_schedules', 'work_hour_type_id')) {
            Schema::table('employee_schedules', function (Blueprint $table) {
                $table->foreignId('work_hour_type_id')->nullable()->constrained('work_hour_types')->nullOnDelete()->after('schedule_type');
            });
        }
    }

    public function down(): void
    {
        Schema::table('employee_schedules', function (Blueprint $table) {
            $table->dropColumn(['is_working_day', 'work_hour_type_id']);
        });
    }
};
