<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\CheckTimeService;
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

        // La clé d'accès n'est jamais renvoyée au navigateur une fois enregistrée
        // (voir updateAccessKey ci-dessous) : on indique seulement si elle est
        // configurée, pour ne pas exposer le secret dans le HTML de la page.
        $hasAccessKey = !empty(Setting::getGroup('company')['api_token'] ?? null);

        return view('settings.index', compact('settings', 'hasAccessKey'));
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
     * Enregistrer / remplacer la clé d'accès (token API biométrique).
     *
     * Stockée en base via Setting::set('api_token', ..., 'company') — même
     * emplacement que celui lu par CheckTimeService et rempli par
     * l'installateur, pour que la synchronisation des employés / présences
     * fonctionne dès l'enregistrement.
     */
    public function updateAccessKey(Request $request)
    {
        try {
            $validated = $request->validate([
                'access_key' => 'required|string|max:500',
            ]);

            Setting::set('api_token', trim($validated['access_key']), 'company');

            Log::info('Clé d\'accès API mise à jour depuis les paramètres.');

            return response()->json([
                'success' => true,
                'message' => 'Clé d\'accès enregistrée avec succès.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour clé d\'accès: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Teste la validité de la clé d'accès via une requête réelle vers
     * l'appareil biométrique (en arrière-plan, ne bloque pas la page).
     *
     * - Si le formulaire contient une valeur (candidate pas encore
     *   enregistrée), c'est ELLE qui est testée.
     * - Sinon, teste la clé actuellement active, quelle que soit sa source
     *   (table settings, ou repli .env CHECKTIME_TOKEN).
     */
    public function testAccessKey(Request $request)
    {
        try {
            $token = trim((string) $request->input('access_key', ''));
            $source = 'saisie dans le formulaire';

            if ($token === '') {
                $token = (string) CheckTimeService::getConfigToken();
                $source = !empty(Setting::getGroup('company')['api_token'] ?? null)
                    ? 'enregistrée dans les paramètres'
                    : 'repli .env (CHECKTIME_TOKEN)';
            }

            if ($token === '') {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => "Aucune clé à tester : rien n'est enregistré ni défini dans .env.",
                ], 400);
            }

            $service = new CheckTimeService();
            $isValid = $service->testTokenValid($token);

            Log::info('Test de la clé d\'accès API', ['source' => $source, 'valid' => $isValid]);

            return response()->json([
                'success' => true,
                'valid' => $isValid,
                'source' => $source,
                'message' => $isValid
                    ? "Clé valide (source : {$source}) — connexion à l'appareil biométrique réussie."
                    : "Clé invalide ou rejetée par l'appareil biométrique (source : {$source}).",
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur test clé d\'accès: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Erreur lors du test (connexion impossible) : ' . $e->getMessage(),
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
                'client' => \App\Models\Setting::company(),
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
                'client' => \App\Models\Setting::company(),
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