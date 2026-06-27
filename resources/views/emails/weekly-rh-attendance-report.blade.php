<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport de présence hebdomadaire</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.5;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 3px solid #1a5276;
        }
        .header h1 {
            color: #1a5276;
            font-size: 22px;
            margin: 0;
        }
        .period {
            background-color: #e8edf9;
            padding: 12px;
            text-align: center;
            margin: 20px 0;
            border-radius: 6px;
        }
        .period strong {
            color: #1a5276;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        .stat-item {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 12px 8px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #1a5276;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
        }
        .message-box {
            background-color: #eaf7ea;
            border-left: 4px solid #2ecc71;
            padding: 12px 16px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .btn {
            display: inline-block;
            background-color: #1a5276;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: bold;
            margin: 15px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #888;
            text-align: center;
        }
        .rate-high {
            color: #008000;
        }
        .rate-medium {
            color: #ff9900;
        }
        .rate-low {
            color: #ff0000;
        }
        @media only screen and (max-width: 480px) {
            .stat-value { font-size: 20px; }
            .container { padding: 15px; }
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- HEADER --}}
        <div class="header">
            <h1>📊 Rapport de présence hebdomadaire</h1>
        </div>

        {{-- SALUTATION --}}
        <p>Bonjour <strong>Service RH</strong>,</p>

        <p>Veuillez trouver ci-joint le rapport de présence pour la période du <strong>{{ $startDate }}</strong> au <strong>{{ $endDate }}</strong>.</p>

        {{-- PÉRIODE RÉCAP --}}
        <div class="period">
            📅 <strong>Période :</strong> {{ $startDate }} au {{ $endDate }}<br>
            🏢 <strong>Client :</strong> {{ $clientName }}<br>
            📊 <strong>Départements :</strong> {{ $totalDepartments }} | 👥 <strong>Employés :</strong> {{ $totalEmployees }}
        </div>

        {{-- STATS RAPIDES --}}
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value">{{ $totalEmployees }}</div>
                <div class="stat-label">Employés actifs</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $totalDepartments }}</div>
                <div class="stat-label">Départements</div>
            </div>
            <div class="stat-item">
                <div class="stat-value 
                    @if($avgPresenceRate >= 90) rate-high
                    @elseif($avgPresenceRate >= 80) rate-medium
                    @else rate-low
                    @endif">
                    {{ number_format($avgPresenceRate, 1) }}%
                </div>
                <div class="stat-label">Taux présence global</div>
            </div>
        </div>

        {{-- MESSAGE INFO PDF --}}
        <div class="message-box">
            📎 <strong>Document joint</strong><br>
            Le fichier PDF ci-joint contient :
            <ul style="margin: 8px 0 0 20px;">
                <li>📊 Tableau récapitulatif par département (présence, absence, ponctualité)</li>
                <li>📋 Tableau détaillé des pointages jour par jour pour chaque employé</li>
                <li>📈 Statistiques globales et observations</li>
            </ul>
        </div>

        {{-- LIEN SI BESOIN --}}
        <p style="text-align: center;">
            <a href="#" class="btn" style="color: white; background-color: #1a5276; text-decoration: none;">📄 Consulter le rapport</a>
        </p>

        {{-- FORMULE DE POLITESSE --}}
        <p>
            Cordialement,<br>
            <strong>Service de Gestion des Présences</strong><br>
            <span style="color: #888; font-size: 12px;">Rapport automatique hebdomadaire</span>
        </p>

        {{-- FOOTER --}}
        <div class="footer">
            <p>
                Cet email a été généré automatiquement. Merci de ne pas y répondre.<br>
                © {{ date('Y') }} - Rapport de présence hebdomadaire
            </p>
        </div>
    </div>
</body>
</html>