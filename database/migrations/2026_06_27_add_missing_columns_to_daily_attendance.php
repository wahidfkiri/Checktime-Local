<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_attendance', function (Blueprint $table) {
            $table->string('emp_code')->nullable()->after('employee_id');
            $table->integer('total_punches')->default(0)->after('overtime_minutes');
            $table->text('punch_times')->nullable()->after('total_punches');
            $table->decimal('work_hours', 5, 2)->default(0)->after('working_hours');
            $table->decimal('break_hours', 5, 2)->default(0)->after('work_hours');
            $table->decimal('effective_hours', 5, 2)->default(0)->after('break_hours');
            $table->decimal('overtime_hours', 5, 2)->default(0)->after('effective_hours');
            $table->boolean('is_late')->default(false)->after('late_minutes');
            $table->boolean('is_early_leave')->default(false)->after('early_leave_minutes');
            $table->integer('early_minutes')->default(0)->after('is_early_leave');
            $table->boolean('is_overtime')->default(false)->after('is_early_leave');
            $table->boolean('is_short_work')->default(false)->after('is_overtime');
            $table->decimal('short_hours', 5, 2)->default(0)->after('is_short_work');
            $table->boolean('has_multiple_punches')->default(false)->after('short_hours');
            $table->integer('multiple_punches_count')->default(0)->after('has_multiple_punches');
            $table->text('raw_data')->nullable()->after('multiple_punches_count');
            $table->text('notes')->nullable()->after('raw_data');
        });
    }

    public function down(): void
    {
        Schema::table('daily_attendance', function (Blueprint $table) {
            $table->dropColumn([
                'emp_code', 'total_punches', 'punch_times', 'work_hours',
                'break_hours', 'effective_hours', 'overtime_hours',
                'is_late', 'is_early_leave', 'early_minutes', 'is_overtime',
                'is_short_work', 'short_hours', 'has_multiple_punches',
                'multiple_punches_count', 'raw_data', 'notes'
            ]);
        });
    }
};
