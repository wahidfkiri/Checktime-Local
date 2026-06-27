<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planning - {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</title>
    <style>
        @page {
            margin: 15mm;
            size: A4 {{ $orientation === 'landscape' ? 'landscape' : 'portrait' }};
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            color: #333;
        }
        
        /* En-tête */
        .header {
            margin-bottom: 15px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            position: relative;
            min-height: 80px;
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
            font-size: 16pt;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .report-title {
            font-size: 14pt;
            color: #34495e;
            margin: 5px 0;
        }
        
        .report-period {
            font-size: 11pt;
            color: #7f8c8d;
        }
        
        /* Informations */
        .info-box {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            font-size: 9pt;
        }
        
        /* Légende */
        .legend {
            margin-bottom: 10px;
            padding: 8px;
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 8pt;
        }
        
        .legend-item {
            display: inline-block;
            margin-right: 15px;
        }
        
        .legend-color {
            display: inline-block;
            width: 12px;
            height: 12px;
            margin-right: 4px;
            border-radius: 2px;
        }
        
        /* Tableau calendrier */
        .calendar-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 8pt;
        }
        
        .calendar-table th {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px 4px;
            text-align: center;
            font-weight: bold;
            vertical-align: middle;
        }
        
        .calendar-table td {
            border: 1px solid #dee2e6;
            padding: 6px 4px;
            vertical-align: middle;
            height: 60px;
            min-height: 60px;
        }
        
        .employee-cell {
            background-color: #f8f9fa;
            font-weight: bold;
            min-width: 150px;
            max-width: 150px;
        }
        
        .day-header {
            background-color: #e9ecef !important;
            font-weight: bold;
        }
        
        /* CORRECTION WEEKEND : Samedi et Dimanche */
        .weekend-day {
            background-color: #f8f9fa !important;
        }
        
        .today {
            background-color: #e3f2fd !important;
            border: 2px solid #0d6efd !important;
        }
        
        /* Badges pour les plannings */
        .schedule-badge {
            color: white;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 8pt;
            text-align: center;
            margin: 2px 0;
            display: block;
            width: 100%;
        }
        
        .badge-fixe {
            background-color: #1cc88a !important; /* Vert */
        }
        
        .badge-rotation {
            background-color: #f6c23e !important; /* Orange */
        }
        
        .badge-planifie {
            background-color: #4e73df !important; /* Bleu */
        }
        
        .badge-exception {
            background-color: #e74a3b !important; /* Rouge */
        }
        
        .badge-non-planifie {
            background-color: #858796 !important; /* Gris */
        }
        
        /* Texte pour "Non planifié" et "Weekend" */
        .non-planified {
            color: #6c757d;
            font-size: 8pt;
            text-align: center;
            font-style: italic;
            padding: 8px 0;
            display: block;
            width: 100%;
        }
        
        .weekend-text {
            color: #6c757d;
            font-size: 8pt;
            text-align: center;
            font-style: italic;
            padding: 8px 0;
            display: block;
            width: 100%;
        }
        
        /* Pied de page */
        .footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 40px;
            text-align: center;
            font-size: 8pt;
            color: #6c757d;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
        }
        
        .footer .page-number:after {
            content: counter(page);
        }
        
        /* Saut de page */
        .page-break {
            page-break-after: always;
        }
        
        /* Styles supplémentaires */
        .small-text {
            font-size: 7pt;
        }
        
        .employee-details {
            font-size: 7pt;
            color: #6c757d;
        }
        
        .day-name {
            font-weight: bold;
            font-size: 9pt;
        }
        
        .day-date {
            font-size: 8pt;
            color: #6c757d;
        }
        
        /* Statistiques */
        .stats-box {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            font-size: 9pt;
        }
        
        .stat-item {
            display: inline-block;
            text-align: center;
            margin: 0 10px;
        }
        
        .stat-value {
            font-weight: bold;
            font-size: 10pt;
        }
        
        .stat-label {
            font-size: 8pt;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <!-- Logo du client -->
         @if(isset($client->logo) && $client->logo)
        <img src="<?php echo $_SERVER["DOCUMENT_ROOT"]; ?>/storage/app/public/<?php echo $client->logo; ?>" alt="{{$client->raison_sociale}}" class="client-logo">
    @endif
        
        <div class="header-content">
            <div class="company-name">{{ $client->company_name ?? config('app.name') }}</div>
            <div class="report-title">Calendrier des Plannings</div>
            <div class="report-period">
                Semaine du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} 
                au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            </div>
            <div style="font-size: 10pt; color: #6c757d;">
                Généré le {{ now()->format('d/m/Y à H:i') }}
            </div>
        </div>
    </div>
    
    <!-- Informations -->
    <div class="info-box">
        <table style="width: 100%; font-size: 9pt;">
            <tr>
                <td style="width: 50%;">
                    <strong>Période :</strong> {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                </td>
                <td style="width: 50%;">
                    <strong>Nombre d'employés :</strong> {{ count($employees) }}
                </td>
            </tr>
            
            <!-- CORRECTION : Vérifier si les variables existent avant de les utiliser -->
            @php
                $departmentFilter = $departmentFilter ?? null;
                $areaFilter = $areaFilter ?? null;
            @endphp
            
            @if($departmentFilter)
            <tr>
                <td colspan="2">
                    <strong>Département :</strong> {{ $departmentFilter->name ?? '' }}
                </td>
            </tr>
            @endif
            
            @if($areaFilter)
            <tr>
                <td colspan="2">
                    <strong>Zone :</strong> {{ $areaFilter->name ?? '' }}
                </td>
            </tr>
            @endif
        </table>
    </div>
    
    <!-- Légende -->
    <div class="legend">
        <div style="font-weight: bold; margin-bottom: 5px;">Légende :</div>
        <div class="legend-item">
            <span class="legend-color" style="background-color: #4e73df;"></span>
            <span>Planifié</span>
        </div>
        <div class="legend-item">
            <span class="legend-color" style="background-color: #1cc88a;"></span>
            <span>Fixe</span>
        </div>
        <div class="legend-item">
            <span class="legend-color" style="background-color: #f6c23e;"></span>
            <span>Rotation</span>
        </div>
        <div class="legend-item">
            <span class="legend-color" style="background-color: #858796;"></span>
            <span>Non planifié</span>
        </div>
    </div>
    
    <!-- Tableau calendrier -->
    <table class="calendar-table">
        <thead>
            <tr>
                <th class="employee-cell day-header" style="min-width: 150px;">Employé</th>
                @php
                    $currentDate = \Carbon\Carbon::parse($startDate);
                    $daysOfWeek = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                    $today = \Carbon\Carbon::today()->format('Y-m-d');
                    
                    // Initialiser les statistiques
                    $totalDays = 0;
                    $plannedDays = 0;
                    $nonPlannedDays = 0;
                    $weekendDays = 0;
                @endphp
                @for($i = 0; $i < 7; $i++)
                    @php
                        $dateStr = $currentDate->format('Y-m-d');
                        $isToday = $dateStr === $today;
                        
                        // CORRECTION IMPORTANTE : Utiliser dayOfWeekIso pour détecter correctement les weekends
                        // dayOfWeekIso : 1=Lundi, 2=Mardi, 3=Mercredi, 4=Jeudi, 5=Vendredi, 6=Samedi, 7=Dimanche
                        $dayOfWeekIso = $currentDate->dayOfWeekIso;
                        $isWeekend = $dayOfWeekIso >= 6; // Samedi=6, Dimanche=7
                        
                        $class = $isToday ? 'today ' : '';
                        $class .= $isWeekend ? 'weekend-day ' : '';
                        $dayName = $daysOfWeek[$i];
                        $dayDate = $currentDate->format('d/m');
                    @endphp
                    <th class="day-header {{ trim($class) }}" style="min-width: 80px;">
                        <div class="day-name">{{ $dayName }}</div>
                        <div class="day-date">{{ $dayDate }}</div>
                    </th>
                    @php $currentDate->addDay(); @endphp
                @endfor
            </tr>
        </thead>
        <tbody>
            @php
                $employeeCount = 0;
                $maxEmployeesPerPage = $orientation === 'landscape' ? 25 : 18;
            @endphp
            
            @foreach($employees as $employee)
                @php
                    $employeeCount++;
                    // Saut de page si nécessaire
                    if ($employeeCount > $maxEmployeesPerPage) {
                        echo '</tbody></table>';
                        echo '<div class="page-break"></div>';
                        echo '<table class="calendar-table"><thead><tr>';
                        echo '<th class="employee-cell day-header">Employé</th>';
                        $currentDateTemp = \Carbon\Carbon::parse($startDate);
                        for($i = 0; $i < 7; $i++) {
                            $isWeekendTemp = $currentDateTemp->dayOfWeekIso >= 6;
                            $classTemp = $isWeekendTemp ? 'weekend-day ' : '';
                            echo '<th class="day-header ' . $classTemp . '">';
                            echo '<div class="day-name">' . $daysOfWeek[$i] . '</div>';
                            echo '<div class="day-date">' . $currentDateTemp->format('d/m') . '</div>';
                            echo '</th>';
                            $currentDateTemp->addDay();
                        }
                        echo '</tr></thead><tbody>';
                        $employeeCount = 1;
                    }
                @endphp
                
                <tr>
                    <td class="employee-cell">
                        <div style="font-weight: bold;">{{ $employee->full_name ?? 'N/A' }}</div>
                        <div class="employee-details">
                            {{ $employee->emp_code ?? '' }}
                            @if($employee->department && $employee->department->name)
                                | {{ $employee->department->name }}
                            @endif
                        </div>
                    </td>
                    
                    @php $currentDate = \Carbon\Carbon::parse($startDate); @endphp
                    <!-- Correction dans la vue PDF - partie boucle des cellules -->
                    @php
                        // Définir le premier jour de la période
                        $firstDayOfPeriod = $startDate->format('Y-m-d');
                        $currentDateInLoop = \Carbon\Carbon::parse($startDate);
                        
                        // Initialiser les statistiques pour cet employé
                        $employeeTotalDays = 0;
                        $employeePlannedDays = 0;
                        $employeeNonPlannedDays = 0;
                        $employeeWeekendDays = 0;
                    @endphp

                    <!-- Dans la vue PDF, dans la boucle des cellules -->
                    @for($i = 0; $i < 7; $i++)
                        @php
                            $dateStr = $currentDateInLoop->format('Y-m-d');
                            $isToday = $dateStr === \Carbon\Carbon::today()->format('Y-m-d');
                            
                            // Détection weekend (sauf pour rotations)
                            $dayOfWeekIso = $currentDateInLoop->dayOfWeekIso;
                            $isWeekend = $dayOfWeekIso >= 6;
                            
                            // Récupérer le planning
                            $schedule = null;
                            $isRotationWorkDay = false;
                            $isRotationRestDay = false;
                            
                            if (isset($schedules[$employee->id]) && isset($schedules[$employee->id][$dateStr])) {
                                $schedule = $schedules[$employee->id][$dateStr];
                                
                                // Pour rotation, déterminer si c'est travail ou repos
                                if ($schedule && $schedule->schedule_type === 'rotation') {
                                    $isRotationWorkDay = $schedule->is_rotation_work_day ?? true;
                                    $isRotationRestDay = !$isRotationWorkDay;
                                    
                                    // Pour rotation, on ignore le weekend
                                    $isWeekend = false;
                                }
                            }
                            
                            // Classes CSS
                            $class = $isToday ? 'today ' : '';
                            $class .= ($isWeekend && !$isRotationRestDay) ? 'weekend-day ' : '';
                            
                            // Mettre à jour les statistiques globales
                            if ($isWeekend && !$isRotationRestDay) {
                                $weekendDays++;
                                $employeeWeekendDays++;
                            } else {
                                $totalDays++;
                                $employeeTotalDays++;
                                if ($schedule && !$isRotationRestDay) {
                                    $plannedDays++;
                                    $employeePlannedDays++;
                                } else {
                                    $nonPlannedDays++;
                                    $employeeNonPlannedDays++;
                                }
                            }
                        @endphp
                        
                        <td class="{{ trim($class) }}">
                            <!-- Dans la vue PDF, partie affichage cellule -->
                            @if($schedule)
                                @php
                                    $scheduleType = $schedule->schedule_type ?? 'planifie';
                                    $isRotationRestDay = ($scheduleType === 'rotation' && isset($schedule->is_rest_day) && $schedule->is_rest_day);
                                    
                                    // Mêmes couleurs que le calendrier web
                                    $badgeClass = '';
                                    switch($scheduleType) {
                                        case 'fixe': 
                                            $badgeClass = 'schedule-badge badge-fixe'; // Jaune
                                            $displayText = $schedule->workHourType->name ?? 'Personnalisé';
                                            break;
                                        case 'rotation': 
                                            if ($isRotationRestDay) {
                                                $badgeClass = 'schedule-badge badge-non-planifie'; // Gris pour repos
                                                $displayText = 'Rotation';
                                            } else {
                                                $badgeClass = 'schedule-badge badge-rotation'; // Vert
                                                $displayText = 'Rotation';
                                                if ($schedule->daily_hours == 24.00) {
                                                    $displayText = 'Rotation 24h';
                                                }
                                            }
                                            break;
                                        case 'planifie': 
                                            $badgeClass = 'schedule-badge badge-planifie'; // Bleu
                                            $displayText = $schedule->workHourType->name ?? 'Planifié';
                                            break;
                                    }
                                    
                                    // Heures
                                    $timeText = '';
                                    if (!$isRotationRestDay && $schedule->start_time && $schedule->end_time) {
                                        $timeText = date('H:i', strtotime($schedule->start_time)) . ' - ' . 
                                                   date('H:i', strtotime($schedule->end_time));
                                    }
                                    
                                    // Durée
                                    $durationText = '';
                                    if (isset($schedule->calculated_total_hours) && $schedule->calculated_total_hours > 0) {
                                        $durationText = '(' . $schedule->calculated_total_hours . 'h)';
                                    }
                                @endphp
                                
                                <div class="{{ $badgeClass }}">
                                    <div><strong>{{ $displayText }}</strong></div>
                                    @if(!empty($timeText) && !$isRotationRestDay)
                                        <div class="small-text">{{ $timeText }}</div>
                                    @endif
                                    @if(!empty($durationText) && !$isRotationRestDay)
                                        <div class="small-text">{{ $durationText }}</div>
                                    @endif
                                    @if($isRotationRestDay)
                                        <div class="small-text">Repos</div>
                                    @endif
                                    @if($schedule->notes && !$isRotationRestDay)
                                        <div class="small-text">📝</div>
                                    @endif
                                </div>
                                
                            @else
                                <!-- Aucun planning -->
                                @php
                                    $currentDateInLoop = \Carbon\Carbon::parse($dateStr);
                                    $dayOfWeekIso = $currentDateInLoop->dayOfWeekIso;
                                    $isWeekend = $dayOfWeekIso >= 6;
                                @endphp
                                
                                @if($isWeekend)
                                    <div class="weekend-text">
                                        Weekend
                                    </div>
                                @else
                                    <div class="non-planified">
                                        Non planifié
                                    </div>
                                @endif
                            @endif
                        </td>
                        @php $currentDateInLoop->addDay(); @endphp
                    @endfor
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <!-- Statistiques -->
    @php
        $totalWorkingDays = $totalDays; // Jours hors weekend
        $planificationRate = $totalWorkingDays > 0 ? round(($plannedDays / $totalWorkingDays) * 100, 1) : 0;
        $rateColor = $planificationRate >= 80 ? '#1cc88a' : ($planificationRate >= 50 ? '#f6c23e' : '#e74a3b');
        $includeWeekend = $includeWeekend ?? true;
    @endphp
    
    <div class="stats-box">
        <div style="font-weight: bold; margin-bottom: 8px; text-align: center;">Statistiques de la semaine</div>
        <div style="text-align: center;">
            <div class="stat-item">
                <div class="stat-value" style="color: #4e73df;">{{ $plannedDays }}</div>
                <div class="stat-label">Jours planifiés</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: #858796;">{{ $nonPlannedDays }}</div>
                <div class="stat-label">Jours non planifiés</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: #6c757d;">{{ $weekendDays }}</div>
                <div class="stat-label">Jours de weekend</div>
            </div>
        </div>
        
        <!-- Détails supplémentaires -->
        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #dee2e6; font-size: 8pt; text-align: center;">
            <div>Total jours travaillables : <strong>{{ $totalWorkingDays }}</strong> | 
                 Jours planifiés : <strong>{{ $plannedDays }}</strong> / {{ $totalWorkingDays }}
                 (<span style="color: {{ $rateColor }};"><strong>{{ $planificationRate }}%</strong></span>)
            </div>
            @if(!$includeWeekend)
            <div style="color: #6c757d; font-style: italic;">
                <i>⚠️</i> Les weekends ne sont pas inclus dans cet export
            </div>
            @endif
        </div>
    </div>
    
    <!-- Pied de page -->
    <div class="footer">
        <div>Page <span class="page-number"></span> | Document généré le {{ now()->format('d/m/Y à H:i') }}</div>
        <div>{{ config('app.name') }} - © {{ now()->year }} | {{ $client->company_name ?? 'Client' }}</div>
    </div>
</body>
</html>