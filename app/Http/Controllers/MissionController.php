<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class MissionController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'emp_code', 'dept_name']);

        $departments = Employee::whereNotNull('dept_name')
            ->where('dept_name', '!=', '')
            ->select('dept_name')
            ->distinct()
            ->orderBy('dept_name')
            ->pluck('dept_name');

        if ($request->ajax()) {
            return $this->getMissionsData($request);
        }

        return view('missions.index', compact('employees', 'departments'));
    }

    private function getMissionsData(Request $request)
    {
        $query = Mission::with('employee')->select('missions.*');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('department')) {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('dept_name', $request->department);
            });
        }

        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $query->where('start_date', '>=', $startDate);
        }

        if ($request->filled('end_date')) {
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->where('end_date', '<=', $endDate);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('employee_name', function($mission) {
                if (!$mission->employee) {
                    return '<span class="text-muted">Employé supprimé</span>';
                }
                return $mission->employee->first_name . ' ' . $mission->employee->last_name;
            })
            ->addColumn('department_name', function($mission) {
                return $mission->employee?->dept_name ?? '-';
            })
            ->addColumn('period', function($mission) {
                if (!$mission->start_date || !$mission->end_date) {
                    return '-';
                }
                $start = $mission->start_date->format('d/m/Y');
                $end   = $mission->end_date->format('d/m/Y');

                // Mission d'une seule journée : une seule date suffit.
                return $start === $end ? $start : $start . '<br>' . $end;
            })
            ->addColumn('duration_formatted', function($mission) {
                if (!$mission->start_date || !$mission->end_date) {
                    return '-';
                }
                // Une mission s'exprime en journées : les deux bornes sont incluses
                // (du 10 au 10 = 1 jour, du 10 au 12 = 3 jours).
                $days = $this->missionDays($mission->start_date, $mission->end_date);

                return $days . ' jour' . ($days > 1 ? 's' : '');
            })
            ->addColumn('actions', function($mission) {
                return '
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-info view-btn" 
                                data-id="' . $mission->id . '" 
                                title="Voir détails">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-warning edit-btn" 
                                data-id="' . $mission->id . '" 
                                title="Modifier">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                data-id="' . $mission->id . '" 
                                title="Supprimer">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['period', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'reference' => 'required|string|max:50|unique:missions,reference',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'destination' => 'nullable|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $validated = $this->normalizeMissionDates($validated);

            $mission = Mission::create($validated);
            $mission->load('employee');

            Log::info("Mission créée: {$mission->reference}");

            return response()->json([
                'success' => true,
                'message' => 'Mission créée avec succès',
                'data' => $mission
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur création mission: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $mission = Mission::with('employee')->find($id);

            if (!$mission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mission non trouvée'
                ], 404);
            }

            $data = [
                'id' => $mission->id,
                'reference' => $mission->reference,
                'title' => $mission->title,
                'description' => $mission->description,
                'destination' => $mission->destination,
                // Format jour : alimente directement les <input type="date">,
                // sans conversion UTC qui décalerait la date d'un jour.
                'start_date' => $mission->start_date->format('Y-m-d'),
                'end_date' => $mission->end_date->format('Y-m-d'),
                'start_date_formatted' => $mission->start_date->format('d/m/Y'),
                'end_date_formatted' => $mission->end_date->format('d/m/Y'),
                'duration_formatted' => $this->missionDays($mission->start_date, $mission->end_date)
                    . ' jour' . ($this->missionDays($mission->start_date, $mission->end_date) > 1 ? 's' : ''),
                'employee_id' => $mission->employee_id,
                'employee' => $mission->employee ? [
                    'id' => $mission->employee->id,
                    'first_name' => $mission->employee->first_name,
                    'last_name' => $mission->employee->last_name,
                    'full_name' => $mission->employee->first_name . ' ' . $mission->employee->last_name,
                    'dept_name' => $mission->employee->dept_name
                ] : null,
                'created_at' => $mission->created_at->toISOString(),
                'updated_at' => $mission->updated_at->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur affichage mission: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $mission = Mission::find($id);

            if (!$mission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mission non trouvée'
                ], 404);
            }

            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'destination' => 'nullable|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $validated = $this->normalizeMissionDates($validated);

            $mission->update($validated);

            Log::info("Mission mise à jour: {$mission->reference}");

            return response()->json([
                'success' => true,
                'message' => 'Mission modifiée avec succès',
                'data' => $mission->load('employee')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur modification mission: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Nombre de journées couvertes par une mission, bornes incluses.
     */
    private function missionDays($start, $end): int
    {
        return Carbon::parse($start)->startOfDay()
            ->diffInDays(Carbon::parse($end)->startOfDay()) + 1;
    }

    /**
     * Une mission s'exprime en journées : on force le début au début de journée
     * et la fin à la fin de journée, quelle que soit l'heure reçue.
     *
     * Les colonnes restent en datetime (données existantes conservées), mais toute
     * comparaison sur une journée entière devient exacte — notamment le rattachement
     * des pointages « En mission ».
     *
     * @param  array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function normalizeMissionDates(array $validated): array
    {
        if (!empty($validated['start_date'])) {
            $validated['start_date'] = Carbon::parse($validated['start_date'])->startOfDay();
        }

        if (!empty($validated['end_date'])) {
            $validated['end_date'] = Carbon::parse($validated['end_date'])->endOfDay();
        }

        return $validated;
    }

    public function destroy($id)
    {
        try {
            $mission = Mission::find($id);

            if (!$mission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mission non trouvée'
                ], 404);
            }

            $reference = $mission->reference;
            $mission->delete();

            Log::info("Mission supprimée: {$reference}");

            return response()->json([
                'success' => true,
                'message' => 'Mission supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur suppression mission: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateReference()
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $day = now()->format('d');
        
        $count = Mission::whereDate('created_at', now()->toDateString())->count() + 1;

        $reference = 'MISS-' . $year . $month . $day . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        return response()->json([
            'reference' => $reference
        ]);
    }
}