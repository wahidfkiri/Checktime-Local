@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Rapport Présence & Ponctualité</h3>
                        <p class="text-subtitle text-muted">Analyse détaillée de la présence et ponctualité des employés</p>
                    </div>
                </div>
            </div>

            <section class="section">
                <div class="card">
                    <div class="card-header">
                        <!-- Filtres -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Filtres du rapport</h6>
                                        <!-- Formulaire pour export PDF -->
                                        <form id="exportPdfByDeptForm" action="{{ route('reports.export-department-pdf') }}" method="POST" style="display: none;">
                                            @csrf
                                            <input type="hidden" name="start_date" id="pdf_by_dept_start_date">
                                            <input type="hidden" name="end_date" id="pdf_by_dept_end_date">
                                        </form>
                                        
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="report_start_date" class="form-label">Date début</label>
                                                    <input type="date" class="form-control" id="report_start_date" 
                                                           value="{{ date('Y-m-d', strtotime('-1 days')) }}">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="report_end_date" class="form-label">Date fin</label>
                                                    <input type="date" class="form-control" id="report_end_date" 
                                                           value="{{ date('Y-m-d') }}">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="report_emp_code" class="form-label">Employé</label>
                                                    <select class="form-control" id="report_emp_code">
                                                        <option value="all">Tous les employés</option>
                                                        @foreach($employees as $employee)
                                                            <option value="{{ $employee['emp_code'] }}">
                                                                {{ $employee['emp_code'] }} - {{ $employee['full_name'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group text-start">
                                                    <label class="form-label d-block" style="margin-bottom:0px;">&nbsp;</label>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <button type="button" class="btn btn-primary" id="generate_report">
                                                            <i class="bi bi-file-earmark-text me-1"></i> Générer
                                                        </button>
                                                        <button type="button" class="btn btn-danger" id="export_dept_pdf">
                                                            <i class="bi bi-file-pdf me-1"></i> Exporter
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <div class="alert alert-info alert-sm p-2 mb-0">
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    Rapport personnalisé avec format spécifique pour présence et ponctualité.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">

                    <!-- Barre de progression pour la génération des données -->
                    <div class="alert alert-primary d-none" id="data-progress-container" role="alert">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-bar-chart-steps me-2"></i>
                            <strong id="data-progress-title">Génération du rapport en cours...</strong>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div id="data-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                 role="progressbar" style="width: 0%;" 
                                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                        <p class="mb-0 small mt-2 text-muted" id="data-progress-details">
                            <i class="bi bi-info-circle me-1"></i>
                            Récupération et analyse des données...
                        </p>
                    </div>
                    
                    <!-- Barre de progression pour l'export PDF -->
                    <div class="alert alert-info d-none" id="pdf-progress-container" role="alert">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-file-pdf me-2"></i>
                            <strong>Génération du PDF en cours...</strong>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div id="pdf-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-info" 
                                 role="progressbar" style="width: 0%;" 
                                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                        <p class="mb-0 small mt-2 text-muted" id="pdf-progress-details">
                            <i class="bi bi-info-circle me-1"></i>
                            Préparation du document PDF...
                        </p>
                    </div>
                    
                    <!-- Alert de chargement pour génération PDF -->
                    <div class="alert alert-warning alert-dismissible fade show d-none" id="pdf-loading-alert" role="alert">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm me-2" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div>
                                <strong>Génération du PDF en cours...</strong>
                                <p class="mb-0 small">Cette opération peut prendre quelques instants. Veuillez patienter.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Résumé statistique -->
                    <div id="report-summary" class="row mb-3 d-none">
                        <div class="col-md-12">
                            <div class="card border-success">
                                <div class="card-body">
                                    <h6 class="card-title">📊 Résumé du rapport</h6>
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <div class="border rounded p-2">
                                                <h5 class="mb-0" id="total-employees">0</h5>
                                                <small class="text-muted">Employés analysés</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="border rounded p-2">
                                                <h5 class="mb-0" id="avg-presence-rate">0%</h5>
                                                <small class="text-muted">Taux moyen présence</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="border rounded p-2">
                                                <h5 class="mb-0" id="avg-ponctualite-rate">0%</h5>
                                                <small class="text-muted">Taux moyen ponctualité</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="border rounded p-2">
                                                <h5 class="mb-0" id="total-days">0</h5>
                                                <small class="text-muted">Jours analysés</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2 text-center">
                                        <div class="col-md-12">
                                            <small class="text-muted" id="period-info"></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chargement pour données -->
                    <div class="alert alert-info alert-dismissible fade show d-none" id="data-loading" role="alert">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm me-2" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div>
                                <strong id="data-loading-message">Récupération des données...</strong>
                                <p class="mb-0 small" id="data-loading-details"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tableau personnalisé -->
                    <div class="table-responsive" id="report-table-container">
                        <table id="custom-report-table" class="table table-bordered table-hover" style="width:100%">
                            <thead class="table-dark">
                                 <tr>
                                    <th rowspan="2" class="text-center align-middle">N° d'ordre</th>
                                    <th rowspan="2" class="text-center align-middle">Nom et Prénoms</th>
                                    <th colspan="4" class="text-center">PRÉSENCE AU POSTE</th>
                                    <th colspan="3" class="text-center">PONCTUALITÉ</th>
                                    <th rowspan="2" class="text-center align-middle">Observation</th>
                                 </tr>
                                <tr class="table-secondary">
                                    <th class="text-center">Présence</th>
                                    <th class="text-center">Absence</th>
                                    <th class="text-center">Taux de présence</th>
                                    <th class="text-center">Détail</th>
                                    <th class="text-center">A l'heure</th>
                                    <th class="text-center">Retard</th>
                                    <th class="text-center">Taux de ponctualité</th>
                                 </tr>
                            </thead>
                            <tbody id="report-table-body">
                                <!-- Les données seront insérées ici dynamiquement -->
                            </tbody>
                            <tfoot id="report-table-footer" class="table-active" style="display: none;">
                                <!-- Totaux seront insérés ici -->
                            </tfoot>
                         </table>
                    </div>
                    
                    <!-- Message vide -->
                    <div id="empty-message" class="text-center py-5 d-none">
                        <div class="mb-3">
                            <i class="bi bi-clipboard-data display-4 text-muted"></i>
                        </div>
                        <h5 class="text-muted">Aucune donnée disponible</h5>
                        <p class="text-muted">Sélectionnez une période et cliquez sur "Générer" pour voir le rapport.</p>
                    </div>
                    
                    <!-- Alert pour erreurs -->
                    <div class="alert alert-danger alert-dismissible fade show d-none" id="error-alert" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div>
                                <strong id="error-title">Erreur</strong>
                                <p class="mb-0" id="error-message"></p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>

                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Variables pour suivre l'état
    var isGeneratingPDF = false;
    var isGeneratingReport = false;
    
    // ========== FONCTIONS DE PROGRESSION ==========
    
    // Progression pour la génération des données
    function showDataProgress(title, details = '') {
        isGeneratingReport = true;
        $('#generate_report').prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> Traitement...');
        
        $('#data-progress-title').text(title || 'Génération du rapport en cours...');
        $('#data-progress-details').html('<i class="bi bi-info-circle me-1"></i> ' + details);
        $('#data-progress-container').removeClass('d-none');
        updateDataProgressBar(0);
        
        // Cacher le tableau et résumé pendant le chargement
        $('#report-table-container').hide();
        $('#report-summary').addClass('d-none');
        // Cacher les autres alerts
        $('#pdf-loading-alert').addClass('d-none');
        $('#pdf-progress-container').addClass('d-none');
        $('#error-alert').addClass('d-none');
        $('#empty-message').addClass('d-none');
        $('#data-loading').addClass('d-none');
    }
    
    function hideDataProgress() {
        isGeneratingReport = false;
        $('#generate_report').prop('disabled', false).html('<i class="bi bi-file-earmark-text me-1"></i> Générer');
        $('#data-progress-container').addClass('d-none');
    }
    
    function updateDataProgressBar(percentage, message = null) {
        percentage = Math.min(100, Math.max(0, Math.floor(percentage)));
        $('#data-progress-bar')
            .css('width', percentage + '%')
            .attr('aria-valuenow', percentage)
            .text(percentage + '%');
        
        if (message) {
            $('#data-progress-details').html('<i class="bi bi-info-circle me-1"></i> ' + message);
        }
        
        // Changer la couleur en fonction du pourcentage
        if (percentage < 30) {
            $('#data-progress-bar').removeClass('bg-warning bg-success').addClass('bg-primary');
        } else if (percentage < 70) {
            $('#data-progress-bar').removeClass('bg-primary bg-success').addClass('bg-warning');
        } else {
            $('#data-progress-bar').removeClass('bg-primary bg-warning').addClass('bg-success');
        }
    }
    
    // Simuler la progression pour la génération des données
    function simulateDataProgress(steps, callback) {
        var progress = 0;
        var stepIndex = 0;
        
        var interval = setInterval(function() {
            if (stepIndex < steps.length) {
                var step = steps[stepIndex];
                progress = step.progress;
                updateDataProgressBar(progress, step.message);
                stepIndex++;
            } else {
                progress += Math.random() * 10;
                if (progress >= 100) {
                    progress = 100;
                    updateDataProgressBar(progress, 'Finalisation...');
                    clearInterval(interval);
                    if (callback) setTimeout(callback, 500);
                } else {
                    updateDataProgressBar(Math.floor(progress), 'Traitement en cours...');
                }
            }
        }, 400);
        
        return interval;
    }
    
    // Progression pour l'export PDF
    function showPdfLoading() {
        isGeneratingPDF = true;
        
        if (!$('#export_dept_pdf').data('original-text')) {
            $('#export_dept_pdf').data('original-text', $('#export_dept_pdf').html());
        }
        
        $('#export_dept_pdf').prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> Génération...');
        
        // Afficher l'alerte de chargement PDF
        $('#pdf-loading-alert').removeClass('d-none');
        
        // Afficher et démarrer la barre de progression
        $('#pdf-progress-container').removeClass('d-none');
        updatePdfProgressBar(0, 'Début de la génération...');
        
        // Cacher les autres alerts
        $('#data-progress-container').addClass('d-none');
        $('#error-alert').addClass('d-none');
    }
    
    function hidePdfLoading() {
        isGeneratingPDF = false;
        
        $('#export_dept_pdf').prop('disabled', false).html($('#export_dept_pdf').data('original-text') || '<i class="bi bi-file-pdf me-1"></i> Exporter');
        
        $('#pdf-loading-alert').addClass('d-none');
        $('#pdf-progress-container').addClass('d-none');
    }
    
    function updatePdfProgressBar(percentage, message) {
        // S'assurer que le pourcentage est un nombre entier
        percentage = Math.min(100, Math.max(0, Math.floor(percentage)));
        
        $('#pdf-progress-bar')
            .css('width', percentage + '%')
            .attr('aria-valuenow', percentage)
            .text(percentage + '%');
        
        if (message) {
            $('#pdf-progress-details').html('<i class="bi bi-info-circle me-1"></i> ' + message);
        }
        
        // Changer la couleur en fonction du pourcentage
        if (percentage < 30) {
            $('#pdf-progress-bar').removeClass('bg-warning bg-success').addClass('bg-info');
        } else if (percentage < 70) {
            $('#pdf-progress-bar').removeClass('bg-info bg-success').addClass('bg-warning');
        } else {
            $('#pdf-progress-bar').removeClass('bg-info bg-warning').addClass('bg-success');
        }
    }
    
    // Simuler la progression pour le PDF
    function simulatePdfProgress(callback) {
        var progress = 0;
        var steps = [
            { progress: 10, message: 'Préparation des données...' },
            { progress: 25, message: 'Récupération des informations...' },
            { progress: 40, message: 'Analyse des données...' },
            { progress: 60, message: 'Génération du tableau...' },
            { progress: 80, message: 'Formatage du document...' },
            { progress: 95, message: 'Finalisation...' }
        ];
        var stepIndex = 0;
        
        var interval = setInterval(function() {
            if (stepIndex < steps.length && progress < steps[stepIndex].progress) {
                progress = steps[stepIndex].progress;
                updatePdfProgressBar(progress, steps[stepIndex].message);
                stepIndex++;
            } else if (progress < 95) {
                progress += 2;
                if (progress > 95) progress = 95;
                updatePdfProgressBar(Math.floor(progress), 'Génération en cours...');
            }
        }, 400);
        
        return interval;
    }
    
    // ========== FONCTIONS UTILITAIRES ==========
    
    function showErrorAlert(title, message) {
        $('#error-title').text(title);
        $('#error-message').text(message);
        $('#error-alert').removeClass('d-none');
        // Cacher les loadings
        hidePdfLoading();
        hideDataProgress();
    }
    
    function hideErrorAlert() {
        $('#error-alert').addClass('d-none');
    }
    
    function showSweetAlert(icon, title, text, showConfirm = false) {
        if (showConfirm) {
            return Swal.fire({
                icon: icon,
                title: title,
                html: text,
                showCancelButton: true,
                confirmButtonText: 'Oui, continuer',
                cancelButtonText: 'Annuler',
                allowOutsideClick: false
            });
        } else {
            Swal.fire({
                icon: icon,
                title: title,
                html: text,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }
    }
    
    // Obtenir la classe CSS pour un taux
    function getRateClass(rate) {
        if (rate >= 90) return 'text-success fw-bold';
        if (rate >= 80) return 'text-warning fw-bold';
        return 'text-danger fw-bold';
    }
    
    // Obtenir la classe badge pour un taux
    function getBadgeClass(rate) {
        if (rate >= 90) return 'badge bg-success';
        if (rate >= 80) return 'badge bg-warning';
        return 'badge bg-danger';
    }
    
    // Valider les dates
    function validateDatesForExport() {
        var startDate = $('#report_start_date').val();
        var endDate = $('#report_end_date').val();
        
        if (!startDate || !endDate) {
            showErrorAlert('Erreur de validation', 'Veuillez sélectionner une période.');
            return false;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            showErrorAlert('Erreur de validation', 'La date de début ne peut pas être après la date de fin.');
            return false;
        }
        
        var daysDiff = Math.ceil((new Date(endDate) - new Date(startDate)) / (1000 * 60 * 60 * 24)) + 1;
        if (daysDiff > 365) {
            showSweetAlert('warning', 'Attention', 
                'La période ne doit pas dépasser 1 an pour des performances optimales.');
            return false;
        }
        
        return true;
    }
    
    // Fonction pour trier les données par somme des taux (décroissant)
    function sortDataByTotalRate(data) {
        if (!data || !Array.isArray(data)) return [];
        
        return data.sort(function(a, b) {
            var totalRateA = (parseFloat(a.presence_data?.rate) || 0) + (parseFloat(a.ponctualite_data?.rate) || 0);
            var totalRateB = (parseFloat(b.presence_data?.rate) || 0) + (parseFloat(b.ponctualite_data?.rate) || 0);
            return totalRateB - totalRateA;
        });
    }
    
    // Mettre à jour le résumé
    function updateReportSummary(data) {
        if (!data || data.length === 0) {
            $('#report-summary').addClass('d-none');
            return;
        }
        
        var startDate = $('#report_start_date').val();
        var endDate = $('#report_end_date').val();
        
        var start = new Date(startDate);
        var end = new Date(endDate);
        var totalCalendarDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
        var workingDays = calculateWorkingDays(startDate, endDate);
        
        var totalEmployees = data.length;
        var avgPresenceRate = 0;
        var avgPonctualiteRate = 0;
        var validEmployeesCount = 0;
        
        data.forEach(function(employee) {
            if (employee && employee.presence_data && employee.ponctualite_data) {
                var presenceRate = parseFloat(employee.presence_data.rate) || 0;
                var ponctualiteRate = parseFloat(employee.ponctualite_data.rate) || 0;
                
                avgPresenceRate += presenceRate;
                avgPonctualiteRate += ponctualiteRate;
                validEmployeesCount++;
            }
        });
        
        avgPresenceRate = validEmployeesCount > 0 ? Math.round(avgPresenceRate / validEmployeesCount) : 0;
        avgPonctualiteRate = validEmployeesCount > 0 ? Math.round(avgPonctualiteRate / validEmployeesCount) : 0;
        
        $('#total-employees').text(totalEmployees);
        $('#avg-presence-rate').text(avgPresenceRate + '%');
        $('#avg-ponctualite-rate').text(avgPonctualiteRate + '%');
        $('#total-days').text(workingDays);
        
        $('#avg-presence-rate')
            .removeClass('text-success text-warning text-danger')
            .addClass(getRateClass(avgPresenceRate));
        
        $('#avg-ponctualite-rate')
            .removeClass('text-success text-warning text-danger')
            .addClass(getRateClass(avgPonctualiteRate));
        
        updatePeriodInfo(startDate, endDate, workingDays, totalCalendarDays);
        $('#report-summary').removeClass('d-none');
    }

    function calculateWorkingDays(startDate, endDate) {
        var start = new Date(startDate);
        var end = new Date(endDate);
        var count = 0;
        var current = new Date(start);
        
        while (current <= end) {
            var dayOfWeek = current.getDay();
            if (dayOfWeek >= 1 && dayOfWeek <= 5) {
                count++;
            }
            current.setDate(current.getDate() + 1);
        }
        
        return count;
    }

    function updatePeriodInfo(startDate, endDate, workingDays, totalDays) {
        function formatDate(dateStr) {
            try {
                var date = new Date(dateStr);
                return date.toLocaleDateString('fr-FR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
            } catch (e) {
                return dateStr;
            }
        }
        
        var formattedStart = formatDate(startDate);
        var formattedEnd = formatDate(endDate);
        
        var info = 'Période: ' + formattedStart + ' au ' + formattedEnd + '<br>';
        info += '<small>' + totalDays + ' jour' + (totalDays > 1 ? 's' : '') + ' calendaire' + (totalDays > 1 ? 's' : '') + 
                ' (' + workingDays + ' jour' + (workingDays > 1 ? 's' : '') + ' ouvrable' + (workingDays > 1 ? 's' : '') + ')</small>';
        
        $('#period-info').html(info);
    }
    
    // Afficher les données dans le tableau
    function displayReportData(data) {
        var tbody = $('#report-table-body');
        var tfoot = $('#report-table-footer');
        
        tbody.empty();
        tfoot.hide().empty();
        
        if (!data || data.length === 0) {
            $('#empty-message').removeClass('d-none');
            $('#report-table-container').hide();
            $('#report-summary').addClass('d-none');
            return;
        }
        
        var sortedData = sortDataByTotalRate(data);
        
        $('#empty-message').addClass('d-none');
        $('#report-table-container').show();
        
        var totalPresence = 0;
        var totalAbsence = 0;
        var totalOnTime = 0;
        var totalLate = 0;
        var totalPresenceRate = 0;
        var totalPonctualiteRate = 0;
        
        sortedData.forEach(function(employee, index) {
            var presenceRate = employee.presence_data.rate || 0;
            var ponctualiteRate = employee.ponctualite_data.rate || 0;
            var totalRate = parseFloat(presenceRate) + parseFloat(ponctualiteRate);
            
            var row = 
                '<tr>' +
                '<td class="text-center fw-bold">' + (index + 1) + '</td>' +
                '<td>' + 
                    '<div class="fw-bold">' + (employee.employee_name || 'N/A') + '</div>' +
                    '<small class="text-muted">Code: ' + (employee.employee_code || 'N/A') + '</small>' +
                '</td>' +
                '<td class="text-center text-success fw-bold">' + (employee.presence_data.present || 0) + '</td>' +
                '<td class="text-center text-danger fw-bold">' + (employee.presence_data.absent || 0) + '</td>' +
                '<td class="text-center">' +
                    '<span class="' + getBadgeClass(presenceRate) + '">' + 
                        presenceRate + '%' +
                    '</span>' +
                '</td>' +
                '<td class="text-center">' +
                    '<small>' + (employee.presence_data.present_days_display || '0/0') + '</small>' +
                '</td>' +
                '<td class="text-center text-success fw-bold">' + (employee.ponctualite_data.on_time || 0) + '</td>' +
                '<td class="text-center text-warning fw-bold">' + (employee.ponctualite_data.late || 0) + '</td>' +
                '<td class="text-center">' +
                    '<span class="' + getBadgeClass(ponctualiteRate) + '">' + 
                        ponctualiteRate + '%' +
                    '</span>' +
                '</td>' +
                '<td class="small text-start">' + 
                    (employee.observation || 'Aucune observation') + 
                    '<br><small class="text-muted">Total taux: ' + totalRate.toFixed(1) + '%</small>' +
                '</td>' +
                '</tr>';
            
            tbody.append(row);
            
            totalPresence += parseInt(employee.presence_data.present) || 0;
            totalAbsence += parseInt(employee.presence_data.absent) || 0;
            totalOnTime += parseInt(employee.ponctualite_data.on_time) || 0;
            totalLate += parseInt(employee.ponctualite_data.late) || 0;
            totalPresenceRate += parseFloat(presenceRate);
            totalPonctualiteRate += parseFloat(ponctualiteRate);
        });
        
        var avgPresenceRate = sortedData.length > 0 ? Math.round(totalPresenceRate / sortedData.length) : 0;
        var avgPonctualiteRate = sortedData.length > 0 ? Math.round(totalPonctualiteRate / sortedData.length) : 0;
        
        var footerRow = 
            '<tr class="table-active">' +
            '<td colspan="2" class="text-end fw-bold">TOTAUX / MOYENNES :</td>' +
            '<td class="text-center fw-bold text-success">' + totalPresence + '</td>' +
            '<td class="text-center fw-bold text-danger">' + totalAbsence + '</td>' +
            '<td class="text-center">' +
                '<span class="' + getBadgeClass(avgPresenceRate) + '">' + avgPresenceRate + '%</span>' +
            '</td>' +
            '<td class="text-center">-</td>' +
            '<td class="text-center fw-bold text-success">' + totalOnTime + '</td>' +
            '<td class="text-center fw-bold text-warning">' + totalLate + '</td>' +
            '<td class="text-center">' +
                '<span class="' + getBadgeClass(avgPonctualiteRate) + '">' + avgPonctualiteRate + '%</span>' +
            '</td>' +
            '<td class="text-center">-</td>' +
            '</tr>';
        
        tfoot.append(footerRow).show();
        
        updateReportSummary(sortedData);
    }
    
    // ========== GÉNÉRATION DU RAPPORT ==========
    
    function generateReport() {
        if (!validateDatesForExport()) return;
        
        if (isGeneratingReport) {
            showSweetAlert('info', 'Opération en cours', 'Une génération est déjà en cours. Veuillez patienter.');
            return;
        }
        
        var startDate = $('#report_start_date').val();
        var endDate = $('#report_end_date').val();
        var empCode = $('#report_emp_code').val();
        
        var daysDiff = Math.ceil((new Date(endDate) - new Date(startDate)) / (1000 * 60 * 60 * 24)) + 1;
        var details = 'Analyse de ' + daysDiff + ' jours (du ' + startDate + ' au ' + endDate + ')';
        
        showDataProgress('Génération du rapport en cours...', details);
        hideErrorAlert();
        
        // Simulation des étapes de progression
        var steps = [
            { progress: 10, message: 'Initialisation de la requête...' },
            { progress: 25, message: 'Récupération des données de présence...' },
            { progress: 40, message: 'Analyse des présences...' },
            { progress: 55, message: 'Récupération des données de pointage...' },
            { progress: 70, message: 'Calcul des taux de ponctualité...' },
            { progress: 85, message: 'Génération du tableau...' }
        ];
        
        var progressInterval = simulateDataProgress(steps);
        
        $.ajax({
            url: "{{ route('reports.custom.generate') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                start_date: startDate,
                end_date: endDate,
                emp_code: empCode
            },
            success: function(response) {
                clearInterval(progressInterval);
                updateDataProgressBar(100, 'Rapport généré avec succès !');
                
                setTimeout(function() {
                    if (response.success) {
                        if (response.data && response.data.length > 0) {
                            response.message = 'Rapport généré avec succès. ' + 
                                              response.total_employees + ' employé(s) analysé(s).';
                        }
                        
                        displayReportData(response.data);
                        showSweetAlert('success', 'Succès', response.message);
                    } else {
                        showErrorAlert('Erreur de génération', response.error || 'Erreur lors de la génération du rapport.');
                    }
                    hideDataProgress();
                }, 800);
            },
            error: function(xhr) {
                clearInterval(progressInterval);
                var errorMsg = 'Erreur lors de la génération du rapport.';
                if (xhr.status === 0) {
                    errorMsg = 'Problème de connexion. Vérifiez votre connexion internet.';
                } else if (xhr.status === 404) {
                    errorMsg = 'Service non disponible. Veuillez réessayer plus tard.';
                } else if (xhr.status === 500) {
                    errorMsg = 'Erreur serveur. Veuillez contacter l\'administrateur.';
                } else if (xhr.responseJSON?.error) {
                    errorMsg = xhr.responseJSON.error;
                }
                
                showErrorAlert('Erreur', errorMsg);
                $('#empty-message').removeClass('d-none');
                $('#report-table-container').hide();
                $('#report-summary').addClass('d-none');
                hideDataProgress();
            }
        });
    }
    
    // ========== EXPORT PDF ==========
    
    function exportDeptToPdf() {
        if (!validateDatesForExport()) return;
        
        if (isGeneratingPDF) {
            showSweetAlert('info', 'Opération en cours', 'Une génération PDF est déjà en cours. Veuillez patienter.');
            return;
        }
        
        var startDate = $('#report_start_date').val();
        var endDate = $('#report_end_date').val();
        
        var message = '<div class="text-start">' +
                     '<p><strong>Confirmer l\'export du rapport par département ?</strong></p>' +
                     '<p><i class="bi bi-calendar me-1"></i> <strong>Période :</strong> ' + startDate + ' au ' + endDate + '</p>' +
                     '<p class="small text-muted mt-2"><i class="bi bi-info-circle me-1"></i> Ce rapport affichera :<br>' +
                     '- Récapitulatif par département (présence, absence, taux)<br>' +
                     '- Détail des heures de pointage par employé et par jour</p>' +
                     '</div>';
        
        showSweetAlert('question', 'Exporter', message, true).then((result) => {
            if (result.isConfirmed) {
                showPdfLoading();
                
                // Progression avec nombres entiers
                var progress = 0;
                var progressSteps = [
                    { progress: 5, message: 'Initialisation...' },
                    { progress: 15, message: 'Récupération des données de présence...' },
                    { progress: 30, message: 'Analyse des pointages...' },
                    { progress: 45, message: 'Regroupement par département...' },
                    { progress: 60, message: 'Construction du récapitulatif...' },
                    { progress: 75, message: 'Ajout des heures de pointage...' },
                    { progress: 90, message: 'Génération du PDF...' },
                    { progress: 100, message: 'Finalisation...' }
                ];
                var stepIndex = 0;
                
                var progressInterval = setInterval(function() {
                    if (stepIndex < progressSteps.length && progress < progressSteps[stepIndex].progress) {
                        progress = progressSteps[stepIndex].progress;
                        updatePdfProgressBar(Math.floor(progress), progressSteps[stepIndex].message);
                        stepIndex++;
                    } else if (progress < 95) {
                        progress += 1;
                        updatePdfProgressBar(Math.floor(progress), 'Génération en cours...');
                    }
                }, 500);
                
                $('#pdf_by_dept_start_date').val(startDate);
                $('#pdf_by_dept_end_date').val(endDate);
                $('#exportPdfByDeptForm').submit();
                
                setTimeout(function() {
                    clearInterval(progressInterval);
                    updatePdfProgressBar(100, 'PDF généré avec succès !');
                    setTimeout(function() {
                        hidePdfLoading();
                    }, 1000);
                }, 5000);
            }
        });
    }
    
    // ========== ÉVÉNEMENTS ==========
    
    $('#generate_report').on('click', function() {
        generateReport();
    });
    
    $('#export_dept_pdf').on('click', function() {
        exportDeptToPdf();
    });
    
    // Génération automatique UNIQUEMENT au chargement de la page
    $(window).on('load', function() {
        setTimeout(function() {
            generateReport();
        }, 500);
    });
    
    // Fermer l'alert d'erreur
    $('#error-alert .btn-close').on('click', function() {
        hideErrorAlert();
    });
});
</script>
<style>
    .card-header { background-color: #f8f9fa; }
    .table th { background-color: #f8f9fa; font-weight: 600; }
    
    /* Styles pour les alerts de chargement */
    #pdf-loading-alert, #data-loading {
        border-left: 4px solid #ffc107;
        animation: pulse-alert 1.5s infinite;
    }
    
    #error-alert {
        border-left: 4px solid #dc3545;
    }
    
    @keyframes pulse-alert {
        0% { opacity: 0.8; }
        50% { opacity: 1; }
        100% { opacity: 0.8; }
    }
    
    .spinner-border {
        width: 1.5rem;
        height: 1.5rem;
    }
    
    /* Styles pour le tableau personnalisé */
    #custom-report-table {
        border: 2px solid #dee2e6;
    }
    
    #custom-report-table thead th {
        vertical-align: middle;
        font-weight: 700;
    }
    
    #custom-report-table .table-dark {
        background-color: #2c3e50 !important;
    }
    
    #custom-report-table .table-secondary {
        background-color: #95a5a6 !important;
    }
    
    /* Badges */
    .badge-success { background-color: #28a745 !important; }
    .badge-warning { background-color: #ffc107 !important; color: #000 !important; }
    .badge-danger { background-color: #dc3545 !important; }
    
    /* Statistiques */
    #report-summary .border {
        transition: all 0.3s ease;
        border: 1px solid #dee2e6 !important;
    }
    
    #report-summary .border:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    #report-summary h5 {
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    /* Style pour les boutons désactivés */
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .d-flex.flex-wrap {
            flex-direction: column;
        }
        
        .d-flex.flex-wrap .btn {
            width: 100%;
            margin-bottom: 5px;
        }
        
        #custom-report-table {
            font-size: 0.85rem;
        }
        
        #custom-report-table th,
        #custom-report-table td {
            padding: 0.3rem;
        }
        
        .alert .d-flex {
            flex-direction: column;
            text-align: center;
        }
        
        .alert .spinner-border {
            margin-right: 0 !important;
            margin-bottom: 0.5rem;
        }
    }
    
    @media (max-width: 576px) {
        .card-body .row.g-3 {
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        #report-summary .row.text-center > div {
            margin-bottom: 0.5rem;
        }
    }
    
    /* Style pour les taux */
    .text-success { color: #28a745 !important; }
    .text-warning { color: #ffc107 !important; }
    .text-danger { color: #dc3545 !important; }
    
    /* Style pour les lignes du tableau */
    #custom-report-table tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
    
    /* Animation pour le chargement */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    #report-table-container {
        animation: fadeIn 0.5s ease-in;
    }
</style>
@endsection