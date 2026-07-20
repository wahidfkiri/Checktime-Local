<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export des Permissions</title>
    <style>
        @page {
            margin: 20px;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
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
            font-size: 24px;
            margin: 0 0 5px 0;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 14px;
        }
        
        .client-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #4e73df;
        }
        
        .client-info h3 {
            color: #4e73df;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        
        .filters-section {
            margin-bottom: 20px;
        }
        
        .filters-title {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .filter-item {
            background-color: #e9ecef;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 11px;
        }
        
        .filter-label {
            color: #4e73df;
            font-weight: bold;
        }
        
        .statistics {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            background-color: #f8f9fa;
            border: 1px solid #e3e6f0;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #4e73df;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table th {
            background-color: #4e73df;
            color: white;
            text-align: left;
            padding: 12px;
            font-weight: bold;
            border: 1px solid #ddd;
        }
        
        table td {
            padding: 10px 12px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #ffc107;
            color: #000;
        }
        
        .status-approved {
            background-color: #198754;
            color: white;
        }
        
        .status-rejected {
            background-color: #dc3545;
            color: white;
        }
        
        .status-canceled {
            background-color: #6c757d;
            color: white;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e3e6f0;
            text-align: center;
            color: #666;
            font-size: 11px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
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
            bottom: 20px;
            right: 20px;
            font-size: 11px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport des Permissions</h1>
        <div class="subtitle">Généré le {{ $export_date }}</div>
    </div>
    
    <div class="client-info">
        <h3>Client: {{ $client->name }}</h3>
        @if($client->address)
        <p>Adresse: {{ $client->address }}</p>
        @endif
        @if($client->phone)
        <p>Téléphone: {{ $client->phone }}</p>
        @endif
    </div>
    
    @if(!empty($filters))
    <div class="filters-section">
        <div class="filters-title">Filtres appliqués:</div>
        <div class="filters-grid">
            @foreach($filters as $key => $value)
            <div class="filter-item">
                <span class="filter-label">{{ ucfirst($key) }}:</span> {{ $value }}
            </div>
            @endforeach
        </div>
    </div>
    @endif
    
    <div class="statistics">
        <div class="stat-card">
            <div class="stat-number">{{ $statistics['total'] }}</div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $statistics['pending'] }}</div>
            <div class="stat-label">En attente</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $statistics['approved'] }}</div>
            <div class="stat-label">Approuvées</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $statistics['rejected'] }}</div>
            <div class="stat-label">Rejetées</div>
        </div>
    </div>
    
    @if($permissions->count() > 0)
    <table>
        <thead>
            <tr>
                <th width="15%">Employé</th>
                <th width="12%">Dates</th>
                <th width="13%">Horaire</th>
                <th width="10%">Durée</th>
                <th width="30%">Raison</th>
                <th width="10%">Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($permissions as $permission)
            <tr>
                <td>{{ $permission->employee->first_name }} {{ $permission->employee->last_name }}</td>
                <td>
                    @php
                        $pStart = $permission->getEffectiveStartDate();
                        $pEnd = $permission->getEffectiveEndDate();
                        $pStartStr = \Carbon\Carbon::parse($pStart)->format('d/m/Y');
                        $pEndStr = $pEnd ? \Carbon\Carbon::parse($pEnd)->format('d/m/Y') : $pStartStr;
                    @endphp
                    {{ $pStartStr === $pEndStr ? $pStartStr : $pStartStr . ' → ' . $pEndStr }}
                </td>
                <td>
                    @if($permission->start_time && $permission->end_time)
                        {{ \Carbon\Carbon::parse($permission->start_time)->format('H:i') }} - 
                        {{ \Carbon\Carbon::parse($permission->end_time)->format('H:i') }}
                    @else
                        Toute la journée
                    @endif
                </td>
                <td>
                    @if($permission->duration_minutes)
                        @php
                            $hours = floor($permission->duration_minutes / 60);
                            $minutes = $permission->duration_minutes % 60;
                        @endphp
                        @if($hours > 0)
                            {{ $hours }}h@if($minutes > 0) {{ $minutes }}min@endif
                        @else
                            {{ $minutes }} min
                        @endif
                    @else
                        N/A
                    @endif
                </td>
                <td>{{ $permission->raison }}</td>
                <td class="text-center">
                    @php
                        $statusClasses = [
                            'pending' => 'status-pending',
                            'approved' => 'status-approved',
                            'rejected' => 'status-rejected',
                            'canceled' => 'status-canceled'
                        ];
                        $statusText = [
                            'pending' => 'En attente',
                            'approved' => 'Approuvé',
                            'rejected' => 'Rejeté',
                            'canceled' => 'Annulé'
                        ];
                    @endphp
                    <span class="status-badge {{ $statusClasses[$permission->status] ?? 'status-canceled' }}">
                        {{ $statusText[$permission->status] ?? $permission->status }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        <p>Document généré automatiquement par le système de gestion des permissions</p>
        <p>Total: {{ $total }} permission(s)</p>
    </div>
    @else
    <div class="no-data">
        <h3>Aucune permission trouvée</h3>
        <p>Aucune donnée ne correspond aux critères de recherche spécifiés.</p>
    </div>
    @endif
    
    <div class="page-number">
        Page 1/1
    </div>
</body>
</html>