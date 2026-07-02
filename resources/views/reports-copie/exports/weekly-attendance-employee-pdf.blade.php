<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport de Présence — {{ $employee_data['employee_name'] }} — {{ $start_date }} au {{ $end_date }}</title>
    <style>
        @page { margin: 20px; font-family: DejaVu Sans, sans-serif; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
        }

        /* ── HEADER (identique à custom-report-pdf-by-dept) ── */
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }
        .client-logo {
            position: absolute;
            left: 0; top: 0;
            max-width: 150px; max-height: 70px;
            object-fit: contain;
        }
        .header-content { text-align: center; padding-top: 5px; }
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

        /* ── EMPLOYEE CARD ── */
        .employee-card {
            border: 1px solid #ccc;
            background: #f9f9f9;
            padding: 8px 12px;
            margin-bottom: 12px;
        }
        .employee-card table { width: 100%; border: none; margin: 0; }
        .employee-card td    { border: none; padding: 2px 6px; }
        .emp-name { font-size: 13px; font-weight: bold; text-transform: uppercase; }

        /* ── STATS SUMMARY ── */
        .stats-box {
            display: table;
            width: 100%;
            margin-bottom: 14px;
            border-collapse: collapse;
        }
        .stat-cell {
            display: table-cell;
            width: 16.6%;
            text-align: center;
            border: 1px solid #ccc;
            padding: 6px 4px;
            background: #eef2ff;
        }
        .stat-val  { font-size: 13px; font-weight: bold; color: #1a3a7a; }
        .stat-lbl  { font-size: 8px; color: #555; }
        .stat-green { color: #008000; }
        .stat-red   { color: #cc0000; }
        .stat-orange { color: #cc6600; }
        .stat-blue  { color: #1a5276; }

        /* ── TABLE POINTAGE (même style que la blade dept) ── */
        .week-title {
            background-color: #c0c0c0;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            padding: 8px;
            border: 1px solid #000;
            margin-bottom: 15px;
        }
        table.att {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9px;
        }
        table.att th {
            background-color: #e8edf9;
            font-weight: bold;
            padding: 6px 4px;
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
        }
        table.att td {
            padding: 4px 4px;
            border: 1px solid #ccc;
            text-align: center;
            vertical-align: middle;
        }
        .day-header { background-color: #f0f0f0; font-weight: bold; font-size: 10px; text-align: center; }
        .day-header strong { font-size: 11px; font-weight: bold; }
        .date-bold { font-weight: bold; font-size: 10px; }
        .check-time { font-family: monospace; font-size: 9px; font-weight: bold; color: #000; }
        .absent-cell { color: #ff0000; font-weight: bold; }
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
        .employee-name { text-align: left; font-weight: bold; }
        .observation-note { font-size: 8px; text-align: left; color: #666; max-width: 150px; }
        .small-note { font-size: 8px; color: #555; }

        /* taux coloré */
        .rate-high   { color: #008000; font-weight: bold; }
        .rate-medium { color: #ff9900; font-weight: bold; }
        .rate-low    { color: #ff0000; font-weight: bold; }

        /* ── FOOTER ── */
        .footer {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            height: 20px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 5px;
            background-color: white;
        }
        .page-number:before { content: "Page " counter(page); }
    </style>
</head>
<body>

{{-- ── HEADER ── --}}
<div class="header">
    @if(!empty($client->logo))
        <img class="client-logo" src="{{ public_path('storage/' . $client->logo) }}" alt="Logo">
    @endif
    <div class="header-content">
        <div class="title">Rapport de Présence Hebdomadaire</div>
        <div class="small-note">{{ $client_name }}</div>
    </div>
</div>

<div class="period-info">
    Période : {{ $start_date }} au {{ $end_date }}
</div>

{{-- ── EMPLOYEE CARD ── --}}
<div class="employee-card">
    <table>
        <tr>
            <td>
                <span class="emp-name">{{ strtoupper($employee_data['employee_name']) }}</span><br>
                <span class="small-note">Code : {{ $employee_data['employee_code'] }}</span>
            </td>
            <td style="text-align:right;">
                <span class="small-note">
                    Rapport généré le {{ $export_date->format('d/m/Y à H:i') }}
                </span>
            </td>
        </tr>
    </table>
</div>

{{-- ── STATS ── --}}
@php $stats = $employee_data['stats']; @endphp
<div class="stats-box">
    <div class="stat-cell">
        <div class="stat-val">{{ $period_days }}</div>
        <div class="stat-lbl">Jours ouvrés</div>
    </div>
    <div class="stat-cell">
        <div class="stat-val stat-green">{{ $stats['present'] }}</div>
        <div class="stat-lbl">Présent(s)</div>
    </div>
    <div class="stat-cell">
        <div class="stat-val stat-red">{{ $stats['absent'] }}</div>
        <div class="stat-lbl">Absent(s)</div>
    </div>
    <div class="stat-cell">
        <div class="stat-val stat-orange">{{ $stats['late'] }}</div>
        <div class="stat-lbl">Retard(s)</div>
    </div>
    <div class="stat-cell">
        <div class="stat-val stat-blue">{{ $stats['mission'] }}</div>
        <div class="stat-lbl">Mission(s)</div>
    </div>
    <div class="stat-cell">
        <div class="stat-val rate-{{ $stats['presence_rate'] >= 90 ? 'high' : ($stats['presence_rate'] >= 80 ? 'medium' : 'low') }}">
            {{ number_format($stats['presence_rate'], 1) }}%
        </div>
        <div class="stat-lbl">Taux présence</div>
    </div>
</div>

{{-- ── TABLEAU POINTAGE JOUR PAR JOUR ── --}}
{{-- Même structure que le Tableau 2 de custom-report-pdf-by-dept --}}
<div class="week-title">{{ $week_range }} — HEURES D'ARRIVÉE ET DE DÉPART</div>

<table class="att">
    <thead>
        <tr>
            <th rowspan="2" style="width: 20%; text-align: left;">NOM ET PRÉNOMS</th>
            @foreach($days_list as $day)
                <th colspan="2" style="width: {{ 65 / count($days_list) }}%;" class="day-header">
                    <strong>{{ $day['day_name'] }}</strong><br>
                    <span class="date-bold">{{ $day['date']->format('d/m') }}</span>
                </th>
            @endforeach
            <th rowspan="2" style="width: 15%;">OBSERVATIONS</th>
        </tr>
        <tr>
            @foreach($days_list as $day)
                <th style="font-size: 8px;">Arrivée</th>
                <th style="font-size: 8px;">Départ</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        <tr>
            {{-- Nom / Code --}}
            <td class="employee-name">
                {{ strtoupper($employee_data['employee_name']) }}<br>
                <small class="small-note">({{ $employee_data['employee_code'] }})</small>
            </td>

            {{-- Cellule par jour — strictement identique à la blade dept --}}
            @foreach($days_list as $day)
                @php
                    $check  = $employee_data['daily_checks'][$day['date_str']] ?? null;
                    $status = $check ? strtoupper($check['status']) : null;
                @endphp

                @if($status === 'MISSION')
                    <td colspan="2" class="mission-cell">Mission</td>

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

            {{-- Observations + taux --}}
            <td class="observation-note">
                {{ $employee_data['observations'] }}<br>
                <small class="small-note">
                    Présence : {{ $stats['presence_rate'] }}% |
                    Ponctualité : {{ $stats['ponctualite_rate'] }}%
                </small>
            </td>
        </tr>
    </tbody>
</table>

{{-- ── LÉGENDE ── --}}
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

{{-- ── FOOTER ── --}}
<div class="footer">
    <span class="page-number"></span> |
    Rapport généré le {{ $export_date->format('d/m/Y à H:i') }}
</div>

</body>
</html>