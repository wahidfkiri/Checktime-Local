<?php

namespace Vendor\Planning\Controllers;

use App\Http\Controllers\Controller;
use App\Models\WorkHourType;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkHourController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Vérifier si c'est une requête AJAX pour DataTable
        if ($request->ajax()) {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            $query = WorkHourType::where('client_id', $client->id)
                ->select(['id', 'name', 'code', 'start_time', 'end_time', 'break_minutes', 'is_overnight', 'is_active', 'created_at']);
            
            // Filtres
            if ($request->filled('code_filter')) {
                $query->where('code', 'like', '%' . $request->code_filter . '%');
            }
            
            if ($request->filled('name_filter')) {
                $query->where('name', 'like', '%' . $request->name_filter . '%');
            }
            
            if ($request->filled('status_filter')) {
                $query->where('is_active', $request->status_filter);
            }
            
            return datatables()->of($query)
                ->addIndexColumn()
                ->addColumn('formatted_hours', function($row) {
                    return date('H:i', strtotime($row->start_time)) . ' - ' . 
                           date('H:i', strtotime($row->end_time));
                })
                ->addColumn('total_hours', function($row) {
                    $start = strtotime($row->start_time);
                    $end = strtotime($row->end_time);
                    
                    if ($row->is_overnight && $end < $start) {
                        $end = strtotime($row->end_time . ' +1 day');
                    }
                    
                    $totalMinutes = ($end - $start) / 60;
                    $workMinutes = $totalMinutes - $row->break_minutes;
                    
                    return number_format($workMinutes / 60, 2) . 'h';
                })
                ->addColumn('status_badge', function($row) {
                    $status = $row->is_active ? 'success' : 'danger';
                    $text = $row->is_active ? 'Actif' : 'Inactif';
                    return '<span class="badge bg-' . $status . '">' . $text . '</span>';
                })
                ->addColumn('actions', function($row) {
                    return '
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-warning edit-hour-btn" 
                                data-id="' . $row->id . '" 
                                data-name="' . $row->name . '" 
                                data-code="' . $row->code . '"
                                data-start_time="' . date('H:i', strtotime($row->start_time)) . '"
                                data-end_time="' . date('H:i', strtotime($row->end_time)) . '"
                                data-break_minutes="' . $row->break_minutes . '"
                                data-is_overnight="' . $row->is_overnight . '"
                                data-is_active="' . $row->is_active . '">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger delete-hour-btn" 
                                data-id="' . $row->id . '" 
                                data-name="' . $row->name . '" 
                                data-code="' . $row->code . '">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }
        
        return view('planning::work-hours.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Retourner la vue de création via modal
        return response()->json([
            'success' => true,
            'html' => view('work-hours.partials.create-form')->render()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            $validated = $request->validate([
                'code' => 'required|string|max:50',
                'name' => 'required|string|max:100',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i',
                'break_minutes' => 'required|integer|min:0|max:240',
                'is_overnight' => 'boolean',
                'is_active' => 'boolean'
            ]);
            
            $validated['client_id'] = $client->id;
            $validated['is_overnight'] = $request->has('is_overnight');
            $validated['is_active'] = $request->has('is_active', true);
            
            $work_hour_type = WorkHourType::create($validated);

            if($request->has('is_active')){
                $work_hour_type->is_active = 1;
            } else {
                $work_hour_type->is_active = 0;
            }
            $work_hour_type->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Type d\'horaire créé avec succès.'
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
                'message' => 'Erreur lors de la création : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(WorkHourType $workHourType)
    {
        // Vérifier que le type d'horaire appartient au client
            $client = Client::where('user_id', auth()->user()->id)->first();
        if ($workHourType->client_id != $client->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé.'
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'data' => $workHourType
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WorkHourType $workHourType)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            // Vérifier que le type d'horaire appartient au client
            if ($workHourType->client_id != $client->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé.'
                ], 403);
            }
            
            $validated = $request->validate([
                'code' => 'required|string|max:50',
                'name' => 'required|string|max:100',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i',
                'break_minutes' => 'required|integer|min:0|max:240',
                'is_overnight' => 'boolean',
                'is_active' => 'boolean'
            ]);
            
            
            $workHourType->update($validated);



            if($request->is_active == '1'){
                $workHourType->is_active = 1;
            } else {
                $workHourType->is_active = 0;
            }

            
            if($request->is_overnight == '1'){
                $workHourType->is_overnight = 1;
            } else {
                $workHourType->is_overnight = 0;
            }
            $workHourType->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Type d\'horaire modifié avec succès.'
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
                'message' => 'Erreur lors de la modification : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WorkHourType $workHourType)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            // Vérifier que le type d'horaire appartient au client
            if ($workHourType->client_id != $client->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé.'
                ], 403);
            }
            
            // Vérifier s'il est utilisé dans des plannings
            $usedInSchedules = $workHourType->employeeSchedules()->exists();
            
            if ($usedInSchedules) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce type d\'horaire est utilisé dans des plannings. Vous ne pouvez pas le supprimer.'
                ], 422);
            }
            
            $workHourType->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Type d\'horaire supprimé avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dupliquer un type d'horaire
     */
    public function duplicate(WorkHourType $workHourType)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            // Vérifier que le type d'horaire appartient au client
            if ($workHourType->client_id != $client->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé.'
                ], 403);
            }
            
            // Créer une copie avec un nouveau code
            $newWorkHourType = $workHourType->replicate();
            $newWorkHourType->code = $workHourType->code . '_COPY_' . time();
            $newWorkHourType->name = $workHourType->name . ' (Copie)';
            $newWorkHourType->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Type d\'horaire dupliqué avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la duplication : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Changer le statut (actif/inactif)
     */
    public function toggleStatus(WorkHourType $workHourType)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            // Vérifier que le type d'horaire appartient au client
            if ($workHourType->client_id != $client->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé.'
                ], 403);
            }
            
            $workHourType->update([
                'is_active' => !$workHourType->is_active
            ]);
            
            $status = $workHourType->is_active ? 'activé' : 'désactivé';
            
            return response()->json([
                'success' => true,
                'message' => 'Type d\'horaire ' . $status . ' avec succès.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de statut : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporter la liste en PDF/Excel
     */
    public function export(Request $request)
    {
        try {
            $client = Client::where('user_id', auth()->user()->id)->first();
            
            $query = WorkHourType::where('client_id', $client->id);
            
            // Appliquer les filtres
            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }
            
            if ($request->filled('name')) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }
            
            if ($request->filled('status')) {
                $query->where('is_active', $request->status);
            }
            
            $workHours = $query->orderBy('name')->get();
            
            // Préparer les données pour l'export
            $data = [
                'work_hours' => $workHours,
                'client' => $client,
                'export_date' => now()->format('d/m/Y H:i'),
                'total' => $workHours->count()
            ];
            
            if ($request->type == 'pdf') {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('planning::work-hours.exports.pdf', $data);
                return $pdf->download('types-horaires-' . date('Y-m-d') . '.pdf');
            } else {
                // Export Excel
                return response()->json([
                    'success' => true,
                    'data' => $workHours
                ]);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export : ' . $e->getMessage()
            ], 500);
        }
    }
}