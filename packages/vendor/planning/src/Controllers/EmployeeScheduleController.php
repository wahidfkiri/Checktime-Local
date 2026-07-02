<?php

namespace Vendor\Planning\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EmployeeSchedule;
use App\Models\Employee;
use App\Models\WorkHourType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmployeeScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index(Request $request)
    {
        try {
            $client = \App\Models\Setting::company();
            
            if ($request->ajax()) {
                // Construire la requête
                $query = EmployeeSchedule::with(['employee', 'workHourType']);
                
                // Filtres
                if ($request->filled('employee_id')) {
                    $query->where('employee_id', $request->employee_id);
                }
                
                if ($request->filled('schedule_type')) {
                    $query->where('schedule_type', $request->schedule_type);
                }
                
                if ($request->filled('start_date')) {
                    $query->whereDate('schedule_date', '>=', $request->start_date);
                }
                
                if ($request->filled('end_date')) {
                    $query->whereDate('schedule_date', '<=', $request->end_date);
                }
                
                if ($request->filled('is_active')) {
                    $query->where('is_active', $request->is_active);
                }
                
                // Trier par date
                $query->orderBy('schedule_date', 'desc');
                
                return datatables()->eloquent($query)
                    ->addIndexColumn()
                    ->addColumn('employee_name', function($row) {
                        return $row->employee->full_name ?? 'N/A';
                    })
                    ->addColumn('matricule', function($row) {
                        return $row->employee->emp_code ?? 'N/A';
                    })
                    ->addColumn('work_hour_type_name', function($row) {
                        return $row->workHourType->name ?? ($row->schedule_type === 'rotation' ? 'Rotation' : 'Personnalisé');
                    })
                    ->addColumn('formatted_time', function($row) {
                        // Si c'est une rotation, afficher les heures par jour
                        if ($row->schedule_type === 'rotation') {
                            if ($row->daily_hours) {
                                $start = $row->start_time ? Carbon::parse($row->start_time)->format('H:i') : '--:--';
                                $end = $row->end_time ? Carbon::parse($row->end_time)->format('H:i') : '--:--';
                                return $start . ' - ' . $end . '<br><small class="text-muted">(' . number_format($row->daily_hours, 1) . 'h/jour)</small>';
                            }
                            return '--:-- - --:--';
                        }
                        
                        // Pour les autres types
                        if ($row->start_time && $row->end_time) {
                            $start = Carbon::parse($row->start_time)->format('H:i');
                            $end = Carbon::parse($row->end_time)->format('H:i');
                            return $start . ' - ' . $end;
                        }
                        
                        // Si pas d'heures spécifiques mais un type d'horaire
                        if ($row->workHourType) {
                            $start = Carbon::parse($row->workHourType->start_time)->format('H:i');
                            $end = Carbon::parse($row->workHourType->end_time)->format('H:i');
                            return $start . ' - ' . $end;
                        }
                        
                        return 'N/A';
                    })
                    ->addColumn('total_hours', function($row) {
                        // Pour rotation
                        if ($row->schedule_type === 'rotation' && $row->daily_hours) {
                            return number_format($row->daily_hours, 1) . 'h';
                        }
                        
                        // Pour les autres types
                        if ($row->workHourType) {
                            $start = strtotime($row->workHourType->start_time);
                            $end = strtotime($row->workHourType->end_time);
                            
                            if ($row->workHourType->is_overnight && $end < $start) {
                                $end = strtotime($row->workHourType->end_time . ' +1 day');
                            }
                            
                            $totalMinutes = ($end - $start) / 60;
                            $workMinutes = $totalMinutes - ($row->workHourType->break_minutes ?? 0);
                            return number_format($workMinutes / 60, 2) . 'h';
                        }
                        
                        // Pour horaires personnalisés
                        if ($row->start_time && $row->end_time) {
                            $start = strtotime($row->start_time);
                            $end = strtotime($row->end_time);
                            
                            // Gérer le cas où l'heure de fin est avant l'heure de début (travail de nuit)
                            if ($end < $start) {
                                $end = strtotime($row->end_time . ' +1 day');
                            }
                            
                            $totalMinutes = ($end - $start) / 60;
                            $workMinutes = $totalMinutes - ($row->break_minutes ?? 0);
                            return number_format($workMinutes / 60, 2) . 'h';
                        }
                        
                        return 'N/A';
                    })
                    ->addColumn('schedule_type_badge', function($row) {
                        $types = [
                            'fixe' => ['warning', 'Fixe'],
                            'rotation' => ['info', 'Rotation'],
                            'planifie' => ['primary', 'Planifié'],
                            'exception' => ['danger', 'Exception'],
                        ];
                        
                        $type = $types[$row->schedule_type] ?? ['secondary', $row->schedule_type];
                        
                        return '<span class="badge bg-' . $type[0] . '">' . $type[1] . '</span>';
                    })
                    ->addColumn('date_range', function($row) {
                        if ($row->start_date && $row->end_date) {
                            // Si la date de début et de fin sont différentes
                            if ($row->start_date != $row->end_date) {
                                return Carbon::parse($row->start_date)->format('d/m/Y') . ' - ' . 
                                       Carbon::parse($row->end_date)->format('d/m/Y');
                            }
                            // Si c'est la même date
                            return Carbon::parse($row->start_date)->format('d/m/Y');
                        }
                        
                        // Pour les anciens plannings sans start_date/end_date
                        if ($row->schedule_date) {
                            return Carbon::parse($row->schedule_date)->format('d/m/Y');
                        }
                        
                        return 'N/A';
                    })
                    ->addColumn('status_badge', function($row) {
                        $status = $row->is_active ? 'success' : 'danger';
                        $text = $row->is_active ? 'Actif' : 'Inactif';
                        return '<span class="badge bg-' . $status . '">' . $text . '</span>';
                    })
                    ->addColumn('working_day_badge', function($row) {
                        $status = $row->is_working_day ? 'success' : 'secondary';
                        $text = $row->is_working_day ? 'Travaillé' : 'Repos';
                        return '<span class="badge bg-' . $status . '">' . $text . '</span>';
                    })
                    ->addColumn('notes_preview', function($row) {
                        if ($row->notes && strlen($row->notes) > 50) {
                            return '<span data-bs-toggle="tooltip" title="' . htmlspecialchars($row->notes) . '">' 
                                   . substr($row->notes, 0, 50) . '...</span>';
                        }
                        return $row->notes ?? '';
                    })
                    ->addColumn('actions', function($row) use ($client) {
                        $actions = '<div class="btn-group" role="group">';
                        
                        // Bouton modifier
                        $actions .= '<button type="button" class="btn btn-sm btn-warning edit-schedule-btn" 
                                    data-id="' . $row->id . '"
                                    data-bs-toggle="tooltip" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </button>';
                        
                        // Bouton supprimer
                        $actions .= '<button type="button" class="btn btn-sm btn-danger delete-schedule-btn" 
                                    data-id="' . $row->id . '" 
                                    data-employee="' . htmlspecialchars($row->employee->full_name ?? '') . '"
                                    data-date="' . ($row->schedule_date ? Carbon::parse($row->schedule_date)->format('d/m/Y') : '') . '"
                                    data-bs-toggle="tooltip" title="Supprimer">
                                    <i class="bi bi-trash"></i>
                                </button>';
                        
                        // Bouton info supplémentaire pour rotation
                        if ($row->schedule_type === 'rotation') {
                            $rotationInfo = 'Rotation: ' . ($row->work_days_count ?? '?') . 'j travail, ' 
                                          . ($row->rest_days_count ?? '?') . 'j repos';
                            if ($row->daily_hours) {
                                $rotationInfo .= ', ' . number_format($row->daily_hours, 1) . 'h/jour';
                            }
                            
                            $actions .= '<button type="button" class="btn btn-sm btn-outline-info rotation-info-btn" 
                                        data-bs-toggle="tooltip" title="' . $rotationInfo . '">
                                        <i class="bi bi-info-circle"></i>
                                    </button>';
                        }
                        
                        $actions .= '</div>';
                        
                        return $actions;
                    })
                    ->rawColumns([
                        'employee_name',
                        'matricule',
                        'formatted_time', 
                        'schedule_type_badge', 
                        'status_badge', 
                        'working_day_badge',
                        'notes_preview',
                        'actions'
                    ])
                    ->make(true);
            }
            
            // Pour les requêtes non-AJAX (affichage initial)
            $employees = Employee::orderBy('first_name')
                ->get();
                
            $workHourTypes = WorkHourType::where('is_active', true)
                ->orderBy('name')
                ->get();
            
            return view('planning::employee-schedules.index', compact('employees', 'workHourTypes', 'client'));
            
        } catch (\Exception $e) {
            \Log::error('Erreur dans EmployeeScheduleController@index: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors du chargement des données: ' . $e->getMessage()
                ], 500);
            }
            
            // Retourner la vue avec une erreur
            return view('planning::employee-schedules.index')->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Création multiple de plannings
     */
    public function bulkCreate(Request $request)
    {
        DB::beginTransaction();
        try {
            $client = \App\Models\Setting::company();
            
            $validator = Validator::make($request->all(), [
                'employee_ids' => 'required',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'schedule_type' => 'required|in:fixe,rotation,planifie',
                'work_hour_type_id' => 'nullable|exists:work_hour_types,id',
                'start_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i',
                'break_minutes' => 'nullable|integer|min:0|max:240',
                'days_of_week' => 'nullable|array',
                'days_of_week.*' => 'in:1,2,3,4,5,6,7',
                'override_existing' => 'boolean',
                'schedule_days' => 'nullable|array', // Pour le type fixe
                'schedule_days.*.day_of_week' => 'required|integer|min:1|max:7',
                'schedule_days.*.start_time' => 'required|date_format:H:i',
                'schedule_days.*.end_time' => 'required|date_format:H:i',
                'work_days_count' => 'nullable|integer|min:1|max:30', // Pour rotation
                'rest_days_count' => 'nullable|integer|min:1|max:30', // Pour rotation
                'daily_hours' => 'nullable|numeric|min:1|max:24', // Pour rotation
                'start_hour' => 'nullable|date_format:H:i', // Pour rotation
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $validated = $validator->validated();
            
            // Récupérer les employés
            $employeeIds = $validated['employee_ids'] === 'all' 
                ? Employee::pluck('id')->toArray()
                : (array)$validated['employee_ids'];
            
            $employees = Employee::whereIn('id', $employeeIds)
                ->get();
            
            if ($employees->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun employé trouvé'
                ], 404);
            }
            
            // Récupérer les infos de l'horaire si fourni (pour planifié)
            if ($validated['schedule_type'] === 'planifie' && isset($validated['work_hour_type_id'])) {
                $workHourType = WorkHourType::find($validated['work_hour_type_id']);
                if (!$workHourType) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Type d\'horaire non autorisé'
                    ], 403);
                }
            }
            
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            
            $createdCount = 0;
            $skippedCount = 0;
            $conflicts = [];
            
            foreach ($employees as $employee) {
                $currentDate = $startDate->copy();
                
                while ($currentDate <= $endDate) {
                    $dayOfWeek = $currentDate->dayOfWeekIso;
                    
                    // Logique spécifique selon le type de planning
                    switch ($validated['schedule_type']) {
                        case 'planifie':
                            if (!$this->shouldCreatePlanifie($currentDate, $validated)) {
                                $currentDate->addDay();
                                continue 2; // Passe au jour suivant
                            }
                            break;
                            
                        case 'fixe':
                            if (!$this->shouldCreateFixe($currentDate, $validated)) {
                                $currentDate->addDay();
                                continue 2; // Passe au jour suivant
                            }
                            break;
                            
                        case 'rotation':
                            if (!$this->shouldCreateRotation($currentDate, $startDate, $validated)) {
                                $currentDate->addDay();
                                continue 2; // Passe au jour suivant
                            }
                            break;
                    }
                    
                    // Vérifier doublon si override_existing est false
                    if (!isset($validated['override_existing']) || !$validated['override_existing']) {
                        $exists = EmployeeSchedule::where('employee_id', $employee->id)
                            ->where('schedule_date', $currentDate->toDateString())
                            ->exists();
                        
                        if ($exists) {
                            $skippedCount++;
                            $conflicts[] = [
                                'employee' => $employee->full_name,
                                'date' => $currentDate->toDateString()
                            ];
                            $currentDate->addDay();
                            continue;
                        }
                    } else {
                        // Supprimer les plannings existants si override_existing est true
                        EmployeeSchedule::where('employee_id', $employee->id)
                            ->where('schedule_date', $currentDate->toDateString())
                            ->delete();
                    }
                    
                    // Préparer les données pour le planning
                    $scheduleData = $this->prepareScheduleData(
                        $employee->id,
                        $currentDate,
                        $validated,
                        $workHourType ?? null
                    );
                    
                    if (!$scheduleData) {
                        $currentDate->addDay();
                        continue;
                    }
                    
                    // Créer le planning
                    EmployeeSchedule::create($scheduleData);
                    $createdCount++;
                    
                    $currentDate->addDay();
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Plannings créés avec succès',
                'created' => $createdCount,
                'skipped' => $skippedCount,
                'employees_count' => count($employees),
                'conflicts' => $conflicts
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur création plannings: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifie si un planning planifié doit être créé pour une date donnée
     */
    private function shouldCreatePlanifie(Carbon $date, array $data): bool
    {
        $dayOfWeek = $date->dayOfWeekIso;
        
        // Vérifier les jours de semaine si spécifiés
        if (isset($data['days_of_week']) && !empty($data['days_of_week'])) {
            if (!in_array($dayOfWeek, $data['days_of_week'])) {
                return false;
            }
        }
        
        // Exclure les samedis et dimanches si non spécifiés
        if ($dayOfWeek == 6 || $dayOfWeek == 7) { // 6 = samedi, 7 = dimanche
            return false;
        }
        
        return true;
    }

    /**
     * Vérifie si un planning fixe doit être créé pour une date donnée
     */
    private function shouldCreateFixe(Carbon $date, array $data): bool
    {
        $dayOfWeek = $date->dayOfWeekIso;
        
        // Vérifier les jours de semaine si spécifiés dans schedule_days
        if (isset($data['schedule_days']) && !empty($data['schedule_days'])) {
            foreach ($data['schedule_days'] as $day) {
                if (isset($day['day_of_week']) && $day['day_of_week'] == $dayOfWeek) {
                    return true;
                }
            }
            return false;
        }
        
        // Si pas de schedule_days, vérifier days_of_week
        if (isset($data['days_of_week']) && !empty($data['days_of_week'])) {
            if (!in_array($dayOfWeek, $data['days_of_week'])) {
                return false;
            }
        }
        
        // Exclure les samedis et dimanches si non spécifiés
        if ($dayOfWeek == 6 || $dayOfWeek == 7) {
            return false;
        }
        
        return true;
    }

    /**
     * Vérifie si un planning rotation doit être créé pour une date donnée
     */
    private function shouldCreateRotation(Carbon $date, Carbon $startDate, array $data): bool
    {
        if (!isset($data['work_days_count']) || !isset($data['rest_days_count'])) {
            return false;
        }
        
        // Calculer le nombre de jours depuis le début
        $daysSinceStart = $startDate->diffInDays($date);
        
        // Calculer le cycle (travail + repos)
        $cycleLength = $data['work_days_count'] + $data['rest_days_count'];
        
        // Vérifier dans quel cycle on se trouve
        $positionInCycle = $daysSinceStart % $cycleLength;
        
        // Si dans la période de travail
        if ($positionInCycle < $data['work_days_count']) {
            // Vérifier que ce n'est pas un weekend (samedi=6, dimanche=7)
            $dayOfWeek = $date->dayOfWeekIso;
            if ($dayOfWeek == 6 || $dayOfWeek == 7) {
                return false; // Ne pas créer les samedis et dimanches
            }
            return true;
        }
        
        // Dans la période de repos
        return false;
    }

    /**
     * Prépare les données du planning selon le type
     */
    private function prepareScheduleData(int $employeeId, Carbon $date, array $data, ?WorkHourType $workHourType = null): array
    {
        $dayOfWeek = $date->dayOfWeekIso;
        $scheduleType = $data['schedule_type'];
        
        $baseData = [
            'employee_id' => $employeeId,
            'schedule_type' => $scheduleType,
            'schedule_date' => $date->toDateString(),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'day_of_week' => $dayOfWeek,
            'is_working_day' => isset($data['is_working_day']) ? $data['is_working_day'] : true,
            'is_active' => isset($data['is_active']) ? $data['is_active'] : true,
            'notes' => $data['notes'] ?? null,
            'created_by' => Auth::id()
        ];
        
        switch ($scheduleType) {
            case 'planifie':
                return array_merge($baseData, $this->getPlanifieData($data, $workHourType));
                
            case 'fixe':
                return array_merge($baseData, $this->getFixeData($dayOfWeek, $data));
                
            case 'rotation':
                return array_merge($baseData, $this->getRotationData($data, $date));
                
            default:
                return [];
        }
    }

    /**
     * Récupère les données spécifiques au type planifié
     */
    private function getPlanifieData(array $data, ?WorkHourType $workHourType = null): array
    {
        if ($workHourType) {
            return [
                'work_hour_type_id' => $workHourType->id,
                'start_time' => $workHourType->start_time,
                'end_time' => $workHourType->end_time,
                'break_minutes' => $workHourType->break_minutes,
                'repeat_weekly' => false,
            ];
        }
        
        // Pour horaires personnalisés
        return [
            'work_hour_type_id' => null,
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'break_minutes' => $data['break_minutes'] ?? 0,
            'repeat_weekly' => false,
        ];
    }

    /**
     * Récupère les données spécifiques au type fixe
     */
    private function getFixeData(int $dayOfWeek, array $data): array
    {
        $startTime = null;
        $endTime = null;
        $breakMinutes = $data['break_minutes'] ?? 0;
        
        // Rechercher les heures pour ce jour spécifique dans schedule_days
        if (isset($data['schedule_days']) && !empty($data['schedule_days'])) {
            foreach ($data['schedule_days'] as $day) {
                if (isset($day['day_of_week']) && $day['day_of_week'] == $dayOfWeek) {
                    $startTime = $day['start_time'] ?? null;
                    $endTime = $day['end_time'] ?? null;
                    break;
                }
            }
        }
        
        // Si pas trouvé dans schedule_days, utiliser les valeurs par défaut
        if (!$startTime || !$endTime) {
            $startTime = $data['start_time'] ?? null;
            $endTime = $data['end_time'] ?? null;
        }
        
        return [
            'work_hour_type_id' => null,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'break_minutes' => $breakMinutes,
            'repeat_weekly' => true, // Le type fixe se répète chaque semaine
        ];
    }

    /**
     * Récupère les données spécifiques au type rotation
     */
    private function getRotationData(array $data, Carbon $date): array
    {
        $dailyHours = $data['daily_hours'] ?? 8;
        $startHour = $data['start_hour'] ?? '08:00';
        
        // Calculer l'heure de fin basée sur le nombre d'heures
        $startTime = Carbon::createFromFormat('H:i', $startHour);
        $endTime = $startTime->copy()->addHours($dailyHours);
        
        // Formater les heures
        $startTimeFormatted = $startTime->format('H:i');
        $endTimeFormatted = $endTime->format('H:i');
        
        return [
            'work_hour_type_id' => null,
            'start_time' => $startTimeFormatted,
            'end_time' => $endTimeFormatted,
            'break_minutes' => 0, // Pas de pause pour rotation
            'daily_hours' => $dailyHours,
            'work_days_count' => $data['work_days_count'],
            'rest_days_count' => $data['rest_days_count'],
            'repeat_weekly' => false,
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $client = \App\Models\Setting::company();
            
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'work_hour_type_id' => 'nullable|exists:work_hour_types,id',
                'schedule_type' => 'required|in:fixe,rotation,planifie,exception',
                'schedule_date' => 'nullable|date',
                'day_of_week' => 'nullable|integer|min:1|max:7',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'break_minutes' => 'nullable|integer|min:0|max:240',
                'is_working_day' => 'nullable|boolean',
                'notes' => 'nullable|string|max:500',
                'is_active' => 'nullable|boolean',
                'override' => 'nullable|boolean'
            ], [
                'employee_id.required' => 'L\'employé est requis',
                'employee_id.exists' => 'Employé non trouvé',
                // 'schedule_date.required' => 'La date est requise',
                'schedule_date.date' => 'Format de date invalide',
                'work_hour_type_id.exists' => 'Type d\'horaire non trouvé'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Vérifier l'employé
            $employee = Employee::find($request->employee_id);
            
            $validated = $validator->validated();
            
            // Si work_hour_type_id fourni, vérifier et récupérer les infos
            if (!empty($validated['work_hour_type_id'])) {
                $workHourType = WorkHourType::find($validated['work_hour_type_id']);
                
                // Remplir les infos depuis le type
                $validated['start_time'] = $workHourType->start_time;
                $validated['end_time'] = $workHourType->end_time;
                $validated['break_minutes'] = $workHourType->break_minutes;
            }
            
            // Calculer day_of_week si non fourni
            if (empty($validated['day_of_week'])) {
                $date = Carbon::parse($validated['schedule_date']);
                $validated['day_of_week'] = $date->dayOfWeekIso;
            }
            
            // Vérifier doublon
            $existing = EmployeeSchedule::where('employee_id', $validated['employee_id'])
                ->where('schedule_date', $validated['schedule_date'])
                ->first();
                
            if ($existing) {
                if (!$request->has('override') || !$request->boolean('override')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Un planning existe déjà pour cette date.',
                        'conflict' => true,
                        'existing_id' => $existing->id,
                        'existing_data' => [
                            'employee' => $existing->employee->full_name ?? '',
                            'date' => $existing->schedule_date,
                            'horaire' => $existing->workHourType->name ?? 'Personnalisé'
                        ]
                    ], 409); // Conflict
                }
                
                // Supprimer l'ancien si override
                $existing->delete();
            }
            
            // Préparer les données
            $scheduleData = [
                'employee_id' => $validated['employee_id'],
                'work_hour_type_id' => $validated['work_hour_type_id'] ?? null,
                'schedule_type' => $validated['schedule_type'],
                'schedule_date' => $validated['schedule_date'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'day_of_week' => $validated['day_of_week'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'break_minutes' => $validated['break_minutes'] ?? 0,
                'is_working_day' => $request->has('is_working_day') ? $request->boolean('is_working_day') : true,
                'notes' => $validated['notes'] ?? null,
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
                'created_by' => Auth::id()
            ];
            
            $employeeSchedule = EmployeeSchedule::create($scheduleData);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Planning créé avec succès.',
                'data' => [
                    'id' => $employeeSchedule->id,
                    'employee_name' => $employee->full_name,
                    'matricule' => $employee->emp_code,
                    'date' => $employeeSchedule->schedule_date,
                    'horaire' => $employeeSchedule->workHourType->name ?? 'Personnalisé'
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
                'message' => 'Erreur : ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeSchedule $employeeSchedule)
    {
        try {
            $client = \App\Models\Setting::company();
            
            return response()->json([
                'success' => true,
                'data' => $employeeSchedule->load(['employee', 'workHourType'])
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
    public function update(Request $request, EmployeeSchedule $employeeSchedule)
    {
        try {
            $client = \App\Models\Setting::company();
            
            $validated = $request->validate([
                'work_hour_type_id' => 'nullable|exists:work_hour_types,id',
                'schedule_type' => 'required|in:fixe,rotation,planifie,exception',
                'day_of_week' => 'nullable|integer|min:1|max:7',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
                'start_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i',
                'break_minutes' => 'nullable|integer|min:0|max:240',
                'is_working_day' => 'boolean',
                'notes' => 'nullable|string',
                'is_active' => 'boolean'
            ]);
            
            // Si work_hour_type_id fourni
            if ($request->filled('work_hour_type_id')) {
                $workHourType = WorkHourType::find($validated['work_hour_type_id']);
                
                $validated['start_time'] = $workHourType->start_time;
                $validated['end_time'] = $workHourType->end_time;
                $validated['break_minutes'] = $workHourType->break_minutes;
            }
            
            $validated['is_working_day'] = $request->has('is_working_day');
            $validated['is_active'] = $request->has('is_active');
            
            $employeeSchedule->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Planning modifié avec succès.'
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
    public function destroy(EmployeeSchedule $employeeSchedule)
    {
        try {
            $client = \App\Models\Setting::company();
            
            $employeeSchedule->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Planning supprimé avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dupliquer un planning sur plusieurs jours
     */
    public function duplicate(Request $request)
    {
        try {
            $client = \App\Models\Setting::company();
            
            $validated = $request->validate([
                'schedule_id' => 'required|exists:employee_schedules,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'days_of_week' => 'nullable|array',
                'days_of_week.*' => 'in:1,2,3,4,5,6,7',
                'override_existing' => 'boolean'
            ]);
            
            $originalSchedule = EmployeeSchedule::find($validated['schedule_id']);
            
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $created = 0;
            
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                $dayOfWeek = $currentDate->dayOfWeekIso;
                
                // Filtrer par jours de semaine
                if (!empty($validated['days_of_week']) && 
                    !in_array($dayOfWeek, $validated['days_of_week'])) {
                    $currentDate->addDay();
                    continue;
                }
                
                // Vérifier si existe déjà
                $exists = EmployeeSchedule::where('employee_id', $originalSchedule->employee_id)
                    ->where('schedule_date', $currentDate->toDateString())
                    ->exists();
                
                if ($exists && !$validated['override_existing']) {
                    $currentDate->addDay();
                    continue;
                }
                
                if ($exists && $validated['override_existing']) {
                    EmployeeSchedule::where('employee_id', $originalSchedule->employee_id)
                        ->where('schedule_date', $currentDate->toDateString())
                        ->delete();
                }
                
                // Créer la copie
                EmployeeSchedule::create([
                    'employee_id' => $originalSchedule->employee_id,
                    'work_hour_type_id' => $originalSchedule->work_hour_type_id,
                    'schedule_type' => $originalSchedule->schedule_type,
                    'schedule_date' => $currentDate->toDateString(),
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $originalSchedule->start_time,
                    'end_time' => $originalSchedule->end_time,
                    'break_minutes' => $originalSchedule->break_minutes,
                    'is_working_day' => $originalSchedule->is_working_day,
                    'notes' => $originalSchedule->notes,
                    'is_active' => $originalSchedule->is_active,
                ]);
                
                $created++;
                $currentDate->addDay();
            }
            
            return response()->json([
                'success' => true,
                'message' => $created . ' plannings créés avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Changer le statut (actif/inactif)
     */
    public function toggleStatus(EmployeeSchedule $employeeSchedule)
    {
        try {
            $client = \App\Models\Setting::company();
            
            $employeeSchedule->update([
                'is_active' => !$employeeSchedule->is_active
            ]);
            
            $status = $employeeSchedule->is_active ? 'activé' : 'désactivé';
            
            return response()->json([
                'success' => true,
                'message' => 'Planning ' . $status . ' avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Importer des plannings depuis Excel/CSV
     */
    public function import(Request $request)
    {
        try {
            $client = \App\Models\Setting::company();
            
            $request->validate([
                'import_file' => 'required|file|mimes:csv,xlsx,xls'
            ]);
            
            // Logique d'importation (exemple basique)
            $file = $request->file('import_file');
            $extension = $file->getClientOriginalExtension();
            
            // Vous pouvez utiliser maatwebsite/excel pour une meilleure gestion
            $imported = 0;
            $errors = [];
            
            // Exemple simple pour CSV
            if ($extension === 'csv') {
                $handle = fopen($file->getPathname(), 'r');
                $headers = fgetcsv($handle);
                
                while (($row = fgetcsv($handle)) !== false) {
                    $data = array_combine($headers, $row);
                    
                    // Valider et créer le planning
                    try {
                        // Trouver l'employé par matricule
                        $employee = Employee::where('emp_code', $data['matricule'])
                            ->first();
                        
                        if (!$employee) {
                            $errors[] = "Employé non trouvé: " . $data['matricule'];
                            continue;
                        }
                        
                        // Trouver le type d'horaire
                        $workHourType = WorkHourType::where('name', $data['horaire'])
                            ->first();
                        
                        EmployeeSchedule::updateOrCreate(
                            [
                                'employee_id' => $employee->id,
                                'schedule_date' => $data['date']
                            ],
                            [
                                'work_hour_type_id' => $workHourType->id ?? null,
                                'schedule_type' => 'planifie',
                                'day_of_week' => Carbon::parse($data['date'])->dayOfWeekIso,
                                'is_working_day' => $data['jour_travaille'] === 'Oui',
                                'notes' => $data['notes'] ?? null,
                            ]
                        );
                        
                        $imported++;
                        
                    } catch (\Exception $e) {
                        $errors[] = "Ligne erreur: " . implode(',', $row) . " - " . $e->getMessage();
                    }
                }
                
                fclose($handle);
            }
            
            return response()->json([
                'success' => true,
                'message' => $imported . ' plannings importés.',
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur d\'importation : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export des plannings
     */
    public function export(Request $request)
    {
        try {
            $client = \App\Models\Setting::company();
            
            $validator = Validator::make($request->all(), [
                'format' => 'required|in:pdf,excel,csv',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'employee_ids' => 'nullable|array',
                'employee_ids.*' => 'exists:employees,id',
                'group_by_employee' => 'nullable|boolean'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
            ], 422);
            }
            
            $validated = $validator->validated();
            
            // Récupérer les plannings
            $query = EmployeeSchedule::with(['employee.department', 'employee.area', 'workHourType'])
                ->whereBetween('schedule_date', [
                    $validated['start_date'],
                    $validated['end_date']
                ]);
            
            // Filtres
            if (!empty($validated['employee_ids'])) {
                $query->whereIn('employee_id', $validated['employee_ids']);
            }
            
            $schedules = $query->orderBy('schedule_date')->get();
            
            if ($schedules->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun planning trouvé pour cette période.'
                ], 404);
            }
            
            // Grouper par employé si demandé
            $groupedData = [];
            foreach ($schedules as $schedule) {
                $employeeId = $schedule->employee_id;
                if (!isset($groupedData[$employeeId])) {
                    $groupedData[$employeeId] = [
                        'employee' => $schedule->employee,
                        'schedules' => []
                    ];
                }
                $groupedData[$employeeId]['schedules'][] = $schedule;
            }
            
            $data = [
                'grouped_data' => $groupedData,
                'client' => $client,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'export_date' => now()->format('d/m/Y H:i'),
                'total_schedules' => $schedules->count(),
                'total_employees' => count($groupedData)
            ];
            
            if ($validated['format'] === 'pdf') {
                try {
                    // Vérifier que la vue existe
                    $pdfView = 'planning::employee-schedules.exports.pdf';
                    
                    // Générer le PDF
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($pdfView, $data);
                    
                    $filename = 'plannings-employes-' . date('Y-m-d-H-i') . '.pdf';
                    
                    // Retourner l'URL de téléchargement
                    return response()->json([
                        'success' => true,
                        'message' => 'PDF généré avec succès',
                        'download_url' => 'data:application/pdf;base64,' . base64_encode($pdf->output()),
                        'filename' => $filename,
                        'data' => [
                            'total_schedules' => $schedules->count(),
                            'total_employees' => count($groupedData),
                            'period' => $validated['start_date'] . ' au ' . $validated['end_date']
                        ]
                    ]);
                    
                } catch (\Exception $pdfException) {
                    \Log::error('PDF Generation Error: ' . $pdfException->getMessage());
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur lors de la génération du PDF : ' . $pdfException->getMessage(),
                        'file' => $pdfException->getFile(),
                        'line' => $pdfException->getLine()
                    ], 500);
                }
                
            } elseif ($validated['format'] === 'csv') {
                // Export CSV
                $filename = 'plannings-employes-' . date('Y-m-d') . '.csv';
                $csvData = $this->generateCsvData($schedules);
                
                return response()->json([
                    'success' => true,
                    'message' => 'CSV généré avec succès',
                    'csv_data' => $csvData,
                    'filename' => $filename,
                    'data' => [
                        'total_schedules' => $schedules->count(),
                        'total_employees' => count($groupedData)
                    ]
                ]);
                
            } else {
                // Export Excel
                return response()->json([
                    'success' => true,
                    'message' => 'Données prêtes pour export Excel',
                    'data' => $data,
                    'format' => 'excel'
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Export Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export : ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Générer les données CSV
     */
    private function generateCsvData($schedules)
    {
        $csv = "Matricule,Nom Employé,Département,Zone,Date,Jour,Type Planning,Type Horaire,Heure Début,Heure Fin,Pause (min),Jour Travaillé,Statut,Notes\n";
        
        foreach ($schedules as $schedule) {
            $csv .= sprintf(
                '%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s',
                $schedule->employee->emp_code ?? '',
                $schedule->employee->full_name ?? '',
                $schedule->employee->department->name ?? '',
                $schedule->employee->area->name ?? '',
                $schedule->schedule_date,
                Carbon::parse($schedule->schedule_date)->locale('fr')->dayName,
                $schedule->schedule_type,
                $schedule->workHourType->name ?? 'Personnalisé',
                $schedule->workHourType->start_time ?? $schedule->start_time ?? '',
                $schedule->workHourType->end_time ?? $schedule->end_time ?? '',
                $schedule->workHourType->break_minutes ?? $schedule->break_minutes ?? 0,
                $schedule->is_working_day ? 'Oui' : 'Non',
                $schedule->is_active ? 'Actif' : 'Inactif',
                str_replace(',', ';', $schedule->notes ?? '')
            ) . "\n";
        
        }
        
        return $csv;
    }

    /**
     * Suppression multiple
     */
    public function bulkDelete(Request $request)
    {
        DB::beginTransaction();
        try {
            $client = \App\Models\Setting::company();
            
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'exists:employee_schedules,id'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $validated = $validator->validated();
            
            $count = EmployeeSchedule::whereIn('id', $validated['ids'])->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => $count . ' planning(s) supprimé(s) avec succès.'
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
     * Mise à jour multiple
     */
    public function bulkUpdate(Request $request)
    {
        DB::beginTransaction();
        try {
            $client = \App\Models\Setting::company();
            
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'exists:employee_schedules,id',
                'field' => 'required|in:is_active,is_working_day,schedule_type',
                'value' => 'required'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $validated = $validator->validated();
            
            $count = EmployeeSchedule::whereIn('id', $validated['ids'])
                ->update([$validated['field'] => $validated['value']]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => $count . ' planning(s) mis à jour avec succès.'
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