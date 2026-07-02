<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Plannings des Employés - {{ $start_date }} au {{ $end_date }}</title>
    <style>
        @page {
            margin: 50px 25px;
            font-family: DejaVu Sans, sans-serif;
        }
        
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 12px; 
            line-height: 1.5;
            color: #333;
        }
        
        .header { 
            text-align: center; 
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #3498db;
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
        
        .title { 
            font-size: 24px; 
            font-weight: bold; 
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .subtitle { 
            font-size: 16px; 
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .info-section { 
            margin-bottom: 30px; 
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
        
        .info-label {
            font-weight: bold;
            color: #2c3e50;
            display: inline-block;
            width: 150px;
        }
        
        .employee-section { 
            margin-bottom: 40px;
            page-break-inside: avoid;
        }
        
        .employee-header { 
            background-color: #2c3e50;
            color: white;
            padding: 12px 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-weight: bold;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 10px;
            font-size: 11px;
        }
        
        th { 
            background-color: #3498db; 
            color: white;
            font-weight: bold; 
            padding: 10px 8px; 
            border: 1px solid #ddd;
            text-align: left;
        }
        
        td { 
            padding: 8px; 
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            min-width: 60px;
        }
        
        .badge-fixe { background-color: #f39c12; color: white; }
        .badge-rotation { background-color: #9b59b6; color: white; }
        .badge-planifie { background-color: #3498db; color: white; }
        .badge-exception { background-color: #e74c3c; color: white; }
        
        .badge-working { background-color: #27ae60; color: white; }
        .badge-rest { background-color: #7f8c8d; color: white; }
        
        .badge-active { background-color: #2ecc71; color: white; }
        .badge-inactive { background-color: #95a5a6; color: white; }
        
        .page-break { page-break-after: always; }
        
        .footer {
            position: fixed;
            bottom: -40px;
            left: 0;
            right: 0;
            height: 40px;
            text-align: center;
            font-size: 10px;
            color: #7f8c8d;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .total-row {
            background-color: #ecf0f1;
            font-weight: bold;
        }
        
        .notes-cell {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .period-cell {
            min-width: 120px;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <!-- Logo du client -->
         @if(isset($client->logo) && $client->logo)
        <img src="{{ public_path($client->logo) }}" alt="{{$client->raison_sociale}}" class="client-logo">
    @endif
        
        <div class="header-content">
        <div class="title">PLANNINGS DES EMPLOYÉS</div>
        <div class="subtitle">Période : {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}</div>
         <div class="subtitle">Exporté le : {{ \Carbon\Carbon::now()->format('d/m/Y à H:i') }}</div> 
        </div>
    </div>
    
    <!-- Informations générales -->
    <div class="info-section">
        <div><span class="info-label">Client :</span> {{ $client->name }}</div>
        <div><span class="info-label">Nombre d'employés :</span> {{ $total_employees }}</div>
        <div><span class="info-label">Nombre de plannings :</span> {{ $total_schedules }}</div>
        <div><span class="info-label">Période :</span> {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}</div>
    </div>
    
    <!-- Plannings par employé -->
    @if(isset($grouped_data) && count($grouped_data) > 0)
        @foreach($grouped_data as $employeeId => $employeeData)
            @if(isset($employeeData['employee']) && $employeeData['employee'])
            <div class="employee-section">
                <div class="employee-header">
                    {{ $employeeData['employee']->emp_code ?? 'N/A' }} - {{ $employeeData['employee']->full_name ?? 'N/A' }}
                    @if(isset($employeeData['employee']->department) && $employeeData['employee']->department)
                        | Département: {{ $employeeData['employee']->department->name }}
                    @endif
                    @if(isset($employeeData['employee']->area) && $employeeData['employee']->area)
                        | Zone: {{ $employeeData['employee']->area->name }}
                    @endif
                </div>
                
                @php
                    // Vérifier si schedules est un array ou une Collection
                    $schedules = isset($employeeData['schedules']) ? $employeeData['schedules'] : [];
                    $scheduleCount = is_array($schedules) ? count($schedules) : ($schedules instanceof \Countable ? $schedules->count() : 0);
                @endphp
                
                @if($scheduleCount > 0)
                <table>
                    <thead>
                        <tr>
                            <th style="width: 15%;">Période</th>
                            <th style="width: 10%;">Type</th>
                            <th style="width: 15%;">Horaire</th>
                            <th style="width: 15%;">Heures</th>
                            <th style="width: 8%;">Durée</th>
                            <th style="width: 10%;">Travail</th>
                            <th style="width: 8%;">Statut</th>
                            <th style="width: 19%;">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($schedules as $schedule)
                        @php
                            // Récupérer le type de planning
                            $scheduleType = $schedule->schedule_type ?? 'planifie';
                            
                            // Format de la période
                            $period = '';
                            if (isset($schedule->start_date) && $schedule->start_date && isset($schedule->end_date) && $schedule->end_date) {
                                $period = \Carbon\Carbon::parse($schedule->start_date)->format('d/m/Y') . ' - ' . 
                                         \Carbon\Carbon::parse($schedule->end_date)->format('d/m/Y');
                            } elseif (isset($schedule->schedule_date) && $schedule->schedule_date) {
                                $period = \Carbon\Carbon::parse($schedule->schedule_date)->format('d/m/Y');
                            } else {
                                $period = 'N/A';
                            }
                            
                            // Format des heures
                            $hours = '';
                            if (isset($schedule->start_time) && $schedule->start_time && isset($schedule->end_time) && $schedule->end_time) {
                                $hours = \Carbon\Carbon::parse($schedule->start_time)->format('H:i') . ' - ' . 
                                        \Carbon\Carbon::parse($schedule->end_time)->format('H:i');
                            } elseif (isset($schedule->workHourType) && $schedule->workHourType) {
                                $hours = \Carbon\Carbon::parse($schedule->workHourType->start_time)->format('H:i') . ' - ' . 
                                        \Carbon\Carbon::parse($schedule->workHourType->end_time)->format('H:i');
                            }
                            
                            // CALCUL DE LA DURÉE SELON LE TYPE DE PLANNING
                            $duration = 'N/A';
                            
                            if ($scheduleType === 'fixe' || $scheduleType === 'planifie') {
                                // Pour types fixe et planifié: calculer avec start_time et end_time
                                if (isset($schedule->start_time) && $schedule->start_time && 
                                    isset($schedule->end_time) && $schedule->end_time) {
                                    
                                    $start = strtotime($schedule->start_time);
                                    $end = strtotime($schedule->end_time);
                                    
                                    // Gérer les plannings nocturnes
                                    $isOvernight = false;
                                    if (isset($schedule->is_overnight)) {
                                        $isOvernight = $schedule->is_overnight;
                                    } elseif (isset($schedule->workHourType) && $schedule->workHourType) {
                                        $isOvernight = $schedule->workHourType->is_overnight ?? false;
                                    }
                                    
                                    if ($isOvernight && $end < $start) {
                                        $end = strtotime($schedule->end_time . ' +1 day');
                                    }
                                    
                                    // Calcul de la durée totale en minutes
                                    $totalMinutes = ($end - $start) / 60;
                                    
                                    // Soustraire les pauses
                                    $breakMinutes = 0;
                                    if (isset($schedule->break_minutes)) {
                                        $breakMinutes = $schedule->break_minutes;
                                    } elseif (isset($schedule->workHourType) && $schedule->workHourType) {
                                        $breakMinutes = $schedule->workHourType->break_minutes ?? 0;
                                    }
                                    
                                    $workMinutes = max(0, $totalMinutes - $breakMinutes);
                                    
                                    // Formater en HH:MM
                                    $hoursWork = floor($workMinutes / 60);
                                    $minutesWork = $workMinutes % 60;
                                    $duration = sprintf('%02d:%02d', $hoursWork, $minutesWork);
                                }
                                
                            } elseif ($scheduleType === 'rotation') {
                                // Pour type rotation: afficher daily_hours
                                if (isset($schedule->workHourType) && $schedule->workHourType) {
                                    $duration = $schedule->workHourType->daily_hours ?? 'N/A';
                                } else {
                                    $duration = isset($schedule->daily_hours) ? $schedule->daily_hours : 'N/A';
                                }
                                
                            } else {
                                // Pour les exceptions et autres types
                                $duration = isset($schedule->duration) ? $schedule->duration : 
                                           (isset($schedule->daily_hours) ? $schedule->daily_hours : 'N/A');
                            }
                            
                            // Sécurité pour les variables non définies
                            $isWorkingDay = $schedule->is_working_day ?? true;
                            $isActive = $schedule->is_active ?? true;
                            $notes = $schedule->notes ?? '';
                        @endphp
                        <tr>
                            <td class="period-cell">{{ $period }}</td>
                            <td>
                                <span class="badge badge-{{ $scheduleType }}">
                                    {{ ucfirst($scheduleType) }}
                                </span>
                            </td>
                            <td>{{ isset($schedule->workHourType) && $schedule->workHourType ? $schedule->workHourType->name : 'Personnalisé' }}</td>
                            <td>{{ $hours }}</td>
                            <td style="text-align: center;">{{ $duration }}</td>
                            <td style="text-align: center;">
                                <span class="badge badge-{{ $isWorkingDay ? 'working' : 'rest' }}">
                                    {{ $isWorkingDay ? 'Travaillé' : 'Repos' }}
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge badge-{{ $isActive ? 'active' : 'inactive' }}">
                                    {{ $isActive ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="notes-cell">{{ $notes }}</td>
                        </tr>
                        @endforeach
                        
                        <!-- Total pour l'employé -->
                        <tr class="total-row">
                            <td colspan="4" style="text-align: right;">Total pour cet employé :</td>
                            <td style="text-align: center; font-weight: bold;">
                                {{ $scheduleCount }} planning(s)
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                </table>
                @else
                <p style="color: #666; font-style: italic; padding: 10px;">Aucun planning trouvé pour cet employé.</p>
                @endif
            </div>
            
            <!-- Saut de page tous les 3 employés -->
            @if(!$loop->last && $loop->iteration % 3 == 0)
                <div class="page-break"></div>
            @endif
            @endif
        @endforeach
    @else
    <div style="text-align: center; padding: 50px; color: #666;">
        <h3>Aucune donnée à exporter</h3>
        <p>Aucun planning trouvé pour les critères sélectionnés.</p>
    </div>
    @endif
    
    <!-- Pied de page -->
    <div class="footer">
        Page <span class="page-number"></span> sur <span class="page-count"></span> |
        Généré le {{ \Carbon\Carbon::now()->format('d/m/Y à H:i') }} |
        © {{ date('Y') }} {{ $client->name ?? 'Système' }}
    </div>
</body>
</html>