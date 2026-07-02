<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport Présence par Département - {{ $start_date }} au {{ $end_date }}</title>
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
            font-size: 18px; 
            font-weight: bold; 
            margin-bottom: 5px;
            text-transform: uppercase;
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
            margin-bottom: 15px;
            font-size: 9px;
        }
        
        th { 
            background-color: #f2f2f2; 
            font-weight: bold; 
            padding: 6px 4px; 
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
        }
        
        td { 
            padding: 4px 4px; 
            border: 1px solid #000;
            vertical-align: middle;
            text-align: center;
        }
        
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
        
        .col-order { width: 5%; }
        .col-department { width: 20%; }
        .col-employees { width: 8%; }
        .col-present { width: 8%; }
        .col-absent { width: 8%; }
        .col-presence-rate { width: 8%; }
        .col-ontime { width: 8%; }
        .col-late { width: 8%; }
        .col-early { width: 8%; }
        .col-ponctualite-rate { width: 8%; }
        
        .present { color: #008000; font-weight: bold; }
        .absent { color: #ff0000; font-weight: bold; }
        .late { color: #ff9900; }
        .early { color: #ff9900; }
        
        .rate-high { color: #008000; font-weight: bold; }
        .rate-medium { color: #ff9900; font-weight: bold; }
        .rate-low { color: #ff0000; font-weight: bold; }
        
        .department-name {
            text-align: left;
            font-weight: bold;
            background-color: #f9f9f9;
        }
        
        .total-row {
            background-color: #e6e6e6;
            font-weight: bold;
        }
        
        .dept-title {
            background-color: #e8edf9;
            font-weight: bold;
            text-align: left;
            padding: 8px 10px;
            font-size: 11px;
            border: 1px solid #000;
            margin-top: 15px;
        }
        
        .employee-name {
            text-align: left;
            font-weight: bold;
        }
        
        .check-time {
            font-family: monospace;
            font-size: 9px;
            font-weight: bold;
            color: #000;
        }
        
        .absent-cell {
            color: #ff0000;
            font-weight: bold;
        }

        .mission-cell {
            background-color: #dce8f7;
            color: #1a5276;
            font-weight: bold;
            font-size: 8px;
            text-align: center;
            vertical-align: middle;
        }

        .conge-cell {
            background-color: #d5f5e3;
            color: #1e8449;
            font-weight: bold;
            font-size: 8px;
            text-align: center;
            vertical-align: middle;
        }
        
        .observation-note {
            font-size: 8px;
            text-align: left;
            color: #666;
            max-width: 150px;
        }
        
        .week-title {
            background-color: #c0c0c0;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            padding: 8px;
            border: 1px solid #000;
            margin-bottom: 15px;
        }
        
        .day-header {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 10px;
            text-align: center;
        }
        
        .day-header strong {
            font-size: 11px;
            font-weight: bold;
        }
        
        .date-bold {
            font-weight: bold;
            font-size: 10px;
        }
        
        .stats-row {
            background-color: #f9f9f9;
            font-size: 9px;
        }
        
        .sub-header {
            background-color: #e0e0e0;
        }
        
        .small-note {
            font-size: 7px;
            color: #666;
        }
        
        .summary-box {
            margin-top: 15px;
            padding: 10px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
        }
        
        .summary-stats {
            display: flex;
            justify-content: space-around;
            text-align: center;
            flex-wrap: wrap;
        }
        
        .stat-item {
            margin: 5px;
        }
        
        .stat-label {
            font-weight: bold;
            color: #666;
            font-size: 9px;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        @if(isset($client->logo) && $client->logo)
            <img src="{{ public_path($client->logo) }}" alt="{{ $client->raison_sociale }}" class="client-logo">
        @endif
        <div class="header-content">
            <div class="title">RAPPORT DE PRÉSENCE DU PERSONNEL</div>
            <div class="period-info">
                Période : {{ \Carbon\Carbon::createFromFormat('d/m/Y', $start_date)->format('d/m/Y') }} au {{ \Carbon\Carbon::createFromFormat('d/m/Y', $end_date)->format('d/m/Y') }}
                ({{ $period_days }} jours ouvrés)
            </div>
            <div class="client-info">
                <strong>Client :</strong> {{ $client->user->name ?? $client->user->email ?? 'N/A' }} | 
                <strong>Départements :</strong> {{ $total_departments }} | 
                <strong>Employés :</strong> {{ $totals['total_employees'] ?? 0 }} |
                <strong>Export :</strong> {{ $export_date->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
    
    @php
        $weekStart = \Carbon\Carbon::createFromFormat('d/m/Y', $start_date);
        $weekEnd   = \Carbon\Carbon::createFromFormat('d/m/Y', $end_date);
        
        $months = [
            1 => 'JANVIER', 2 => 'FÉVRIER',  3 => 'MARS',      4 => 'AVRIL',
            5 => 'MAI',     6 => 'JUIN',      7 => 'JUILLET',   8 => 'AOÛT',
            9 => 'SEPTEMBRE', 10 => 'OCTOBRE', 11 => 'NOVEMBRE', 12 => 'DÉCEMBRE'
        ];
        
        $weekRange = "SEMAINE DU " . $weekStart->format('d') . " " . ($months[$weekStart->month] ?? '') . 
                     " AU " . $weekEnd->format('d') . " " . ($months[$weekEnd->month] ?? '');
    @endphp
    
    @if(count($report_data) == 0)
        <div style="text-align: center; padding: 50px; color: #ff0000;">
            <strong>Aucune donnée trouvée pour la période sélectionnée.</strong><br>
            Vérifiez que des pointages existent dans daily_attendances.
        </div>
    @endif
    
    <!-- ====================================================
         TABLEAU 1 : RÉCAPITULATIF PAR DÉPARTEMENT
    ===================================================== -->
    <div class="week-title" style="margin-bottom: 15px;">
        RÉCAPITULATIF PAR DÉPARTEMENT
    </div>
    
    <table>
        <thead>
            <tr>
                <th rowspan="2" class="col-order">N°</th>
                <th rowspan="2" class="col-department">Département</th>
                <th rowspan="2" class="col-employees">Employés</th>
                <th colspan="4" style="background-color: #d9d9d9;">PRÉSENCE</th>
                <th colspan="4" style="background-color: #d9d9d9;">PONCTUALITÉ</th>
            </tr>
            <tr>
                <th class="col-present">Présent</th>
                <th class="col-absent">Absent</th>
                <th class="col-presence-rate">Taux</th>
                <th style="width: 8%;">Ratio</th>
                <th class="col-ontime">À l'heure</th>
                <th class="col-late">Retard</th>
                <th class="col-early">Départ précoce</th>
                <th class="col-ponctualite-rate">Taux</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report_data as $index => $dept)
                @php
                    $avgPresenceRate    = $dept['avg_presence_rate']    ?? 0;
                    $avgPonctualiteRate = $dept['avg_ponctualite_rate'] ?? 0;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="department-name">{{ $dept['department_name'] ?? 'N/A' }}</td>
                    <td><strong>{{ $dept['total_employees'] ?? 0 }}</strong></td>

                    <td class="present">{{ number_format($dept['total_present'] ?? 0) }}</td>
                    <td class="absent">{{ number_format($dept['total_absent'] ?? 0) }}</td>
                    <td>
                        <span class="rate-{{ $avgPresenceRate >= 90 ? 'high' : ($avgPresenceRate >= 80 ? 'medium' : 'low') }}">
                            {{ number_format($avgPresenceRate, 1) }}%
                        </span>
                    </td>
                    <td class="small-note">
                        {{ number_format($dept['total_present'] ?? 0) }}/{{ number_format(($dept['total_employees'] ?? 0) * $period_days) }}
                    </td>

                    <td>{{ number_format($dept['total_on_time'] ?? 0) }}</td>
                    <td class="late">{{ number_format($dept['total_late'] ?? 0) }}</td>
                    <td class="early">{{ number_format($dept['total_early_leave'] ?? 0) }}</td>
                    <td>
                        <span class="rate-{{ $avgPonctualiteRate >= 90 ? 'high' : ($avgPonctualiteRate >= 80 ? 'medium' : 'low') }}">
                            {{ number_format($avgPonctualiteRate, 1) }}%
                        </span>
                    </td>
                </tr>
            @endforeach

            <!-- Totaux généraux -->
            @if(count($report_data) > 0)
            <tr class="total-row">
                <td colspan="2" style="text-align: right;"><strong>TOTAUX GÉNÉRAUX :</strong></td>
                <td><strong>{{ number_format($totals['total_employees'] ?? 0) }}</strong></td>
                <td><strong>{{ number_format($totals['total_present'] ?? 0) }}</strong></td>
                <td><strong>{{ number_format($totals['total_absent'] ?? 0) }}</strong></td>
                <td>
                    <strong class="rate-{{ ($totals['avg_presence_rate'] ?? 0) >= 90 ? 'high' : (($totals['avg_presence_rate'] ?? 0) >= 80 ? 'medium' : 'low') }}">
                        {{ number_format($totals['avg_presence_rate'] ?? 0, 1) }}%
                    </strong>
                </td>
                <td class="small-note">
                    {{ number_format($totals['total_present'] ?? 0) }}/{{ number_format(($totals['total_employees'] ?? 0) * $period_days) }}
                </td>
                <td><strong>{{ number_format($totals['total_on_time'] ?? 0) }}</strong></td>
                <td><strong>{{ number_format($totals['total_late'] ?? 0) }}</strong></td>
                <td><strong>{{ number_format($totals['total_early_leave'] ?? 0) }}</strong></td>
                <td>
                    <strong class="rate-{{ ($totals['avg_ponctualite_rate'] ?? 0) >= 90 ? 'high' : (($totals['avg_ponctualite_rate'] ?? 0) >= 80 ? 'medium' : 'low') }}">
                        {{ number_format($totals['avg_ponctualite_rate'] ?? 0, 1) }}%
                    </strong>
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    <!-- Statistiques globales -->
    @if(count($report_data) > 0)
    <div class="summary-box">
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-label">Ratio Global Présence/Absence</div>
                <div class="stat-value">
                    <span class="present">{{ number_format($totals['total_present'] ?? 0) }}</span> / 
                    <span class="absent">{{ number_format($totals['total_absent'] ?? 0) }}</span>
                </div>
                <div class="small-note">Total jours: {{ number_format(($totals['total_employees'] ?? 0) * $period_days) }}</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Taux présence global</div>
                <div class="stat-value rate-{{ ($totals['avg_presence_rate'] ?? 0) >= 90 ? 'high' : (($totals['avg_presence_rate'] ?? 0) >= 80 ? 'medium' : 'low') }}">
                    {{ number_format($totals['avg_presence_rate'] ?? 0, 1) }}%
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Missions</div>
                <div class="stat-value" style="color: #1a5276;">{{ number_format($totals['total_mission'] ?? 0) }}</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Congés</div>
                <div class="stat-value" style="color: #1e8449;">{{ number_format($totals['total_leave'] ?? 0) }}</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Départs précoces</div>
                <div class="stat-value" style="color: #ff9900;">{{ number_format($totals['total_early_leave'] ?? 0) }}</div>
            </div>
        </div>
    </div>
    @endif

    <!-- ====================================================
         TABLEAU 2 : DÉTAIL PAR EMPLOYÉ — HEURES DE POINTAGE
    ===================================================== -->
    <div class="week-title" style="margin-top: 20px; margin-bottom: 15px;">
        {{ $weekRange }} - HEURES D'ARRIVÉE ET DE DÉPART
    </div>
    
    @foreach($report_data as $department)
        @if(!empty($department['employees']))

        <div class="dept-title">
            DÉPARTEMENT : {{ strtoupper($department['department_name']) }}
            <span style="float: right; font-size: 9px; font-weight: normal;">
                Employés: {{ $department['total_employees'] }} | 
                Présence: {{ number_format($department['avg_presence_rate'], 1) }}%
            </span>
        </div>

        <table>
            <thead>
                <tr class="sub-header">
                    <th rowspan="2" style="width: 5%;">N°</th>
                    <th rowspan="2" style="width: 15%; text-align: left;">NOM ET PRÉNOMS</th>
                    @foreach($days_list as $day)
                        <th colspan="2" style="width: {{ 70 / count($days_list) }}%;" class="day-header">
                            <strong>{{ $day['day_name'] }}</strong><br>
                            <span class="date-bold">{{ $day['date']->format('d/m') }}</span>
                        </th>
                    @endforeach
                    <th rowspan="2" style="width: 10%;">OBSERVATIONS</th>
                </tr>
                <tr class="sub-header">
                    @foreach($days_list as $day)
                        <th style="width: {{ 35 / count($days_list) }}%; font-size: 8px;">Arrivée</th>
                        <th style="width: {{ 35 / count($days_list) }}%; font-size: 8px;">Départ</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php $empCounter = 1; @endphp
                @foreach($department['employees'] as $employee)
                <tr>
                    <td>{{ $empCounter++ }}</td>
                    <td class="employee-name">
                        {{ strtoupper($employee['employee_name']) }}<br>
                        <small class="small-note">({{ $employee['employee_code'] }})</small>
                    </td>

                    @foreach($days_list as $day)
                        @php
                            $check  = $employee['daily_checks'][$day['date_str']] ?? null;
                            $status = $check ? strtoupper($check['status']) : null;
                        @endphp

                        @if($status === 'MISSION')
                            <td colspan="2" class="mission-cell">
                                Mission
                            </td>

                        @elseif($status === 'CONGE')
                            <td colspan="2" class="conge-cell">
                                {{ $check['leave_info']['type_name'] ?? 'Congé' }}
                            </td>

                        @elseif($check && $status !== 'ABSENT')
                            <td class="check-time">
                                @if(!empty($check['check_in']))
                                    {{ $check['check_in'] }}
                                @else
                                    <span style="color:#999;">--:--</span>
                                @endif
                            </td>
                            <td class="check-time">
                                @if(!empty($check['check_out']))
                                    {{ $check['check_out'] }}
                                @else
                                    <span style="color:#999;">--:--</span>
                                @endif
                            </td>

                        @else
                            <td class="check-time"><span class="absent-cell">-</span></td>
                            <td class="check-time"><span class="absent-cell">-</span></td>
                        @endif

                    @endforeach

                    <td class="observation-note">
                        {{ $employee['observations'] }}
                        <br>
                        <small class="small-note">
                            Présence: {{ $employee['stats']['presence_rate'] }}%
                        </small>
                    </td>
                </tr>
                @endforeach

                <!-- Stats du département par jour -->
                <tr class="stats-row">
                    <td colspan="2" style="text-align: right;"><strong>STATS DÉPARTEMENT :</strong></td>
                    @foreach($days_list as $day)
                        @php
                            $dayPresent = 0;
                            foreach ($department['employees'] as $emp) {
                                $c = $emp['daily_checks'][$day['date_str']] ?? null;
                                if ($c && strtoupper($c['status']) !== 'ABSENT') {
                                    $dayPresent++;
                                }
                            }
                        @endphp
                        <td colspan="2" class="small-note" style="text-align: center;">
                            @if($dayPresent > 0)
                                {{ $dayPresent }}/{{ $department['total_employees'] }}
                            @else
                                -
                            @endif
                        </td>
                    @endforeach
                    <td class="small-note">
                        <strong>Présence: {{ number_format($department['avg_presence_rate'], 1) }}%</strong>
                    </td>
                </tr>
            </tbody>
        </table>
        @endif
    @endforeach

    <!-- Légende -->
    <div style="margin-top: 15px; font-size: 8px; color: #666;">
        <p><strong>Légende :</strong></p>
        <p>
            • <span style="color: #008000;">Taux ≥ 90%</span> : Excellent | 
            • <span style="color: #ff9900;">Taux 80-89%</span> : Satisfaisant | 
            • <span style="color: #ff0000;">Taux &lt; 80%</span> : À améliorer |
            • <span style="color: #ff0000;">-</span> : Absent |
            • <span style="color: #1a5276; background-color:#dce8f7; padding:1px 3px;">Mission</span> : Jour de mission |
            • <span style="color: #1e8449; background-color:#d5f5e3; padding:1px 3px;">Congé</span> : Jour de congé approuvé
        </p>
        <p><strong>Notes :</strong> 
            1. Les statistiques portent uniquement sur les jours ouvrés (lundi-vendredi).<br>
            2. Les heures affichées sont les heures d'arrivée et de départ enregistrées.<br>
            3. Mission et Congé comptent comme présents dans le calcul du taux de présence.
        </p>
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <span class="page-number"></span> | 
        Rapport généré le {{ $export_date->format('d/m/Y à H:i') }} par le système CHECKTIME - Tél: 0141555592.
    </div>
</body>
</html>
