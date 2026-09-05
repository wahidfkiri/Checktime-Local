<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Tableau de Suivi de la Ponctualité</title>
    <style>
        @page { margin: 18px 14px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #222;
        }

        .entete { text-align: center; margin-bottom: 10px; }
        .logo { position: absolute; left: 0; top: 0; max-width: 90px; max-height: 45px; }
        .titre { font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .mois { font-size: 10px; font-weight: bold; margin-top: 3px; }
        .periode { font-size: 9px; color: #555; }

        table { width: 100%; border-collapse: collapse; }

        th, td {
            border: 1px solid #555;
            padding: 2px 1px;
            text-align: center;
            vertical-align: middle;
        }

        thead th { background-color: #e8e8e8; font-size: 7.5px; }

        .col-nom { text-align: left; width: 13%; font-weight: bold; font-size: 7px; padding-left: 3px; }

        /* Contenu des cellules : très étroit, on réduit encore la police. */
        tbody td { font-size: 5.5px; line-height: 1.15; padding: 1px 0; }
        tbody td.col-nom { font-size: 7px; }
        tbody td .detail { font-size: 5px; color: #8a4b00; }
        .cell-ok { background-color: #ffffff; }

        .cell-late { background-color: #fff3cd; font-weight: bold; }
        .cell-early { background-color: #ffe5d0; }
        .cell-absent { background-color: #f8d7da; }
        .cell-mission { background-color: #d1ecf1; }
        .cell-leave { background-color: #d4edda; }
        .cell-permission { background-color: #ececec; }
        .total { background-color: #e8e8e8; font-weight: bold; font-size: 7.5px; }

        .legende { margin-top: 8px; font-size: 7px; color: #444; }
        .legende span { display: inline-block; border: 1px solid #bbb; padding: 0 5px; margin-right: 4px; }

        .signatures { margin-top: 22px; width: 100%; }
        .signatures td { border: none; text-align: center; font-size: 8px; padding-top: 6px; }
        .signature-poste { font-weight: bold; }
        .signature-ligne { margin-top: 26px; border-top: 1px solid #555; width: 70%; margin-left: auto; margin-right: auto; }

        .pied { position: fixed; bottom: -8px; left: 0; right: 0; text-align: center; font-size: 6.5px; color: #777; }
    </style>
</head>
<body>
    @php
        $logoPath = \App\Models\Setting::where('key', 'app_logo')->value('value');
        $companyName = \App\Models\Setting::where('key', 'company_name')->value('value') ?? 'CheckTime';
        $nbJours = count($report['days']);
    @endphp

    <div class="entete">
        @if($logoPath)
            <img src="{{ public_path($logoPath) }}" alt="{{ $companyName }}" class="logo">
        @endif
        <div class="titre">Statistiques des retards, sorties et absences non justifiées par jour</div>
        <div class="mois">{{ $report['month_label'] }}</div>
        <div class="periode">{{ $report['period_label'] }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" class="col-nom">Nom et Prénoms</th>
                <th colspan="{{ $nbJours }}">Jours ouvrables de la période</th>
                <th colspan="2">TOTAL</th>
            </tr>
            <tr>
                @foreach($report['days'] as $day)
                    <th>{{ $day['day_short'] }}<br>{{ $day['day_number'] }}</th>
                @endforeach
                <th>Retard</th>
                <th>en mn</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    <td class="col-nom">{{ $row['employee_name'] }}</td>
                    @foreach($report['days'] as $day)
                        @php $cell = $row['cells'][$day['date']] ?? ['text' => '', 'detail' => '', 'type' => 'ok']; @endphp
                        <td class="cell-{{ $cell['type'] }}">
                            {{ $cell['text'] }}
                            @if(!empty($cell['detail']))
                                <div class="detail">{{ $cell['detail'] }}</div>
                            @endif
                        </td>
                    @endforeach
                    <td class="total">{{ $row['total_retards'] }}</td>
                    <td class="total">{{ $row['total_minutes'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $nbJours + 3 }}" style="padding:12px;">Aucun employé pour ces critères.</td>
                </tr>
            @endforelse

            @if(count($report['rows']) > 0)
                <tr class="total">
                    <td class="col-nom">TOTAL GÉNÉRAL</td>
                    <td colspan="{{ $nbJours }}"></td>
                    <td>{{ $report['totals']['retards'] }}</td>
                    <td>{{ $report['totals']['minutes'] }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="legende">
        <strong>Légende :</strong>
        <span class="cell-ok">08:30 , 17:00</span> arrivée et départ
        <span class="cell-late">25 mn</span> retard
        <span class="cell-early">sortie</span> sortie anticipée
        <span class="cell-absent">absent</span> absence non justifiée
        <span class="cell-mission">en mission</span>
        <span class="cell-leave">en congé</span>
        <span class="cell-permission">autorisation</span>
        — pour une journée travaillée, la cellule donne l'arrivée et le départ, l'anomalie figurant en dessous.
    </div>

    @if($signatairePostes->count() > 0)
        <table class="signatures">
            <tr>
                @foreach($signatairePostes as $poste)
                    <td>
                        <div class="signature-poste">{{ $poste->name ?? $poste->libelle ?? '' }}</div>
                        <div class="signature-ligne"></div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    <div class="pied">
        {{ $companyName }} — Tableau de Suivi de la Ponctualité — exporté le {{ $export_date->format('d/m/Y à H:i') }}
    </div>
</body>
</html>
