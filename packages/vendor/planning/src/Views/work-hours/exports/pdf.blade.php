<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Liste des types d'horaires - {{ $client->name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
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
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .report-title {
            font-size: 16px;
            color: #666;
            margin-top: 10px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 5px;
            border: 1px solid #ddd;
        }
        .info-table .label {
            font-weight: bold;
            background-color: #f5f5f5;
            width: 30%;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .data-table th {
            background-color: #2c3e50;
            color: white;
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .data-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .status-active {
            color: #28a745;
            font-weight: bold;
        }
        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(isset($client->logo) && $client->logo)
        <img src="{{ public_path($client->logo) }}" alt="{{$client->raison_sociale}}" class="client-logo">
    @endif
        <div class="header-content">
        <div class="report-title">LISTE DES TYPES D'HORAIRES</div>
        <div style="font-size: 11px; color: #888; margin-top: 5px;">
            Exporté le: {{ $export_date }}
        </div>
    </div>
    </div>
    
    <table class="info-table">
        <tr>
            <td class="label">Date d'export:</td>
            <td>{{ $export_date }}</td>
        </tr>
        <tr>
            <td class="label">Nombre total:</td>
            <td>{{ $total }} types d'horaires</td>
        </tr>
    </table>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Code</th>
                <th>Nom</th>
                <th>Horaires</th>
                <th>Pause</th>
                <th>Durée</th>
                <th>Statut</th>
                <th>Type</th>
            </tr>
        </thead>
        <tbody>
            @foreach($work_hours as $index => $hour)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $hour->code }}</td>
                <td>{{ $hour->name }}</td>
                <td>{{ date('H:i', strtotime($hour->start_time)) }} - {{ date('H:i', strtotime($hour->end_time)) }}</td>
                <td>{{ $hour->break_minutes }} min</td>
                <td>
                    @php
                        $start = strtotime($hour->start_time);
                        $end = strtotime($hour->end_time);
                        if ($hour->is_overnight && $end < $start) {
                            $end = strtotime($hour->end_time . ' +1 day');
                        }
                        $totalMinutes = ($end - $start) / 60;
                        $workMinutes = $totalMinutes - $hour->break_minutes;
                        $workHours = $workMinutes / 60;
                    @endphp
                    {{ number_format($workHours, 2) }}h
                </td>
                <td class="{{ $hour->is_active ? 'status-active' : 'status-inactive' }}">
                    {{ $hour->is_active ? 'Actif' : 'Inactif' }}
                </td>
                <td>
                    {{ $hour->is_overnight ? 'Nuit' : 'Jour' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        <p>Document généré automatiquement par le système de gestion des plannings</p>
        <p>{{ config('app.name') }} • {{ date('Y') }}</p>
    </div>
</body>
</html>