<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\Employee;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class LeaveController extends Controller
{
    public function index()
    {
        $employees = Employee::where('status', 'active')
            ->orderBy('first_name')
            ->get();
        
        $leaveTypes = LeaveType::where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('leaves.index', compact('employees', 'leaveTypes'));
    }

    public function datatable(Request $request)
    {
        $query = Leave::with(['employee', 'type']);
        
        if ($request->employee_filter) {
            $query->where('employee_id', $request->employee_filter);
        }
        
        if ($request->type_filter) {
            $query->where('type_id', $request->type_filter);
        }
        
        if ($request->status_filter) {
            $query->where('status', $request->status_filter);
        }
        
        return DataTables::of($query)
            ->addColumn('employee_name', function($leave) {
                return $leave->employee ? $leave->employee->first_name . ' ' . $leave->employee->last_name : null;
            })
            ->addColumn('duration', function($leave) {
                return \Carbon\Carbon::parse($leave->start_date)->diffInDays(\Carbon\Carbon::parse($leave->end_date)) + 1;
            })
            ->addColumn('status', function($leave) {
                return ucfirst($leave->status);
            })
            ->addColumn('type_name', function($leave) {
                return $leave->type ? $leave->type->name : null;
            })
            ->filterColumn('employee_name', function($query, $keyword) {
                $query->whereHas('employee', function($q) use ($keyword) {
                    $q->where('first_name', 'like', "%{$keyword}%")
                      ->orWhere('last_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('type_name', function($query, $keyword) {
                $query->whereHas('type', function($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'status' => 'required|in:pending,approved,rejected',
        ]);

        try {
            $leave = Leave::create([
                'employee_id' => $request->employee_id,
                'type_id' => $request->type_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'reason' => $request->reason,
                'status' => $request->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Congé créé avec succès',
                'data' => $leave
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du congé: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $leave = Leave::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $leave->id,
                    'employee_id' => $leave->employee_id,
                    'type_id' => $leave->type_id,
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'reason' => $leave->reason,
                    'status' => $leave->status,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Congé non trouvé'
            ], 402);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'status' => 'required|in:pending,approved,rejected',
        ]);

        try {
            $leave = Leave::findOrFail($id);
            
            $leave->update([
                'employee_id' => $request->employee_id,
                'type_id' => $request->type_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'reason' => $request->reason,
                'status' => $request->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Congé mis à jour avec succès',
                'data' => $leave
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du congé: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
            'comments' => 'nullable|string|max:500',
        ]);

        try {
            $leave = Leave::findOrFail($id);
            
            $leave->update([
                'status' => $request->status,
                'comments' => $request->comments,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Statut du congé mis à jour avec succès',
                'data' => $leave
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $leave = Leave::findOrFail($id);
            $leave->delete();

            return response()->json([
                'success' => true,
                'message' => 'Congé supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du congé: ' . $e->getMessage()
            ], 500);
        }
    }
}