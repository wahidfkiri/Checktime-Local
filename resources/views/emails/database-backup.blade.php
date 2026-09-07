<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sauvegarde de la base de données</title>
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
            background: linear-gradient(135deg, #2c3e50, #16a085);
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
            font-size: 22px;
            font-weight: bold;
            margin: 8px 0;
        }
        .attachment-box {
            background-color: #e8f8f0;
            border: 2px dashed #16a085;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .warning-box {
            background-color: #fff8e1;
            border: 2px dashed #f39c12;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
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
        <h1>💾 SAUVEGARDE DE LA BASE DE DONNÉES</h1>
        <p>{{ $appName }} • {{ now()->format('d/m/Y à H:i') }}</p>
    </div>

    <div class="content">
        <p>Bonjour,</p>

        <p>Une sauvegarde automatique de la base de données a été générée.</p>

        <div class="stats-grid">
            <div class="stat-card">
                <div>Tables</div>
                <div class="stat-value">{{ $backup->tables_count }}</div>
            </div>
            <div class="stat-card">
                <div>Lignes</div>
                <div class="stat-value">{{ number_format($backup->rows_count, 0, ',', ' ') }}</div>
            </div>
            <div class="stat-card">
                <div>Taille</div>
                <div class="stat-value">{{ $backup->size_human }}</div>
            </div>
        </div>

        @if($attachmentOmitted)
            <div class="warning-box">
                <h3>⚠️ Archive non jointe</h3>
                <p>
                    L'archive ({{ $backup->size_human }}) dépasse la taille acceptée en pièce jointe email.<br>
                    Elle reste disponible au téléchargement dans l'application, sous
                    <strong>Paramètres &gt; Sauvegarde des données</strong>.
                </p>
            </div>
        @else
            <div class="attachment-box">
                <h3>📎 Pièce jointe</h3>
                <p>
                    <strong>{{ $backup->filename }}</strong><br>
                    <small>Archive .zip contenant le dump SQL (structure + données) et un export CSV par table</small>
                </p>
            </div>
        @endif

        <p style="margin-top: 25px;">
            Cordialement,<br>
            <strong>{{ $appName }}</strong>
        </p>

        <p><em>Cet email est généré automatiquement par la planification des sauvegardes.</em></p>
    </div>

    <div class="footer">
        <p>📧 Ceci est un email automatique, merci de ne pas y répondre directement.</p>
        <p style="font-size: 10px; color: #95a5a6;">
            Confidentialité : cette archive contient l'intégralité des données de l'application. Conservez-la en lieu sûr.
        </p>
    </div>
</body>
</html>
