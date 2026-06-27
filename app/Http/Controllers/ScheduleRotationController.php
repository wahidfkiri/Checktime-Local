<?php

namespace App\Http\Controllers;

use App\Models\ScheduleRotation;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ScheduleRotationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ScheduleRotation::with([
                'employee:id,emp_code,first_name,last_name,dept_name,area_name',
                'employee.department:id,name',
                'employee.area:id,name'
            ]);
            
            // Filtres
            if ($request->filled('employee_filter')) {
                $query->whereHas('employee', function($q) use ($request) {
                    $q->where('code', 'like', '%' . $request->employee_filter . '%')
                      ->orWhere('first_name', 'like', '%' . $request->employee_filter . '%')
                      ->orWhere('last_name', 'like', '%' . $request->employee_filter . '%');
                });
            }
            
            if ($request->filled('type_filter')) {
                $query->where('rotation_type', $request->type_filter);
            }
            
            if ($request->filled('status_filter')) {
                $query->where('is_active', $request->status_filter);
            }
            
            if ($request->filled('date_filter')) {
                $query->whereDate('start_datetime', '<=', $request->date_filter)
                      ->whereDate('end_datetime', '>=', $request->date_filter);
            }
            
            return datatables()->of($query)
                ->addIndexColumn()
                ->addColumn('employee_name', function($row) {
                    return $row->employee->first_name ?? 'N/A';
                })
                ->addColumn('matricule', function($row) {
                    return $row->employee->code ?? 'N/A';
                })
                ->addColumn('department', function($row) {
                    return $row->employee->dept_name ?? 'N/A';
                })
                ->addColumn('zone', function($row) {
                    return $row->employee->area_name ?? 'N/A';
                })
                ->addColumn('formatted_period', function($row) {
                    $start = Carbon::parse($row->start_datetime);
                    $end = Carbon::parse($row->end_datetime);
                    return $start->format('d/m/Y H:i') . ' - ' . $end->format('d/m/Y H:i');
                })
                ->addColumn('duration', function($row) {
                    return $row->work_hours . 'h travail / ' . $row->rest_hours . 'h repos';
                })
                ->addColumn('status_badge', function($row) {
                    $status = $row->is_active ? 'success' : 'danger';
                    $text = $row->is_active ? 'Actif' : 'Inactif';
                    return '<span class="badge bg-' . $status . '">' . $text . '</span>';
                })
                ->addColumn('recurring_badge', function($row) {
                    $status = $row->is_recurring ? 'info' : 'secondary';
                    $text = $row->is_recurring ? 'Récurrent' : 'Unique';
                    return '<span class="badge bg-' . $status . '">' . $text . '</span>';
                })
                ->addColumn('actions', function($row) {
                    return '
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-warning edit-rotation-btn" 
                                data-id="' . $row->id . '">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger delete-rotation-btn" 
                                data-id="' . $row->id . '" 
                                data-employee="' . ($row->employee->first_name ?? '') . '">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['status_badge', 'recurring_badge', 'actions'])
                ->make(true);
        }
        
        $employees = Employee::orderBy('first_name')->get();
            
        return view('rotations.index', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'rotation_type' => 'required|string|in:24_48,24_72,12_12,custom',
                'start_datetime' => 'required|date',
                'end_datetime' => 'required|date|after:start_datetime',
                'work_hours' => 'required|integer|min:1|max:168',
                'rest_hours' => 'required|integer|min:1|max:168',
                'is_recurring' => 'boolean',
                'is_active' => 'boolean',
                'description' => 'nullable|string'
            ]);
            
            $validated['is_recurring'] = $request->has('is_recurring');
            $validated['is_active'] = $request->has('is_active', true);
            
            // Si récurrent, calculer la date de fin de récurrence
            if ($validated['is_recurring'] && $request->filled('recurrence_end_date')) {
                $validated['recurrence_end_date'] = $request->recurrence_end_date;
            }
            
            ScheduleRotation::create($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Rotation créée avec succès.'
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ScheduleRotation $scheduleRotation)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $scheduleRotation
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ScheduleRotation $scheduleRotation)
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'rotation_type' => 'required|string|in:24_48,24_72,12_12,custom',
                'start_datetime' => 'required|date',
                'end_datetime' => 'required|date|after:start_datetime',
                'work_hours' => 'required|integer|min:1|max:168',
                'rest_hours' => 'required|integer|min:1|max:168',
                'is_recurring' => 'boolean',
                'is_active' => 'boolean',
                'description' => 'nullable|string'
            ]);
            
            $validated['is_recurring'] = $request->has('is_recurring');
            $validated['is_active'] = $request->has('is_active');
            
            $scheduleRotation->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Rotation modifiée avec succès.'
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ScheduleRotation $scheduleRotation)
    {
        try {
            $scheduleRotation->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Rotation supprimée avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer les prochaines rotations
     */
    public function generateNextRotations(Request $request)
    {
        try {
            $rotations = ScheduleRotation::where('is_active', true)
                ->where('is_recurring', true)
                ->where('recurrence_end_date', '>', now())
                ->get();
            
            $generated = 0;
            
            foreach ($rotations as $rotation) {
                $nextStart = Carbon::parse($rotation->end_datetime)->addHours($rotation->rest_hours);
                $nextEnd = $nextStart->copy()->addHours($rotation->work_hours);
                
                // Vérifier si la prochaine rotation existe déjà
                $exists = ScheduleRotation::where('employee_id', $rotation->employee_id)
                    ->where('start_datetime', $nextStart)
                    ->exists();
                
                if (!$exists && $nextStart <= $rotation->recurrence_end_date) {
                    ScheduleRotation::create([
                        'employee_id' => $rotation->employee_id,
                        'rotation_type' => $rotation->rotation_type,
                        'start_datetime' => $nextStart,
                        'end_datetime' => $nextEnd,
                        'work_hours' => $rotation->work_hours,
                        'rest_hours' => $rotation->rest_hours,
                        'is_recurring' => $rotation->is_recurring,
                        'recurrence_end_date' => $rotation->recurrence_end_date,
                        'description' => $rotation->description,
                        'is_active' => true
                    ]);
                    
                    $generated++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => $generated . ' nouvelle(s) rotation(s) générée(s).'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }
}