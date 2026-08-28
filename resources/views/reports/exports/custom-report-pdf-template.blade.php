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

        .text-left { text-align: left; }
        .employee-name { text-align: left; font-weight: bold; }

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

        .sub-header {
            background-color: #e0e0e0;
            font-weight: bold;
        }

        .section-title {
            font-weight: bold;
            background-color: #f8f8f8;
        }

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
    </style>
</head>
<body>
    @php
        // Trier par somme des taux, comme le rapport à l'écran.
        $sortedData = collect($report_data)->map(function ($employee) {
            $presenceRate = floatval($employee['presence_data']['rate'] ?? 0);
            $ponctualiteRate = floatval($employee['ponctualite_data']['rate'] ?? 0);
            $employee['total_rate'] = $presenceRate + $ponctualiteRate;
            return $employee;
        })->sortByDesc('total_rate')->values()->all();

        $showTotals = $options['show_totals'] ?? true;

        // Découpage en sections (une section = un groupe non-identité présent
        // dans les colonnes choisies). Les colonnes d'identité sont répétées
        // en tête de chaque tableau en disposition "par section".
        $identityColumns = array_values(array_filter($columns, fn ($k) => ($catalogue[$k]['group'] ?? null) === 'identite'));

        $sections = [];
        foreach ($columns as $key) {
            $group = $catalogue[$key]['group'] ?? null;
            if ($group === null || $group === 'identite') continue;
            if (!isset($sections[$group])) {
                $sections[$group] = ['label' => $catalogue[$key]['group_label'], 'keys' => []];
            }
            $sections[$group]['keys'][] = $key;
        }
        $sections = array_values($sections);

        $useMultiTable = ($options['layout'] ?? 'single') === 'per_section' && count($sections) >= 2;
    @endphp

    <!-- En-tête -->
    <div class="header">
        @php
            $appLogo = \App\Models\Setting::where('key', 'app_logo')->value('value');
        @endphp
        @if($appLogo)
            <img src="{{ public_path($appLogo) }}" alt="Logo" class="client-logo">
        @endif
        <div class="header-content">
            <div class="title">RAPPORT DE PRÉSENCE & PONCTUALITÉ</div>
            <div class="period-info">
                Période : {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}
                ({{ $period_days }} jours)
            </div>
            <div class="client-info">
                Employés : {{ $total_employees }} |
                Modèle : {{ $template_name }} |
                Exporté le : {{ $export_date->format('d/m/Y à H:i') }}
            </div>
        </div>
    </div>

    <div class="sort-info">
        Les employés sont classés par ordre décroissant de la somme des taux de présence et ponctualité
    </div>

    @if($useMultiTable)
        @foreach($sections as $i => $section)
            @if($i > 0)
                <div style="page-break-before: always;"></div>
                <div class="header-content" style="margin-bottom: 8px;">
                    <div class="client-info">
                        Employés : {{ $total_employees }} | Modèle : {{ $template_name }} (suite)
                    </div>
                </div>
            @endif
            <div class="section-title" style="padding: 4px 6px; margin-bottom: 4px; font-size: 11px;">{{ $section['label'] }}</div>
            @include('reports.exports.partials.presence-table', [
                'tableColumns' => array_values(array_unique(array_merge($identityColumns, $section['keys']))),
                'catalogue' => $catalogue,
                'sortedData' => $sortedData,
                'showTotals' => $showTotals,
            ])
        @endforeach
    @else
        @include('reports.exports.partials.presence-table', [
            'tableColumns' => $columns,
            'catalogue' => $catalogue,
            'sortedData' => $sortedData,
            'showTotals' => $showTotals,
        ])
    @endif

    @if($options['show_signatures'] ?? true)
        @include('reports.exports.partials.signataires', ['signatairePostes' => $signatairePostes ?? collect()])
    @endif

    <div style="margin-top: 15px; font-size: 8px; color: #666;">
        <p><strong>Légende :</strong></p>
        <p>
            • <span style="color: #008000;">Taux ≥ 90%</span> : Excellent |
            • <span style="color: #ff9900;">Taux 80-89%</span> : Satisfaisant |
            • <span style="color: #ff0000;">Taux &lt; 80%</span> : À améliorer
        </p>
        <p><strong>Notes :</strong>
        1. Les employés sont classés par ordre décroissant de la somme des taux de présence et ponctualité.<br>
        @if($options['show_weekends'] ?? false)
        2. Les statistiques portent sur tous les jours de la période, week-ends inclus.</p>
        @else
        2. Les statistiques portent uniquement sur les jours ouvrés (lundi-vendredi).<br>
        3. Les weekends et jours fériés ne sont pas inclus dans le calcul.</p>
        @endif
    </div>

    <div class="footer">
        <span class="page-number"></span> |
        Rapport généré le {{ $export_date->format('d/m/Y à H:i') }} par le système CHECKTIME.
    </div>
</body>
</html>
