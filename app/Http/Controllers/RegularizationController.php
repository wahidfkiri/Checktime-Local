<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRegularization;
use App\Models\DailyAttendance;
use App\Models\Employee;
use App\Models\RegularizationCause;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Régularisation manuelle de pointages (coupure d'électricité, oubli de
 * pointage…). Chaque régularisation ÉCRIT une ligne daily_attendance : tous
 * les rapports (HTML, PDF, e-mails) la lisent donc sans modification.
 */
class RegularizationController extends Controller
{
    private const DEFAULT_CAUSES = ["Coupure d'électricité", 'Oubli de pointage'];

    /** Valeur de daily_attendance.source : protège la ligne de la synchronisation. */
    public const SOURCE = 'regularization';

    public function index()
    {
        foreach (self::DEFAULT_CAUSES as $label) {
            RegularizationCause::firstOrCreate(['label' => $label], ['is_protected' => true]);
        }

        $causes = RegularizationCause::orderBy('label')->get();

        $employees = Employee::active()
            ->whereNotNull('emp_code')->where('emp_code', '!=', '')
            ->orderBy('emp_code')
            ->get()
            ->map(fn ($e) => [
                'id'        => $e->id,
                'emp_code'  => $e->emp_code,
                'full_name' => $e->full_name,
            ])
            ->values();

        $recent = AttendanceRegularization::with(['cause', 'employee'])
            ->latest('id')
            ->limit(50)
            ->get();

        return view('regularizations.index', compact('causes', 'employees', 'recent'));
    }

    /**
     * Test temps réel : quels employés ont déjà un pointage ce jour-là ?
     */
    public function checkConflicts(Request $request)
    {
        $data = $request->validate([
            'date'           => 'required|date',
            'employee_ids'   => 'required|array',
            'employee_ids.*' => 'integer',
        ]);

        $conflicts = [];
        foreach ($this->existingPunches($data['date'], $data['employee_ids']) as $row) {
            $conflicts[$row->employee_id] = [
                'check_in'  => $row->check_in ? $row->check_in->format('H:i') : null,
                'check_out' => $row->check_out ? $row->check_out->format('H:i') : null,
                'status'    => $row->status,
            ];
        }

        return response()->json((object) $conflicts);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cause_id'       => 'required|exists:regularization_causes,id',
            'employee_ids'   => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:employees,id',
            'date'           => 'required|date',
            'check_in'       => 'required|date_format:H:i',
            'check_out'      => 'required|date_format:H:i|after:check_in',
            'force'          => 'nullable|boolean',
        ], [
            'cause_id.required'     => 'Veuillez choisir une cause.',
            'employee_ids.required' => 'Veuillez sélectionner au moins un employé.',
            'employee_ids.min'      => 'Veuillez sélectionner au moins un employé.',
            'check_out.after'       => "L'heure de départ doit être postérieure à l'heure d'arrivée.",
        ]);

        $force = (bool) ($data['force'] ?? false);
        $cause = RegularizationCause::findOrFail($data['cause_id']);
        $day   = Carbon::parse($data['date'])->format('Y-m-d');

        $checkIn  = Carbon::parse($day . ' ' . $data['check_in']);
        $checkOut = Carbon::parse($day . ' ' . $data['check_out']);
        $hours    = round($checkIn->diffInMinutes($checkOut) / 60, 2);

        $employees = Employee::active()->whereIn('id', $data['employee_ids'])->get();
        $existing  = $this->existingPunches($day, $employees->pluck('id')->all())->keyBy('employee_id');

        $applied = 0;
        $skipped = [];

        DB::transaction(function () use ($employees, $existing, $force, $day, $checkIn, $checkOut, $hours, $cause, $data, &$applied, &$skipped) {
            foreach ($employees as $employee) {
                if ($existing->has($employee->id) && !$force) {
                    $skipped[] = $employee->full_name;
                    continue;
                }

                $attendance = DailyAttendance::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', $day)
                    ->first() ?? new DailyAttendance(['employee_id' => $employee->id, 'attendance_date' => $day]);

                $attendance->fill([
                    'emp_code'             => $employee->emp_code,
                    'check_in'             => $checkIn,
                    'check_out'            => $checkOut,
                    'work_hours'           => $hours,
                    'working_hours'        => $hours,
                    'effective_hours'      => $hours,
                    'status'               => 'PRESENT',
                    'is_late'              => false,
                    'late_minutes'         => 0,
                    'is_early_leave'       => false,
                    'is_overtime'          => false,
                    'is_short_work'        => false,
                    'total_punches'        => 2,
                    'has_multiple_punches' => false,
                    'source'               => self::SOURCE,
                    'notes'                => 'Régularisation — ' . $cause->label,
                    'raw_data'             => json_encode([
                        'source' => self::SOURCE,
                        'cause'  => $cause->label,
                        'by'     => auth()->id(),
                        'at'     => now()->toDateTimeString(),
                    ]),
                ])->save();

                AttendanceRegularization::create([
                    'cause_id'    => $cause->id,
                    'employee_id' => $employee->id,
                    'date'        => $day,
                    'check_in'    => $data['check_in'],
                    'check_out'   => $data['check_out'],
                    'created_by'  => auth()->id(),
                ]);

                $applied++;
            }
        });

        $message = $applied . ' régularisation(s) appliquée(s).';
        if ($skipped) {
            $message .= ' ' . count($skipped) . ' employé(s) ignoré(s) (pointage déjà existant) : ' . implode(', ', $skipped) . '.';
        }

        return response()->json([
            'success' => $applied > 0 || !$skipped,
            'applied' => $applied,
            'skipped' => $skipped,
            'message' => $message,
        ]);
    }

    public function storeCause(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:255',
        ], [
            'label.required' => 'Veuillez saisir le libellé de la cause.',
        ]);

        $label = trim($data['label']);

        if (RegularizationCause::whereRaw('LOWER(label) = ?', [mb_strtolower($label)])->exists()) {
            return response()->json([
                'message' => 'Cette cause existe déjà.',
                'errors'  => ['label' => ['Cette cause existe déjà.']],
            ], 422);
        }

        $cause = RegularizationCause::create(['label' => $label, 'is_protected' => false]);

        return response()->json(['id' => $cause->id, 'label' => $cause->label]);
    }

    public function destroyCause($id)
    {
        $cause = RegularizationCause::findOrFail($id);

        if ($cause->is_protected) {
            return response()->json(['message' => 'Cette cause par défaut ne peut pas être supprimée.'], 422);
        }
        if ($cause->regularizations()->exists()) {
            return response()->json(['message' => 'Cette cause est utilisée par des régularisations.'], 422);
        }

        $cause->delete();

        return response()->json(['message' => 'Cause supprimée.']);
    }

    /**
     * Lignes daily_attendance de la date qui contiennent déjà un pointage
     * (check_in renseigné, ou statut autre qu'absent).
     */
    private function existingPunches(string $date, array $employeeIds)
    {
        return DailyAttendance::whereIn('employee_id', $employeeIds)
            ->whereDate('attendance_date', $date)
            ->where(function ($q) {
                $q->whereNotNull('check_in')->orWhere('status', '!=', 'ABSENT');
            })
            ->get();
    }
}
