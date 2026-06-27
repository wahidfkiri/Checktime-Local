<?php 

// app/Notifications/ResetPasswordNotification.php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{
    public $token;
    
    public function __construct($token)
    {
        $this->token = $token;
    }
    
    public function via($notifiable)
    {
        return ['mail'];
    }
    
    public function toMail($notifiable)
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset()
        ], false));
        
        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe')
            ->view('emails.password-reset', ['resetUrl' => $resetUrl]);
    }
}