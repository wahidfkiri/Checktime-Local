<?php

namespace App\Http\Controllers;

use App\Models\Signataire;
use App\Models\SignatairePoste;
use Illuminate\Http\Request;

/**
 * Gestion des signataires (cartouche de signatures des rapports).
 * Un poste (colonne) possède plusieurs responsables (Nom complet + fonction).
 */
class SignataireController extends Controller
{
    /**
     * Lister les postes et leurs responsables (AJAX).
     */
    public function index()
    {
        $postes = SignatairePoste::with('signataires')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return response()->json(['success' => true, 'postes' => $postes]);
    }

    /**
     * Créer un poste.
     */
    public function storePoste(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $poste = SignatairePoste::create([
            'name'     => trim($request->input('name')),
            'position' => (int) SignatairePoste::max('position') + 1,
        ]);

        $poste->load('signataires');

        return response()->json([
            'success' => true,
            'message' => 'Poste ajouté avec succès.',
            'poste'   => $poste,
        ]);
    }

    /**
     * Modifier le nom d'un poste.
     */
    public function updatePoste(Request $request, $id)
    {
        $poste = SignatairePoste::find($id);

        if (!$poste) {
            return response()->json(['success' => false, 'message' => 'Poste non trouvé.'], 404);
        }

        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $poste->update(['name' => trim($request->input('name'))]);

        return response()->json([
            'success' => true,
            'message' => 'Poste modifié avec succès.',
            'poste'   => $poste,
        ]);
    }

    /**
     * Supprimer un poste (et ses responsables via cascade).
     */
    public function destroyPoste($id)
    {
        $poste = SignatairePoste::find($id);

        if (!$poste) {
            return response()->json(['success' => false, 'message' => 'Poste non trouvé.'], 404);
        }

        $poste->delete();

        return response()->json(['success' => true, 'message' => 'Poste supprimé avec succès.']);
    }

    /**
     * Ajouter un responsable à un poste.
     */
    public function storeSignataire(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'poste_id'  => 'required|integer',
            'full_name' => 'required|string|max:255',
            'fonction'  => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $poste = SignatairePoste::find($request->input('poste_id'));

        if (!$poste) {
            return response()->json(['success' => false, 'message' => 'Poste non trouvé.'], 404);
        }

        $signataire = Signataire::create([
            'poste_id'  => $poste->id,
            'full_name' => trim($request->input('full_name')),
            'fonction'  => trim((string) $request->input('fonction')),
            'position'  => (int) Signataire::where('poste_id', $poste->id)->max('position') + 1,
        ]);

        return response()->json([
            'success'    => true,
            'message'    => 'Responsable ajouté avec succès.',
            'signataire' => $signataire,
        ]);
    }

    /**
     * Supprimer un responsable.
     */
    public function destroySignataire($id)
    {
        $signataire = Signataire::find($id);

        if (!$signataire) {
            return response()->json(['success' => false, 'message' => 'Responsable non trouvé.'], 404);
        }

        $signataire->delete();

        return response()->json(['success' => true, 'message' => 'Responsable supprimé avec succès.']);
    }
}
