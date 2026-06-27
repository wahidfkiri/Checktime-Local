<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class ResetPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    /**
     * Reset the given user's password.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);
        
        Log::info('Password reset attempt for email: ' . $request->email);
        
        try {
            // Vérifier si l'utilisateur existe
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                Log::warning('Password reset attempt for non-existent user: ' . $request->email);
                
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'Utilisateur non trouvé.']);
            }
            
            Log::info('Proceeding with password reset for: ' . $request->email);
            
            // Réinitialiser le mot de passe
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->setRememberToken(Str::random(60));

                    $user->save();

                    event(new PasswordReset($user));
                    
                    Log::info('Password successfully reset for user: ' . $user->email);
                }
            );
            
            Log::info('Password reset status for ' . $request->email . ': ' . $status);
            
            return $status == Password::PASSWORD_RESET
                ? redirect()->route('login')->with('status', 'Votre mot de passe a été réinitialisé avec succès !')
                : back()->withErrors(['email' => __($status)]);
                
        } catch (\Exception $e) {
            Log::error('Password reset error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Une erreur technique est survenue. Veuillez réessayer.']);
        }
    }
}