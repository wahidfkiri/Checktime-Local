<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        return view('profile');
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        if ($request->update_type === 'profile') {
            // Validation pour la mise à jour du profil
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            ]);
            
            $user->name = $request->name;
            $user->email = $request->email;
            $user->save();
            
            return redirect()->route('profile')->with('success', 'Profil mis à jour avec succès!');
            
        } elseif ($request->update_type === 'password') {
            // Validation pour le changement de mot de passe
            $request->validate([
                'current_password' => 'required',
                'password' => 'required|string|min:8|confirmed',
            ]);
            
            // Vérifier le mot de passe actuel
            if (!Hash::check($request->current_password, $user->password)) {
                return redirect()->route('profile')
                    ->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
            }
            
            // Mettre à jour le mot de passe
            $user->password = Hash::make($request->password);
            $user->save();
            
            return redirect()->route('profile')->with('success', 'Mot de passe changé avec succès!');
        }
        
        return redirect()->route('profile');
    }
}