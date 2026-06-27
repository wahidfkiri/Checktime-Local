<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    /**
     * Afficher la page des paramètres
     */
    public function index()
    {
        $settings = Setting::first();
        
        return view('settings.index', compact('settings'));
    }
    
    /**
     * Mettre à jour les paramètres
     */
    public function update(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'email' => 'nullable|email',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $settings = Setting::updateOrCreate(
                ['id' => Setting::value('id') ?? 0],
                [
                    'email' => $request->input('email', ''),
                    'email_is_active' => $request->boolean('email_is_active'),
                    'email_employees_is_active' => $request->boolean('email_employees_is_active'),
                    'sms_is_active' => $request->boolean('sms_is_active')
                ]
            );
            
            Log::info('Paramètres mis à jour', $settings->toArray());
            
            return response()->json([
                'success' => true,
                'message' => 'Paramètres mis à jour avec succès.',
                'settings' => $settings
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour paramètres: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Tester l'envoi d'email RH
     */
    public function testRhEmail(Request $request)
    {
        try {
            $settings = Setting::first();
            
            if (!$settings || empty($settings->email)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email RH non configuré.'
                ], 400);
            }
            
            $testData = [
                'settings' => $settings,
                'test_time' => now(),
                'type' => 'rh'
            ];
            
            \Illuminate\Support\Facades\Mail::to($settings->email)
                ->send(new \App\Mail\TestEmail($testData));
            
            Log::info('Email de test RH envoyé à: ' . $settings->email);
            
            return response()->json([
                'success' => true,
                'message' => 'Email de test envoyé avec succès à: ' . $settings->email
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur envoi email test RH: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur envoi email: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Tester l'envoi d'email aux employés
     */
    public function testEmployeesEmail(Request $request)
    {
        try {
            $settings = Setting::first();
            
            if (!$settings || !$settings->email_employees_is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Emails employés désactivés ou non configurés.'
                ], 400);
            }
            
            $employee = \App\Models\Employee::whereNotNull('email')
                ->where('email', '!=', '')
                ->first();
            
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun employé avec email trouvé.'
                ], 400);
            }
            
            $testData = [
                'employee' => $employee,
                'settings' => $settings,
                'test_time' => now(),
                'type' => 'employee'
            ];
            
            \Illuminate\Support\Facades\Mail::to($employee->email)
                ->send(new \App\Mail\TestEmail($testData));
            
            Log::info('Email de test employé envoyé à: ' . $employee->email);
            
            return response()->json([
                'success' => true,
                'message' => 'Email de test envoyé à: ' . $employee->email
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur envoi email test employé: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur envoi email: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtenir le statut des paramètres
     */
    public function getStatus()
    {
        try {
            $settings = Setting::first();
            
            if (!$settings) {
                $settings = (object)[
                    'email' => null,
                    'email_is_active' => false,
                    'email_employees_is_active' => false,
                    'sms_is_active' => false,
                    'sms_credit' => 0
                ];
            }
            
            $employeesWithEmail = \App\Models\Employee::whereNotNull('email')
                ->where('email', '!=', '')
                ->count();
            
            return response()->json([
                'success' => true,
                'settings' => $settings,
                'stats' => [
                    'employees_with_email' => $employeesWithEmail,
                    'total_employees' => \App\Models\Employee::count()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur récupération statut paramètres: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }
}