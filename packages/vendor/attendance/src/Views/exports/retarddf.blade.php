<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - {{ $start_date }} au {{ $end_date }}</title>
    <style>
        @page { margin: 20px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; line-height: 1.4; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #e74c3c; padding-bottom: 10px; }
        .header h1 { color: #e74c3c; font-size: 22px; margin: 0 0 5px 0; }
        .header .subtitle { color: #666; font-size: 12px; }
        .header .period { color: #e74c3c; font-size: 13px; font-weight: bold; margin-top: 5px; }
        .client-logo { position: absolute; left: 0; top: 0; max-width: 120px; max-height: 60px; object-fit: contain; }
        .header-content { text-align: center; padding-top: 5px; }
        .info-section { margin-bottom: 15px; }
        .info-label { font-weight: bold; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10px; }
        table th { background-color: #e74c3c; color: white; padding: 8px 6px; font-weight: bold; border: 1px solid #ddd; }
        table td { padding: 6px; border: 1px solid #ddd; }
        table tr:nth-child(even) { background-color: #fdf2f2; }
        .status-badge { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .status-late { background-color: #f39c12; color: white; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 10px; }
        .summary-card { background: #fdf2f2; padding: 12px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #e74c3c; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .summary-item { text-align: center; }
        .summary-number { font-size: 18px; font-weight: bold; color: #e74c3c; }
        .summary-label { font-size: 10px; color: #666; }
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

    <div class="summary-card">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-number">{{ $summary['total_retards'] ?? 0 }}</div>
                <div class="summary-label">Total retards</div>
            </div>
            <div class="summary-item">
                <div class="summary-number">{{ $summary['total_late_minutes'] ?? 0 }}</div>
                <div class="summary-label">Minutes de retard</div>
            </div>
            <div class="summary-item">
                <div class="summary-number">{{ $summary['unique_employees'] ?? 0 }}</div>
                <div class="summary-label">Employés concernés</div>
            </div>
            <div class="summary-item">
                <div class="summary-number">{{ $summary['average_late_minutes'] ?? 0 }}</div>
                <div class="summary-label">Moyenne (min)</div>
            </div>
        </div>
    </div>

    @if(count($attendances) > 0)
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Jour</th>
                <th>Employé</th>
                <th>Code</th>
                <th>Département</th>
                <th>Pointage</th>
                <th>Théorique</th>
                <th>Retard</th>
                <th>Statut</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $a)
            <tr>
                <td>{{ $a['date'] }}</td>
                <td>{{ $a['day_name'] }}</td>
                <td>{{ $a['employee_name'] }}</td>
                <td>{{ $a['emp_code'] }}</td>
                <td>{{ $a['dept_name'] }}</td>
                <td>{{ $a['check_in'] }}</td>
                <td>{{ $a['theoretical_start'] }}</td>
                <td>{{ $a['late_hours'] }}</td>
                <td><span class="status-badge status-late">{{ $a['status'] }}</span></td>
                <td>{{ $a['notes'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-data">
        <h3>Aucun retard trouvé</h3>
        <p>Aucune donnée ne correspond aux critères de recherche.</p>
    </div>
    @endif

    <div class="footer">
        <p>Document généré automatiquement par le système de pointage - {{ $companyName }}</p>
        <p>Total: {{ count($attendances) }} retard(s)</p>
    </div>
</body>
</html>
