<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport Mensuel RH - {{ $data['month_name'] }} {{ $data['year'] }}</title>
    <style>
        /* Style pour le PDF */
        @page {
            margin: 20mm;
            size: A4 landscape;
        }
        
        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 0;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 18pt;
            margin: 0 0 5px 0;
        }
        
        .header h2 {
            color: #3498db;
            font-size: 14pt;
            margin: 0 0 10px 0;
        }
        
        .header .period {
            font-size: 11pt;
            color: #7f8c8d;
        }
        
        .summary-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .summary-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 12pt;
        }
        
        .stats-grid {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .stat-card {
            text-align: center;
            flex: 1;
            margin: 0 5px;
            border: 1px solid #e1e5e9;
            border-radius: 4px;
            padding: 10px;
        }
        
        .stat-value {
            font-size: 16pt;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .stat-good { color: #27ae60; }
        .stat-warning { color: #f39c12; }
        .stat-danger { color: #e74c3c; }
        .stat-info { color: #3498db; }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 9pt;
        }
        
        .report-table th {
            background-color: #2c3e50;
            color: white;
            text-align: center;
            padding: 8px;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        
        .report-table td {
            padding: 6px;
            border: 1px solid #ddd;
            text-align: center;
        }
        
        .report-table .main-header {
            background-color: #34495e;
        }
        
        .report-table .sub-header {
            background-color: #95a5a6;
        }
        
        .report-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9pt;
            font-weight: bold;
        }
        
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 8pt;
            color: #7f8c8d;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .text-success { color: #27ae60; }
        .text-danger { color: #e74c3c; }
        .text-warning { color: #f39c12; }
        
        .company-info {
            margin-bottom: 15px;
            text-align: center;
        }
        
        .company-name {
            font-size: 14pt;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .observation-cell {
            font-size: 8pt;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <div class="company-info">
            <div class="company-name">{{ $data['client']->name }}</div>
            <div>Rapport Mensuel RH - Présence & Ponctualité</div>
        </div>
        
        <h1>📊 RAPPORT MENSUEL RH</h1>
        <h2>Présence & Ponctualité</h2>
        
        <div class="period">
            {{ $data['month_name'] }} {{ $data['year'] }} • Période : {{ $data['start_date'] }} au {{ $data['end_date'] }}
        </div>
    </div>
    
    <!-- Résumé statistique -->
    <div class="summary-box">
        <div class="summary-title">📋 RÉSUMÉ STATISTIQUE</div>
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
        <div style="text-align: center; font-size: 9pt; color: #7f8c8d;">
            Période analysée : {{ $data['period_days'] ?? 0 }} jours ouvrables • Généré le : {{ $data['generated_at']->format('d/m/Y à H:i') }} par le système CHECKTIME - Tél: 0141555592.
        </div>
    </div>
    
    <!-- Tableau détaillé -->
    <table class="report-table">
        <thead>
            <tr>
                <th rowspan="2" class="main-header">N°</th>
                <th rowspan="2" class="main-header">Employé</th>
                <th colspan="4" class="main-header">PRÉSENCE AU POSTE</th>
                <th colspan="3" class="main-header">PONCTUALITÉ</th>
                <th rowspan="2" class="main-header">Observation</th>
            </tr>
            <tr class="sub-header">
                <!-- Sous-colonnes Présence -->
                <th>Présence</th>
                <th>Absence</th>
                <th>Taux</th>
                <th>Détail</th>
                
                <!-- Sous-colonnes Ponctualité -->
                <th>À l'heure</th>
                <th>Retard</th>
                <th>Taux</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalPresence = 0;
                $totalAbsence = 0;
                $totalOnTime = 0;
                $totalLate = 0;
                $totalPresenceRate = 0;
                $totalPonctualiteRate = 0;
            @endphp
            
            @foreach($data['employees_data'] as $employee)
            @php
                $presenceRate = $employee['presence_data']['rate'] ?? 0;
                $ponctualiteRate = $employee['ponctualite_data']['rate'] ?? 0;
                
                $totalPresence += $employee['presence_data']['present'] ?? 0;
                $totalAbsence += $employee['presence_data']['absent'] ?? 0;
                $totalOnTime += $employee['ponctualite_data']['on_time'] ?? 0;
                $totalLate += $employee['ponctualite_data']['late'] ?? 0;
                $totalPresenceRate += $presenceRate;
                $totalPonctualiteRate += $ponctualiteRate;
                
                // Déterminer les classes
                $presenceBadgeClass = 'badge-success';
                if ($presenceRate < 80) $presenceBadgeClass = 'badge-danger';
                elseif ($presenceRate < 90) $presenceBadgeClass = 'badge-warning';
                
                $ponctualiteBadgeClass = 'badge-success';
                if ($ponctualiteRate < 80) $ponctualiteBadgeClass = 'badge-danger';
                elseif ($ponctualiteRate < 90) $ponctualiteBadgeClass = 'badge-warning';
            @endphp
            <tr>
                <td class="text-center">{{ $employee['order_number'] }}</td>
                <td class="text-left">
                    <strong>{{ $employee['employee_name'] }}</strong><br>
                    <small>Code: {{ $employee['employee_code'] }}</small>
                </td>
                <td class="text-center text-success">{{ $employee['presence_data']['present'] ?? 0 }}</td>
                <td class="text-center text-danger">{{ $employee['presence_data']['absent'] ?? 0 }}</td>
                <td class="text-center">
                    <span class="badge {{ $presenceBadgeClass }}">{{ $presenceRate }}%</span>
                </td>
                <td class="text-center">
                    <small>{{ $employee['presence_data']['present_days_display'] ?? '0/0' }}</small>
                </td>
                <td class="text-center text-success">{{ $employee['ponctualite_data']['on_time'] ?? 0 }}</td>
                <td class="text-center text-warning">{{ $employee['ponctualite_data']['late'] ?? 0 }}</td>
                <td class="text-center">
                    <span class="badge {{ $ponctualiteBadgeClass }}">{{ $ponctualiteRate }}%</span>
                </td>
                <td class="text-left observation-cell">
                    {{ $employee['observation'] ?? 'Aucune observation' }}
                </td>
            </tr>
            @endforeach
            
            <!-- Ligne des totaux -->
            @php
                $avgPresenceRate = count($data['employees_data']) > 0 ? round($totalPresenceRate / count($data['employees_data']), 1) : 0;
                $avgPonctualiteRate = count($data['employees_data']) > 0 ? round($totalPonctualiteRate / count($data['employees_data']), 1) : 0;
                
                $totalPresenceBadgeClass = 'badge-success';
                if ($avgPresenceRate < 80) $totalPresenceBadgeClass = 'badge-danger';
                elseif ($avgPresenceRate < 90) $totalPresenceBadgeClass = 'badge-warning';
                
                $totalPonctualiteBadgeClass = 'badge-success';
                if ($avgPonctualiteRate < 80) $totalPonctualiteBadgeClass = 'badge-danger';
                elseif ($avgPonctualiteRate < 90) $totalPonctualiteBadgeClass = 'badge-warning';
            @endphp
            <tr style="background-color: #e8f4fc; font-weight: bold;">
                <td colspan="2" class="text-right">TOTAUX / MOYENNES :</td>
                <td class="text-center text-success">{{ $totalPresence }}</td>
                <td class="text-center text-danger">{{ $totalAbsence }}</td>
                <td class="text-center">
                    <span class="badge {{ $totalPresenceBadgeClass }}">{{ $avgPresenceRate }}%</span>
                </td>
                <td class="text-center">-</td>
                <td class="text-center text-success">{{ $totalOnTime }}</td>
                <td class="text-center text-warning">{{ $totalLate }}</td>
                <td class="text-center">
                    <span class="badge {{ $totalPonctualiteBadgeClass }}">{{ $avgPonctualiteRate }}%</span>
                </td>
                <td class="text-center">-</td>
            </tr>
        </tbody>
    </table>
    
    <!-- Page 2 : Top 5 et Recommandations -->
    <div class="page-break"></div>
    
    <div class="header">
        <h2>ANALYSE DÉTAILLÉE</h2>
        <div class="period">{{ $data['month_name'] }} {{ $data['year'] }} - {{ $data['client']->name }}</div>
    </div>
    
    <!-- Top 5 employés -->
    @if(!empty($data['global_stats']['top_employees']))
    <div style="margin-bottom: 25px;">
        <h3 style="color: #2c3e50; border-bottom: 2px solid #27ae60; padding-bottom: 5px; font-size: 12pt;">
            🏆 TOP 5 - MEILLEURE PRÉSENCE
        </h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 9pt;">
            <thead>
                <tr style="background-color: #f8f9fa;">
                    <th style="border: 1px solid #ddd; padding: 6px; text-align: center;">Rang</th>
                    <th style="border: 1px solid #ddd; padding: 6px; text-align: left;">Employé</th>
                    <th style="border: 1px solid #ddd; padding: 6px; text-align: center;">Code</th>
                    <th style="border: 1px solid #ddd; padding: 6px; text-align: center;">Taux présence</th>
                    <th style="border: 1px solid #ddd; padding: 6px; text-align: center;">Taux ponctualité</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['global_stats']['top_employees'] as $index => $employee)
                <tr>
                    <td style="border: 1px solid #ddd; padding: 6px; text-align: center; font-weight: bold;">{{ $index + 1 }}</td>
                    <td style="border: 1px solid #ddd; padding: 6px; text-align: left;">{{ $employee['employee_name'] }}</td>
                    <td style="border: 1px solid #ddd; padding: 6px; text-align: center;">{{ $employee['employee_code'] }}</td>
                    <td style="border: 1px solid #ddd; padding: 6px; text-align: center;">
                        <span class="badge badge-success">{{ $employee['presence_data']['rate'] ?? 0 }}%</span>
                    </td>
                    <td style="border: 1px solid #ddd; padding: 6px; text-align: center;">
                        <span class="badge badge-success">{{ $employee['ponctualite_data']['rate'] ?? 0 }}%</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Employés à surveiller -->
    @if(!empty($data['global_stats']['bottom_employees']))
    <div style="margin-bottom: 25px;">
        <h3 style="color: #2c3e50; border-bottom: 2px solid #e74c3c; padding-bottom: 5px; font-size: 12pt;">
            ⚠️ EMPLOYÉS À SURVEILLER
        </h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 9pt;">
            <thead>
                <tr style="background-color: #fff3cd;">
                    <th style="border: 1px solid #ddd; padding: 6px; text-align: center;">N°</th>
                    <th style="border: 1px solid #ddd; padding: 6px; text-align: left;">Employé</th>
                    <th style="border: 1px solid #ddd; padding: 6px; text-align: center;">Code</th>
                    <th style="border: 1px solid #ddd; padding: 6px; text-align: center;">Taux présence</th>
                    <th style="border: 1px solid #ddd; padding: 6px; text-align: center;">Taux ponctualité</th>
                    <th style="border: 1px solid #ddd; padding: 6px; text-align: left;">Observations</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['global_stats']['bottom_employees'] as $index => $employee)
                <tr>
                    <td style="border: 1px solid #ddd; padding: 6px; text-align: center;">{{ $index + 1 }}</td>
                    <td style="border: 1px solid #ddd; padding: 6px; text-align: left;">{{ $employee['employee_name'] }}</td>
                    <td style="border: 1px solid #ddd; padding: 6px; text-align: center;">{{ $employee['employee_code'] }}</td>
                    <td style="border: 1px solid #ddd; padding: 6px; text-align: center;">
                        <span class="badge badge-danger">{{ $employee['presence_data']['rate'] ?? 0 }}%</span>
                    </td>
                    <td style="border: 1px solid #ddd; padding: 6px; text-align: center;">
                        <span class="badge badge-danger">{{ $employee['ponctualite_data']['rate'] ?? 0 }}%</span>
                    </td>
                    <td style="border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 8pt;">
                        {{ $employee['observation'] ?? 'Aucune observation' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Recommandations -->
    <div style="margin-bottom: 25px;">
        <h3 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 5px; font-size: 12pt;">
            💡 RECOMMANDATIONS
        </h3>
        <ul style="font-size: 9pt; line-height: 1.6;">
            @if(($data['global_stats']['avg_presence_rate'] ?? 0) < 85)
            <li><strong>Renforcer le suivi des absences</strong> - Taux de présence moyen inférieur à 85%</li>
            @endif
            @if(($data['global_stats']['avg_ponctualite_rate'] ?? 0) < 85)
            <li><strong>Sensibilisation à la ponctualité</strong> - Taux de ponctualité moyen inférieur à 85%</li>
            @endif
            @if(($data['global_stats']['total_ponctualite_late'] ?? 0) > 20)
            <li><strong>Analyser les causes des retards</strong> - {{ $data['global_stats']['total_ponctualite_late'] ?? 0 }} retards enregistrés</li>
            @endif
            @if(count($data['global_stats']['bottom_employees'] ?? []) > 0)
            <li><strong>Entretiens individuels nécessaires</strong> - {{ count($data['global_stats']['bottom_employees'] ?? []) }} employé(s) en difficulté</li>
            @endif
            @if(($data['global_stats']['total_presence_absent'] ?? 0) > 15)
            <li><strong>Revoir la politique d'absences</strong> - {{ $data['global_stats']['total_presence_absent'] ?? 0 }} absences totales</li>
            @endif
            <li><strong>Prochain rapport</strong> : {{ Carbon\Carbon::now()->addMonth()->locale('fr')->monthName }} {{ Carbon\Carbon::now()->addMonth()->year }}</li>
        </ul>
    </div>
    
    <!-- Pied de page -->
    <div class="footer">
        <div>Rapport généré automatiquement par le système de gestion des présences</div>
        <div>© {{ date('Y') }} {{ $data['client']->name }} • Document confidentiel</div>
        <div>Page 2/2 | Rapport généré le {{ $data['generated_at']->format('d/m/Y à H:i') }} par le système CHECKTIME - Tél: 0141555592.</div>
    </div>
</body>
</html>
