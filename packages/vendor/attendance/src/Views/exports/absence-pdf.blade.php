<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - {{ $start_date }} au {{ $end_date }}</title>
    <style>
        @page { margin: 20px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; line-height: 1.4; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #dc3545; padding-bottom: 10px; }
        .header h1 { color: #dc3545; font-size: 22px; margin: 0 0 5px 0; }
        .header .subtitle { color: #666; font-size: 12px; }
        .header .period { color: #dc3545; font-size: 13px; font-weight: bold; margin-top: 5px; }
        .client-logo { position: absolute; left: 0; top: 0; max-width: 120px; max-height: 60px; object-fit: contain; }
        .header-content { text-align: center; padding-top: 5px; }
        .info-section { margin-bottom: 15px; }
        .info-label { font-weight: bold; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10px; }
        table th { background-color: #dc3545; color: white; padding: 8px 6px; font-weight: bold; border: 1px solid #ddd; }
        table td { padding: 6px; border: 1px solid #ddd; }
        table tr:nth-child(even) { background-color: #fdf2f2; }
        .status-badge { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .status-absent { background-color: #dc3545; color: white; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 10px; }
        .stats-card { background: #fdf2f2; padding: 12px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #dc3545; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .stat-item { text-align: center; }
        .stat-number { font-size: 18px; font-weight: bold; color: #dc3545; }
        .stat-label { font-size: 10px; color: #666; }
        .no-data { text-align: center; padding: 30px; color: #666; font-style: italic; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    @php
        $logoPath = \App\Models\Setting::where('key', 'app_logo')->value('value');
        $companyName = \App\Models\Setting::where('key', 'company_name')->value('value') ?? 'CheckTime';
    @endphp

    @if($logoPath)
    <img src="{{ public_path($logoPath) }}" alt="{{ $companyName }}" class="client-logo">
    @endif

    <div class="header">
        <div class="header-content">
            <h1>{{ $title }}</h1>
            <div class="subtitle">Généré le {{ $export_date }}</div>
            <div class="period">Période: {{ $start_date }} au {{ $end_date }}</div>
        </div>
    </div>

    <div class="info-section">
        <div><span class="info-label">Client :</span> {{ $companyName }}</div>
        @foreach($filters as $label => $value)
            <div><span class="info-label">{{ ucfirst($label) }} :</span> {{ $value }}</div>
        @endforeach
    </div>

    @if(!empty($statistics))
    <div class="stats-card">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number">{{ $statistics['total_absences'] ?? 0 }}</div>
                <div class="stat-label">Total absences</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">{{ $statistics['unique_employees'] ?? 0 }}</div>
                <div class="stat-label">Employés concernés</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">{{ $statistics['working_days'] ?? 0 }}</div>
                <div class="stat-label">Jours ouvrés</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">{{ $statistics['absence_rate'] ?? 0 }}%</div>
                <div class="stat-label">Taux d'absence</div>
            </div>
        </div>
    </div>
    @endif

    @if(count($attendances) > 0)
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Employé</th>
                <th>Code</th>
                <th>Département</th>
                <th>Arrivée</th>
                <th>Départ</th>
                <th>Heures</th>
                <th>Statut</th>
                <th>Retard</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $a)
            <tr>
                <td>{{ $a['date'] }}</td>
                <td>{{ $a['employee_name'] }}</td>
                <td>{{ $a['emp_code'] }}</td>
                <td>{{ $a['dept_name'] }}</td>
                <td>{{ $a['check_in'] }}</td>
                <td>{{ $a['check_out'] }}</td>
                <td>{{ $a['work_hours'] }}</td>
                <td><span class="status-badge status-absent">{{ $a['status'] }}</span></td>
                <td>{{ $a['is_late'] }}</td>
                <td>{{ $a['notes'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-data">
        <h3>Aucune absence trouvée</h3>
        <p>Aucune donnée ne correspond aux critères de recherche.</p>
    </div>
    @endif

    <div class="footer">
        <p>Document généré automatiquement par le système de pointage - {{ $companyName }}</p>
        <p>Total: {{ count($attendances) }} absence(s)</p>
    </div>
</body>
</html>
