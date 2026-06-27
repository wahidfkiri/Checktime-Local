<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_attendance_id')->nullable()->constrained('daily_attendance')->onDelete('set null');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('emp_code')->nullable();
            $table->string('transaction_id')->nullable();
            $table->dateTime('punch_time')->nullable();
            $table->string('punch_state')->nullable();
            $table->string('verify_type')->nullable();
            $table->string('work_code')->nullable();
            $table->string('terminal_sn')->nullable();
            $table->string('terminal_alias')->nullable();
            $table->string('area_alias')->nullable();
            $table->decimal('longitude', 10, 2)->nullable();
            $table->decimal('latitude', 10, 2)->nullable();
            $table->text('gps_location')->nullable();
            $table->string('mobile')->nullable();
            $table->string('source')->nullable();
            $table->string('purpose')->nullable();
            $table->string('crc')->nullable();
            $table->boolean('is_attendance')->default(false);
            $table->string('reserved')->nullable();
            $table->dateTime('upload_time')->nullable();
            $table->string('sync_status')->nullable();
            $table->dateTime('sync_time')->nullable();
            $table->decimal('temperature', 4, 2)->nullable();
            $table->boolean('mask_flag')->default(false);
            $table->string('company')->nullable();
            $table->string('terminal')->nullable();
            $table->boolean('processed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_transactions');
    }
};
