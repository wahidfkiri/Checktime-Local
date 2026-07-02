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
        
        /* Styles spécifiques pour le tableau principal */
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
        
        .total-rate {
            font-size: 8px;
            color: #666;
            font-weight: normal;
        }
        
        .sort-info {
            text-align: center;
            font-size: 9px;
            font-style: italic;
            margin-bottom: 5px;
            color: #555;
        }
        
        /* Styles pour le tableau de présence par département */
        .dept-header {
            background-color: #d9e1f2;
            font-weight: bold;
            font-size: 11px;
        }
        
        .dept-name {
            background-color: #e8edf9;
            font-weight: bold;
            text-align: left;
            padding-left: 10px;
        }
        
        .check-time {
            font-family: monospace;
            font-size: 9px;
        }
        
        .late-time {
            color: #ff6600;
            font-weight: bold;
        }
        
        .ontime-time {
            color: #008000;
        }
        
        .observation-note {
            font-size: 8px;
            text-align: left;
            color: #666;
        }
        
        .week-title {
            background-color: #c0c0c0;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
        }
        
        .day-header {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 9px;
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
            <div class="title">RAPPORT DE PRÉSENCE & PONCTUALITÉ</div>
            <div class="period-info">
                Période : {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}
                ({{ $period_days }} jours)
            </div>
            <div class="client-info">
                Client : {{ $client->name }} | 
                Employés : {{ $total_employees }} | 
                Exporté le : {{ $export_date->format('d/m/Y à H:i') }}
            </div>
        </div>
    </div>
    
    <!-- Information sur le tri -->
    <div class="sort-info">
        Les employés sont classés par ordre décroissant de la somme des taux de présence et ponctualité
    </div>
    
    <!-- Tableau principal - Présence & Ponctualité -->
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
            @php
                // Calculer et trier les données par somme des taux
                $sortedData = collect($report_data)->map(function($employee) {
                    $presenceRate = floatval($employee['presence_data']['rate'] ?? 0);
                    $ponctualiteRate = floatval($employee['ponctualite_data']['rate'] ?? 0);
                    $employee['total_rate'] = $presenceRate + $ponctualiteRate;
                    return $employee;
                })->sortByDesc('total_rate')->values();
            @endphp
            
            @foreach($sortedData as $index => $employee)
            <tr>
                <!-- N° d'ordre -->
                <td>{{ $index + 1 }}</td>
                
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
                        {{ $employee['presence_data']['rate'] }}%
                    </span>
                </td>
                <td>{{ $employee['presence_data']['present_days_display'] }}</td>
                
                <!-- Ponctualité -->
                <td class="ponctualite-ontime">{{ $employee['ponctualite_data']['on_time'] }}</td>
                <td class="ponctualite-late">{{ $employee['ponctualite_data']['late'] }}</td>
                <td>
                    <span class="rate-{{ $employee['ponctualite_data']['rate'] >= 90 ? 'high' : ($employee['ponctualite_data']['rate'] >= 80 ? 'medium' : 'low') }}">
                        {{ $employee['ponctualite_data']['rate'] }}%
                    </span>
                </td>
                
                <!-- Observation -->
                <td style="text-align: left; font-size: 8px;">
                    {{ $employee['observation'] }}
                    <br>
                    <span class="total-rate">Total taux: {{ number_format($employee['total_rate'], 1) }}%</span>
                </td>
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
                        {{ $totals['avg_presence_rate'] }}%
                    </strong>
                </td>
                <td>-</td>
                
                <!-- Totaux Ponctualité -->
                <td><strong>{{ $totals['total_ponctualite_on_time'] }}</strong></td>
                <td><strong>{{ $totals['total_ponctualite_late'] }}</strong></td>
                <td>
                    <strong class="rate-{{ $totals['avg_ponctualite_rate'] >= 90 ? 'high' : ($totals['avg_ponctualite_rate'] >= 80 ? 'medium' : 'low') }}">
                        {{ $totals['avg_ponctualite_rate'] }}%
                    </strong>
                </td>
                
                <td>-</td>
            </tr>
        </tbody>
    </table>
    
    <!-- Espacement entre les deux tableaux -->
    <div style="margin-top: 20px; margin-bottom: 10px;">
        <hr style="border: 1px solid #ccc;">
    </div>
    
    <!-- Deuxième tableau : Situation de présence du Personnel par département -->
    <div class="sort-info" style="margin-top: 15px;">
        <strong>SITUATION DE PRÉSENCE DU PERSONNEL</strong> - Heures d'arrivée et de départ par département
    </div>
    
    @php
        // Grouper les données par département
        $groupedByDept = collect($report_data)->groupBy('department_name');
        
        // Générer la liste des jours de la période
        $start = \Carbon\Carbon::parse($start_date);
        $end = \Carbon\Carbon::parse($end_date);
        $daysOfWeek = [];
        $currentDate = $start->copy();
        
        while ($currentDate <= $end) {
            $dayOfWeekNumber = $currentDate->dayOfWeekIso;
            // Inclure seulement les jours de semaine (lundi-vendredi)
            if ($dayOfWeekNumber >= 1 && $dayOfWeekNumber <= 5) {
                $daysOfWeek[] = [
                    'date' => $currentDate->copy(),
                    'day_name' => $this->getDayNameFrench($currentDate->dayOfWeekIso),
                    'date_str' => $currentDate->format('Y-m-d'),
                    'day_number' => $currentDate->day
                ];
            }
            $currentDate->addDay();
        }
        
        // Semaine du...
        $weekStart = $start->copy();
        $weekEnd = $end->copy();
        $weekRange = "SEMAINE DU LUNDI " . $weekStart->format('d') . " " . $this->getMonthNameFrench($weekStart->month) . " AU VENDREDI " . $weekEnd->format('d') . " " . $this->getMonthNameFrench($weekEnd->month);
    @endphp
    
    @foreach($groupedByDept as $deptName => $employees)
        <table style="margin-top: 15px; margin-bottom: 15px;">
            <!-- Titre de la semaine -->
            <tr>
                <td colspan="{{ count($daysOfWeek) + 3 }}" class="week-title" style="background-color: #c0c0c0; font-weight: bold; text-align: center;">
                    {{ $weekRange }}
                </td>
            </tr>
            <tr>
                <td colspan="{{ count($daysOfWeek) + 3 }}" style="background-color: #f5f5f5; height: 5px;"></td>
            </tr>
            <tr>
                <td colspan="{{ count($daysOfWeek) + 3 }}" style="background-color: #e0e0e0; font-weight: bold; text-align: center;">
                    HEURES D'ARRIVEE ET DE DEPART
                </td>
            </tr>
            <tr>
                <th style="width: 5%;">N°</th>
                <th style="width: 25%; text-align: left;">NOM ET PRENOMS</th>
                @foreach($daysOfWeek as $day)
                    <th style="width: {{ 60 / count($daysOfWeek) }}%;" class="day-header">
                        {{ $day['day_name'] }}<br>
                        <small>{{ $day['date']->format('d/m') }}</small>
                    </th>
                @endforeach
                <th style="width: 15%;">OBSERVATIONS</th>
            </tr>
            <tr>
                <td colspan="{{ count($daysOfWeek) + 3 }}" class="dept-name">
                    <strong>DÉPARTEMENT : {{ strtoupper($deptName) }}</strong>
                </td>
            </tr>
            
            @php
                $empCounter = 1;
            @endphp
            
            @foreach($employees as $employee)
                <tr>
                    <td>{{ $empCounter++ }}</td>
                    <td style="text-align: left; font-weight: bold;">{{ strtoupper($employee['employee_name']) }}</td>
                    
                    @foreach($daysOfWeek as $day)
                        @php
                            // Récupérer les données de pointage pour ce jour
                            $checkData = $this->getEmployeeCheckData($employee['employee_id'], $day['date_str']);
                        @endphp
                        <td class="check-time">
                            @if($checkData)
                                <span class="{{ $checkData['is_late'] ? 'late-time' : 'ontime-time' }}">
                                    {{ $checkData['check_in'] ?? '-' }}<br>
                                    {{ $checkData['check_out'] ?? '-' }}
                                </span>
                                @if($checkData['is_late'])
                                    <br><small style="color: #ff6600; font-size: 7px;">({{ $checkData['late_minutes'] }} min)</small>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                    @endforeach
                    
                    <td class="observation-note">
                        {{ $employee['observation'] }}
                    </td>
                </tr>
            @endforeach
        </table>
    @endforeach
    
    <!-- Légende et notes -->
    <div style="margin-top: 15px; font-size: 8px; color: #666;">
        <p><strong>Légende :</strong></p>
        <p>
            • <span style="color: #008000;">Taux ≥ 90%</span> : Excellent | 
            • <span style="color: #ff9900;">Taux 80-89%</span> : Satisfaisant | 
            • <span style="color: #ff0000;">Taux &lt; 80%</span> : À améliorer
        </p>
        <p><strong>Notes :</strong> 
        1. Les employés sont classés par ordre décroissant de la somme des taux de présence et ponctualité.<br>
        2. Les statistiques portent uniquement sur les jours ouvrés (lundi-vendredi).<br>
        3. Les weekends et jours fériés ne sont pas inclus dans le calcul.<br>
        4. Les heures de pointage affichées sont les heures d'arrivée et de départ enregistrées.
        </p>
    </div>
    
    <!-- Pied de page -->
    <div class="footer">
        <span class="page-number"></span> | 
        Rapport généré par le logiciel CHECKTIME le {{ $export_date->format('d/m/Y à H:i') }}
    </div>
</body>
</html>