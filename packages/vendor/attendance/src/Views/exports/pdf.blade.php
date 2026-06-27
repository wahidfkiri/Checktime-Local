<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export des Présences - Pointages</title>
    <style>
        @page {
            margin: 20px;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #4e73df;
            padding-bottom: 10px;
        }
        
        .header h1 {
            color: #4e73df;
            font-size: 22px;
            margin: 0 0 5px 0;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 12px;
        }
        
        .header .period {
            color: #4e73df;
            font-size: 13px;
            font-weight: bold;
            margin-top: 5px;
        }
        
        .client-info {
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #4e73df;
        }
        
        .client-info h3 {
            color: #4e73df;
            margin: 0 0 8px 0;
            font-size: 14px;
        }
        
        .client-info p {
            margin: 2px 0;
        }
        
        .filters-section {
            margin-bottom: 15px;
        }
        
        .filters-title {
            color: #666;
            font-size: 12px;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 8px;
        }
        
        .filter-item {
            background-color: #e9ecef;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 10px;
        }
        
        .filter-label {
            color: #4e73df;
            font-weight: bold;
        }
        
        .statistics {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin: 15px 0;
        }
        
        .stat-card {
            padding: 12px;
            border-radius: 6px;
            text-align: center;
            background-color: #f8f9fa;
            border: 1px solid #e3e6f0;
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #4e73df;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 11px;
            color: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 10px;
        }
        
        table th {
            background-color: #4e73df;
            color: white;
            text-align: left;
            padding: 8px 10px;
            font-weight: bold;
            border: 1px solid #ddd;
            font-size: 11px;
        }
        
        table td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-present {
            background-color: #198754;
            color: white;
        }
        
        .status-absent {
            background-color: #dc3545;
            color: white;
        }
        
        .punches-cell {
            max-width: 150px;
            word-wrap: break-word;
        }
        
        .punch-time {
            display: inline-block;
            background-color: #e9ecef;
            padding: 1px 5px;
            margin: 1px 2px;
            border-radius: 3px;
            font-size: 9px;
        }
        
        .employee-not-found {
            color: #dc3545;
            font-style: italic;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e3e6f0;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        
        .no-data {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .page-number {
            position: fixed;
            bottom: 15px;
            right: 15px;
            font-size: 10px;
            color: #666;
        }
        
        /* Pour éviter les coupures de page dans les lignes */
        tr { 
            page-break-inside: avoid; 
        }
        
        /* Styles spécifiques pour l'export */
        .column-date {
            width: 10%;
        }
        
        .column-employee {
            width: 20%;
        }
        
        .column-empcode {
            width: 8%;
        }
        
        .column-punches {
            width: 25%;
        }
        
        .column-hours {
            width: 10%;
        }
        
        
        .column-status {
            width: 7%;
        }
        
        .column-matched {
            width: 5%;
        }
        
        .summary-row {
            background-color: #e3f2fd !important;
            font-weight: bold;
        }
        
        .summary-label {
            text-align: right;
            padding-right: 10px !important;
        }
        
        .summary-value {
            color: #4e73df;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport des Présences - Pointages</h1>
        <div class="subtitle">Généré le {{ $export_date }}</div>
        <div class="period">Période: {{ $start_date }} au {{ $end_date }}</div>
    </div>
    
    
    @if(count($attendances) > 0)
    <table>
        <thead>
            <tr>
                <th class="column-date">Date</th>
                <th class="column-employee">Employé</th>
                <th class="column-empcode">Code</th>
                <th class="column-punches">Pointages</th>
                <th class="column-hours">Durée</th>
                <th class="column-status">Statut</th>
                <th class="column-matched">Enregistré</th>
            </tr>
        </thead>
        <tbody>
            @php
                $currentDate = null;
                $dateCount = 0;
            @endphp
            
            @foreach($attendances as $attendance)
                @if($currentDate !== $attendance['date'])
                    @if($currentDate !== null)
                        <!-- Ligne de résumé pour la date précédente -->
                        <tr class="summary-row">
                            <td colspan="3" class="summary-label">
                                Total pour {{ $currentDate }}:
                            </td>
                            <td class="summary-value">
                                {{ $dateCount }} employé(s)
                            </td>
                            <td colspan="4"></td>
                        </tr>
                    @endif
                    
                    @php
                        $currentDate = $attendance['date'];
                        $dateCount = 1;
                    @endphp
                @else
                    @php $dateCount++; @endphp
                @endif
            
            <tr>
                <td>{{ $attendance['date'] }}</td>
                <td>
                    {{ $attendance['full_name'] }}
                    @if($attendance['employee_found'] === 'Non')
                    <span class="employee-not-found">*</span>
                    @endif
                </td>
                <td>{{ $attendance['emp_code'] }}</td>
                <td class="punches-cell">
                    <div style="margin-bottom: 3px;">
                        @foreach($attendance['punch_list'] as $punch)
                            <span class="punch-time">{{ $punch }}</span>
                        @endforeach
                    </div>
                    <div style="font-size: 9px; color: #666;">
                        @if($attendance['first_punch'])
                        Début: <strong>{{ $attendance['first_punch'] }}</strong>
                        @endif
                        @if($attendance['last_punch'])
                        | Fin: <strong>{{ $attendance['last_punch'] }}</strong>
                        @endif
                        | Total: <strong>{{ $attendance['total_punches'] }}</strong>
                    </div>
                </td>
                <td class="text-center">
                    @if($attendance['total_work_hours'])
                    <strong>{{ $attendance['total_work_hours'] }}h</strong>
                    @else
                    <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="text-center">
                    <span class="status-badge status-{{ strtolower($attendance['status']) }}">
                        {{ $attendance['status'] }}
                    </span>
                </td>
                <td class="text-center">
                    {{ $attendance['employee_found'] }}
                </td>
            </tr>
            @endforeach
            
            <!-- Dernière ligne de résumé -->
            @if($currentDate !== null)
            <tr class="summary-row">
                <td colspan="3" class="summary-label">
                    Total pour {{ $currentDate }}:
                </td>
                <td class="summary-value">
                    {{ $dateCount }} employé(s)
                </td>
                <td colspan="4"></td>
            </tr>
            @endif
            
            <!-- Ligne de total général -->
            <tr class="summary-row">
                <td colspan="3" class="summary-label">
                    <strong>TOTAL GÉNÉRAL:</strong>
                </td>
                <td class="summary-value">
                    <strong>{{ count($attendances) }} présence(s)</strong>
                </td>
                <td colspan="4"></td>
            </tr>
        </tbody>
    </table>
    
    @if(collect($attendances)->contains('employee_found', 'Non'))
    <div style="margin-top: 10px; font-size: 10px; color: #dc3545;">
        * Employé non enregistré dans la base de données
    </div>
    @endif
    
    <div class="footer">
        <p>Document généré automatiquement par le système de pointage</p>
        <p>Total: {{ count($attendances) }} présence(s) | 
           Présents: {{ $statistics['present'] }} | 
           Absents: {{ $statistics['absent'] }}</p>
    </div>
    @else
    <div class="no-data">
        <h3>Aucune présence trouvée</h3>
        <p>Aucune donnée ne correspond aux critères de recherche spécifiés.</p>
    </div>
    @endif
    
    <div class="page-number">
        Page 1/1
    </div>
</body>
</html>