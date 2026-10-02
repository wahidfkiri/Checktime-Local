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
}
