<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport Mensuel RH - {{ \Carbon\Carbon::parse($start_date)->format('m/Y') }}</title>
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
            position: relative;
        }

        .client-logo {
            position: absolute;
            left: 0;
            top: 0;
            max-width: 150px;
            max-height: 70px;
            object-fit: contain;
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

        .present          { color: #008000; font-weight: bold; }
        .absent           { color: #ff0000; font-weight: bold; }
        .late             { color: #ff9900; }
        .early            { color: #ff9900; }
        .rate-high        { color: #008000; font-weight: bold; }
        .rate-medium      { color: #ff9900; font-weight: bold; }
        .rate-low         { color: #ff0000; font-weight: bold; }
        .department-name  { text-align: left; font-weight: bold; background-color: #f9f9f9; }
        .total-row        { background-color: #e6e6e6; font-weight: bold; }
        .small-note       { font-size: 7px; color: #666; }

        .week-title {
            background-color: #c0c0c0;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            padding: 8px;
            border: 1px solid #000;
            margin-bottom: 15px;
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

        .stat-item   { margin: 5px; }
        .stat-label  { font-weight: bold; color: #666; font-size: 9px; }
        .stat-value  { font-size: 18px; font-weight: bold; }
    </style>
</head>
<body>

    <!-- ── En-tête ─────────────────────────────────────────────── -->
    <div class="header">
        @if(isset($client->logo) && $client->logo)
            <img src="{{ public_path($client->logo) }}"
                 alt="{{ $client->raison_sociale ?? '' }}"
                 class="client-logo">
        @endif
        <div>
            <div class="title">RAPPORT MENSUEL DE PRÉSENCE DU PERSONNEL</div>
            <div class="period-info">
                Mois de {{ ucfirst($month_name) }} {{ $year }} —
                Période : {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }}
                au {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}
                ({{ $period_days }} jours ouvrés)
            </div>
            <div class="client-info">
                <strong>Client :</strong> {{ $client->name ?? $client_name }} |
                <strong>Départements :</strong> {{ $total_departments }} |
                <strong>Employés :</strong> {{ $totals['total_employees'] ?? 0 }} |
                <strong>Export :</strong> {{ $export_date->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    @if(count($report_data) == 0)
        <div style="text-align:center; padding:50px; color:#ff0000;">
            <strong>Aucune donnée trouvée pour ce mois.</strong>
        </div>
    @endif

    <!-- ── Titre tableau ───────────────────────────────────────── -->
    <div class="week-title">
        RÉCAPITULATIF PAR DÉPARTEMENT —
        {{ strtoupper($month_name) }} {{ $year }}
    </div>

    <!-- ── TABLEAU RÉCAPITULATIF (identique au 1er tableau de exportCustomPdfByDept) ── -->
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width:5%;">N°</th>
                <th rowspan="2" style="width:20%;">Département</th>
                <th rowspan="2" style="width:8%;">Employés</th>
                <th colspan="4" style="background-color:#d9d9d9;">PRÉSENCE</th>
                <th colspan="4" style="background-color:#d9d9d9;">PONCTUALITÉ</th>
            </tr>
            <tr>
                <th style="width:8%;">Présent</th>
                <th style="width:8%;">Absent</th>
                <th style="width:8%;">Taux</th>
                <th style="width:8%;">Ratio</th>
                <th style="width:8%;">À l'heure</th>
                <th style="width:8%;">Retard</th>
                <th style="width:8%;">Départ précoce</th>
                <th style="width:8%;">Taux</th>
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

                    {{-- Présence --}}
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

                    {{-- Ponctualité --}}
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

            {{-- Totaux généraux --}}
            @if(count($report_data) > 0)
            <tr class="total-row">
                <td colspan="2" style="text-align:right;"><strong>TOTAUX GÉNÉRAUX :</strong></td>
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

    <!-- ── Statistiques globales ───────────────────────────────── -->
    @if(count($report_data) > 0)
    <div class="summary-box">
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-label">Ratio Global Présence/Absence</div>
                <div class="stat-value">
                    <span class="present">{{ number_format($totals['total_present'] ?? 0) }}</span> /
                    <span class="absent">{{ number_format($totals['total_absent'] ?? 0) }}</span>
                </div>
                <div class="small-note">
                    Total jours: {{ number_format(($totals['total_employees'] ?? 0) * $period_days) }}
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Taux présence global</div>
                <div class="stat-value rate-{{ ($totals['avg_presence_rate'] ?? 0) >= 90 ? 'high' : (($totals['avg_presence_rate'] ?? 0) >= 80 ? 'medium' : 'low') }}">
                    {{ number_format($totals['avg_presence_rate'] ?? 0, 1) }}%
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Missions</div>
                <div class="stat-value" style="color:#1a5276;">
                    {{ number_format($totals['total_mission'] ?? 0) }}
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Congés</div>
                <div class="stat-value" style="color:#1e8449;">
                    {{ number_format($totals['total_leave'] ?? 0) }}
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Retards</div>
                <div class="stat-value" style="color:#ff9900;">
                    {{ number_format($totals['total_late'] ?? 0) }}
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Départs précoces</div>
                <div class="stat-value" style="color:#ff9900;">
                    {{ number_format($totals['total_early_leave'] ?? 0) }}
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ── Légende ─────────────────────────────────────────────── -->
    <div style="margin-top:15px; font-size:8px; color:#666;">
        <p><strong>Légende :</strong>
            • <span style="color:#008000;">Taux ≥ 90%</span> : Excellent |
            • <span style="color:#ff9900;">Taux 80-89%</span> : Satisfaisant |
            • <span style="color:#ff0000;">Taux &lt; 80%</span> : À améliorer
        </p>
        <p><strong>Notes :</strong>
            1. Statistiques sur jours ouvrés uniquement (lundi–vendredi).<br>
            2. Missions et congés approuvés comptent comme présents.<br>
            3. Rapport généré automatiquement le dernier jour du mois.
        </p>
    </div>

    <!-- ── Pied de page ────────────────────────────────────────── -->
    <div class="footer">
        <span class="page-number"></span> |
        Rapport généré le {{ $export_date->format('d/m/Y à H:i') }} par le système CHECKTIME - Tél: 0141555592.
    </div>

</body>
</html>
