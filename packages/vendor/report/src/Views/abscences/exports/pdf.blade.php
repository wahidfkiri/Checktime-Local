<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport Absences & Retards - {{ $start_date }} au {{ $end_date }}</title>
    <style>
        @page {
            margin: 50px 25px;
            font-family: DejaVu Sans, sans-serif;
        }
        
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 11px; 
            line-height: 1.4;
            color: #333;
        }
        
        .header { 
            text-align: center; 
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
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
            font-size: 22px; 
            font-weight: bold; 
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .subtitle { 
            font-size: 14px; 
            color: #7f8c8d;
            margin-bottom: 4px;
        }
        
        .info-section { 
            margin-bottom: 20px; 
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 4px;
            border-left: 4px solid #3498db;
            font-size: 10px;
        }
        
        .info-label {
            font-weight: bold;
            color: #2c3e50;
            display: inline-block;
            width: 120px;
        }
        
        .employee-section { 
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .employee-header { 
            background-color: #2c3e50;
            color: white;
            padding: 10px 12px;
            margin-bottom: 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 8px;
            font-size: 9px;
        }
        
        th { 
            background-color: #3498db; 
            color: white;
            font-weight: bold; 
            padding: 8px 6px; 
            border: 1px solid #ddd;
            text-align: left;
            font-size: 9px;
        }
        
        td { 
            padding: 6px; 
            border: 1px solid #ddd;
            vertical-align: middle;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
            min-width: 50px;
        }
        
        .badge-present    { background-color: #27ae60; color: white; }
        .badge-absent     { background-color: #e74c3c; color: white; }
        .badge-late       { background-color: #f39c12; color: white; }
        .badge-permission { background-color: #9b59b6; color: white; }
        .badge-leave      { background-color: #3498db; color: white; }
        .badge-holiday    { background-color: #e67e22; color: white; }
        .badge-weekend    { background-color: #7f8c8d; color: white; }
        .badge-day_off    { background-color: #95a5a6; color: white; }
        .badge-no_schedule{ background-color: #34495e; color: white; }
        .badge-mission    { background-color: #8e44ad; color: white; }
        
        .status-ok      { color: #27ae60; font-weight: bold; }
        .status-warning { color: #f39c12; font-weight: bold; }
        .status-error   { color: #e74c3c; font-weight: bold; }
        .status-primary { color: #2980b9; font-weight: bold; }
        
        .page-break { page-break-after: always; }
        
        .footer {
            position: fixed;
            bottom: -35px;
            left: 0;
            right: 0;
            height: 30px;
            text-align: center;
            font-size: 8px;
            color: #7f8c8d;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
        
        .total-row {
            background-color: #ecf0f1;
            font-weight: bold;
        }
        
        .time-cell {
            white-space: nowrap;
        }
        
        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        
        .summary-box {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 15px;
        }
        
        .summary-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 12px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 10px;
        }
    </style>
</head>
<body>

    {{-- ===== EN-TÊTE ===== --}}
    <div class="header">
        @if(isset($client->logo) && $client->logo)
            <img src="{{ public_path($client->logo) }}"
                 alt="{{ $client->raison_sociale }}" class="client-logo">
        @endif
        <div class="header-content">
            <div class="title">RAPPORT DES ABSENCES & RETARDS</div>
            <div class="subtitle">Période : {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}</div>
            <div class="subtitle">Exporté le : {{ $export_date->format('d/m/Y à H:i') }}</div>
        </div>
    </div>

    {{-- ===== INFORMATIONS GÉNÉRALES ===== --}}
    <div class="info-section">
        <div><span class="info-label">Client :</span> {{ $client->name }}</div>
        <div><span class="info-label">Nombre d'employés :</span> {{ $total_employees }}</div>
        <div><span class="info-label">Période analysée :</span> {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}</div>
        <div><span class="info-label">Filtre employé :</span> {{ $filters['emp_code'] }}</div>
        <div><span class="info-label">Total enregistrements :</span> {{ $total_records }}</div>
    </div>

    {{-- ===== LÉGENDE ===== --}}
    <div class="summary-box">
        <div class="summary-title">Légende des statuts :</div>
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <span class="badge badge-present">Présent</span>
            <span class="badge badge-absent">Absent</span>
            <span class="badge badge-late">En retard</span>
            <span class="badge badge-permission">Permission</span>
            <span class="badge badge-leave">Congé</span>
            <span class="badge badge-mission">Mission</span>
            <span class="badge badge-holiday">Férié</span>
            <span class="badge badge-weekend">Weekend</span>
            <span class="badge badge-day_off">Repos</span>
            <span class="badge badge-no_schedule">Non planifié</span>
        </div>
    </div>

    {{-- ===== DONNÉES PAR EMPLOYÉ ===== --}}
    @if(count($grouped_data) > 0)
        @foreach($grouped_data as $empCode => $employeeData)
            @if($employeeData['employee'] && count($employeeData['records']) > 0)
            <div class="employee-section">

                <div class="employee-header">
                    {{ $employeeData['employee']->emp_code ?? 'N/A' }} -
                    {{ $employeeData['employee']->first_name ?? '' }}
                    {{ $employeeData['employee']->last_name ?? '' }}
                </div>

                {{-- ===== RÉSUMÉ EMPLOYÉ ===== --}}
                @php
                    $employeeRecords    = $employeeData['records'];
                    $presentCount       = 0;
                    $absentCount        = 0;
                    $lateCount          = 0;
                    $missionCount       = 0;   // ← initialisé AVANT la boucle
                    $totalLateMinutes   = 0;
                    $totalWorkMinutes   = 0;
                    $totalOvertimeMinutes = 0;

                    foreach ($employeeRecords as $record) {
                        if ($record['status'] === 'present')  $presentCount++;
                        if ($record['status'] === 'absent')   $absentCount++;
                        if ($record['status'] === 'mission')  $missionCount++;
                        if ($record['is_late'])               $lateCount++;
                        if ($record['late_minutes'])          $totalLateMinutes   += $record['late_minutes'];
                        if ($record['work_minutes'])          $totalWorkMinutes   += $record['work_minutes'];
                        if (isset($record['overtime_minutes']) && $record['overtime_minutes'] > 0)
                                                              $totalOvertimeMinutes += $record['overtime_minutes'];
                    }

                    $totalDays       = count($employeeRecords);
                    $absenceRate     = $totalDays   > 0 ? round(($absentCount  / $totalDays)   * 100, 1) : 0;
                    $averageLate     = $lateCount   > 0 ? round($totalLateMinutes / $lateCount, 1)        : 0;
                    $averageWorkHours= $presentCount> 0 ? round(($totalWorkMinutes / $presentCount) / 60, 1) : 0;
                @endphp

                <div class="summary-box">
                    <div class="summary-title">Résumé pour cet employé :</div>
                    <div class="summary-item">
                        <span>Jours analysés :</span>
                        <span>{{ $totalDays }} jours</span>
                    </div>
                    <div class="summary-item">
                        <span>Présences :</span>
                        <span>{{ $presentCount }} jours ({{ $totalDays > 0 ? round(($presentCount / $totalDays) * 100, 1) : 0 }}%)</span>
                    </div>
                    <div class="summary-item">
                        <span>Absences :</span>
                        <span class="{{ $absenceRate > 20 ? 'status-error' : 'status-ok' }}">
                            {{ $absentCount }} jours ({{ $absenceRate }}%)
                        </span>
                    </div>
                    <div class="summary-item">
                        <span>Retards :</span>
                        <span class="{{ $lateCount > 0 ? 'status-warning' : 'status-ok' }}">
                            {{ $lateCount }} jours ({{ $totalDays > 0 ? round(($lateCount / $totalDays) * 100, 1) : 0 }}%)
                        </span>
                    </div>
                    <div class="summary-item">
                        <span>Retard moyen :</span>
                        <span>{{ $averageLate }} minutes</span>
                    </div>
                    <div class="summary-item">
                        <span>Temps moyen travaillé :</span>
                        <span>{{ $averageWorkHours }} heures</span>
                    </div>
                    <div class="summary-item">
                        <span>Missions :</span>
                        <span>{{ $missionCount }} jour(s)</span>
                    </div>
                    <div class="summary-item">
                        <span>H. Supplémentaires totales :</span>
                        <span class="{{ $totalOvertimeMinutes > 0 ? 'status-primary' : '' }}">
                            {{ $totalOvertimeMinutes > 0 ? $totalOvertimeMinutes . ' min' : '-' }}
                        </span>
                    </div>
                </div>

                {{-- ===== TABLEAU DÉTAILLÉ ===== --}}
                <table>
                    <thead>
                        <tr>
                            <th style="width: 9%;">Date</th>
                            <th style="width: 9%;">Jour</th>
                            <th style="width: 10%;">Horaire prévu</th>
                            <th style="width: 13%;">Pointages</th>
                            <th style="width: 7%;">Retard</th>
                            <th style="width: 8%;">Départ anticipé</th>
                            <th style="width: 7%;">H. Supp.</th>
                            <th style="width: 8%;">H. Travaillées</th>
                            <th style="width: 9%;">Statut</th>
                            <th style="width: 20%;">Remarques</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employeeRecords as $record)
                        @php
                            $date      = \Carbon\Carbon::parse($record['date']);
                            $dayName   = $date->locale('fr')->dayName;
                            $dayOfWeek = $date->dayOfWeekIso;

                            // Horaire prévu
                            $scheduleTime = ($record['schedule_start'] !== '-' && $record['schedule_end'] !== '-')
                                ? $record['schedule_start'] . ' - ' . $record['schedule_end']
                                : '-';

                            // Pointages
                            $punchTimes = '';
                            if ($record['actual_arrival'])   $punchTimes .= '<strong>Arrivée:</strong> ' . $record['actual_arrival'] . '<br>';
                            if ($record['actual_departure'])  $punchTimes .= '<strong>Départ:</strong> '  . $record['actual_departure'];
                            if (!$record['actual_arrival'] && !$record['actual_departure']) $punchTimes = 'Aucun pointage';

                            // Retard
                            $lateDisplay = '-';
                            if ($record['late_minutes'] !== null) {
                                $lateDisplay = ($record['late_minutes'] == 0) ? 'À l\'heure' : $record['late_minutes'] . ' min';
                            }

                            // Départ anticipé
                            $earlyLeaveDisplay = '-';
                            if ($record['early_leave_minutes'] !== null) {
                                $earlyLeaveDisplay = ($record['early_leave_minutes'] == 0) ? 'Non' : $record['early_leave_minutes'] . ' min';
                            }

                            // Heures supplémentaires
                            $overtimeDisplay = '-';
                            if (isset($record['overtime_minutes']) && $record['overtime_minutes'] !== null && $record['overtime_minutes'] > 0) {
                                $overtimeDisplay = $record['overtime_minutes'] . ' min';
                            }

                            // Heures travaillées
                            $workHoursDisplay = '-';
                            if ($record['work_minutes'] > 0) {
                                $hours   = floor($record['work_minutes'] / 60);
                                $minutes = $record['work_minutes'] % 60;
                                $workHoursDisplay = sprintf('%dh%02d', $hours, $minutes);
                            }

                            // Badge statut
                            $statusClass = 'badge-' . $record['status'];
                            $statusText  = match($record['status']) {
                                'present'    => 'Présent',
                                'absent'     => 'Absent',
                                'leave'      => 'Congé',
                                'permission' => 'Permission',
                                'mission'    => 'Mission',
                                'weekend'    => 'Weekend',
                                'holiday'    => 'Férié',
                                'day_off'    => 'Repos',
                                'no_schedule'=> 'Non planifié',
                                default      => ucfirst(str_replace('_', ' ', $record['status'])),
                            };
                            // Si présent avec retard → badge late
                            if ($record['status'] === 'present' && $record['is_late']) {
                                $statusClass = 'badge-late';
                                $statusText  = 'En retard';
                            }

                            // Remarques
                            $remarks = [];
                            if ($record['is_weekend'])                                          $remarks[] = 'Weekend';
                            if ($record['is_holiday'])                                          $remarks[] = 'Jour férié';
                            if ($record['is_on_leave'])                                         $remarks[] = 'En congé';
                            if ($record['has_permission'])                                      $remarks[] = 'Permission';
                            if (isset($record['is_on_mission']) && $record['is_on_mission'])    $remarks[] = 'En mission';
                            if ($record['schedule_type'] === 'Non planifié')                    $remarks[] = 'Non planifié';
                            if (!empty($record['all_punches']))                                 $remarks[] = count($record['all_punches']) . ' pointage(s)';
                            $remarksText = implode(', ', $remarks);
                        @endphp
                        <tr>
                            <td class="time-cell">{{ $date->format('d/m/Y') }}</td>
                            <td class="time-cell">{{ ucfirst($dayName) }}</td>
                            <td class="time-cell">{{ $scheduleTime }}</td>
                            <td>{!! $punchTimes !!}</td>
                            <td class="text-center {{ $record['late_minutes'] > 0 ? 'status-warning' : '' }}">
                                {{ $lateDisplay }}
                            </td>
                            <td class="text-center {{ ($record['early_leave_minutes'] ?? 0) > 0 ? 'status-warning' : '' }}">
                                {{ $earlyLeaveDisplay }}
                            </td>
                            <td class="text-center {{ (isset($record['overtime_minutes']) && $record['overtime_minutes'] > 0) ? 'status-primary' : '' }}">
                                {{ $overtimeDisplay }}
                            </td>
                            <td class="text-center">{{ $workHoursDisplay }}</td>
                            <td class="text-center">
                                <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                            </td>
                            <td>{{ $remarksText }}</td>
                        </tr>
                        @endforeach

                        {{-- ===== LIGNE TOTAL EMPLOYÉ ===== --}}
                        <tr class="total-row">
                            <td colspan="4" style="text-align: right; font-weight: bold;">Total pour cet employé :</td>
                            <td class="text-center">
                                {{ $totalLateMinutes > 0 ? $totalLateMinutes . ' min' : '-' }}
                            </td>
                            <td class="text-center">
                                {{ $lateCount > 0 ? $lateCount . ' jour(s)' : '-' }}
                            </td>
                            <td class="text-center {{ $totalOvertimeMinutes > 0 ? 'status-primary' : '' }}">
                                {{ $totalOvertimeMinutes > 0 ? $totalOvertimeMinutes . ' min' : '-' }}
                            </td>
                            <td class="text-center">
                                {{ $totalWorkMinutes > 0 ? round($totalWorkMinutes / 60, 1) . 'h' : '-' }}
                            </td>
                            <td class="text-center">
                                {{ $presentCount }} / {{ $totalDays }}
                            </td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if(!$loop->last && $loop->iteration % 2 == 0)
                <div class="page-break"></div>
            @endif
            @endif
        @endforeach
    @else
        <div style="text-align: center; padding: 40px; color: #666;">
            <h3>Aucune donnée à exporter</h3>
            <p>Aucun enregistrement trouvé pour les critères sélectionnés.</p>
        </div>
    @endif

    {{-- ===== PIED DE PAGE ===== --}}
    <div class="footer">
        Page <span class="page-number"></span> sur <span class="page-count"></span> |
        Généré le {{ $export_date->format('d/m/Y à H:i') }} |
        © {{ date('Y') }} {{ $client->name ?? 'Système de Gestion' }}
    </div>
</body>
</html>
