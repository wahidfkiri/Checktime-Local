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
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
            margin-bottom: 25px;
        }
        .content {
            background-color: white;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .summary-box {
            background-color: #e8f4fc;
            border-left: 5px solid #3498db;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
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
            padding: 20px;
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-good { color: #27ae60; }
        .stat-warning { color: #f39c12; }
        .stat-danger { color: #e74c3c; }
        .stat-info { color: #3498db; }
        .mini-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }
        .mini-table th {
            background-color: #f1f2f6;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
        }
        .mini-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .mini-table tr:hover {
            background-color: #f9f9f9;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
        }
        .alert-box {
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .section-title {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin: 30px 0 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📈 RAPPORT MENSUEL RH</h1>
        <h2>Présence & Ponctualité</h2>
        <p>{{ $data['month_name'] }} {{ $data['year'] }} • {{ $data['client']->name }}</p>
        <p>Période : {{ $data['start_date'] }} au {{ $data['end_date'] }}</p>
    </div>
    
    <div class="content">
        <p>Bonjour,</p>
        
        <p>Voici le <strong>rapport mensuel de présence et ponctualité</strong> pour le mois de <strong>{{ $data['month_name'] }} {{ $data['year'] }}</strong>.</p>
        
        <!-- Résumé exécutif -->
        <div class="summary-box">
            <h3>📋 Résumé Exécutif</h3>
            <p>Ce rapport analyse la présence et la ponctualité de <strong>{{ $data['global_stats']['total_employees'] ?? 0 }} employés</strong> sur <strong>{{ $data['period_days'] }} jours ouvrables</strong>.</p>
        </div>
        
        <!-- Statistiques clés -->
        <h3 class="section-title">📊 Statistiques Clés</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <div>Employés analysés</div>
                <div class="stat-value stat-info">{{ $data['global_stats']['total_employees'] ?? 0 }}</div>
            </div>
            <div class="stat-card">
                <div>Taux moyen présence</div>
                <div class="stat-value {{ ($data['global_stats']['avg_presence_rate'] ?? 0) >= 90 ? 'stat-good' : (($data['global_stats']['avg_presence_rate'] ?? 0) >= 80 ? 'stat-warning' : 'stat-danger') }}">
                    {{ $data['global_stats']['avg_presence_rate'] ?? 0 }}%
                </div>
            </div>
            <div class="stat-card">
                <div>Taux moyen ponctualité</div>
                <div class="stat-value {{ ($data['global_stats']['avg_ponctualite_rate'] ?? 0) >= 90 ? 'stat-good' : (($data['global_stats']['avg_ponctualite_rate'] ?? 0) >= 80 ? 'stat-warning' : 'stat-danger') }}">
                    {{ $data['global_stats']['avg_ponctualite_rate'] ?? 0 }}%
                </div>
            </div>
            <div class="stat-card">
                <div>Absences totales</div>
                <div class="stat-value {{ ($data['global_stats']['total_presence_absent'] ?? 0) > 10 ? 'stat-danger' : (($data['global_stats']['total_presence_absent'] ?? 0) > 5 ? 'stat-warning' : 'stat-good') }}">
                    {{ $data['global_stats']['total_presence_absent'] ?? 0 }}
                </div>
            </div>
        </div>
        
        <!-- Top 5 employés -->
        @if(!empty($data['global_stats']['top_employees']))
        <h3 class="section-title">🏆 Top 5 - Meilleure Présence</h3>
        <table class="mini-table">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Employé</th>
                    <th>Code</th>
                    <th>Taux présence</th>
                    <th>Taux ponctualité</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['global_stats']['top_employees'] as $employee)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $employee['employee_name'] }}</td>
                    <td>{{ $employee['employee_code'] }}</td>
                    <td>
                        <span class="badge {{ $employee['presence_data']['rate'] >= 90 ? 'badge-success' : ($employee['presence_data']['rate'] >= 80 ? 'badge-warning' : 'badge-danger') }}">
                            {{ $employee['presence_data']['rate'] }}%
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $employee['ponctualite_data']['rate'] >= 90 ? 'badge-success' : ($employee['ponctualite_data']['rate'] >= 80 ? 'badge-warning' : 'badge-danger') }}">
                            {{ $employee['ponctualite_data']['rate'] }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        
        <!-- Observations -->
        @if(!empty($data['global_stats']['bottom_employees']))
        <h3 class="section-title">⚠️ À Surveiller</h3>
        <div class="alert-box">
            <p><strong>{{ count($data['global_stats']['bottom_employees']) }} employé(s)</strong> nécessitent une attention particulière :</p>
            <ul>
                @foreach($data['global_stats']['bottom_employees'] as $employee)
                <li>
                    <strong>{{ $employee['employee_name'] }}</strong> ({{ $employee['employee_code'] }}) - 
                    Présence: {{ $employee['presence_data']['rate'] }}%, 
                    Ponctualité: {{ $employee['ponctualite_data']['rate'] }}%
                </li>
                @endforeach
            </ul>
        </div>
        @endif
        
        <!-- Détails des absences -->
        <h3 class="section-title">📅 Détails des Absences</h3>
        <p>
            <strong>Total des présences :</strong> {{ $data['global_stats']['total_presence_present'] ?? 0 }}<br>
            <strong>Total des absences :</strong> {{ $data['global_stats']['total_presence_absent'] ?? 0 }}<br>
            <strong>Total des retards :</strong> {{ $data['global_stats']['total_ponctualite_late'] ?? 0 }}
        </p>
        
        <!-- Recommandations -->
        <h3 class="section-title">💡 Recommandations</h3>
        <ul>
            @if(($data['global_stats']['avg_presence_rate'] ?? 0) < 85)
            <li>Renforcer le suivi des absences non justifiées</li>
            @endif
            @if(($data['global_stats']['avg_ponctualite_rate'] ?? 0) < 85)
            <li>Rappeler l'importance de la ponctualité aux équipes</li>
            @endif
            @if(($data['global_stats']['total_ponctualite_late'] ?? 0) > 20)
            <li>Analyser les causes récurrentes des retards</li>
            @endif
            @if(count($data['global_stats']['bottom_employees'] ?? []) > 0)
            <li>Entretiens individuels avec les employés en difficulté</li>
            @endif
        </ul>
        
        <!-- Accès au système -->
        <div class="summary-box">
            <h3>🔗 Accès au système</h3>
            <p>Pour plus de détails ou pour consulter les rapports individuels :</p>
            <p>
                <strong>Système de Gestion :</strong> <a href="{{ config('app.url') }}">{{ config('app.url') }}</a><br>
                <strong>Client :</strong> {{ $data['client']->name }}
            </p>
        </div>
        
        <p style="margin-top: 30px;">
            Cordialement,<br>
            <strong>Le Service RH - {{ $data['client']->name }}</strong>
        </p>
        
        <p><em>Ce rapport est généré automatiquement par le système de gestion des présences.</em></p>
    </div>
    
    <div class="footer">
        <p>📧 Ceci est un email automatique, merci de ne pas y répondre directement.</p>
        <p>© {{ date('Y') }} {{ $data['client']->name }}. Tous droits réservés.</p>
        <p>Rapport généré le {{ $data['generated_at']->format('d/m/Y à H:i') }} par le système CHECKTIME - Tél: 0141555592.</p>
        <p style="font-size: 10px; color: #95a5a6;">
            Confidentialité : Ce rapport contient des informations confidentielles destinées uniquement au(x) destinataire(s) mentionné(s).
        </p>
    </div>
</body>
</html>
