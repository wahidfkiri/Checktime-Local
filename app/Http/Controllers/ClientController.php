<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * Company Profile Controller
 * 
 * Repurposed from the multi-client ClientController.
 * Manages the single company's profile information (formerly stored in the clients table).
 */
class ClientController extends Controller
{
    /**
     * Affiche le profil de l'entreprise
     */
    public function index()
    {
        $companySettings = Setting::getGroup('company');
        return view('clients.index', compact('companySettings'));
    }

    /**
     * Met à jour le profil de l'entreprise
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'raison_sociale' => 'required|string|max:255',
                'sigle' => 'nullable|string|max:50',
                'rccm' => 'nullable|string|max:255',
                'ifu' => 'nullable|string|max:255',
                'directeur' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'telephone' => 'nullable|string|max:20',
                'adresse' => 'nullable|string|max:500',
                'ville' => 'nullable|string|max:100',
            ]);

            foreach ($validated as $key => $value) {
                Setting::set($key, $value, 'company');
            }

            return response()->json([
                'success' => true,
                'message' => 'Profil de l\'entreprise mis à jour avec succès.'
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche le formulaire d'édition (pour modal)
     */
    public function edit()
    {
        $companySettings = Setting::getGroup('company');
        return view('clients.modals.edit', ['client' => $companySettings]);
    }
}