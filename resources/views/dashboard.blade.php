@extends('layouts.app')
@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Tableau de Bord</h3>
                        <p class="text-subtitle text-muted">
                            Vue d'ensemble de votre compte
                            @if(isset($client) && $client)
                                - {{ $client->raison_sociale }}
                            @endif
                        </p>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Accueil</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Statistiques Principales - Ligne 1 -->
            <section class="section">
                <div class="row">
                    <!-- Employés Totaux -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card">
                            <div class="card-body px-4 py-4-5">
                                <div class="row">
                                    <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                                        <div class="stats-icon purple mb-2">
                                            <i class="fas fa-users"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                        <h6 class="text-muted font-semibold">Employés</h6>
                                        <h4 class="font-extrabold mb-0">{{ $totalEmployees ?? 0 }}</h4>
                                        <small class="text-success">
                                            <i class="fas fa-user-check"></i> {{ $activeEmployees ?? 0 }} actifs
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Présents Aujourd'hui -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card">
                            <div class="card-body px-4 py-4-5">
                                <div class="row">
                                    <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                                        <div class="stats-icon green mb-2">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                        <h6 class="text-muted font-semibold">Présents Aujourd'hui</h6>
                                        <h4 class="font-extrabold mb-0">{{ $totalPresentToday ?? 0 }}</h4>
                                        <small class="text-info">
                                            <i class="fas fa-percentage"></i> {{ $attendanceRate ?? 0 }}% de présence
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Absents Aujourd'hui -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card">
                            <div class="card-body px-4 py-4-5">
                                <div class="row">
                                    <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                                        <div class="stats-icon orange mb-2">
                                            <i class="fas fa-times-circle"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                        <h6 class="text-muted font-semibold">Absents Aujourd'hui</h6>
                                        <h4 class="font-extrabold mb-0">{{ $totalAbsentToday ?? 0 }}</h4>
                                        <small class="text-warning">
                                            <i class="fas fa-user-clock"></i> Sur {{ $activeEmployees ?? 0 }} actifs
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Retards Aujourd'hui -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card">
                            <div class="card-body px-4 py-4-5">
                                <div class="row">
                                    <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                                        <div class="stats-icon red mb-2">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                        <h6 class="text-muted font-semibold">Retards Aujourd'hui</h6>
                                        <h4 class="font-extrabold mb-0">{{ $totalRetardToday ?? 0 }}</h4>
                                        <small class="text-danger">
                                            <i class="fas fa-exclamation-triangle"></i> {{ $totalPresentToday > 0 ? round(($totalRetardToday / $totalPresentToday) * 100, 1) : 0 }}% des présents
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Statistiques Générales - Ligne 2 (3 nouvelles cards) -->
            <section class="section mt-3">
                <div class="row">
                    <!-- Départements -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card">
                            <div class="card-body px-4 py-4-5">
                                <div class="row">
                                    <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                                        <div class="stats-icon blue mb-2">
                                            <i class="fas fa-layer-group"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                        <h6 class="text-muted font-semibold">Départements</h6>
                                        <h4 class="font-extrabold mb-0">{{ $totalDepartments ?? 0 }}</h4>
                                        <small class="text-info">
                                            <i class="fas fa-sitemap"></i> Organisation interne
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Zones -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card">
                            <div class="card-body px-4 py-4-5">
                                <div class="row">
                                    <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                                        <div class="stats-icon teal mb-2">
                                            <i class="fas fa-map-marked-alt"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                        <h6 class="text-muted font-semibold">Zones</h6>
                                        <h4 class="font-extrabold mb-0">{{ $totalZones ?? 0 }}</h4>
                                        <small class="text-success">
                                            <i class="fas fa-globe"></i> Zones géographiques
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Appareils -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card">
                            <div class="card-body px-4 py-4-5">
                                <div class="row">
                                    <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                                        <div class="stats-icon purple-light mb-2">
                                            <i class="fas fa-fingerprint"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                        <h6 class="text-muted font-semibold">Appareils</h6>
                                        <h4 class="font-extrabold mb-0">{{ $totalDevices ?? 0 }}</h4>
                                        <small class="text-secondary">
                                            <i class="fas fa-wifi"></i> Sync: {{ $recentlySyncedDevices ?? 0 }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Graphiques et Visualisations -->
            <section class="section">
                <div class="row">
                    <!-- Statut des Appareils (remplace Statut des Employés) -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h4>Statut des Appareils</h4>
                                <small class="text-muted">Actif si synchro &lt; 24h</small>
                            </div>
                            <div class="card-body">
                                <div id="device-status-chart"></div>
                                <div class="text-center mt-3">
                                    <span class="badge bg-success me-2">{{ $activeDevices ?? 0 }} Actifs</span>
                                    <span class="badge bg-danger">{{ $inactiveDevices ?? 0 }} Inactifs</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Présence du Jour -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h4>Présence Aujourd'hui</h4>
                            </div>
                            <div class="card-body">
                                <div id="attendance-today-chart"></div>
                                <div class="text-center mt-3">
                                    <span class="badge bg-success me-2">{{ $totalPresentToday ?? 0 }} Présents</span>
                                    <span class="badge bg-warning me-2">{{ $totalRetardToday ?? 0 }} Retards</span>
                                    <span class="badge bg-danger">{{ $totalAbsentToday ?? 0 }} Absents</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Départements -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h4>Top Départements</h4>
                            </div>
                            <div class="card-body">
                                <div id="departments-chart"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Présence Hebdomadaire (Présence vs Absence) -->
                @if(isset($weeklyAttendance) && count($weeklyAttendance) > 0)
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Présence Hebdomadaire</h4>
                            </div>
                            <div class="card-body">
                                <div id="weekly-attendance-chart"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Dernières Présences -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Dernières Présences Enregistrées</h4>
                                <a href="{{ route('admin.daily-attendance.index') }}" class="btn btn-primary btn-sm">Voir toutes</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Employé</th>
                                                <th>Date</th>
                                                <th>Heure Arrivée</th>
                                                <th>Heure Départ</th>
                                                <th>Heures</th>
                                                <th>Statut</th>
                                                <th>Retard</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($recentAttendances ?? [] as $attendance)
                                            <tr>
                                                <td>
                                                    <strong>{{ $attendance->employee->first_name ?? '' }} {{ $attendance->employee->last_name ?? '' }}</strong>
                                                    <br>
                                                    <small>{{ $attendance->emp_code }}</small>
                                                </td>
                                                <td>{{ $attendance->attendance_date ? $attendance->attendance_date->format('d/m/Y') : 'N/A' }}</td>
                                                <td>{{ $attendance->check_in ? $attendance->check_in->format('H:i:s') : 'N/A' }}</td>
                                                <td>{{ $attendance->check_out ? $attendance->check_out->format('H:i:s') : 'N/A' }}</td>
                                                <td>{{ $attendance->work_hours ?? 'N/A' }}</td>
                                                <td>
                                                    @php
                                                        $status = strtolower($attendance->status ?? 'present');
                                                        $statusClasses = [
                                                            'present' => 'badge bg-success',
                                                            'absent' => 'badge bg-danger',
                                                            'late' => 'badge bg-warning',
                                                            'holiday' => 'badge bg-info',
                                                            'leave' => 'badge bg-secondary'
                                                        ];
                                                        $statusClass = $statusClasses[$status] ?? 'badge bg-secondary';
                                                    @endphp
                                                    <span class="{{ $statusClass }}">{{ ucfirst($status) }}</span>
                                                </td>
                                                <td>
                                                    @if($attendance->is_late)
                                                        <span class="badge bg-warning">Retard</span>
                                                    @else
                                                        <span class="badge bg-success">À l'heure</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="7" class="text-center">Aucune présence enregistrée</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau des Derniers Employés -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Derniers Employés Ajoutés</h4>
                                <a href="{{ route('employees.index') }}" class="btn btn-primary btn-sm">Voir tous</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Code</th>
                                                <th>Nom Complet</th>
                                                <th>Zone</th>
                                                <th>Département</th>
                                                <th>Téléphone</th>
                                                <th>Email</th>
                                                <th>Statut</th>
                                                <th>Date Ajout</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($recentEmployees as $employee)
                                            <tr>
                                                <td>
                                                    <strong>{{ $employee->emp_code ?? 'N/A' }}</strong>
                                                </td>
                                                <td>{{ $employee->first_name ?? '' }} {{ $employee->last_name ?? '' }}</td>
                                                <td>{{ $employee->area_name ?? 'N/A' }}</td>
                                                <td>{{ $employee->dept_name ?? 'N/A' }}</td>
                                                <td>{{ $employee->phone ?? 'N/A' }}</td>
                                                <td>{{ $employee->email ?? 'N/A' }}</td>
                                                <td>
                                                    @php
                                                        $status = strtolower($employee->status ?? 'active');
                                                        $statusClasses = [
                                                            'active' => 'badge bg-success',
                                                            'inactive' => 'badge bg-danger',
                                                            'suspended' => 'badge bg-warning'
                                                        ];
                                                        $statusClass = $statusClasses[$status] ?? 'badge bg-secondary';
                                                    @endphp
                                                    <span class="{{ $statusClass }}">{{ ucfirst($status) }}</span>
                                                </td>
                                                <td>{{ $employee->created_at ? $employee->created_at->format('d/m/Y') : 'N/A' }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="8" class="text-center">Aucun employé trouvé</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vue d'Ensemble Graphique -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Évolution des Employés</h4>
                            </div>
                            <div class="card-body">
                                <div id="monthly-growth-chart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
.stats-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.stats-icon.purple { background-color: #6f42c1; }
.stats-icon.purple-light { background-color: #9b6fe0; }
.stats-icon.green { background-color: #28a745; }
.stats-icon.orange { background-color: #fd7e14; }
.stats-icon.red { background-color: #dc3545; }
.stats-icon.blue { background-color: #0d6efd; }
.stats-icon.teal { background-color: #20c997; }

.card {
    transition: transform 0.3s;
    border: 1px solid #e0e0e0;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.progress {
    height: 10px;
    border-radius: 5px;
}

.badge {
    font-size: 0.75em;
    padding: 0.4em 0.8em;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
}
</style>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique de statut des appareils
    var deviceStatusOptions = {
        series: [
            {{ $activeDevices ?? 0 }},
            {{ $inactiveDevices ?? 0 }}
        ],
        chart: {
            type: 'donut',
            height: 250,
        },
        labels: ['Appareils Actifs', 'Appareils Inactifs'],
        colors: ['#28a745', '#dc3545'],
        legend: {
            position: 'bottom'
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " appareils"
                }
            }
        }
    };

    var deviceStatusChart = new ApexCharts(document.querySelector("#device-status-chart"), deviceStatusOptions);
    deviceStatusChart.render();

    // Graphique de présence aujourd'hui
    @if(isset($attendanceTodayData))
    var attendanceTodayOptions = {
        series: [
            {{ $attendanceTodayData['present'] ?? 0 }},
            {{ $attendanceTodayData['retard'] ?? 0 }},
            {{ $attendanceTodayData['absent'] ?? 0 }}
        ],
        chart: {
            type: 'donut',
            height: 250,
        },
        labels: ['Présents', 'Retards', 'Absents'],
        colors: ['#28a745', '#ffc107', '#dc3545'],
        legend: {
            position: 'bottom'
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " employés"
                }
            }
        }
    };

    var attendanceTodayChart = new ApexCharts(document.querySelector("#attendance-today-chart"), attendanceTodayOptions);
    attendanceTodayChart.render();
    @endif

    // Graphique de présence hebdomadaire
    @if(isset($weeklyAttendance) && count($weeklyAttendance) > 0)
    var weeklyDays = @json(array_column($weeklyAttendance, 'day'));
    var weeklyPresent = @json(array_column($weeklyAttendance, 'present'));
    var weeklyAbsent = @json(array_column($weeklyAttendance, 'absent'));

    var weeklyAttendanceOptions = {
        series: [
            {
                name: 'Présents',
                data: weeklyPresent
            },
            {
                name: 'Absents',
                data: weeklyAbsent
            }
        ],
        chart: {
            type: 'bar',
            height: 350,
            stacked: true,
            toolbar: {
                show: true
            }
        },
        colors: ['#28a745', '#dc3545'],
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                endingShape: 'rounded'
            },
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        xaxis: {
            categories: weeklyDays,
        },
        yaxis: {
            title: {
                text: "Nombre d'employés"
            }
        },
        fill: {
            opacity: 1
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " employés"
                }
            }
        },
        legend: {
            position: 'top'
        }
    };

    var weeklyChart = new ApexCharts(document.querySelector("#weekly-attendance-chart"), weeklyAttendanceOptions);
    weeklyChart.render();
    @endif

    // Graphique des top départements
    @if(isset($topDepartmentsCountData) && isset($topDepartmentsLabels))
    var departmentsOptions = {
        series: [{
            data: @json($topDepartmentsCountData ?? [])
        }],
        chart: {
            type: 'bar',
            height: 250,
            toolbar: {
                show: false
            }
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                horizontal: true,
            }
        },
        dataLabels: {
            enabled: true
        },
        xaxis: {
            categories: @json($topDepartmentsLabels ?? []),
            title: {
                text: "Nombre d'employés"
            }
        },
        colors: ['#0d6efd'],
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " employés"
                }
            }
        }
    };

    var departmentsChart = new ApexCharts(document.querySelector("#departments-chart"), departmentsOptions);
    departmentsChart.render();
    @endif

    // Graphique de croissance mensuelle
    @if(isset($monthlyNewEmployees) && isset($monthlyLabels))
    var monthlyGrowthOptions = {
        series: [{
            name: "Nouveaux Employés",
            data: @json($monthlyNewEmployees ?? [])
        }],
        chart: {
            height: 350,
            type: 'area',
            toolbar: {
                show: true
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth'
        },
        xaxis: {
            categories: @json($monthlyLabels ?? [])
        },
        yaxis: {
            title: {
                text: 'Nombre'
            }
        },
        colors: ['#20c997'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.3,
                stops: [0, 90, 100]
            }
        }
    };

    var monthlyChart = new ApexCharts(document.querySelector("#monthly-growth-chart"), monthlyGrowthOptions);
    monthlyChart.render();
    @endif
});
</script>
@endsection