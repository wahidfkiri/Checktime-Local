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
                        <p class="text-subtitle text-muted">Analyse des présences comparées aux plannings</p>
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
                                        <form id="exportPdfForm" action="{{ route('reports.export.pdf') }}" method="POST" style="display: none;">
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
                                                           value="{{ date('Y-m-d', strtotime('-2 days')) }}">
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
                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <div class="alert alert-info alert-sm p-2 mb-0">
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    Le rapport compare les pointages réels avec les plannings prévus.
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
                                            <div class="col-md-2">
                                                <div class="border rounded p-2">
                                                    <h5 class="mb-0" id="total-days">0</h5>
                                                    <small class="text-muted">Jours analysés</small>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="border rounded p-2">
                                                    <h5 class="mb-0 text-success" id="present-count">0</h5>
                                                    <small class="text-muted">Présences</small>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="border rounded p-2">
                                                    <h5 class="mb-0 text-danger" id="absent-count">0</h5>
                                                    <small class="text-muted">Absences</small>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="border rounded p-2">
                                                    <h5 class="mb-0 text-warning" id="late-count">0</h5>
                                                    <small class="text-muted">Retards</small>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="border rounded p-2">
                                                    <h5 class="mb-0 text-info" id="leave-count">0</h5>
                                                    <small class="text-muted">Congés</small>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="border rounded p-2">
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
                                <strong id="report-loading-message">Génération du rapport en cours...</strong>
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
                                        <th>Retard</th>
                                        <th>Départ anticipé</th>
                                        <th>Temps passé au poste</th>
                                        <th>Statut</th>
                                        <th>Observations</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
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
        $('#report-loading-message').text(message);
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
    
    // Mise à jour du résumé avec les données totales
    function updateReportSummary(data) {
        if (!data || data.length === 0) {
            $('#report-summary').addClass('d-none');
            return;
        }
        
        var present = 0, presentLate = 0, absent = 0, leave = 0, permission = 0;
        var days = new Set();
        
        data.forEach(function(row) {
            days.add(row.date);
            
            if (row.status === 'present') {
                if (row.late_minutes > 0) {
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
    
    // Fonction pour formater la date
    function formatDate(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
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
                'La période ne doit pas dépasser 31 jours pour des performances optimales. ' +
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
                url: "{{ route('reports.data') }}",
                type: 'GET',
                data: function(d) {
                    // Ajouter les paramètres pour le backend
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
                        showReportLoading('Analyse des données en cours...');
                    }
                },
                complete: function() {
                    hideReportLoading();
                },
                error: function(xhr) {
                    hideReportLoading();
                    var errorMsg = xhr.responseJSON?.error || 'Erreur de génération du rapport';
                    showSweetAlert('error', 'Erreur', errorMsg);
                },
                dataSrc: function(json) {
                    // Vérifier si le backend retourne des erreurs
                    if (json.error) {
                        showSweetAlert('error', 'Erreur', json.error);
                        return [];
                    }
                    
                    // S'assurer que les données sont dans le bon format
                    if (json.data) {
                        // Mettre à jour le résumé avec les données totales (recordsTotal)
                        if (json.recordsTotal > 0) {
                            // Si le backend renvoie les totaux, les utiliser
                            if (json.summary) {
                                updateReportSummaryFromBackend(json.summary);
                            } else {
                                // Sinon, utiliser les données de la page (pas idéal)
                                updateReportSummary(json.data);
                            }
                        }
                        return json.data;
                    }
                    
                    return [];
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
                    render: function(data, type, row, meta) {
                        var scheduleText = '';
                        
                        if (data.schedule_start === '-' && data.schedule_end === '-') {
                            scheduleText = 'Non planifié';
                        } else {
                            scheduleText = data.schedule_start + ' - ' + data.schedule_end;
                            
                            if (data.schedule_start !== '-' && data.schedule_end === '-') {
                                scheduleText += ' <span class="badge bg-warning" title="Heure de fin non définie">!</span>';
                            } else if (data.schedule_start === '-' && data.schedule_end !== '-') {
                                scheduleText += ' <span class="badge bg-warning" title="Heure de début non définie">!</span>';
                            }
                        }
                        
                        // Ajouter le type de planning
                        if (data.schedule_type && data.schedule_type !== 'Non planifié') {
                            scheduleText += '<br><small class="text-muted">' + data.schedule_type + '</small>';
                        }
                        
                        return scheduleText;
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
                        // Si pas de pointage du tout
                        if (!row.actual_arrival && !row.actual_departure) {
                            return '-';
                        }
                        
                        // Si pas d'heure de début prévue
                        if (row.schedule_start === '-' || row.schedule_start === 'N/A') {
                            return '<span class="text-muted">N/A</span>';
                        }
                        
                        // Si pas d'arrivée enregistrée
                        if (!row.actual_arrival) {
                            return '<span class="text-muted">N/A</span>';
                        }
                        
                        if (data === null || data === undefined) {
                            return '<span class="text-muted">N/A</span>';
                        }
                        
                        if (data === 0) {
                            return '<span class="text-success">À l\'heure</span>';
                        }
                        
                        var lateText = '';
                        if (data < 60) {
                            lateText = data + ' min';
                        } else {
                            var hours = Math.floor(data / 60);
                            var minutes = data % 60;
                            lateText = hours + 'h' + (minutes > 0 ? minutes + 'min' : '');
                        }
                        
                        return '<span class="text-danger"><i class="bi bi-clock-history me-1"></i>' + lateText + '</span>';
                    }
                },
                { 
                    data: 'early_leave_minutes',
                    render: function(data, type, row) {
                        // Si pas de pointage du tout
                        if (!row.actual_arrival && !row.actual_departure) {
                            return '-';
                        }
                        
                        // Si pas d'heure de fin prévue
                        if (row.schedule_end === '-' || row.schedule_end === 'N/A') {
                            return '<span class="text-muted">N/A</span>';
                        }
                        
                        // Si pas de départ enregistré
                        if (!row.actual_departure) {
                            return '<span class="text-muted">N/A</span>';
                        }
                        
                        if (data === null || data === undefined) {
                            return '<span class="text-muted">N/A</span>';
                        }
                        
                        if (data === 0) {
                            return '<span class="text-success">Heure normale</span>';
                        }
                        
                        var earlyText = '';
                        if (data < 60) {
                            earlyText = data + ' min';
                        } else {
                            var hours = Math.floor(data / 60);
                            var minutes = data % 60;
                            earlyText = hours + 'h' + (minutes > 0 ? minutes + 'min' : '');
                        }
                        
                        return '<span class="text-warning"><i class="bi bi-clock me-1"></i>' + earlyText + '</span>';
                    }
                },
                { 
                    data: 'work_hours',
                    render: function(data) {
                        if (!data || data == 0) return '-';
                        
                        // Convertir en heures/minutes si nécessaire
                        if (data >= 1) {
                            var hours = Math.floor(data);
                            var minutes = Math.round((data - hours) * 60);
                            if (minutes === 0) {
                                return hours + 'h';
                            } else {
                                return hours + 'h ' + minutes + 'min';
                            }
                        } else {
                            // Moins d'une heure
                            var minutes = Math.round(data * 60);
                            return minutes + 'min';
                        }
                    }
                },
                { 
                    data: 'status',
                    render: function(data, type, row) {
                        var badgeClass = 'secondary';
                        var text = data;
                        
                        switch(data) {
                            case 'present':
                                if (row.late_minutes > 0) {
                                    badgeClass = 'warning';
                                    text = 'Présent (Retard)';
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
                                badgeClass = 'light text-dark';
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
                        }
                        
                        var badge = '<span class="badge bg-' + badgeClass + '">' + text + '</span>';
                        
                        // Ajouter un indicateur si planning incomplet
                        if (row.schedule_start === '-' || row.schedule_end === '-') {
                            badge += ' <span class="badge bg-warning" title="Planning incomplet"><i class="bi bi-exclamation-triangle"></i></span>';
                        }
                        
                        return badge;
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
                        if (data.schedule_type && data.schedule_type !== 'Non planifié') {
                            observations.push(data.schedule_type);
                        }
                        
                        // Ajouter les pointages
                        if (data.all_punches && data.all_punches.length > 0) {
                            if (data.all_punches.length === 1) {
                                observations.push('1 pointage');
                            } else {
                                observations.push(data.all_punches.length + ' pointages');
                            }
                        }
                        
                        // Ajouter info sur le retard/départ anticipé
                        if (data.late_minutes > 0) {
                            observations.push('Retard: ' + data.late_minutes + 'min');
                        }
                        if (data.early_leave_minutes > 0) {
                            observations.push('Départ: ' + data.early_leave_minutes + 'min');
                        }
                        
                        return observations.join(', ') || '-';
                    }
                }
            ],
            language: {
                url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json"
            },
            pageLength: 25,
            order: [[0, 'desc']], // Trier par date décroissante par défaut
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
            // Personnaliser le tri pour la colonne statut
            columnDefs: [
                {
                    targets: 9, // Colonne Statut
                    type: 'status-order'
                }
            ],
            // Définir l'ordre de tri pour les statuts
            createdRow: function(row, data, dataIndex) {
                // Ajouter une classe pour le tri
                $(row).attr('data-status', data.status);
                if (data.status === 'present' && data.late_minutes > 0) {
                    $(row).attr('data-status', 'present-late');
                }
            },
            drawCallback: function() {
                // Initialiser les tooltips
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });
        
        // Définir le type de tri pour les statuts
        $.fn.dataTable.ext.type.order['status-order-pre'] = function(data) {
            var order = {
                'present': 1,           // Présent à l'heure
                'present-late': 2,      // Présent avec retard
                'permission': 3,        // Permission
                'leave': 4,             // Congé
                'absent': 5,            // Absent
                'no_schedule': 6,       // Non planifié
                'day_off': 7,           // Repos
                'weekend': 8,           // Weekend
                'holiday': 9            // Férié
            };
            
            return order[data] || 10;
        };
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
            { progress: 55, message: 'Comparaison avec les plannings...' },
            { progress: 70, message: 'Calcul des statistiques...' },
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
                
                // Remplir le formulaire caché
                $('#pdf_start_date').val(startDate);
                $('#pdf_end_date').val(endDate);
                $('#pdf_emp_code').val(empCode);
                
                // Soumettre le formulaire
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
    
    // Prévisualiser PDF dans un nouvel onglet
    $('#preview_pdf').on('click', function() {
        if (!validateDatesForExport()) return;
        
        var startDate = $('#report_start_date').val();
        var endDate = $('#report_end_date').val();
        var empCode = $('#report_emp_code').val();
        
        // Construire l'URL de prévisualisation
        var url = "{{ route('reports.preview.pdf') }}";
        var params = new URLSearchParams({
            start_date: startDate,
            end_date: endDate,
            emp_code: empCode,
            _token: "{{ csrf_token() }}"
        });
        
        // Ouvrir dans un nouvel onglet
        window.open(url + '?' + params.toString(), '_blank');
    });
    
    // SUPPRIMÉ : Génération automatique lors du changement des filtres
    // Les filtres ne déclenchent plus la génération automatique
    
    // Initialiser les tooltips Bootstrap
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
    
    /* Styles pour les boutons d'export */
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
    
    /* Badges de statut */
    .badge-present { background-color: #198754 !important; }
    .badge-absent { background-color: #dc3545 !important; }
    .badge-late { background-color: #fd7e14 !important; }
    .badge-permission { background-color: #ffc107 !important; color: #000 !important; }
    .badge-leave { background-color: #0dcaf0 !important; color: #000 !important; }
    .badge-weekend { background-color: #6c757d !important; }
    .badge-holiday { background-color: #20c997 !important; }
    
    /* Statistiques */
    #report-summary .border {
        transition: all 0.3s ease;
    }
    #report-summary .border:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    /* Table responsive */
    @media (max-width: 1200px) {
        .table-responsive {
            font-size: 0.9rem;
        }
    }
    /* Améliorations supplémentaires */
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

/* Amélioration responsive */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.85rem;
    }
    
    #report-table th,
    #report-table td {
        padding: 0.5rem;
    }
    
    .btn-group {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .btn-group .btn {
        width: 100%;
        margin-bottom: 0.25rem;
    }
}

/* Style pour les cellules vides */
#report-table td:empty::before {
    content: "-";
    color: #6c757d;
}

/* Amélioration de l'affichage des heures */
.hour-cell {
    font-family: 'Courier New', monospace;
    font-weight: 600;
}
</style>
@endsection