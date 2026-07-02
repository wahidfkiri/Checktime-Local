<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE daily_attendance MODIFY COLUMN status ENUM(
            'PRESENT','ABSENT','LATE','EARLY_LEAVE','HALF_DAY','OVERTIME','SHORT_WORK',
            'LEAVE','IRREGULAR','MULTIPLE_PUNCHES',
            'normal','retard','depart_anticipe','absence','conge','permission'
        ) NOT NULL DEFAULT 'normal'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE daily_attendance MODIFY COLUMN status ENUM(
            'normal','retard','depart_anticipe','absence','conge','permission'
        ) NOT NULL DEFAULT 'normal'");
    }
};
