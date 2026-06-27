<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapport Mensuel RH</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 25px;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        .content {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .attachment-box {
            background-color: #e8f4fc;
            border: 2px dashed #3498db;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: white;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 RAPPORT MENSUEL RH</h1>
        <h2>Présence & Ponctualité</h2>
        <p>{{ $data['month_name'] }} {{ $data['year'] }} • {{ $data['client']->name }}</p>
    </div>
    
    <div class="content">
        <p>Bonjour,</p>
        
        <p>Veuillez trouver ci-joint le <strong>rapport mensuel de présence et ponctualité</strong> pour le mois de <strong>{{ $data['month_name'] }} {{ $data['year'] }}</strong>.</p>
        
        <!-- Résumé rapide -->
        <div class="stats-grid">
            <div class="stat-card">
                <div>Employés analysés</div>
                <div class="stat-value">{{ $data['global_stats']['total_employees'] ?? 0 }}</div>
            </div>
            <div class="stat-card">
                <div>Taux présence</div>
                <div class="stat-value">{{ $data['global_stats']['avg_presence_rate'] ?? 0 }}%</div>
            </div>
            <div class="stat-card">
                <div>Taux ponctualité</div>
                <div class="stat-value">{{ $data['global_stats']['avg_ponctualite_rate'] ?? 0 }}%</div>
            </div>
            <div class="stat-card">
                <div>Absences</div>
                <div class="stat-value">{{ $data['global_stats']['total_presence_absent'] ?? 0 }}</div>
            </div>
        </div>
        
        <!-- Boîte de pièce jointe -->
        <div class="attachment-box">
            <h3>📎 Pièce jointe</h3>
            <p>
                <strong>{{ $data['pdf_filename'] ?? 'rapport_mensuel.pdf' }}</strong><br>
                <small>Contient le rapport détaillé avec analyses et recommandations</small>
            </p>
            <p style="margin-top: 10px;">
                <em>📄 Format : PDF ({{ $data['period_days'] ?? 0 }} pages)</em>
            </p>
        </div>
        
        <!-- Informations du rapport -->
        <h3>📋 Informations du rapport</h3>
        <ul>
            <li><strong>Période analysée :</strong> {{ $data['start_date'] }} au {{ $data['end_date'] }}</li>
            <li><strong>Nombre de jours :</strong> {{ $data['period_days'] ?? 0 }} jours ouvrables</li>
            <li><strong>Généré le :</strong> {{ $data['generated_at']->format('d/m/Y à H:i') }} par le système CHECKTIME - Tél: 0141555592.</li>
            <li><strong>Format :</strong> PDF avec tableau détaillé et analyses</li>
        </ul>
        
        <!-- Accès au système -->
        <div style="background-color: #f1f2f6; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3>🔗 Accès au système</h3>
            <p>Pour plus de détails ou pour consulter les rapports individuels :</p>
            <p>
                <strong>Système de Gestion :</strong> <a href="{{ config('app.url') }}">{{ config('app.url') }}</a><br>
                <strong>Client :</strong> {{ $data['client']->name }}
            </p>
        </div>
        
        <p style="margin-top: 25px;">
            Cordialement,<br>
            <strong>Le Service RH - {{ $data['client']->name }}</strong>
        </p>
        
        <p><em>Cet email est généré automatiquement par le système de gestion des présences.</em></p>
    </div>
    
    <div class="footer">
        <p>📧 Ceci est un email automatique, merci de ne pas y répondre directement.</p>
        <p>© {{ date('Y') }} {{ $data['client']->name }}. Tous droits réservés.</p>
        <p style="font-size: 10px; color: #95a5a6;">
            Confidentialité : Ce rapport contient des informations confidentielles.
        </p>
    </div>
</body>
</html>
