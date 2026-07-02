<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\WorkHourType;
use App\Models\EmployeeSchedule;
use App\Models\Department;
use App\Models\Zone as Area;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleAssignmentController extends Controller
{
    /**
     * Afficher le calendrier d'assignation
     */
    public function calendar(Request $request)
    {
        // Récupérer les données pour le calendrier
        $employees = Employee::with(['department', 'area'])
            ->orderBy('first_name')
            ->get();
            
        $workHourTypes = WorkHourType::where('is_active', true)
            ->get();
            
        $departments = Department::all();
        $areas = Area::all();
        
        // Date par défaut (semaine courante)
        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfWeek();
            
        $endDate = $startDate->copy()->addDays(6);
        
        return view('schedules.calendar', compact(
            'employees', 'workHourTypes', 'departments', 'areas', 'startDate', 'endDate'
        ));
    }

    /**
     * Formulaire d'affectation en masse
     */
    public function massAssignForm(Request $request)
    {
        $employees = Employee::with(['department', 'area'])
            ->orderBy('first_name')
            ->get();
            
        $workHourTypes = WorkHourType::where('is_active', true)
            ->get();
            
        $departments = Department::all();
        $areas = Area::all();
        
        return view('schedules.mass-assign', compact(
            'employees', 'workHourTypes', 'departments', 'areas'
        ));
    }

    /**
     * Récupérer les données d'une cellule du calendrier
     */
    public function getCellData(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'date' => 'required|date'
            ]);
            
            $date = Carbon::parse($validated['date']);
            $isWeekend = $date->isWeekend();
            
            // Vérifier si c'est un jour férié
            $isHoliday = false;
            if (class_exists('App\\Models\\Holiday')) {
                $isHoliday = \App\Models\Holiday::isHoliday($date);
            }
            
            // Récupérer le planning existant POUR CETTE DATE SPÉCIFIQUE
            $schedule = EmployeeSchedule::with('workHourType')
                ->where('employee_id', $validated['employee_id'])
                ->where('schedule_date', $validated['date'])
                ->first();
            
            // Si non trouvé, vérifier si la date est dans une plage
            if (!$schedule) {
                $schedule = EmployeeSchedule::with('workHourType')
                    ->where('employee_id', $validated['employee_id'])
                    ->where('start_date', '<=', $validated['date'])
                    ->where('end_date', '>=', $validated['date'])
                    ->first();
            }
            
            $data = [
                'is_weekend' => $isWeekend,
                'is_holiday' => $isHoliday,
                'schedule' => null,
                'date' => $date->format('Y-m-d'),
                'formatted_date' => $date->format('d/m/Y'),
                'day_name' => $date->locale('fr')->dayName
            ];
            
            if ($schedule) {
                // Récupérer les heures de travail
                $startTime = null;
                $endTime = null;
                $workHourTypeName = 'Personnalisé';
                $totalHours = 0;
                
                if ($schedule->workHourType) {
                    $workHourTypeName = $schedule->workHourType->name;
                    $startTime = date('H:i', strtotime($schedule->workHourType->start_time));
                    $endTime = date('H:i', strtotime($schedule->workHourType->end_time));
                    
                    // Calculer les heures totales
                    $start = strtotime($schedule->workHourType->start_time);
                    $end = strtotime($schedule->workHourType->end_time);
                    if ($schedule->workHourType->is_overnight && $end < $start) {
                        $end = strtotime($schedule->workHourType->end_time . ' +1 day');
                    }
                    $totalMinutes = ($end - $start) / 60;
                    $workMinutes = $totalMinutes - $schedule->workHourType->break_minutes;
                    $totalHours = number_format($workMinutes / 60, 2);
                } elseif ($schedule->start_time && $schedule->end_time) {
                    $startTime = date('H:i', strtotime($schedule->start_time));
                    $endTime = date('H:i', strtotime($schedule->end_time));
                }
                
                $data['schedule'] = [
                    'id' => $schedule->id,
                    'work_hour_type_id' => $schedule->work_hour_type_id,
                    'work_hour_type' => $workHourTypeName,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'total_hours' => $totalHours,
                    'schedule_type' => $schedule->schedule_type,
                    'notes' => $schedule->notes,
                    'is_working_day' => (bool)$schedule->is_working_day,
                    'is_active' => (bool)$schedule->is_active,
                    'start_date' => $schedule->start_date,
                    'end_date' => $schedule->end_date,
                    'is_in_range' => $schedule->start_date && $schedule->end_date,
                    'day_of_week' => $schedule->day_of_week
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            \Log::error('getCellData Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les plannings pour une période
     */
    public function getSchedules(Request $request)
    {
        try {
            $query = EmployeeSchedule::with(['employee.department', 'employee.area', 'workHourType']);
            
            $schedules = $query->get()->map(function($schedule) {
                return [
                    'id' => $schedule->id,
                    'employee_id' => $schedule->employee_id,
                    'employee_name' => $schedule->employee->full_name,
                    'matricule' => $schedule->employee->emp_code,
                    'schedule_date' => $schedule->schedule_date,
                    'work_hour_type' => $schedule->workHourType->name ?? null,
                    'start_time' => $schedule->workHourType->start_time ?? null,
                    'end_time' => $schedule->workHourType->end_time ?? null,
                    'is_working_day' => $schedule->is_working_day,
                    'notes' => $schedule->notes
                ];
            });
            
            return response()->json([
                'success' => true,
                'schedules' => $schedules
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assigner un horaire à un employé
     */
    public function assignSchedule(Request $request)
    {
        try {
            DB::beginTransaction();
            
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'work_hour_type_id' => 'required|exists:work_hour_types,id',
                'date' => 'required|date',
                'is_working_day' => 'boolean',
                'notes' => 'nullable|string'
            ]);
            
            // Vérifier que l'employé existe
            $employee = Employee::find($validated['employee_id']);
            
            // Vérifier que le type d'horaire existe
            $workHourType = WorkHourType::find($validated['work_hour_type_id']);
            
            $date = Carbon::parse($validated['date']);
            
            // Vérifier si c'est un jour férié
            if (Holiday::isHoliday($date)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette date est un jour férié.'
                ], 422);
            }
            
            // Créer ou mettre à jour le planning
            $schedule = EmployeeSchedule::updateOrCreate(
                [
                    'employee_id' => $validated['employee_id'],
                    'schedule_date' => $validated['date']
                ],
                [
                    'work_hour_type_id' => $validated['work_hour_type_id'],
                    'schedule_type' => 'planifie',
                    'day_of_week' => $date->dayOfWeekIso,
                    'is_working_day' => $request->has('is_working_day', true),
                    'notes' => $validated['notes'] ?? null
                ]
            );
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Horaire assigné avec succès.',
                'schedule' => $schedule
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affectation en masse
     */
    public function massAssign(Request $request)
    {
        try {
            DB::beginTransaction();
            
            $validated = $request->validate([
                'employee_ids' => 'required|array',
                'employee_ids.*' => 'exists:employees,id',
                'work_hour_type_id' => 'required|exists:work_hour_types,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'days_of_week' => 'nullable|array',
                'days_of_week.*' => 'in:1,2,3,4,5,6,7',
                'specific_dates' => 'nullable|array',
                'specific_dates.*' => 'date',
                'is_working_day' => 'boolean',
                'override_existing' => 'boolean',
                'include_weekends' => 'boolean',
                'notes' => 'nullable|string'
            ]);
            
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $created = 0;
            $updated = 0;
            $skipped = 0;
            
            foreach ($validated['employee_ids'] as $employeeId) {
                // Déterminer les dates à assigner
                $datesToAssign = [];
                
                if (!empty($validated['specific_dates'])) {
                    // Dates spécifiques
                    foreach ($validated['specific_dates'] as $dateStr) {
                        $date = Carbon::parse($dateStr);
                        if (!$validated['include_weekends'] && $date->isWeekend()) {
                            continue;
                        }
                        $datesToAssign[] = $date->toDateString();
                    }
                } else {
                    // Période avec filtres par jour
                    $currentDate = $startDate->copy();
                    while ($currentDate <= $endDate) {
                        $dayOfWeek = $currentDate->dayOfWeekIso;
                        
                        // Vérifier les weekends
                        if (!$validated['include_weekends'] && $currentDate->isWeekend()) {
                            $currentDate->addDay();
                            continue;
                        }
                        
                        // Vérifier les jours de semaine
                        if (!empty($validated['days_of_week']) && 
                            !in_array($dayOfWeek, $validated['days_of_week'])) {
                            $currentDate->addDay();
                            continue;
                        }
                        
                        // Vérifier les jours fériés
                        if (Holiday::isHoliday($currentDate)) {
                            $currentDate->addDay();
                            continue;
                        }
                        
                        $datesToAssign[] = $currentDate->toDateString();
                        $currentDate->addDay();
                    }
                }
                
                // Assigner les dates
                foreach ($datesToAssign as $date) {
                    $existingSchedule = EmployeeSchedule::where('employee_id', $employeeId)
                        ->where('schedule_date', $date)
                        ->first();
                    
                    if ($existingSchedule) {
                        if ($validated['override_existing'] ?? false) {
                            $existingSchedule->update([
                                'work_hour_type_id' => $validated['work_hour_type_id'],
                                'is_working_day' => $validated['is_working_day'] ?? true,
                                'notes' => $validated['notes'] ?? null
                            ]);
                            $updated++;
                        } else {
                            $skipped++;
                            continue;
                        }
                    } else {
                        EmployeeSchedule::create([
                            'employee_id' => $employeeId,
                            'work_hour_type_id' => $validated['work_hour_type_id'],
                            'schedule_type' => 'fixe',
                            'schedule_date' => $date,
                            'day_of_week' => Carbon::parse($date)->dayOfWeekIso,
                            'is_working_day' => $validated['is_working_day'] ?? true,
                            'notes' => $validated['notes'] ?? null
                        ]);
                        $created++;
                    }
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Affectation en masse terminée.',
                'details' => [
                    'created' => $created,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'total' => $created + $updated
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un horaire assigné
     */
    public function removeSchedule(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'date' => 'required|date'
            ]);
            
            $schedule = EmployeeSchedule::where('employee_id', $validated['employee_id'])
                ->where('schedule_date', $validated['date'])
                ->first();
                
            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Planning non trouvé.'
                ], 404);
            }
            
            $schedule->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Horaire supprimé avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporter les plannings en PDF
     */
    public function export(Request $request)
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date',
                'department_id' => 'nullable|exists:departments,id',
                'area_id' => 'nullable|exists:areas,id',
                'format' => 'required|in:pdf,excel,csv'
            ]);
            
            $query = Employee::with(['department', 'area'])
                ->where('is_active', true);
            
            if ($request->filled('department_id')) {
                $query->where('department_id', $request->department_id);
            }
            
            if ($request->filled('area_id')) {
                $query->where('area_id', $request->area_id);
            }
            
            $employees = $query->orderBy('first_name')->get();
            
            // Récupérer les plannings
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            
            $client = \App\Models\Setting::company();
            
            $data = [
                'employees' => [],
                'client' => $client,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'export_date' => now()
            ];
            
            foreach ($employees as $employee) {
                $schedules = EmployeeSchedule::with('workHourType')
                    ->where('employee_id', $employee->id)
                    ->whereBetween('schedule_date', [$startDate, $endDate])
                    ->orderBy('schedule_date')
                    ->get();
                    
                if ($schedules->isEmpty() && !$request->has('include_empty')) {
                    continue;
                }
                
                $employeeData = [
                    'employee' => $employee,
                    'schedules' => $schedules,
                    'total_working_days' => $schedules->where('is_working_day', true)->count(),
                    'total_hours' => $schedules->sum(function($schedule) {
                        if ($schedule->workHourType) {
                            $start = strtotime($schedule->workHourType->start_time);
                            $end = strtotime($schedule->workHourType->end_time);
                            if ($schedule->workHourType->is_overnight && $end < $start) {
                                $end = strtotime($schedule->workHourType->end_time . ' +1 day');
                            }
                            return (($end - $start) / 3600) - ($schedule->workHourType->break_minutes / 60);
                        }
                        return 0;
                    })
                ];
                
                $data['employees'][] = $employeeData;
            }
            
            if ($validated['format'] == 'pdf') {
                // Utiliser DomPDF
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('schedules.exports.pdf', $data);
                return $pdf->download('plannings-' . $startDate->format('Y-m-d') . '-to-' . $endDate->format('Y-m-d') . '.pdf');
            } elseif ($validated['format'] == 'csv') {
                // Export CSV
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="plannings-' . $startDate->format('Y-m-d') . '.csv"',
                ];
                
                $callback = function() use ($data) {
                    $file = fopen('php://output', 'w');
                    
                    // En-têtes
                    fputcsv($file, [
                        'Matricule', 'Employé', 'Département', 'Zone', 
                        'Date', 'Jour', 'Horaire', 'Heure début', 'Heure fin',
                        'Pause (min)', 'Jour travaillé', 'Notes'
                    ]);
                    
                    // Données
                    foreach ($data['employees'] as $employeeData) {
                        foreach ($employeeData['schedules'] as $schedule) {
                            fputcsv($file, [
                                $employeeData['employee']->emp_code,
                                $employeeData['employee']->full_name,
                                $employeeData['employee']->department->name ?? '',
                                $employeeData['employee']->area->name ?? '',
                                $schedule->schedule_date,
                                Carbon::parse($schedule->schedule_date)->locale('fr')->dayName,
                                $schedule->workHourType->name ?? '',
                                $schedule->workHourType->start_time ?? '',
                                $schedule->workHourType->end_time ?? '',
                                $schedule->workHourType->break_minutes ?? 0,
                                $schedule->is_working_day ? 'Oui' : 'Non',
                                $schedule->notes ?? ''
                            ]);
                        }
                    }
                    
                    fclose($file);
                };
                
                return response()->stream($callback, 200, $headers);
            } else {
                // Export Excel (via package maatwebsite/excel si installé)
                return response()->json([
                    'success' => true,
                    'data' => $data
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
     * Générer un planning mensuel automatique
     */
    public function generateMonthly(Request $request)
    {
        try {
            DB::beginTransaction();
            
            $validated = $request->validate([
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:2023|max:2100',
                'employee_ids' => 'required|array',
                'employee_ids.*' => 'exists:employees,id',
                'default_work_hour_type_id' => 'required|exists:work_hour_types,id',
                'include_weekends' => 'boolean'
            ]);
            
            $startDate = Carbon::create($validated['year'], $validated['month'], 1);
            $endDate = $startDate->copy()->endOfMonth();
            $created = 0;
            
            foreach ($validated['employee_ids'] as $employeeId) {
                $currentDate = $startDate->copy();
                while ($currentDate <= $endDate) {
                    // Vérifier les weekends
                    if (!$validated['include_weekends'] && $currentDate->isWeekend()) {
                        $currentDate->addDay();
                        continue;
                    }
                    
                    // Vérifier les jours fériés
                    if (Holiday::isHoliday($currentDate)) {
                        $currentDate->addDay();
                        continue;
                    }
                    
                    // Vérifier si le planning existe déjà
                    $exists = EmployeeSchedule::where('employee_id', $employeeId)
                        ->where('schedule_date', $currentDate->toDateString())
                        ->exists();
                    
                    if (!$exists) {
                        EmployeeSchedule::create([
                            'employee_id' => $employeeId,
                            'work_hour_type_id' => $validated['default_work_hour_type_id'],
                            'schedule_type' => 'fixe',
                            'schedule_date' => $currentDate->toDateString(),
                            'day_of_week' => $currentDate->dayOfWeekIso,
                            'is_working_day' => true
                        ]);
                        
                        $created++;
                    }
                    
                    $currentDate->addDay();
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Planning mensuel généré avec succès.',
                'details' => [
                    'month' => $startDate->format('F Y'),
                    'created' => $created,
                    'total_days' => $startDate->daysInMonth
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }
}