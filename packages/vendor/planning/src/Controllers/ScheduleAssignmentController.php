<?php

namespace Vendor\Planning\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\WorkHourType;
use App\Models\EmployeeSchedule;
use App\Models\Department;
use App\Models\Zone as Area;
use App\Models\Client;
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
        $client = Client::where('user_id', auth()->user()->id)->first();
        
        // Récupérer les données pour le calendrier
        $employees = Employee::where('client_id', $client->id)
            // ->where('is_active', true)
            ->with(['department', 'area'])
            ->orderBy('first_name')
            ->get();
            
        $workHourTypes = WorkHourType::where('client_id', $client->id)
            ->where('is_active', true)
            ->get();
            
        $departments = Department::where('client_id', $client->id)->get();
        $areas = Area::where('client_id', $client->id)->get();
        
        // Date par défaut (semaine courante)
        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfWeek();
            
        $endDate = $startDate->copy()->addDays(6);
        
        return view('planning::schedules.calendar', compact(
            'employees', 'workHourTypes', 'departments', 'areas', 'startDate', 'endDate'
        ));
    }

    /**
     * Formulaire d'affectation en masse
     */
    public function massAssignForm(Request $request)
    {
        $client = Client::where('user_id', auth()->user()->id)->first();
        
        $employees = Employee::where('client_id', $client->id)
           // ->where('is_active', true)
            ->with(['department', 'area'])
            ->orderBy('first_name')
            ->get();
            
        $workHourTypes = WorkHourType::where('client_id', $client->id)
            ->where('is_active', true)
            ->get();
            
        $departments = Department::where('client_id', $client->id)->get();
        $areas = Area::where('client_id', $client->id)->get();
        
        return view('planning::schedules.mass-assign', compact(
            'employees', 'workHourTypes', 'departments', 'areas'
        ));
    }

/**
 * Récupérer les données d'une cellule du calendrier
 */
public function getCellData(Request $request)
{
    try {
        $client = Client::where('user_id', auth()->user()->id)->first();
        
        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client non trouvé.'
            ], 404);
        }
        
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date'
        ]);
        
        $date = Carbon::parse($validated['date']);
        $dayOfWeekIso = $date->dayOfWeekIso;
        
        // CORRECTION: On ne détermine pas isWeekend ici pour les rotations
        // On le gérera dans la logique d'affichage
        $isWeekend = $dayOfWeekIso >= 6;
        
        // Vérifier si c'est un jour férié
        $isHoliday = false;
        if (class_exists('App\\Models\\Holiday')) {
            $isHoliday = \App\Models\Holiday::isHoliday($date, $client->id);
        }
        
        // RECHERCHE PRIMAIRE : Chercher par schedule_date exact (tous types)
        $schedule = EmployeeSchedule::with('workHourType')
            ->where('employee_id', $validated['employee_id'])
            ->where('schedule_date', $validated['date'])
            ->first();
        
        // Si pas trouvé par date exacte, vérifier selon le type
        if (!$schedule) {
            // Chercher tous les plannings récurrents pour cet employé
            $recurringSchedules = EmployeeSchedule::with('workHourType')
                ->where('employee_id', $validated['employee_id'])
                ->where('start_date', '<=', $validated['date'])
                ->where('end_date', '>=', $validated['date'])
                ->get();
            
            foreach ($recurringSchedules as $recurringSchedule) {
                $isApplicable = false;
                
                // Vérifier selon le type de planning
                switch ($recurringSchedule->schedule_type) {
                    case 'rotation':
                        // Pour "rotation", vérifier si c'est un jour de travail
                        $isApplicable = $this->isWorkDayInRotation($recurringSchedule, $validated['date']);
                        break;
                        
                    case 'fixe':
                        // Pour "fixe", vérifier si le jour de la semaine correspond
                        $isApplicable = $this->isApplicableForFixe($recurringSchedule, $date);
                        break;
                        
                    case 'planifie':
                        // Pour "planifié", s'applique à tous les jours de la plage
                        $isApplicable = true;
                        break;
                        
                    default:
                        $isApplicable = true;
                }
                
                if ($isApplicable) {
                    $schedule = $recurringSchedule;
                    break;
                }
            }
        }
        
        // CORRECTION IMPORTANTE: Pour les rotations, on ignore le concept de "weekend"
        // Si c'est une rotation, on retourne toujours is_weekend = false
        $isWeekendForDisplay = $isWeekend;
        $isRotationDay = false;
        $isRotationWorkDay = false;
        
        if ($schedule && $schedule->schedule_type === 'rotation') {
            // C'est une journée de rotation
            $isRotationDay = true;
            $isRotationWorkDay = $this->isWorkDayInRotation($schedule, $validated['date']);
            
            // Pour rotation, JAMAIS afficher "Weekend"
            $isWeekendForDisplay = false;
        }
        
        $data = [
            'is_weekend' => $isWeekendForDisplay,
            'is_holiday' => $isHoliday,
            'is_rotation_day' => $isRotationDay,
            'is_rotation_work_day' => $isRotationWorkDay,
            'schedule' => null,
            'date' => $date->format('Y-m-d'),
            'formatted_date' => $date->format('d/m/Y'),
            'day_name' => $date->locale('fr')->dayName,
            'day_of_week_iso' => $dayOfWeekIso
        ];
        
        if ($schedule) {
            // Récupérer les heures de travail
            $startTime = $schedule->start_time;
            $endTime = $schedule->end_time;
            $workHourTypeName = 'Personnalisé';
            $totalHours = 0;
            
            // Calcul de la durée basée sur start_time et end_time
            if ($startTime && $endTime) {
                $start = strtotime($startTime);
                $end = strtotime($endTime);
                
                // Gestion du travail de nuit (si end < start, on ajoute 24h)
                if ($end < $start) {
                    $end = strtotime($endTime . ' +1 day');
                }
                
                $totalMinutes = ($end - $start) / 60;
                
                // Soustraire les pauses
                if ($schedule->break_minutes) {
                    $totalMinutes -= $schedule->break_minutes;
                }
                
                $totalHours = number_format($totalMinutes / 60, 2);
            }
            
            // Déterminer le nom du type d'horaire
            if ($schedule->workHourType) {
                $workHourTypeName = $schedule->workHourType->name;
            } elseif ($schedule->schedule_type === 'fixe') {
                $workHourTypeName = 'Fixe';
            } elseif ($schedule->schedule_type === 'planifie') {
                $workHourTypeName = 'Planifié';
            } elseif ($schedule->schedule_type === 'rotation') {
                $workHourTypeName = 'Rotation';
            }
            
            // Gestion spéciale pour le type "rotation"
            $rotationDayNumber = 0;
            $rotationTotalDays = 0;
            
            if ($schedule->schedule_type === 'rotation') {
                // Calculer le jour dans la rotation
                $rotationData = $this->calculateRotationDay($schedule, $validated['date']);
                $rotationDayNumber = $rotationData['day_number'];
                $rotationTotalDays = $rotationData['total_cycle_days'];
                
                // Pour rotation de 24h, ajuster l'affichage
                if ($totalHours >= 24.00 || $schedule->daily_hours == 24.00) {
                    $workHourTypeName = 'Rotation 24h';
                    $endTime = date('H:i', strtotime($endTime)) . ' (+1)';
                }
            }
            
            $data['schedule'] = [
                'id' => $schedule->id,
                'work_hour_type_id' => $schedule->work_hour_type_id,
                'work_hour_type' => $workHourTypeName,
                'start_time' => $startTime ? date('H:i', strtotime($startTime)) : null,
                'end_time' => $endTime ? date('H:i', strtotime($schedule->end_time)) : null,
                'total_hours' => $totalHours,
                'schedule_type' => $schedule->schedule_type,
                'notes' => $schedule->notes,
                'is_working_day' => (bool)$schedule->is_working_day,
                'is_active' => (bool)$schedule->is_active,
                'start_date' => $schedule->start_date,
                'end_date' => $schedule->end_date,
                'is_in_range' => $schedule->start_date && $schedule->end_date,
                'day_of_week' => $schedule->day_of_week,
                'is_rotation_work_day' => $isRotationWorkDay,
                'rotation_day' => $rotationDayNumber,
                'rotation_total_days' => $rotationTotalDays,
                'work_days_count' => $schedule->work_days_count,
                'rest_days_count' => $schedule->rest_days_count,
                'daily_hours' => $schedule->daily_hours,
                'break_minutes' => $schedule->break_minutes
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
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            // $validated = $request->validate([
            //     'start_date' => 'required|date',
            //     'end_date' => 'required|date',
            //     'employee_ids' => 'nullable|array',
            //     'department_id' => 'nullable|exists:departments,id',
            //     'area_id' => 'nullable|exists:areas,id'
            // ]);
            
            $query = EmployeeSchedule::with(['employee.department', 'employee.area', 'workHourType'])
                ->where('client_id', $client->id);
                // ->whereBetween('schedule_date', [
                //     $validated['start_date'],
                //     $validated['end_date']
                // ]);
            
            // Filtres
            // if (!empty($validated['employee_ids'])) {
            //     $query->whereIn('employee_id', $validated['employee_ids']);
            // }
            
            // if ($request->filled('department_id')) {
            //     $query->whereHas('employee', function($q) use ($request) {
            //         $q->where('department_id', $request->department_id);
            //     });
            // }
            
            // if ($request->filled('area_id')) {
            //     $query->whereHas('employee', function($q) use ($request) {
            //         $q->where('area_id', $request->area_id);
            //     });
            // }
            
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
            
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'work_hour_type_id' => 'required|exists:work_hour_types,id',
                'date' => 'required|date',
                'is_working_day' => 'boolean',
                'notes' => 'nullable|string'
            ]);
            
            // Vérifier que l'employé appartient au client
            $employee = Employee::find($validated['employee_id']);
            if ($employee->client_id != $client->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé.'
                ], 403);
            }
            
            // Vérifier que le type d'horaire appartient au client
            $workHourType = WorkHourType::find($validated['work_hour_type_id']);
            if ($workHourType->client_id != $client->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé.'
                ], 403);
            }
            
            $date = Carbon::parse($validated['date']);
            
            // Vérifier si c'est un jour férié
            if (Holiday::isHoliday($date, $client->id)) {
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
                    'client_id' => $client->id,
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
            
            $client = Client::where('user_id', auth()->user()->id)->first();
            
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
            
            $workHourType = WorkHourType::find($validated['work_hour_type_id']);
            if ($workHourType->client_id != $client->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé.'
                ], 403);
            }
            
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $created = 0;
            $updated = 0;
            $skipped = 0;
            
            foreach ($validated['employee_ids'] as $employeeId) {
                $employee = Employee::find($employeeId);
                
                if ($employee->client_id != $client->id) {
                    $skipped++;
                    continue;
                }
                
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
                        if (Holiday::isHoliday($currentDate, $client->id)) {
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
                            'client_id' => $client->id,
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
            $client = Client::where('user_id', auth()->user()->id)->first();
            
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
            
            if ($schedule->client_id != $client->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé.'
                ], 403);
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
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date',
                'department_id' => 'nullable|exists:departments,id',
                'area_id' => 'nullable|exists:areas,id',
                'format' => 'required|in:pdf,excel,csv'
            ]);
            
            $query = Employee::with(['department', 'area'])
                ->where('client_id', $client->id)
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
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('planning::schedules.exports.pdf', $data);
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
 * Exporter le calendrier en PDF - Version utilisant la MÊME logique que getCellData
 */
public function exportPdf(Request $request)
{
    try {
        $client = Client::where('user_id', auth()->user()->id)->first();
        
        if (!$client) {
            throw new \Exception('Client non trouvé.');
        }
        
        $validator = \Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'employee_ids' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
        
        $validated = $validator->validated();
        
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $orientation = $request->input('pdf_orientation', 'portrait');
        
        // Récupérer les employés
        $employeeIds = [];
        if (!empty($validated['employee_ids'])) {
            $decoded = json_decode($validated['employee_ids'], true);
            $employeeIds = is_array($decoded) ? array_map('intval', $decoded) : [];
        }
        
        $employees = Employee::where('client_id', $client->id)
            ->when(!empty($employeeIds), function($query) use ($employeeIds) {
                return $query->whereIn('id', $employeeIds);
            })
            ->with(['department', 'area'])
            ->orderBy('first_name')
            ->get();
        
        if ($employees->isEmpty()) {
            throw new \Exception('Aucun employé trouvé pour l\'export.');
        }
        
        \Log::info('=== PDF EXPORT START ===');
        \Log::info('Period: ' . $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'));
        \Log::info('Employees: ' . $employees->count());
        
        // Organiser les plannings par employé et par date
        $schedulesByEmployee = [];
        
        foreach ($employees as $employee) {
            $schedulesByEmployee[$employee->id] = [];
            
            \Log::info("Processing employee: {$employee->full_name} (ID: {$employee->id})");
            
            // Pour chaque jour de la période
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                $dateStr = $currentDate->format('Y-m-d');
                
                // DEBUG: Premier jour du premier employé
                $isDebug = ($employee->id === $employees->first()->id && $dateStr === $startDate->format('Y-m-d'));
                
                if ($isDebug) {
                    \Log::info("=== DEBUG FIRST DAY ===");
                    \Log::info("Date: {$dateStr}, Employee: {$employee->full_name}");
                }
                
                // UTILISER LA MÊME MÉTHODE QUE getCellData !!!
                $schedule = $this->getScheduleForDate($employee->id, $dateStr, $client->id);
                
                if ($isDebug) {
                    \Log::info("getScheduleForDate result: " . ($schedule ? "FOUND" : "NOT FOUND"));
                    if ($schedule) {
                        \Log::info("Schedule details:", [
                            'ID' => $schedule->id,
                            'Type' => $schedule->schedule_type,
                            'Schedule Date' => $schedule->schedule_date,
                            'Start Date' => $schedule->start_date,
                            'End Date' => $schedule->end_date,
                            'Is Rotation Work Day' => $schedule->is_rotation_work_day ?? 'N/A'
                        ]);
                    }
                    \Log::info("=== END DEBUG ===");
                }
                
                // Si c'est une rotation et jour de repos, créer un objet spécial
                if ($schedule && $schedule->schedule_type === 'rotation' && 
                    isset($schedule->is_rotation_work_day) && !$schedule->is_rotation_work_day) {
                    
                    $restDaySchedule = (object)[
                        'id' => $schedule->id,
                        'schedule_type' => 'rotation',
                        'schedule_date' => $dateStr,
                        'is_rotation_work_day' => false,
                        'is_rotation_day' => true,
                        'is_rest_day' => true,
                        'workHourType' => $schedule->workHourType,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'notes' => $schedule->notes,
                        'work_days_count' => $schedule->work_days_count,
                        'rest_days_count' => $schedule->rest_days_count,
                        'daily_hours' => $schedule->daily_hours,
                        'calculated_total_hours' => $schedule->calculated_total_hours ?? 0,
                        'start_date' => $schedule->start_date,
                        'end_date' => $schedule->end_date
                    ];
                    
                    $schedulesByEmployee[$employee->id][$dateStr] = $restDaySchedule;
                } else {
                    $schedulesByEmployee[$employee->id][$dateStr] = $schedule;
                }
                
                $currentDate->addDay();
            }
        }
        
        // VÉRIFICATION FINALE
        $firstEmployeeId = $employees->first()->id;
        $firstDay = $startDate->format('Y-m-d');
        $finalResult = $schedulesByEmployee[$firstEmployeeId][$firstDay] ?? null;
        
        \Log::info("=== FINAL VERIFICATION ===");
        \Log::info("First employee ID: {$firstEmployeeId}");
        \Log::info("First day: {$firstDay}");
        \Log::info("Result: " . ($finalResult ? "FOUND" : "NOT FOUND"));
        
        if ($finalResult) {
            \Log::info("Final schedule details:", [
                'Type' => $finalResult->schedule_type ?? 'N/A',
                'Schedule Date' => $finalResult->schedule_date ?? 'N/A',
                'Is Rotation Work Day' => $finalResult->is_rotation_work_day ?? 'N/A',
                'Is Rest Day' => $finalResult->is_rest_day ?? 'N/A'
            ]);
        }
        
        $data = [
            'client' => $client,
            'employees' => $employees,
            'schedules' => $schedulesByEmployee,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'orientation' => $orientation,
            'includeWeekend' => true,
        ];
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('planning::schedules.export', $data)
            ->setPaper('A4', $orientation);
            
            
        $filename = 'planning-' . $startDate->format('Y-m-d') . '-au-' . $endDate->format('Y-m-d') . '.pdf';
        
        \Log::info('=== PDF EXPORT COMPLETED ===');
        
        return $pdf->download($filename);
        
    } catch (\Exception $e) {
        \Log::error('PDF Export Error: ' . $e->getMessage());
        \Log::error($e->getTraceAsString());
        return back()->with('error', 'Erreur lors de l\'export PDF: ' . $e->getMessage());
    }
}

/**
 * Vérifier si c'est un jour de travail dans une rotation - VERSION SIMPLE
 */
private function isRotationWorkDay($rotationSchedule, $dateStr)
{
    if ($rotationSchedule->schedule_type !== 'rotation') {
        return true;
    }
    
    // 1. Vérifier s'il y a une entrée exacte pour cette date
    $hasExactEntry = EmployeeSchedule::where('employee_id', $rotationSchedule->employee_id)
        ->where('schedule_type', 'rotation')
        ->where('schedule_date', $dateStr)
        ->exists();
    
    if ($hasExactEntry) {
        \Log::info("isRotationWorkDay: Found exact entry for {$dateStr} - Is work day");
        return true;
    }
    
    \Log::info("isRotationWorkDay: No exact entry for {$dateStr}");
    
    // 2. Si pas d'entrée exacte, calculer selon le cycle
    $startDate = Carbon::parse($rotationSchedule->start_date);
    $checkDate = Carbon::parse($dateStr);
    
    // Vérifier que la date est dans la période
    if ($checkDate < $startDate || $checkDate > Carbon::parse($rotationSchedule->end_date)) {
        return false;
    }
    
    $daysFromStart = $startDate->diffInDays($checkDate);
    $workDays = $rotationSchedule->work_days_count ?? 3;
    $restDays = $rotationSchedule->rest_days_count ?? 2;
    $totalCycleDays = $workDays + $restDays;
    
    if ($totalCycleDays <= 0) {
        return false;
    }
    
    $positionInCycle = $daysFromStart % $totalCycleDays;
    $isWorkDay = $positionInCycle < $workDays;
    
    \Log::info("isRotationWorkDay: Calculation for {$dateStr} - " .
              "daysFromStart={$daysFromStart}, position={$positionInCycle}, " .
              "workDays={$workDays}, isWorkDay=" . ($isWorkDay ? 'YES' : 'NO'));
    
    return $isWorkDay;
}

/**
 * Créer un objet pour un jour de repos dans une rotation
 */
private function createRestDayRotationObject($rotationSchedule, $dateStr)
{
    return (object)[
        'id' => null,
        'schedule_type' => 'rotation',
        'schedule_date' => $dateStr,
        'is_rotation_work_day' => false,
        'is_rotation_day' => true,
        'workHourType' => null,
        'start_time' => null,
        'end_time' => null,
        'notes' => null,
        'work_days_count' => $rotationSchedule->work_days_count,
        'rest_days_count' => $rotationSchedule->rest_days_count,
        'daily_hours' => $rotationSchedule->daily_hours,
        'start_date' => $rotationSchedule->start_date,
        'end_date' => $rotationSchedule->end_date,
        'is_rest_day' => true
    ];
}
/**
 * Vérifier si c'est un jour de travail dans une rotation
 * CORRECTION: Basé sur les données existantes
 */
private function isWorkDayInRotation($rotationSchedule, $dateStr)
{
    if ($rotationSchedule->schedule_type !== 'rotation') {
        return true;
    }
    
    \Log::info("isWorkDayInRotation called for date: {$dateStr}, schedule: " . 
              "start={$rotationSchedule->start_date}, end={$rotationSchedule->end_date}");
    
    // OPTION 1: Vérifier s'il existe une entrée schedule_date exacte pour cette date
    $hasExactEntry = EmployeeSchedule::where('employee_id', $rotationSchedule->employee_id)
        ->where('schedule_type', 'rotation')
        ->where('schedule_date', $dateStr)
        ->exists();
    
    if ($hasExactEntry) {
        \Log::info("  Found exact entry for {$dateStr}: YES - Is work day");
        return true;
    }
    
    \Log::info("  No exact entry found for {$dateStr}");
    
    // OPTION 2: Calculer selon le cycle
    $startDate = Carbon::parse($rotationSchedule->start_date);
    $checkDate = Carbon::parse($dateStr);
    
    // Vérifier que la date est dans la période
    if ($checkDate < $startDate || $checkDate > Carbon::parse($rotationSchedule->end_date)) {
        \Log::info("  Date {$dateStr} is outside rotation period");
        return false;
    }
    
    $daysFromStart = $startDate->diffInDays($checkDate);
    $workDays = $rotationSchedule->work_days_count ?? 3;
    $restDays = $rotationSchedule->rest_days_count ?? 2;
    $totalCycleDays = $workDays + $restDays;
    
    if ($totalCycleDays <= 0) {
        \Log::info("  Invalid cycle: workDays={$workDays}, restDays={$restDays}");
        return false;
    }
    
    $positionInCycle = $daysFromStart % $totalCycleDays;
    $isWorkDay = $positionInCycle < $workDays;
    
    \Log::info("  Calculation: daysFromStart={$daysFromStart}, positionInCycle={$positionInCycle}, " .
              "workDays={$workDays}, isWorkDay=" . ($isWorkDay ? 'YES' : 'NO'));
    
    return $isWorkDay;
}

/**
 * Vérifier si un planning "fixe" s'applique pour une date donnée
 */
private function isApplicableForFixe($fixeSchedule, $date)
{
    if ($fixeSchedule->schedule_type !== 'fixe') {
        return false;
    }
    
    $dateCarbon = Carbon::parse($date);
    
    // 1. Vérifier si le planning est répété hebdomadairement
    if ($fixeSchedule->repeat_weekly) {
        // Vérifier le jour de la semaine (day_of_week dans la table)
        $dayOfWeek = $dateCarbon->dayOfWeekIso; // 1=Lundi, 2=Mardi, etc.
        
        // Si day_of_week est défini, vérifier la correspondance
        if ($fixeSchedule->day_of_week && $fixeSchedule->day_of_week != $dayOfWeek) {
            return false;
        }
        
        // Vérifier si c'est une semaine paire/impaire (optionnel)
        if ($fixeSchedule->repeat_every_n_weeks > 1) {
            $startDate = Carbon::parse($fixeSchedule->start_date);
            $weeksFromStart = floor($startDate->diffInDays($dateCarbon) / 7);
            if (($weeksFromStart % $fixeSchedule->repeat_every_n_weeks) != 0) {
                return false;
            }
        }
        
        return true;
    }
    
    // 2. Si pas répété hebdomadairement, vérifier les jours personnalisés
    if ($fixeSchedule->custom_days) {
        $customDays = json_decode($fixeSchedule->custom_days, true) ?? [];
        return in_array($dateCarbon->format('Y-m-d'), $customDays);
    }
    
    // 3. Par défaut (ancienne logique) - vérifier si c'est le bon jour de semaine
    if ($fixeSchedule->day_of_week) {
        return $fixeSchedule->day_of_week == $dateCarbon->dayOfWeekIso;
    }
    
    // 4. Si aucun critère, s'applique à tous les jours de la plage
    return true;
}




/**
 * Calculer le jour spécifique dans une rotation
 */
private function calculateRotationDay($rotationSchedule, $dateStr)
{
    if ($rotationSchedule->schedule_type !== 'rotation') {
        return [
            'is_work_day' => true,
            'day_number' => 0,
            'total_cycle_days' => 0,
            'cycle_number' => 0
        ];
    }
    
    $startDate = Carbon::parse($rotationSchedule->start_date);
    $checkDate = Carbon::parse($dateStr);
    
    $workDays = $rotationSchedule->work_days_count ?? 3;
    $restDays = $rotationSchedule->rest_days_count ?? 2;
    $totalCycleDays = $workDays + $restDays;
    
    $daysFromStart = $startDate->diffInDays($checkDate);
    $positionInCycle = $daysFromStart % $totalCycleDays;
    
    return [
        'is_work_day' => $positionInCycle < $workDays,
        'day_number' => $positionInCycle + 1,
        'total_cycle_days' => $totalCycleDays,
        'cycle_number' => floor($daysFromStart / $totalCycleDays) + 1
    ];
}


    /**
     * Générer un planning mensuel automatique
     */
    public function generateMonthly(Request $request)
    {
        try {
            DB::beginTransaction();
            
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            $validated = $request->validate([
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:2023|max:2100',
                'employee_ids' => 'required|array',
                'employee_ids.*' => 'exists:employees,id',
                'default_work_hour_type_id' => 'required|exists:work_hour_types,id',
                'include_weekends' => 'boolean'
            ]);
            
            $workHourType = WorkHourType::find($validated['default_work_hour_type_id']);
            if ($workHourType->client_id != $client->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé.'
                ], 403);
            }
            
            $startDate = Carbon::create($validated['year'], $validated['month'], 1);
            $endDate = $startDate->copy()->endOfMonth();
            $created = 0;
            
            foreach ($validated['employee_ids'] as $employeeId) {
                $employee = Employee::find($employeeId);
                
                if ($employee->client_id != $client->id) {
                    continue;
                }
                
                $currentDate = $startDate->copy();
                while ($currentDate <= $endDate) {
                    // Vérifier les weekends
                    if (!$validated['include_weekends'] && $currentDate->isWeekend()) {
                        $currentDate->addDay();
                        continue;
                    }
                    
                    // Vérifier les jours fériés
                    if (Holiday::isHoliday($currentDate, $client->id)) {
                        $currentDate->addDay();
                        continue;
                    }
                    
                    // Vérifier si le planning existe déjà
                    $exists = EmployeeSchedule::where('employee_id', $employeeId)
                        ->where('schedule_date', $currentDate->toDateString())
                        ->exists();
                    
                    if (!$exists) {
                        EmployeeSchedule::create([
                            'client_id' => $client->id,
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

    /**
 * Méthode UNIQUE pour récupérer un planning (utilisée par getCellData ET exportPdf)
 */
private function getScheduleForDate($employeeId, $dateStr, $clientId = null)
{
    $date = Carbon::parse($dateStr);
    $dayOfWeekIso = $date->dayOfWeekIso;
    
    // 1. Chercher par schedule_date exact (TOUS TYPES)
    $schedule = EmployeeSchedule::with('workHourType')
        ->where('employee_id', $employeeId)
        ->where('schedule_date', $dateStr)
        ->first();
    
    // 2. Si pas trouvé, vérifier les plannings récurrents
    if (!$schedule) {
        $recurringSchedules = EmployeeSchedule::with('workHourType')
            ->where('employee_id', $employeeId)
            ->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->get();
        
        foreach ($recurringSchedules as $recurringSchedule) {
            $isApplicable = false;
            
            switch ($recurringSchedule->schedule_type) {
                case 'rotation':
                    $isApplicable = $this->isWorkDayInRotation($recurringSchedule, $dateStr);
                    break;
                    
                case 'fixe':
                    $isApplicable = $this->isApplicableForFixe($recurringSchedule, $date);
                    break;
                    
                case 'planifie':
                    $isApplicable = true;
                    break;
                    
                default:
                    $isApplicable = true;
            }
            
            if ($isApplicable) {
                $schedule = $recurringSchedule;
                break;
            }
        }
    }
    
    // 3. Préparer les données comme dans getCellData
    if ($schedule) {
        // Ajouter les propriétés calculées
        $schedule->is_rotation_day = ($schedule->schedule_type === 'rotation');
        if ($schedule->schedule_type === 'rotation') {
            $schedule->is_rotation_work_day = $this->isWorkDayInRotation($schedule, $dateStr);
            
            // Calculer rotation day number
            $rotationData = $this->calculateRotationDay($schedule, $dateStr);
            $schedule->rotation_day = $rotationData['day_number'];
            $schedule->rotation_total_days = $rotationData['total_cycle_days'];
        }
        
        // Calculer la durée
        $schedule->calculated_total_hours = $this->calculateTotalHours($schedule);
    }
    
    return $schedule;
}

/**
 * Calculer les heures totales
 */
private function calculateTotalHours($schedule)
{
    if (!$schedule->start_time || !$schedule->end_time) {
        return 0;
    }
    
    $start = strtotime($schedule->start_time);
    $end = strtotime($schedule->end_time);
    
    if ($end < $start) {
        $end = strtotime($schedule->end_time . ' +1 day');
    }
    
    $totalMinutes = ($end - $start) / 60;
    
    if ($schedule->break_minutes) {
        $totalMinutes -= $schedule->break_minutes;
    }
    
    return number_format($totalMinutes / 60, 2);
}
}