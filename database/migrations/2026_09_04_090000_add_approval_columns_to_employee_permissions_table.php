<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ajoute les colonnes d'approbation utilisées par EmployeePermissionController
     * (création, modification, approve, reject) mais jamais créées en base.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_permissions', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('duration_minutes');
                $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('employee_permissions', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('employee_permissions', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_permissions', function (Blueprint $table) {
            if (Schema::hasColumn('employee_permissions', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            }

            foreach (['approved_at', 'rejection_reason'] as $column) {
                if (Schema::hasColumn('employee_permissions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
