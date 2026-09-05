<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HolidayController extends Controller
{
    public function index()
    {
        $years = Holiday::selectRaw('DISTINCT YEAR(holiday_date) as year')
            ->orderBy('year', 'desc')
            ->pluck('year');
        return view('holidays.index', compact('years'));
    }

    public function datatable(Request $request)
    {
        $query = Holiday::query();

        if ($request->filled('year_filter')) {
            // Pour les fériés récurrents, on filtre sur mois/jour mais on affiche année d'origine
            // Ici on filtre simplement par année exacte ou récurrents de l'année
            $year = $request->year_filter;
            $query->where(function ($q) use ($year) {
                $q->whereYear('holiday_date', $year)
                  ->orWhere('is_recurring', true);
            });
        }

        if ($request->filled('recurring_filter') && $request->recurring_filter !== '') {
            $query->where('is_recurring', $request->recurring_filter === '1');
        }

        if ($request->filled('working_day_filter') && $request->working_day_filter !== '') {
            $query->where('is_working_day', $request->working_day_filter === '1');
        }

        return DataTables::of($query)
            ->addColumn('date_formatted', function ($holiday) {
                $date = Carbon::parse($holiday->holiday_date);
                $base = $date->locale('fr')->isoFormat('DD MMMM YYYY');
                if ($holiday->is_recurring) {
                    $base = $date->locale('fr')->isoFormat('DD MMMM') . ' (chaque année)';
                }
                return $base;
            })
            ->addColumn('recurring_badge', function ($holiday) {
                return $holiday->is_recurring
                    ? '<span class="badge bg-info">Récurrent</span>'
                    : '<span class="badge bg-secondary">Ponctuel</span>';
            })
            ->addColumn('working_day_badge', function ($holiday) {
                return $holiday->is_working_day
                    ? '<span class="badge bg-warning text-dark">Travaillé</span>'
                    : '<span class="badge bg-success">Non travaillé</span>';
            })
            ->addColumn('actions', function ($holiday) {
                $edit = '<button type="button" class="btn btn-sm btn-warning edit-btn" data-id="'.$holiday->id.'" title="Modifier"><i class="bi bi-pencil"></i></button>';
                $delete = '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="'.$holiday->id.'" data-name="'.e($holiday->name).'" title="Supprimer"><i class="bi bi-trash"></i></button>';
                return '<div class="btn-group" role="group">'.$edit.$delete.'</div>';
            })
            ->rawColumns(['recurring_badge', 'working_day_badge', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'holiday_date' => 'required|date',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_recurring' => 'nullable|boolean',
            'is_working_day' => 'nullable|boolean',
        ]);

        $validated['is_recurring'] = $request->boolean('is_recurring');
        $validated['is_working_day'] = $request->boolean('is_working_day');

        $date = Carbon::parse($validated['holiday_date'])->format('Y-m-d');

        // Vérif doublon date exacte
        if (Holiday::where('holiday_date', $date)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Un jour férié existe déjà à cette date.',
                'errors' => ['holiday_date' => ['Date déjà utilisée.']]
            ], 422);
        }

        // Si récurrent : vérifier qu'aucun autre férié récurrent n'a même mois/jour
        if ($validated['is_recurring']) {
            $month = Carbon::parse($date)->format('m');
            $day = Carbon::parse($date)->format('d');
            $exists = Holiday::where('is_recurring', true)
                ->whereMonth('holiday_date', $month)
                ->whereDay('holiday_date', $day)
                ->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un jour férié récurrent existe déjà pour ce jour/mois.',
                    'errors' => ['holiday_date' => ['Jour/mois déjà utilisé pour un férié récurrent.']]
                ], 422);
            }
        }

        try {
            $holiday = Holiday::create([
                'holiday_date' => $date,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_recurring' => $validated['is_recurring'],
                'is_working_day' => $validated['is_working_day'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jour férié créé avec succès',
                'data' => $holiday
            ]);
        } catch (\Exception $e) {
            Log::error('Holiday store: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: '.$e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $holiday = Holiday::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $holiday->id,
                    'holiday_date' => $holiday->holiday_date->format('Y-m-d'),
                    'name' => $holiday->name,
                    'description' => $holiday->description,
                    'is_recurring' => $holiday->is_recurring,
                    'is_working_day' => $holiday->is_working_day,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Jour férié non trouvé'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'holiday_date' => 'required|date',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_recurring' => 'nullable|boolean',
            'is_working_day' => 'nullable|boolean',
        ]);

        $validated['is_recurring'] = $request->boolean('is_recurring');
        $validated['is_working_day'] = $request->boolean('is_working_day');

        $date = Carbon::parse($validated['holiday_date'])->format('Y-m-d');

        try {
            $holiday = Holiday::findOrFail($id);

            if (Holiday::where('holiday_date', $date)->where('id', '!=', $holiday->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un jour férié existe déjà à cette date.',
                    'errors' => ['holiday_date' => ['Date déjà utilisée.']]
                ], 422);
            }

            if ($validated['is_recurring']) {
                $month = Carbon::parse($date)->format('m');
                $day = Carbon::parse($date)->format('d');
                $exists = Holiday::where('is_recurring', true)
                    ->whereMonth('holiday_date', $month)
                    ->whereDay('holiday_date', $day)
                    ->where('id', '!=', $holiday->id)
                    ->exists();
                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Un jour férié récurrent existe déjà pour ce jour/mois.',
                        'errors' => ['holiday_date' => ['Jour/mois déjà utilisé.']]
                    ], 422);
                }
            }

            $holiday->update([
                'holiday_date' => $date,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_recurring' => $validated['is_recurring'],
                'is_working_day' => $validated['is_working_day'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jour férié mis à jour avec succès',
                'data' => $holiday
            ]);
        } catch (\Exception $e) {
            Log::error('Holiday update: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $holiday = Holiday::findOrFail($id);
            $holiday->delete();
            return response()->json(['success' => true, 'message' => 'Jour férié supprimé avec succès']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur suppression: '.$e->getMessage()], 500);
        }
    }
}
