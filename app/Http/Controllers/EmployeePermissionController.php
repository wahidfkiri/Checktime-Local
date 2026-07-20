<?php

namespace App\Http\Controllers;

use App\Models\EmployeePermission;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use PDF;

class EmployeePermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = EmployeePermission::with(['employee'])
                ->select('employee_permissions.*');

            return DataTables::eloquent($query)
                ->addColumn('employee_name', function($permission) {
                    return $permission->employee->first_name . ' ' . $permission->employee->last_name;
                })
                ->addColumn('date_formatted', function($permission) {
                    $start = $permission->getEffectiveStartDate();
                    $end = $permission->getEffectiveEndDate();
                    $startStr = Carbon::parse($start)->format('d/m/Y');
                    $endStr = $end ? Carbon::parse($end)->format('d/m/Y') : $startStr;

                    // Une seule journée : afficher une seule date ; sinon la plage.
                    return $startStr === $endStr
                        ? $startStr
                        : $startStr . ' → ' . $endStr;
                })
                ->addColumn('time_range', function($permission) {
                    if ($permission->start_time && $permission->end_time) {
                        return Carbon::parse($permission->start_time)->format('H:i') . ' - ' . 
                               Carbon::parse($permission->end_time)->format('H:i');
                    }
                    return 'Toute la journée';
                })
                ->addColumn('status_badge', function($permission) {
                    $badgeClass = [
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'canceled' => 'secondary'
                    ][$permission->status] ?? 'secondary';
                    
                    $statusText = [
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                        'canceled' => 'Annulé'
                    ][$permission->status] ?? $permission->status;
                    
                    return '<span class="badge bg-' . $badgeClass . '">' . $statusText . '</span>';
                })
                ->addColumn('duration_formatted', function($permission) {
                    if ($permission->duration_minutes) {
                        $hours = floor($permission->duration_minutes / 60);
                        $minutes = $permission->duration_minutes % 60;
                        
                        if ($hours > 0) {
                            return $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'min' : '');
                        }
                        return $minutes . ' min';
                    }
                    return 'N/A';
                })
                ->addColumn('actions', function($permission) {
                    $buttons = '<div class="btn-group" role="group">';
                    
                    if ($permission->status == 'pending') {
                        $buttons .= '<button class="btn btn-sm btn-success approve-btn" data-id="' . $permission->id . '" title="Approuver">
                            <i class="bi bi-check-circle"></i>
                        </button>';
                        
                        $buttons .= '<button class="btn btn-sm btn-danger reject-btn" data-id="' . $permission->id . '" title="Rejeter">
                            <i class="bi bi-x-circle"></i>
                        </button>';
                    }
                    
                    $buttons .= '<button class="btn btn-sm btn-warning edit-btn" data-id="' . $permission->id . '" title="Modifier">
                        <i class="bi bi-pencil"></i>
                    </button>';
                    
                    $buttons .= '<button class="btn btn-sm btn-danger delete-btn" data-id="' . $permission->id . '" title="Supprimer">
                        <i class="bi bi-trash"></i>
                    </button>';
                    
                    $buttons .= '</div>';
                    return $buttons;
                })
                ->rawColumns(['status_badge', 'actions'])
                ->toJson();
        }

        $employees = Employee::orderBy('first_name')->get();

        return view('employee-permissions.index', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after_or_equal:start_time',
            'raison' => 'required|string|min:10|max:1000',
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
        ]);

        try {
            DB::beginTransaction();

            $permission = EmployeePermission::create([
                'employee_id' => $request->employee_id,
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
                // `date` conservé (= début) pour la rétro-compatibilité.
                'date' => $request->date_debut,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'raison' => $request->raison,
                'duration_minutes' => $request->duration_minutes ??
                    ($request->start_time && $request->end_time ?
                        Carbon::parse($request->start_time)->diffInMinutes(Carbon::parse($request->end_time)) : null),
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permission créée avec succès',
                'data' => $permission
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(EmployeePermission $employeePermission)
    {
        return response()->json([
            'success' => true,
            'data' => $employeePermission->load(['employee'])
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeePermission $employeePermission)
    {
        return response()->json([
            'success' => true,
            'data' => $employeePermission
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeePermission $employeePermission)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after_or_equal:start_time',
            'raison' => 'required|string|min:10|max:1000',
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
        ]);

        try {
            DB::beginTransaction();

            $employeePermission->update([
                'employee_id' => $request->employee_id,
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
                // `date` conservé (= début) pour la rétro-compatibilité.
                'date' => $request->date_debut,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'raison' => $request->raison,
                'duration_minutes' => $request->duration_minutes ??
                    ($request->start_time && $request->end_time ?
                        Carbon::parse($request->start_time)->diffInMinutes(Carbon::parse($request->end_time)) : null),
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'rejection_reason' => null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permission modifiée avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeePermission $employeePermission)
    {
        try {
            $employeePermission->delete();

            return response()->json([
                'success' => true,
                'message' => 'Permission supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approuver une permission
     */
    public function approve(Request $request, EmployeePermission $employeePermission)
    {
        if ($employeePermission->status != 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cette permission ne peut plus être approuvée'
            ], 400);
        }

        try {
            $employeePermission->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission approuvée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'approbation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rejeter une permission
     */
    public function reject(Request $request, EmployeePermission $employeePermission)
    {
        if ($employeePermission->status != 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cette permission ne peut plus être rejetée'
            ], 400);
        }

        $request->validate([
            'rejection_reason' => 'nullable|string|max:500'
        ]);

        try {
            $employeePermission->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission rejetée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des permissions
     */
    public function statistics(Request $request)
    {
        $query = EmployeePermission::query();

        if ($request->start_date) {
            $query->whereRaw('COALESCE(date_fin, `date`) >= ?', [$request->start_date]);
        }

        if ($request->end_date) {
            $query->whereRaw('COALESCE(date_debut, `date`) <= ?', [$request->end_date]);
        }

        $total = $query->count();
        $pending = $query->where('status', 'pending')->count();
        $approved = $query->where('status', 'approved')->count();
        $rejected = $query->where('status', 'rejected')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected,
                'pending_percentage' => $total > 0 ? round(($pending / $total) * 100, 2) : 0,
                'approved_percentage' => $total > 0 ? round(($approved / $total) * 100, 2) : 0,
                'rejected_percentage' => $total > 0 ? round(($rejected / $total) * 100, 2) : 0,
            ]
        ]);
    }

    /**
     * Récupérer les permissions par employé
     */
    public function byEmployee(Employee $employee)
    {
        $permissions = EmployeePermission::where('employee_id', $employee->id)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }

    /**
     * Export des permissions
     */
    public function export(Request $request)
    {
        try {
            $query = EmployeePermission::with(['employee']);
            
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }
            
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->filled('date')) {
                $query->overlappingPeriod($request->date, $request->date);
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->overlappingPeriod($request->start_date, $request->end_date);
            }
            
            $permissions = $query->orderBy('date', 'desc')->get();
            
            $data = [
                'permissions' => $permissions,
                'export_date' => now()->format('d/m/Y H:i'),
                'total' => $permissions->count(),
                'filters' => $this->getAppliedFilters($request)
            ];
            
            $data['statistics'] = [
                'total' => $permissions->count(),
                'pending' => $permissions->where('status', 'pending')->count(),
                'approved' => $permissions->where('status', 'approved')->count(),
                'rejected' => $permissions->where('status', 'rejected')->count(),
            ];
            
            if ($request->type == 'pdf') {
                $pdf = PDF::loadView('employee-permissions.exports.pdf', $data);
                return $pdf->download('permissions-' . date('Y-m-d') . '.pdf');
            } else {
                return response()->json([
                    'success' => true,
                    'data' => $permissions,
                    'count' => $permissions->count()
                ]);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les filtres appliqués
     */
    private function getAppliedFilters(Request $request)
    {
        $filters = [];
        
        if ($request->filled('employee_id')) {
            $employee = Employee::find($request->employee_id);
            $filters['employé'] = $employee ? $employee->first_name . ' ' . $employee->last_name : 'N/A';
        } else {
            $filters['employé'] = 'Tous les employés';
        }
        
        if ($request->filled('status')) {
            $statusText = [
                'pending' => 'En attente',
                'approved' => 'Approuvé',
                'rejected' => 'Rejeté',
                'canceled' => 'Annulé'
            ];
            $filters['statut'] = $statusText[$request->status] ?? $request->status;
        } else {
            $filters['statut'] = 'Tous';
        }
        
        if ($request->filled('date')) {
            $filters['date'] = Carbon::parse($request->date)->format('d/m/Y');
        } else {
            $filters['date'] = 'Toutes dates';
        }
        
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $filters['période'] = Carbon::parse($request->start_date)->format('d/m/Y') . 
                                 ' au ' . 
                                 Carbon::parse($request->end_date)->format('d/m/Y');
        }
        
        return $filters;
    }

    /**
     * Formater la durée
     */
    private function formatDuration($minutes)
    {
        if (!$minutes) return 'N/A';
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($hours > 0) {
            return $hours . 'h' . ($remainingMinutes > 0 ? ' ' . $remainingMinutes . 'min' : '');
        }
        return $remainingMinutes . ' min';
    }
}