<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport par Département - {{ $start_date }} au {{ $end_date }}</title>
    <style>
        @page {
            margin: 20px;
            font-family: DejaVu Sans, sans-serif;
        }
        
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 10px; 
            line-height: 1.3;
            color: #000;
        }
        
        .header { 
            text-align: center; 
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }
        
        /* Logo du client */
        .client-logo {
            position: absolute;
            left: 0;
            top: 0;
            max-width: 150px;
            max-height: 70px;
            object-fit: contain;
        }
        
        .header-content {
            text-align: center;
            padding-top: 5px;
        }
        
        .title { 
            font-size: 16px; 
            font-weight: bold; 
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .subtitle { 
            font-size: 12px; 
            margin-bottom: 3px;
        }
        
        .period-info { 
            text-align: center;
            margin-bottom: 10px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .client-info {
            text-align: left;
            margin-bottom: 10px;
            font-size: 10px;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 10px;
            font-size: 9px;
        }
        
        th { 
            background-color: #f2f2f2; 
            font-weight: bold; 
            padding: 6px 4px; 
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
            font-size: 9px;
        }
        
        td { 
            padding: 5px 4px; 
            border: 1px solid #000;
            vertical-align: middle;
            text-align: center;
        }
        
        .department-name {
            text-align: left;
            font-weight: bold;
        }
        
        .total-row {
            background-color: #e6e6e6;
            font-weight: bold;
        }
        
        .rate-high { color: #008000; font-weight: bold; }
        .rate-medium { color: #ff9900; font-weight: bold; }
        .rate-low { color: #ff0000; font-weight: bold; }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 5px;
            background-color: white;
        }
        
        .page-number:before {
            content: "Page " counter(page);
        }
        
        /* Styles spécifiques pour le tableau */
        .col-order { width: 5%; }
        .col-department { width: 30%; text-align: left; }
        .col-employees { width: 10%; }
        .col-presence { width: 20%; }
        .col-ponctualite { width: 20%; }
        .col-total { width: 15%; }
        
        .sub-header {
            background-color: #e0e0e0;
            font-weight: bold;
        }
        
        .section-title {
            font-weight: bold;
            background-color: #f8f8f8;
        }
        
        .employee-count {
            font-weight: bold;
            color: #333;
        }
        
        .sort-info {
            text-align: center;
            font-size: 9px;
            font-style: italic;
            margin-bottom: 5px;
            color: #555;
        }
        
        .stat-box {
            display: inline-block;
            margin: 5px 10px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <!-- Logo du client -->
        @if(isset($client->logo) && $client->logo)
            <img src="{{ public_path($client->logo) }}" alt="{{$client->raison_sociale}}" class="client-logo">
        @endif
        
        <div class="header-content">
            <div class="title">RAPPORT PAR DÉPARTEMENT</div>
            <div class="subtitle">Statistiques de Présence & Ponctualité</div>
            <div class="period-info">
                Période : {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}
                ({{ $period_days }} jours)
            </div>
            <div class="client-info">
                Client : {{ $client->name }} | 
                Départements : {{ $total_departments }} | 
                Total employés : {{ $totals['total_employees'] }} | 
                Exporté le : {{ $export_date->format('d/m/Y à H:i') }}
            </div>
        </div>
    </div>
    
    <!-- Statistiques récapitulatives -->
    <div style="text-align: center; margin-bottom: 15px;">
        <div class="stat-box">
            <strong>Départements:</strong> {{ $totals['total_departments'] }}
        </div>
        <div class="stat-box">
            <strong>Employés:</strong> {{ $totals['total_employees'] }}
        </div>
        <div class="stat-box">
            <strong>Présence moyenne:</strong> 
            <span class="rate-{{ $totals['avg_presence_rate'] >= 90 ? 'high' : ($totals['avg_presence_rate'] >= 80 ? 'medium' : 'low') }}">
                {{ $totals['avg_presence_rate'] }}%
            </span>
        </div>
        <div class="stat-box">
            <strong>Ponctualité moyenne:</strong> 
            <span class="rate-{{ $totals['avg_ponctualite_rate'] >= 90 ? 'high' : ($totals['avg_ponctualite_rate'] >= 80 ? 'medium' : 'low') }}">
                {{ $totals['avg_ponctualite_rate'] }}%
            </span>
        </div>
    </div>
    
    <!-- Information sur le tri -->
    <div class="sort-info">
        Les départements sont classés par ordre décroissant de la somme des taux de présence et ponctualité
    </div>
    
    <!-- Tableau principal -->
    <table>
        <thead>
            <tr>
                <th rowspan="2" class="col-order">N°</th>
                <th rowspan="2" class="col-department">Département</th>
                <th rowspan="2" class="col-employees">Employés</th>
                <th colspan="3" class="section-title">PRÉSENCE AU POSTE</th>
                <th colspan="3" class="section-title">PONCTUALITÉ</th>
                <th rowspan="2" class="col-total">Total Taux</th>
            </tr>
            <tr class="sub-header">
                <!-- Sous-colonnes Présence -->
                <th>Présences</th>
                <th>Absences</th>
                <th>Taux</th>
                
                <!-- Sous-colonnes Ponctualité -->
                <th>À l'heure</th>
                <th>Retards</th>
                <th>Taux</th>
            </tr>
        </thead>
        <tbody>
            @foreach($department_data as $index => $department)
            <tr>
                <!-- N° d'ordre -->
                <td>{{ $index + 1 }}</td>
                
                <!-- Nom du département -->
                <td class="department-name">
                    {{ $department['department_name'] }}
                    @if($department['employee_count'] == 0)
                        <br><small style="color: #ff0000;">(Aucun employé)</small>
                    @endif
                </td>
                
                <!-- Nombre d'employés -->
                <td class="employee-count">{{ $department['employee_count'] }}</td>
                
                <!-- Présence au Poste -->
                <td>{{ $department['presence_data']['present'] }}</td>
                <td>{{ $department['presence_data']['absent'] }}</td>
                <td>
                    <span class="rate-{{ $department['presence_data']['rate'] >= 90 ? 'high' : ($department['presence_data']['rate'] >= 80 ? 'medium' : 'low') }}">
                        {{ $department['presence_data']['rate'] }}%
                    </span>
                </td>
                
                <!-- Ponctualité -->
                <td>{{ $department['ponctualite_data']['on_time'] }}</td>
                <td>{{ $department['ponctualite_data']['late'] }}</td>
                <td>
                    <span class="rate-{{ $department['ponctualite_data']['rate'] >= 90 ? 'high' : ($department['ponctualite_data']['rate'] >= 80 ? 'medium' : 'low') }}">
                        {{ $department['ponctualite_data']['rate'] }}%
                    </span>
                </td>
                
                <!-- Total taux -->
                <td>
                    <strong class="rate-{{ $department['total_rate'] >= 180 ? 'high' : ($department['total_rate'] >= 160 ? 'medium' : 'low') }}">
                        {{ number_format($department['total_rate'], 1) }}%
                    </strong>
                </td>
            </tr>
            @endforeach
            
            <!-- Ligne des totaux -->
            <tr class="total-row">
                <td colspan="2" style="text-align: right;"><strong>TOTAUX / MOYENNES :</strong></td>
                <td><strong>{{ $totals['total_employees'] }}</strong></td>
                
                <!-- Totaux Présence -->
                <td><strong>{{ $totals['total_presence_present'] }}</strong></td>
                <td><strong>{{ $totals['total_presence_absent'] }}</strong></td>
                <td>
                    <strong class="rate-{{ $totals['avg_presence_rate'] >= 90 ? 'high' : ($totals['avg_presence_rate'] >= 80 ? 'medium' : 'low') }}">
                        {{ $totals['avg_presence_rate'] }}%
                    </strong>
                </td>
                
                <!-- Totaux Ponctualité -->
                <td><strong>{{ $totals['total_ponctualite_on_time'] }}</strong></td>
                <td><strong>{{ $totals['total_ponctualite_late'] }}</strong></td>
                <td>
                    <strong class="rate-{{ $totals['avg_ponctualite_rate'] >= 90 ? 'high' : ($totals['avg_ponctualite_rate'] >= 80 ? 'medium' : 'low') }}">
                        {{ $totals['avg_ponctualite_rate'] }}%
                    </strong>
                </td>
                
                <!-- Total taux -->
                <td>
                    <strong class="rate-{{ ($totals['avg_presence_rate'] + $totals['avg_ponctualite_rate']) >= 180 ? 'high' : (($totals['avg_presence_rate'] + $totals['avg_ponctualite_rate']) >= 160 ? 'medium' : 'low') }}">
                        {{ number_format($totals['avg_presence_rate'] + $totals['avg_ponctualite_rate'], 1) }}%
                    </strong>
                </td>
            </tr>
        </tbody>
    </table>
    
    <!-- Légende et notes -->
    <div style="margin-top: 15px; font-size: 8px; color: #666;">
        <p><strong>Légende :</strong></p>
        <p>
            • <span style="color: #008000;">Taux ≥ 90%</span> : Excellent | 
            • <span style="color: #ff9900;">Taux 80-89%</span> : Satisfaisant | 
            • <span style="color: #ff0000;">Taux &lt; 80%</span> : À améliorer<br>
            • <strong>Total Taux</strong> : Somme du taux de présence et de ponctualité
        </p>
        <p><strong>Notes :</strong> 
        1. Les départements sont classés par ordre décroissant de la somme des taux de présence et ponctualité.<br>
        2. Les statistiques sont calculées sur la base des données individuelles de chaque employé.<br>
        3. Un département sans employé n'est pas inclus dans les calculs de moyennes.</p>
    </div>
    
    <!-- Pied de page -->
    <div class="footer">
        <span class="page-number"></span> | 
        Rapport généré le {{ $export_date->format('d/m/Y à H:i') }} par le système CHECKTIME - Tél: 0141555592.
    </div>
</body>
</html>
