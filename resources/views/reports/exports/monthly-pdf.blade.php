<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport Présence & Ponctualité - {{ $start_date }} au {{ $end_date }}</title>
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
        
        .employee-name {
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
        .col-name { width: 20%; text-align: left; }
        .col-presence { width: 25%; }
        .col-ponctualite { width: 25%; }
        .col-observation { width: 25%; text-align: left; }
        
        .sub-header {
            background-color: #e0e0e0;
            font-weight: bold;
        }
        
        .section-title {
            font-weight: bold;
            background-color: #f8f8f8;
        }
        
        .presence-present { color: #008000; }
        .presence-absent { color: #ff0000; }
        .ponctualite-ontime { color: #008000; }
        .ponctualite-late { color: #ff9900; }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <!-- Logo du client -->
         @if(isset($client->logo) && $client->logo)
        <img src="<?php echo $_SERVER["DOCUMENT_ROOT"]; ?>/storage/app/public/<?php echo $client->logo; ?>" alt="{{$client->raison_sociale}}" class="client-logo">
    @endif
        <div class="header-content">
        <div class="title">RAPPORT DE PRÉSENCE & PONCTUALITÉ</div>
        <div class="period-info">
            Période : {{ $start_date }} au {{ $end_date }}
        </div>
        <div class="client-info">
            Client : {{ $client_name ?: ($client->name ?: 'Client #' . $client->id) }} |
            Employés : {{ $total_employees }} | 
            Exporté le : {{ $generated_at->format('d/m/Y à H:i') }}
        </div>
    </div>
    </div>
    
    <!-- Tableau principal -->
    <table>
        <thead>
            <tr>
                <th rowspan="2" class="col-order">N° d'ordre</th>
                <th rowspan="2" class="col-name">Nom et Prénoms</th>
                <th colspan="4" class="section-title">PRÉSENCE AU POSTE</th>
                <th colspan="3" class="section-title">PONCTUALITÉ</th>
                <th rowspan="2" class="col-observation">Observation</th>
            </tr>
            <tr class="sub-header">
                <!-- Sous-colonnes Présence -->
                <th>Présence</th>
                <th>Absence</th>
                <th>Taux de présence</th>
                <th style="width: 8%;">Détail</th>
                
                <!-- Sous-colonnes Ponctualité -->
                <th>A l'heure</th>
                <th>Retard</th>
                <th>Taux de ponctualité</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report_data as $employee)
            <tr>
                <!-- N° d'ordre -->
                <td>{{ $employee['order_number'] }}</td>
                
                <!-- Nom et Prénoms -->
                <td class="employee-name">
                    {{ $employee['employee_name'] }}<br>
                    <small style="color: #666;">({{ $employee['employee_code'] }})</small>
                </td>
                
                <!-- Présence au Poste -->
                <td class="presence-present">{{ $employee['presence_data']['present'] }}</td>
                <td class="presence-absent">{{ $employee['presence_data']['absent'] }}</td>
                <td>
                    <span class="rate-{{ $employee['presence_data']['rate'] >= 90 ? 'high' : ($employee['presence_data']['rate'] >= 80 ? 'medium' : 'low') }}">
                       {{ number_format($employee['presence_data']['rate'], 1) }}%
                    </span>
                </td>
                <td>{{ $employee['presence_data']['present_days_display'] }}</td>
                
                <!-- Ponctualité -->
                <td class="ponctualite-ontime">{{ $employee['ponctualite_data']['on_time'] }}</td>
                <td class="ponctualite-late">{{ $employee['ponctualite_data']['late'] }}</td>
                <td>
                    <span class="rate-{{ $employee['ponctualite_data']['rate'] >= 90 ? 'high' : ($employee['ponctualite_data']['rate'] >= 80 ? 'medium' : 'low') }}">
                        {{ number_format($employee['ponctualite_data']['rate'], 1) }}%
                    </span>
                </td>
                
                <!-- Observation -->
                <td style="text-align: left; font-size: 8px;">{{ $employee['observation'] }}</td>
            </tr>
            @endforeach
            
            <!-- Ligne des totaux -->
            <tr class="total-row">
                <td colspan="2" style="text-align: right;"><strong>TOTAUX / MOYENNES :</strong></td>
                
                <!-- Totaux Présence -->
                <td><strong>{{ $totals['total_presence_present'] }}</strong></td>
                <td><strong>{{ $totals['total_presence_absent'] }}</strong></td>
                <td>
                    <strong class="rate-{{ $totals['avg_presence_rate'] >= 90 ? 'high' : ($totals['avg_presence_rate'] >= 80 ? 'medium' : 'low') }}">
                        {{ number_format($totals['avg_presence_rate'], 1) }}%
                    </strong>
                </td>
                <td>-</td>
                
                <!-- Totaux Ponctualité -->
                <td><strong>{{ $totals['total_ponctualite_on_time'] }}</strong></td>
                <td><strong>{{ $totals['total_ponctualite_late'] }}</strong></td>
                <td>
                    <strong class="rate-{{ $totals['avg_ponctualite_rate'] >= 90 ? 'high' : ($totals['avg_ponctualite_rate'] >= 80 ? 'medium' : 'low') }}">
                        {{ number_format($totals['avg_ponctualite_rate'], 1) }}%
                    </strong>
                </td>
                
                <td>-</td>
            </tr>
        </tbody>
    </table>
    
    <!-- Légende et notes -->
    <div style="margin-top: 15px; font-size: 8px; color: #666;">
        <p><strong>Légende :</strong></p>
        <p>
            • <span style="color: #008000;">Taux ≥ 90%</span> : Excellent | 
            • <span style="color: #ff9900;">Taux 80-89%</span> : Satisfaisant | 
            • <span style="color: #ff0000;">Taux &lt; 80%</span> : À améliorer
        </p>
        <p><strong>Notes :</strong> Les statistiques portent uniquement sur les jours ouvrés (lundi-vendredi). 
        Les weekends et jours fériés ne sont pas inclus dans le calcul.</p>
    </div>
    
    <!-- Pied de page -->
    <div class="footer">
        <span class="page-number"></span> | 
        Rapport généré le {{ $generated_at->format('d/m/Y à H:i') }} par le système CHECKTIME - Tél: 0141555592.
    </div>
</body>
</html>
