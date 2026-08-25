<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:leave_types,name',
        ]);

        try {
            $leaveType = LeaveType::create([
                'name' => $request->name,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Type de congé créé avec succès',
                'data' => $leaveType,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du type de congé: ' . $e->getMessage(),
            ], 500);
        }
    }
}
