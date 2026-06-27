<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapport de présence hebdomadaire</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .summary {
            background-color: #e8f5e9;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .attendance-table th,
        .attendance-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .attendance-table th {
            background-color: #4CAF50;
            color: white;
        }
        .attendance-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport de présence hebdomadaire</h1>
        <p>Période : {{ $data['start_date'] }} au {{ $data['end_date'] }}</p>
    </div>
    
    <div class="content">
        <p>Bonjour {{ $data['employee']->first_name }},</p>
        
        <p>Voici votre rapport de présence pour la semaine du <strong>{{ $data['start_date'] }} au {{ $data['end_date'] }}</strong>.</p>
        
        <div class="summary">
            <h3>Résumé hebdomadaire</h3>
            <p><strong>Total d'heures travaillées :</strong> {{ $data['total_hours'] }} heures</p>
            <p><strong>Nombre de jours de présence :</strong> {{ $data['days_with_attendance'] ?? count($data['attendances']) }} jours</p>
        </div>
        
        @if(!empty($data['attendances']))
        <h3>Détails des pointages</h3>
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Arrivée</th>
                    <th>Départ</th>
                    <th>Heures travaillées</th>
                    <th>Terminal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['attendances'] as $attendance)
                @php
                    $workHours = 'N/A';
                    if (isset($attendance['arrival_time']) && isset($attendance['departure_time']) && 
                        $attendance['arrival_time'] && $attendance['departure_time']) {
                        try {
                            $arrival = \Carbon\Carbon::createFromFormat('H:i:s', $attendance['arrival_time']);
                            $departure = \Carbon\Carbon::createFromFormat('H:i:s', $attendance['departure_time']);
                            if ($departure > $arrival) {
                                $minutes = $departure->diffInMinutes($arrival);
                                $workHours = round($minutes / 60, 2) . 'h';
                            }
                        } catch (\Exception $e) {
                            $workHours = 'N/A';
                        }
                    }
                    
                    // Formater la date
                    $formattedDate = 'N/A';
                    if (isset($attendance['date'])) {
                        try {
                            $formattedDate = \Carbon\Carbon::parse($attendance['date'])->format('d/m/Y');
                        } catch (\Exception $e) {
                            $formattedDate = $attendance['date'];
                        }
                    }
                @endphp
                <tr>
                    <td>{{ $formattedDate }}</td>
                    <td>{{ $attendance['arrival_time'] ?? 'Non pointé' }}</td>
                    <td>{{ $attendance['departure_time'] ?? 'Non pointé' }}</td>
                    <td>{{ $workHours }}</td>
                    <td>{{ $attendance['device_name'] ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>Aucun pointage enregistré pour cette période.</p>
        @endif
        
        <p>Cordialement,<br>
        L'équipe de {{ $data['client_name'] ?? ($data['client']->name ?? 'CHECKTIME') }}</p>
    </div>
    
    <div class="footer">
        <p>Ceci est un email automatique, merci de ne pas y répondre.</p>
        <p>© {{ date('Y') }} {{ $data['client_name'] ?? ($data['client']->name ?? 'CHECKTIME') }}. Tous droits réservés.</p>
    </div>
</body>
</html>