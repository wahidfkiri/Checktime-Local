<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE employee_schedules CHANGE specific_date schedule_date DATE NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE employee_schedules CHANGE schedule_date specific_date DATE NULL');
    }
};
