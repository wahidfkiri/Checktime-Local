<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    /**
     * Catalogue des permissions liées aux liens du menu, groupées pour
     * l'affichage du formulaire (voir resources/views/components/side-bar.blade.php
     * pour la correspondance avec chaque lien).
     */
    private function menuPermissionGroups(): array
    {
        return [
            'Gestion des employés' => [
                'menu.employees' => 'Liste des employés',
                'menu.departments' => 'Départements',
                'menu.areas' => 'Zones',
            ],
            'Gestion des plannings' => [
                'menu.work-hours' => "Types d'horaires",
                'menu.employee-schedules' => 'Plannings employés',
                'menu.schedules' => 'Calendrier',
            ],
            'Gestion des autorisations' => [
                'menu.employee-permissions' => 'Permissions',
                'menu.missions' => 'Missions',
                'menu.leaves' => 'Congés',
            ],
            'Suivi' => [
                'menu.daily-attendance' => 'Historique des pointages (toutes les sous-pages)',
                'menu.attendance-history' => '↳ Historique complet',
                'menu.attendance-presence' => '↳ Liste des présences',
                'menu.attendance-absence' => '↳ Liste des absences',
                'menu.attendance-retards' => '↳ Liste des retards',
                'menu.reports' => 'Rapports des présences (toutes les sous-pages)',
                'menu.reports-absences-delays' => "↳ État de pointage (arrivées – départs)",
                'menu.reports-custom-presence' => "↳ Rapport d'assiduité et de ponctualité",
            ],
            'Administration' => [
                'menu.devices' => 'Appareils',
                'menu.settings' => 'Paramètres',
            ],
        ];
    }

    public function index()
    {
        $users = User::with('roles', 'permissions')
            ->orderBy('id')
            ->get();

        $permissionGroups = $this->menuPermissionGroups();

        return view('users.index', compact('users', 'permissionGroups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'is_admin' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if ($request->boolean('is_admin')) {
            $user->assignRole('admin');
        } else {
            $user->syncPermissions($validated['permissions'] ?? []);
        }

        Log::info("Utilisateur créé: {$user->email}");

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
        ]);
    }

    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->hasRole('admin'),
                'permissions' => $user->getDirectPermissions()->pluck('name'),
            ],
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'is_admin' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        // Dernier admin : on ne peut pas le rétrograder, sinon plus personne
        // ne pourrait gérer les utilisateurs.
        $wasAdmin = $user->hasRole('admin');
        $willBeAdmin = $request->boolean('is_admin');
        if ($wasAdmin && !$willBeAdmin && User::role('admin')->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => "Impossible de retirer le rôle admin : c'est le dernier administrateur.",
            ], 422);
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        if ($willBeAdmin) {
            $user->syncRoles(['admin']);
            $user->syncPermissions([]);
        } else {
            $user->syncRoles([]);
            $user->syncPermissions($validated['permissions'] ?? []);
        }

        Log::info("Utilisateur mis à jour: {$user->email}");

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès',
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ], 422);
        }

        if ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => "Impossible de supprimer le dernier administrateur.",
            ], 422);
        }

        $email = $user->email;
        $user->delete();

        Log::info("Utilisateur supprimé: {$email}");

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès',
        ]);
    }
}
