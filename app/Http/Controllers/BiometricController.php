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
     * Générer une réponse biométrique pour un employé spécifique
     */
    public function getBiometricVerification($employeeCode)
    {
       // try {
            $biometricData = $this->biometricService->generateBiometricResponse($employeeCode);
            return response()->json($biometricData);
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'error' => $e->getMessage()
        //     ], 404);
        // }
    }
}