<?php

namespace App\Http\Controllers;

use App\Services\BiometricService;
use App\Services\CheckTimeService;
use Illuminate\Http\Request;

class BiometricController extends Controller
{
    protected $biometricService;
    
    public function __construct(BiometricService $biometricService)
    {
        $this->biometricService = $biometricService;
    }
    
    /**
     * Récupérer les transactions avec vérification biométrique
     */
    public function getTransactions(Request $request)
    {
        try {
            $generalToken = CheckTimeService::getConfigToken();
            $response = $this->biometricService->getTransactions($request, $generalToken);
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Générer une réponse biométrique pour un employé spécifique.
     * Identification par employee_id (id externe unique) — les emp_code
     * peuvent être en doublon.
     */
    public function getBiometricVerification($id)
    {
        $employee = \App\Models\Employee::where('employee_id', $id)->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'error'   => "Employé introuvable (employee_id {$id})",
            ], 404);
        }

        $biometricData = $this->biometricService->generateBiometricResponseForEmployee($employee);

        return response()->json($biometricData);
    }
}