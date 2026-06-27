<!DOCTYPE html>
<html>
<head>
    <title>Réinitialisation de votre mot de passe</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { display: inline-block; padding: 12px 24px; background: #198754; 
                  color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Réinitialisation de mot de passe</h2>
        
        <p>Bonjour,</p>
        
        <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
        
        <p>Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe :</p>
        
        <p style="margin: 30px 0;">
            <!-- ICI : Utilisez $actionUrl au lieu de $resetUrl et $token -->
            <a href="{{ $resetUrl }}" class="button">
                Réinitialiser mon mot de passe
            </a>
        </p>
        
        <p>Ou copiez-collez ce lien dans votre navigateur :</p>
        <p style="word-break: break-all; color: #666;">
            {{ $actionUrl }}
        </p>
        
        <p><strong>Important :</strong> Ce lien expirera dans 60 minutes.</p>
        
        <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
        
        <p>Cordialement,<br>L'équipe {{ config('app.name') }}</p>
    </div>
</body>
</html>