<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .info-box {
            background-color: #e8f5e9;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ Test d'Email Réussi</h1>
        <p>Système de Gestion des Présences</p>
    </div>
    
    <div class="content">
        @if($data['type'] === 'rh')
        <p>Bonjour,</p>
        <p>Ceci est un email de test pour vérifier la configuration des emails RH.</p>
        
        <div class="info-box">
            <h3>Détails du test</h3>
            <p><strong>Client :</strong> {{ $data['client']->name }}</p>
            <p><strong>Email RH :</strong> {{ $data['settings']->email }}</p>
            <p><strong>Date/Heure :</strong> {{ $data['test_time']->format('d/m/Y H:i:s') }}</p>
            <p><strong>Statut :</strong> <span style="color: #4CAF50; font-weight: bold;">ACTIF</span></p>
        </div>
        
        <p>Si vous recevez cet email, cela signifie que :</p>
        <ol>
            <li>Les paramètres emails sont correctement configurés</li>
            <li>Les rapports mensuels RH seront envoyés à cette adresse</li>
            <li>Le système de notifications fonctionne correctement</li>
        </ol>
        
        @else
        <p>Bonjour {{ $data['employee']->first_name }},</p>
        <p>Ceci est un email de test pour vérifier la configuration des emails employés.</p>
        
        <div class="info-box">
            <h3>Détails du test</h3>
            <p><strong>Employé :</strong> {{ $data['employee']->first_name }} {{ $data['employee']->last_name }}</p>
            <p><strong>Code :</strong> {{ $data['employee']->emp_code }}</p>
            <p><strong>Email :</strong> {{ $data['employee']->email }}</p>
            <p><strong>Date/Heure :</strong> {{ $data['test_time']->format('d/m/Y H:i:s') }}</p>
            <p><strong>Client :</strong> {{ $data['client']->name }}</p>
        </div>
        
        <p>Si vous recevez cet email, cela signifie que :</p>
        <ol>
            <li>Votre email est correctement configuré dans le système</li>
            <li>Vous recevrez vos rapports de présence hebdomadaires</li>
            <li>Le système de notifications fonctionne correctement</li>
        </ol>
        @endif
        
        <p>Cet email est généré automatiquement, merci de ne pas y répondre.</p>
    </div>
    
    <div class="footer">
        <p>© {{ date('Y') }} {{ $data['client']->name }}. Tous droits réservés.</p>
        <p>Système de Gestion des Présences - Email de test</p>
    </div>
</body>
</html>