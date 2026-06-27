@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Rapport Absences & Retards</h3>
                        <p class="text-subtitle text-muted">Analyse des présences à partir des données quotidiennes</p>
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
                                        <!-- Formulaire pour PDF -->
                                        <form id="exportPdfForm" action="{{ route('admin.reports.export.pdf') }}" method="POST" style="display: none;">
                                            @csrf
                                            <input type="hidden" name="start_date" id="pdf_start_date">
                                            <input type="hidden" name="end_date" id="pdf_end_date">
                                            <input type="hidden" name="emp_code" id="pdf_emp_code">
                                        </form>
                                        
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="report_start_date" class="form-label">Date début</label>
                                                    <input type="date" class="form-control" id="report_start_date" 
                                                           value="{{ date('Y-m-d', strtotime('-7 days')) }}">
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
                                                        <!-- <button type="button" class="btn btn-success" id="export_excel">
                                                            <i class="bi bi-file-excel me-1"></i> Excel
                                                        </button> -->
                                                        <button type="button" class="btn btn-danger" id="export_pdf">
                                                            <i class="bi bi-file-pdf me-1"></i> PDF
                                                        </button>
                                                        <!-- <button type="button" class="btn btn-info" id="preview_pdf">
                                                            <i class="bi bi-eye me-1"></i> Prévisualiser
                                                        </button> -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <!-- Résumé statistique -->
                        <div id="report-summary" class="row mb-3 d-none">
                            <div class="col-md-12">
                                <div class="card border-success">
                                    <div class="card-body">
                                        <h6 class="card-title">📊 Résumé du rapport</h6>
                                        <div class="row text-center">
                                            <div class="col-md-2 col-sm-4 mb-2">
                                                <div class="border rounded p-2 bg-light">
                                                    <h5 class="mb-0" id="total-days">0</h5>
                                                    <small class="text-muted">Jours analysés</small>
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-sm-4 mb-2">
                                                <div class="border rounded p-2 bg-light">
                                                    <h5 class="mb-0 text-success" id="present-count">0</h5>
                                                    <small class="text-muted">Présences</small>
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-sm-4 mb-2">
                                                <div class="border rounded p-2 bg-light">
                                                    <h5 class="mb-0 text-danger" id="absent-count">0</h5>
                                                    <small class="text-muted">Absences</small>
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-sm-4 mb-2">
                                                <div class="border rounded p-2 bg-light">
                                                    <h5 class="mb-0 text-warning" id="late-count">0</h5>
                                                    <small class="text-muted">Retards</small>
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-sm-4 mb-2">
                                                <div class="border rounded p-2 bg-light">
                                                    <h5 class="mb-0 text-info" id="leave-count">0</h5>
                                                    <small class="text-muted">Congés</small>
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-sm-4 mb-2">
                                                <div class="border rounded p-2 bg-light">
                                                    <h5 class="mb-0 text-secondary" id="permission-count">0</h5>
                                                    <small class="text-muted">Permissions</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ajoutez ces deux barres de progression après le chargement existant (vers ligne 130) -->

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
                        
                        <!-- Chargement -->
                        <div class="alert alert-info alert-dismissible fade show d-none" id="report-loading" role="alert">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <strong id="report-loading-message">Chargement des données en cours...</strong>
                            </div>
                        </div>
                        
                        <!-- Tableau -->
                        <div class="table-responsive">
                            <table id="report-table" class="table table-striped table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Code</th>
                                        <th>Employé</th>
                                        <th>Planning</th>
                                        <th>Arrivée</th>
                                        <th>Départ</th>
                                        <th>Retard (min)</th>
                                        <th>Départ anticipé (min)</th>
                                        <th>H. Supp. (min)</th>
                                        <th>Heures travaillées</th>
                                        <th>Statut</th>
                                        <th>Observations</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        
                        <!-- Pied de tableau avec informations -->
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="alert alert-secondary p-2 small">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Légende :</strong>
                                    <span class="badge bg-success ms-2">Présent</span>
                                    <span class="badge bg-warning ms-1">Retard</span>
                                    <span class="badge bg-danger ms-1">Absent</span>
                                    <span class="badge bg-info ms-1">Congé</span>
                                    <span class="badge bg-secondary ms-1">Weekend/Repos</span>
                                </div>
                            </div>
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
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
// Script complet à remplacer
<script>
$(document).ready(function() {
    // Variables
    var reportTable;
    var isGeneratingPDF = false;
    var isGeneratingReport = false;
    var totalSummaryData = {
        present: 0,
        presentLate: 0,
        absent: 0,
        leave: 0,
        permission: 0,
        days: 0
    };
    
    // ========== FONCTIONS DE PROGRESSION ==========
    
    // Progression pour la génération des données
    function showDataProgress(title, details = '') {
        isGeneratingReport = true;
        $('#generate_report').prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> Traitement...');
        
        $('#data-progress-title').text(title || 'Génération du rapport en cours...');
        $('#data-progress-details').html('<i class="bi bi-info-circle me-1"></i> ' + details);
        $('#data-progress-container').removeClass('d-none');
        updateDataProgressBar(0);
        
        // Cacher les autres éléments
        $('#report-table').hide();
        $('#report-summary').addClass('d-none');
        $('#report-loading').addClass('d-none');
        $('#pdf-progress-container').addClass('d-none');
    }
    
    function hideDataProgress() {
        isGeneratingReport = false;
        $('#generate_report').prop('disabled', false).html('<i class="bi bi-file-earmark-text me-1"></i> Générer');
        $('#data-progress-container').addClass('d-none');
        $('#report-table').show();
    }
    
    function updateDataProgressBar(percentage, message = null) {
        percentage = Math.min(100, Math.max(0, percentage));
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
                    updateDataProgressBar(progress, 'Traitement en cours...');
                }
            }
        }, 400);
        
        return interval;
    }
    
    // Progression pour l'export PDF
    function showPdfLoading() {
        isGeneratingPDF = true;
        $('#export_pdf').prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> Génération PDF...');
        
        $('#pdf-progress-container').removeClass('d-none');
        updatePdfProgressBar(0);
        
        // Cacher les autres éléments
        $('#data-progress-container').addClass('d-none');
        $('#report-loading').addClass('d-none');
    }
    
    function hidePdfLoading() {
        isGeneratingPDF = false;
        $('#export_pdf').prop('disabled', false).html('<i class="bi bi-file-pdf me-1"></i> PDF');
        $('#pdf-progress-container').addClass('d-none');
    }
    
    function updatePdfProgressBar(percentage, message = null) {
        percentage = Math.min(100, Math.max(0, percentage));
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
            { progress: 15, message: 'Préparation des données...' },
            { progress: 30, message: 'Génération du tableau...' },
            { progress: 50, message: 'Ajout du résumé statistique...' },
            { progress: 70, message: 'Formatage du document...' },
            { progress: 90, message: 'Finalisation du PDF...' }
        ];
        var stepIndex = 0;
        
        var interval = setInterval(function() {
            if (stepIndex < steps.length && progress < steps[stepIndex].progress) {
                progress = steps[stepIndex].progress;
                updatePdfProgressBar(progress, steps[stepIndex].message);
                stepIndex++;
            } else {
                progress += Math.random() * 8;
                if (progress >= 100) {
                    progress = 100;
                    updatePdfProgressBar(progress, 'PDF prêt au téléchargement !');
                    clearInterval(interval);
                    if (callback) setTimeout(callback, 500);
                } else {
                    updatePdfProgressBar(progress, 'Génération en cours...');
                }
            }
        }, 300);
        
        return interval;
    }
    
    // ========== FONCTIONS UTILITAIRES ==========
    
    function showReportLoading(message) {
        $('#report-loading-message').text(message || 'Chargement des données en cours...');
        $('#report-loading').removeClass('d-none');
    }
    
    function hideReportLoading() {
        $('#report-loading').addClass('d-none');
    }
    
    function showSweetAlert(icon, title, text, showConfirm = false) {
        if (showConfirm) {
            return Swal.fire({
                icon: icon,
                title: title,
                html: text,
                showCancelButton: true,
                confirmButtonText: 'Oui, continuer',
                cancelButtonText: 'Annuler'
            });
        } else {
            Swal.fire({
                icon: icon,
                title: title,
                html: text,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    }
    
    function showErrorAlert(title, message) {
        Swal.fire({
            icon: 'error',
            title: title,
            text: message,
            confirmButtonColor: '#d33'
        });
    }
    
    // Mettre à jour le résumé avec les données totales
    function updateReportSummary(data) {
        if (!data || data.length === 0) {
            $('#report-summary').addClass('d-none');
            return;
        }
        
        var present = 0;
        var presentLate = 0;
        var absent = 0;
        var leave = 0;
        var permission = 0;
        var days = new Set();
        
        data.forEach(function(row) {
            days.add(row.date);
            
            if (row.status === 'present') {
                if (row.is_late || row.late_minutes > 0) {
                    presentLate++;
                } else {
                    present++;
                }
            } else {
                switch(row.status) {
                    case 'absent': absent++; break;
                    case 'leave': leave++; break;
                    case 'permission': permission++; break;
                }
            }
        });
        
        // Stocker les totaux pour utilisation ultérieure
        totalSummaryData = {
            present: present,
            presentLate: presentLate,
            absent: absent,
            leave: leave,
            permission: permission,
            days: days.size
        };
        
        $('#total-days').text(days.size);
        $('#present-count').text(present);
        $('#absent-count').text(absent);
        $('#late-count').text(presentLate);
        $('#leave-count').text(leave);
        $('#permission-count').text(permission);
        
        $('#report-summary').removeClass('d-none');
    }

    // Formater les heures
    function formatHours(hours) {
        if (!hours && hours !== 0) return '-';
        if (hours === 0) return '0h';
        
        var h = Math.floor(hours);
        var m = Math.round((hours - h) * 60);
        
        if (m === 0) {
            return h + 'h';
        } else {
            return h + 'h ' + m + 'min';
        }
    }

    // Formater les minutes
    function formatMinutes(minutes) {
        if (minutes === null || minutes === undefined) return '-';
        if (minutes === 0) return '0';
        return minutes;
    }
    
    // Valider les dates avant export
    function validateDatesForExport() {
        var startDate = $('#report_start_date').val();
        var endDate = $('#report_end_date').val();
        
        if (!startDate || !endDate) {
            showSweetAlert('error', 'Erreur', 'Veuillez sélectionner une période.');
            return false;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            showSweetAlert('error', 'Erreur', 'La date de début ne peut pas être après la date de fin.');
            return false;
        }
        
        var daysDiff = Math.ceil((new Date(endDate) - new Date(startDate)) / (1000 * 60 * 60 * 24)) + 1;
        if (daysDiff > 31) {
            showSweetAlert('warning', 'Attention', 
                'La période ne doit pas dépasser 31 jours. ' +
                'Période sélectionnée: ' + daysDiff + ' jours.');
            return false;
        }
        
        return true;
    }
    
    // ========== INITIALISATION DATATABLE ==========
    
    function initReportTable() {
        reportTable = $('#report-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.reports.data') }}",
                type: 'GET',
                data: function(d) {
                    return {
                        start_date: $('#report_start_date').val(),
                        end_date: $('#report_end_date').val(),
                        emp_code: $('#report_emp_code').val(),
                        draw: d.draw,
                        start: d.start,
                        length: d.length,
                        order: d.order,
                        columns: d.columns,
                        search: d.search
                    };
                },
                beforeSend: function() {
                    // Ne pas afficher le chargement si la barre de progression est déjà affichée
                    if (!$('#data-progress-container').hasClass('d-none')) {
                        // La barre de progression est déjà affichée
                    } else {
                        showReportLoading('Chargement des données...');
                    }
                },
                complete: function() {
                    hideReportLoading();
                },
                error: function(xhr) {
                    hideReportLoading();
                    var errorMsg = xhr.responseJSON?.error || 'Erreur de chargement des données';
                    showSweetAlert('error', 'Erreur', errorMsg);
                },
                dataSrc: function(json) {
                    if (json.error) {
                        showSweetAlert('error', 'Erreur', json.error);
                        return [];
                    }
                    
                    // Résumé toujours global: on utilise uniquement le summary backend
                    if (json.summary) {
                        updateReportSummaryFromBackend(json.summary);
                    } else {
                        // Ne pas recalculer sur la page paginée
                        $('#report-summary').addClass('d-none');
                    }

                    return Array.isArray(json.data) ? json.data : [];
                }
            },
            columns: [
                { 
                    data: 'date',
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString('fr-FR') : '-';
                    }
                },
                { 
                    data: 'employee_code',
                    render: function(data) {
                        return data || '-';
                    }
                },
                { 
                    data: 'employee_name',
                    render: function(data) {
                        return data || '-';
                    }
                },
                { 
                    data: null,
                    render: function(data) {
                        if (data.schedule_start === '-' && data.schedule_end === '-') {
                            return 'Non planifié';
                        } else {
                            return data.schedule_start + ' - ' + data.schedule_end;
                        }
                    }
                },
                { 
                    data: 'actual_arrival',
                    render: function(data) {
                        return data || '-';
                    }
                },
                { 
                    data: 'actual_departure',
                    render: function(data) {
                        return data || '-';
                    }
                },
                { 
                    data: 'late_minutes',
                    render: function(data, type, row) {
                        if (!row.actual_arrival && !row.actual_departure) return '-';
                        if (data === null || data === undefined) return '-';
                        
                        if (data === 0) {
                            return '<span class="text-success">À l\'heure</span>';
                        }
                        
                        return '<span class="text-danger">' + formatMinutes(data) + '</span>';
                    }
                },
                { 
                    data: 'early_leave_minutes',
                    render: function(data, type, row) {
                        if (!row.actual_arrival && !row.actual_departure) return '-';
                        if (data === null || data === undefined) return '-';
                        
                        if (data === 0) {
                            return '<span class="text-success">Normal</span>';
                        }
                        
                        return '<span class="text-warning">' + formatMinutes(data) + ' Minutes</span>';
                    }
                },
                { 
    data: 'overtime_minutes',
    render: function(data, type, row) {
        if (!row.actual_departure) return '-';
        if (data === null || data === undefined) return '-';
        if (data === 0) return '-';
        return '<span class="text-primary fw-bold">' + data + ' min</span>';
    }
},
                { 
                    data: 'work_hours',
                    render: function(data) {
                        return formatHours(data);
                    }
                },
                { 
                    data: 'status',
                    render: function(data, type, row) {
                        var badgeClass = 'secondary';
                        var text = data;
                        
                        switch(data) {
                            case 'present':
                                if (row.is_late || row.late_minutes > 0) {
                                    badgeClass = 'warning';
                                    text = 'Retard';
                                } else {
                                    badgeClass = 'success';
                                    text = 'Présent';
                                }
                                break;
                            case 'absent':
                                badgeClass = 'danger';
                                text = 'Absent';
                                break;
                            case 'leave':
                                badgeClass = 'info';
                                text = 'Congé';
                                break;
                            case 'permission':
                                badgeClass = 'warning';
                                text = 'Permission';
                                break;
                            case 'weekend':
                                text = 'Weekend';
                                badgeClass = 'secondary';
                                break;
                            case 'holiday':
                                badgeClass = 'success';
                                text = 'Férié';
                                break;
                            case 'day_off':
                                badgeClass = 'secondary';
                                text = 'Repos';
                                break;
                            case 'no_schedule':
                                text = 'Non planifié';
                                badgeClass = 'light text-dark';
                                break;
                            case 'mission':
                                badgeClass = 'primary';
                                text = 'Mission';
                                break;
                        }
                        
                        return '<span class="badge bg-' + badgeClass + '">' + text + '</span>';
                    }
                },
                { 
                    data: null,
                    render: function(data) {
                        var observations = [];
                        
                        if (data.is_weekend) observations.push('Weekend');
                        if (data.is_holiday) observations.push('Férié');
                        if (data.is_on_leave) observations.push('Congé');
                        if (data.has_permission) observations.push('Permission');
                        if (data.is_on_mission) observations.push('Mission');
                        
                        if (data.all_punches && data.all_punches.length > 0) {
                            observations.push(data.all_punches.length + ' pointage(s)');
                        }
                        
                        return observations.join(', ') || '-';
                    }
                }
            ],
            language: {
                url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json"
            },
            pageLength: 25,
            order: [[0, 'desc']],
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="bi bi-file-excel me-1"></i> Excel',
                    className: 'btn btn-success btn-sm',
                    title: 'Rapport Absences Retards',
                    messageTop: function() {
                        var startDate = $('#report_start_date').val();
                        var endDate = $('#report_end_date').val();
                        var empCode = $('#report_emp_code').val();
                        var empName = $('#report_emp_code option:selected').text();
                        
                        return 'Période: ' + startDate + ' au ' + endDate + '\n' +
                               'Employé: ' + empName + '\n' +
                               'Généré le: ' + new Date().toLocaleDateString('fr-FR');
                    },
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer me-1"></i> Imprimer',
                    className: 'btn btn-secondary btn-sm',
                    title: 'Rapport Absences Retards',
                    messageTop: function() {
                        var startDate = $('#report_start_date').val();
                        var endDate = $('#report_end_date').val();
                        var empCode = $('#report_emp_code').val();
                        var empName = $('#report_emp_code option:selected').text();
                        
                        return '<h4>Rapport Absences et Retards</h4>' +
                               '<p>Période: ' + startDate + ' au ' + endDate + '</p>' +
                               '<p>Employé: ' + empName + '</p>' +
                               '<p>Généré le: ' + new Date().toLocaleDateString('fr-FR') + '</p><hr>';
                    },
                    exportOptions: {
                        columns: ':visible',
                        stripHtml: false
                    }
                },
                {
                    extend: 'colvis',
                    text: '<i class="bi bi-eye me-1"></i> Colonnes',
                    className: 'btn btn-info btn-sm'
                }
            ],
            drawCallback: function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });
    }
    
    // Fonction pour mettre à jour le résumé depuis le backend
    function updateReportSummaryFromBackend(summary) {
        if (!summary) return;
        
        totalSummaryData = {
            present: summary.present || 0,
            presentLate: summary.late || 0,
            absent: summary.absent || 0,
            leave: summary.leave || 0,
            permission: summary.permission || 0,
            days: summary.total_days || 0
        };
        
        $('#total-days').text(summary.total_days || 0);
        $('#present-count').text(summary.present || 0);
        $('#absent-count').text(summary.absent || 0);
        $('#late-count').text(summary.late || 0);
        $('#leave-count').text(summary.leave || 0);
        $('#permission-count').text(summary.permission || 0);
        
        $('#report-summary').removeClass('d-none');
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
        
        var daysDiff = Math.ceil((new Date(endDate) - new Date(startDate)) / (1000 * 60 * 60 * 24)) + 1;
        var details = 'Analyse de ' + daysDiff + ' jours (du ' + startDate + ' au ' + endDate + ')';
        
        showDataProgress('Génération du rapport en cours...', details);
        
        // Simulation des étapes de progression
        var steps = [
            { progress: 10, message: 'Initialisation de la requête...' },
            { progress: 25, message: 'Récupération des données de présence...' },
            { progress: 40, message: 'Analyse des pointages...' },
            { progress: 55, message: 'Calcul des retards...' },
            { progress: 70, message: 'Génération des statistiques...' },
            { progress: 85, message: 'Préparation du tableau...' }
        ];
        
        var progressInterval = simulateDataProgress(steps);
        
        // Recharger les données
        reportTable.ajax.reload(function(json) {
            clearInterval(progressInterval);
            updateDataProgressBar(100, 'Rapport généré avec succès !');
            
            setTimeout(function() {
                hideDataProgress();
                showSweetAlert('success', 'Succès', 'Rapport généré avec succès.');
            }, 800);
        });
    }
    
    // ========== EXPORT PDF AVEC PROGRESSION ==========
    
    function exportToPdf() {
        if (!validateDatesForExport()) return;
        
        if (isGeneratingPDF) {
            showSweetAlert('info', 'Opération en cours', 'Un export PDF est déjà en cours. Veuillez patienter.');
            return;
        }
        
        var startDate = $('#report_start_date').val();
        var endDate = $('#report_end_date').val();
        var empCode = $('#report_emp_code').val();
        var empName = $('#report_emp_code option:selected').text();
        
        var message = '<div class="text-start">' +
                     '<p><strong>Confirmer l\'export PDF ?</strong></p>' +
                     '<p><i class="bi bi-calendar me-1"></i> <strong>Période :</strong> ' + startDate + ' au ' + endDate + '</p>' +
                     '<p><i class="bi bi-person me-1"></i> <strong>Employé :</strong> ' + empName + '</p>' +
                     '<p class="small text-muted mt-2"><i class="bi bi-info-circle me-1"></i> Cette opération peut prendre quelques secondes.</p>' +
                     '</div>';
        
        showSweetAlert('question', 'Exporter en PDF', message, true).then((result) => {
            if (result.isConfirmed) {
                showPdfLoading();
                var progressInterval = simulatePdfProgress();
                
                $('#pdf_start_date').val(startDate);
                $('#pdf_end_date').val(endDate);
                $('#pdf_emp_code').val(empCode);
                
                $('#exportPdfForm').submit();
                
                // Cacher la progression après un délai (le formulaire va rediriger)
                setTimeout(function() {
                    clearInterval(progressInterval);
                    updatePdfProgressBar(100, 'PDF généré avec succès !');
                    
                    setTimeout(function() {
                        hidePdfLoading();
                    }, 1000);
                }, 3000);
            }
        });
    }
    
    // ========== ÉVÉNEMENTS ==========
    
    // Initialisation
    initReportTable();
    
    // Générer le rapport (avec barre de progression)
    $('#generate_report').on('click', function() {
        generateReport();
    });
    
    // Exporter en PDF (avec barre de progression)
    $('#export_pdf').on('click', function() {
        exportToPdf();
    });
    
    // Exporter en Excel (via DataTables)
    $('#export_excel').on('click', function() {
        $('.buttons-excel').click();
    });
    
    // Prévisualiser PDF
    $('#preview_pdf').on('click', function() {
        if (!validateDatesForExport()) return;
        
        var startDate = $('#report_start_date').val();
        var endDate = $('#report_end_date').val();
        var empCode = $('#report_emp_code').val();
        
        var url = "{{ route('admin.reports.preview.pdf') }}";
        var params = new URLSearchParams({
            start_date: startDate,
            end_date: endDate,
            emp_code: empCode,
            _token: "{{ csrf_token() }}"
        });
        
        window.open(url + '?' + params.toString(), '_blank');
    });
    
    // SUPPRIMÉ : Génération automatique lors du changement des filtres
    // Les filtres ne déclenchent plus la génération automatique
    
    // Initialiser les tooltips
    $(function () {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
    
    // Génération automatique UNIQUEMENT au chargement de la page
    $(window).on('load', function() {
        setTimeout(function() {
            generateReport();
        }, 500);
    });
});
</script>
<style>
    .card-header { background-color: #f8f9fa; }
    .table th { background-color: #f8f9fa; font-weight: 600; }
    .btn-group .btn { border-radius: 0.375rem !important; }
    
    .btn-export {
        min-width: 120px;
    }
    
    @media (max-width: 768px) {
        .d-flex.flex-wrap {
            flex-direction: column;
        }
        .d-flex.flex-wrap .btn {
            width: 100%;
            margin-bottom: 5px;
        }
        .btn-group { flex-direction: column; gap: 5px; }
    }
    
    #report-table th {
        white-space: nowrap;
        background-color: #f1f5f9;
    }
    
    #report-table td {
        vertical-align: middle;
    }
    
    .badge {
        font-size: 0.8em;
        font-weight: 500;
    }
    
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.85rem;
        }
        
        #report-table th,
        #report-table td {
            padding: 0.5rem;
        }
    }
    
    #report-table td:empty::before {
        content: "-";
        color: #6c757d;
    }
    
    .hour-cell {
        font-family: 'Courier New', monospace;
        font-weight: 600;
    }
    
    #report-summary .border {
        transition: all 0.3s ease;
    }
    
    #report-summary .border:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    /* Couleurs des badges */
    .bg-success { background-color: #198754 !important; }
    .bg-danger { background-color: #dc3545 !important; }
    .bg-warning { background-color: #ffc107 !important; color: #000 !important; }
    .bg-info { background-color: #0dcaf0 !important; color: #000 !important; }
    .bg-secondary { background-color: #6c757d !important; }
    .bg-light { background-color: #f8f9fa !important; }
</style>
@endsection
