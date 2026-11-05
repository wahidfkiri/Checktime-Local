<?php

namespace App\Models\Concerns;

use App\Models\Employee;

/**
 * Pour les modèles rattachés à un employé (pointages, congés, missions…) :
 * restreint aux lignes dont l'employé est actif. Les employés désactivés
 * (employees.status = 'inactive') sont masqués de tous les rapports,
 * statistiques et synchronisations.
 */
trait FiltersActiveEmployees
{
    public function scopeForActiveEmployees($query)
    {
        $table = $query->getModel()->getTable();

        return $query->whereIn($table . '.employee_id', Employee::active()->select('employees.id'));
    }

    /**
     * Pour les RAPPORTS portant sur une période : employés actifs + employés
     * désactivés jusqu'à leur date de désactivation (voir Employee::activeDuring()).
     */
    public function scopeForReportEmployees($query, $periodStart)
    {
        $table = $query->getModel()->getTable();

        return $query->whereIn($table . '.employee_id', Employee::activeDuring($periodStart)->select('employees.id'));
    }
}
