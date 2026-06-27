@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Liste des Absences</h3>
                        <p class="text-subtitle text-muted">Historique des absences</p>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('home') }}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{ route('admin.daily-attendance.index') }}">Pointages</a>
                                </li>
                                <li class="breadcrumb-item active">Absences</li>
                            </ol>
                        </nav>
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
                                        <h6 class="card-title">Filtres de consultation</h6>
                                        <div class="row g-3">
                                            <!-- Date début -->
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="filter_start_date" class="form-label">Date début</label>
                                                    <input type="date" class="form-control" id="filter_start_date" 
                                                           value="{{ date('Y-m-d', strtotime('-30 days')) }}">
                                                </div>
                                            </div>
                                            
                                            <!-- Date fin -->
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="filter_end_date" class="form-label">Date fin</label>
                                                    <input type="date" class="form-control" id="filter_end_date" 
                                                           value="{{ date('Y-m-d') }}">
                                                </div>
                                            </div>
                                            
                                            <!-- Département -->
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="filter_department" class="form-label">Département</label>
                                                    <select class="form-control" id="filter_department">
                                                        <option value="all">Tous les départements</option>
                                                        @foreach($departments ?? [] as $department)
                                                            <option value="{{ $department->name ?? $department }}">
                                                                {{ $department->name ?? $department }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <!-- Employé -->
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="filter_emp_code" class="form-label">Employé</label>
                                                    <select class="form-control" id="filter_emp_code">
                                                        <option value="all">Tous les employés</option>
                                                        @foreach($employees ?? [] as $employee)
                                                            <option value="{{ $employee['emp_code'] ?? $employee->emp_code }}">
                                                                {{ $employee['emp_code'] ?? $employee->emp_code }} - {{ $employee['full_name'] ?? ($employee->first_name . ' ' . $employee->last_name) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <!-- Boutons -->
                                            <div class="col-md-4">
                                                <div class="form-group text-start">
                                                    <label class="form-label d-block" style="margin-bottom:0px;">&nbsp;</label>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-primary" id="apply_filters">
                                                            <i class="bi bi-funnel me-1"></i> Appliquer
                                                        </button>
                                                        <button type="button" class="btn btn-outline-primary ms-2" id="today_button">
                                                            <i class="bi bi-calendar-check me-1"></i> Aujourd'hui
                                                        </button>
                                                        <button type="button" class="btn btn-outline-secondary ms-2" id="last_week_button">
                                                            <i class="bi bi-calendar-week me-1"></i> 7 derniers jours
                                                        </button>
                                                        <button type="button" class="btn btn-outline-info ms-2" id="this_month_button">
                                                            <i class="bi bi-calendar-month me-1"></i> Ce mois
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Bouton export -->
                                            <div class="col-md-3 d-none">
                                                <div class="form-group text-end">
                                                    <label class="form-label d-block" style="margin-bottom:0px;">&nbsp;</label>
                                                    <button type="button" id="exportPdfBtn" class="btn btn-danger">
                                                        <i class="fas fa-file-pdf me-1"></i> Exporter PDF
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Info -->
                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <div class="alert alert-info alert-sm p-2 mb-0">
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    Les données sont chargées depuis la base de données. Par défaut: 30 derniers jours.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">

                    <!-- Ajoutez ces deux barres de progression après l'alerte loading-alert (vers ligne 130) -->

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
                        <!-- Alerte chargement -->
                        <div class="alert alert-info alert-dismissible fade show d-none" id="loading-alert" role="alert">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <strong id="loading-message">Chargement des données ...</strong>
                            </div>
                        </div>

                        <!-- Alerte génération PDF -->
                        <div class="alert alert-primary alert-dismissible fade show d-none" id="pdf-loading-alert" role="alert">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <strong id="pdf-loading-message">Génération du PDF en cours...</strong>
                            </div>
                            <p class="mb-0 mt-1">Veuillez patienter, cela peut prendre quelques instants.</p>
                        </div>
                        
                        
                        <!-- Tableau des absences -->
                        <div class="table-responsive">
                            <table id="absences-table" class="table table-striped table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Code</th>
                                        <th>Employé</th>
                                        <th>Département</th>
                                        <th>Jour</th>
                                        <th>Statut</th>
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

<!-- Modal pour le chargement PDF -->
<div class="modal fade" id="pdfLoadingModal" tabindex="-1" aria-labelledby="pdfLoadingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="pdfLoadingModalLabel">
                    <i class="fas fa-file-pdf me-2"></i>Génération du PDF
                </h5>
            </div>
            <div class="modal-body text-center">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <h5 class="mb-2">Génération du rapport en cours</h5>
                <p class="text-muted mb-0" id="pdfModalMessage">
                    Veuillez patienter pendant que nous générons votre rapport PDF...
                </p>
                <div class="progress mt-3" style="height: 8px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         id="pdfProgressBar" style="width: 100%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<!-- Font Awesome pour les icônes PDF -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

// Script complet à remplacer
<script>
$(document).ready(function() {
    // ========== VARIABLES GLOBALES ==========
    var isGeneratingPDF = false;
    var isGeneratingReport = false;
    
    // ========== FONCTIONS DE PROGRESSION ==========
    
    // Progression pour la génération des données
    function showDataProgress(title, details = '') {
        isGeneratingReport = true;
        $('#apply_filters').prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> Traitement...');
        $('#today_button').prop('disabled', true);
        $('#last_week_button').prop('disabled', true);
        $('#this_month_button').prop('disabled', true);
        
        $('#data-progress-title').text(title || 'Génération du rapport en cours...');
        $('#data-progress-details').html('<i class="bi bi-info-circle me-1"></i> ' + details);
        $('#data-progress-container').removeClass('d-none');
        updateDataProgressBar(0);
        
        // Cacher les autres éléments
        $('#absences-table').hide();
        $('#loading-alert').addClass('d-none');
        $('#pdf-progress-container').addClass('d-none');
        $('#pdf-loading-alert').addClass('d-none');
        $('#pdfLoadingModal').modal('hide');
    }
    
    function hideDataProgress() {
        isGeneratingReport = false;
        $('#apply_filters').prop('disabled', false).html('<i class="bi bi-funnel me-1"></i> Appliquer');
        $('#today_button').prop('disabled', false);
        $('#last_week_button').prop('disabled', false);
        $('#this_month_button').prop('disabled', false);
        $('#data-progress-container').addClass('d-none');
        $('#absences-table').show();
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
    function showPdfProgress() {
        isGeneratingPDF = true;
        $('#exportPdfBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Génération...');
        
        $('#pdf-progress-container').removeClass('d-none');
        updatePdfProgressBar(0);
        
        // Cacher les autres éléments
        $('#data-progress-container').addClass('d-none');
        $('#loading-alert').addClass('d-none');
        $('#pdf-loading-alert').addClass('d-none');
        $('#pdfLoadingModal').modal('hide');
    }
    
    function hidePdfProgress() {
        isGeneratingPDF = false;
        $('#exportPdfBtn').prop('disabled', false).html('<i class="fas fa-file-pdf me-1"></i> Exporter PDF');
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
            { progress: 30, message: 'Récupération des absences...' },
            { progress: 50, message: 'Génération du tableau...' },
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
    
    // Obtenir la date d'aujourd'hui
    function getTodayDate() {
        var today = new Date();
        var year = today.getFullYear();
        var month = String(today.getMonth() + 1).padStart(2, '0');
        var day = String(today.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }
    
    // Obtenir la date d'il y a X jours
    function getDateDaysAgo(days) {
        var date = new Date();
        date.setDate(date.getDate() - days);
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }
    
    // Obtenir le premier jour du mois en cours
    function getFirstDayOfMonth() {
        var date = new Date();
        date.setDate(1);
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        return year + '-' + month + '-01';
    }
    
    // Afficher le chargement
    function showLoading(message) {
        $('#loading-message').text(message || 'Chargement...');
        $('#loading-alert').removeClass('d-none');
    }
    
    // Masquer le chargement
    function hideLoading() {
        $('#loading-alert').addClass('d-none');
    }
    
    // Afficher le chargement PDF (ancien modal, conservé pour compatibilité)
    function showPdfLoading(message) {
        $('#pdfModalMessage').text(message || 'Génération du PDF en cours...');
        $('#pdfLoadingModal').modal('show');
    }
    
    // Masquer le chargement PDF
    function hidePdfLoading() {
        $('#pdfLoadingModal').modal('hide');
    }
    
    // Afficher une notification
    function showSweetAlert(icon, title, text, timer = null) {
        Swal.fire({
            icon: icon,
            title: title,
            html: text,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: timer || 5000,
            timerProgressBar: true
        });
    }
    
    // Formater le nombre
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }
    
    // ========== INITIALISATION ==========
    
    var todayDate = getTodayDate();
    var thirtyDaysAgo = getDateDaysAgo(30);
    
    console.log("Initialisation avec dates:", {
        start: thirtyDaysAgo,
        end: todayDate
    });
    
    // ========== DATATABLE CONFIGURATION ==========
    
    var table = $('#absences-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.daily-attendance.absence.data') }}",
            type: 'GET',
            data: function(d) {
                // Récupérer tous les filtres
                var startDate = $('#filter_start_date').val() || thirtyDaysAgo;
                var endDate = $('#filter_end_date').val() || todayDate;
                var empCode = $('#filter_emp_code').val();
                var department = $('#filter_department').val();
                
                // Envoyer les filtres au serveur
                d.start_date = startDate;
                d.end_date = endDate;
                d.emp_code = empCode === 'all' ? '' : empCode;
                d.department = department === 'all' ? '' : department;
                
                console.log("Filtres envoyés:", d);
            },
            beforeSend: function() {
                // Ne pas afficher le chargement si la barre de progression est déjà affichée
                if (!$('#data-progress-container').hasClass('d-none')) {
                    // La barre de progression est déjà affichée
                } else {
                    showLoading('Chargement des absences...');
                }
            },
            complete: function() {
                hideLoading();
            },
            error: function(xhr, error, thrown) {
                hideLoading();
                hideDataProgress();
                console.error('Erreur DataTables:', xhr.responseJSON);
                
                var errorMessage = 'Une erreur est survenue lors du chargement des données';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                }
                
                showSweetAlert('error', 'Erreur', errorMessage);
            },
            dataSrc: function(json) {
                // Mettre à jour le résumé avec les données reçues
                if (json.summary) {
                    $('#total-absences').text(formatNumber(json.summary.total_absences || 0));
                    $('#total-employees').text(formatNumber(json.summary.total_employees || 0));
                    $('#total-working-days').text(formatNumber(json.summary.total_working_days || 0));
                    $('#absence-rate').text((json.summary.absence_rate || 0) + '%');
                }
                
                return json.data;
            }
        },
        columns: [
            { 
                data: 'date_formatted', 
                name: 'date_formatted',
                width: '12%'
            },
            { 
                data: 'emp_code', 
                name: 'emp_code',
                width: '10%'
            },
            { 
                data: 'full_name', 
                name: 'full_name',
                width: '25%',
                render: function(data, type, row) {
                    if (row.employee_found === 'no') {
                        return '<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>' + data + '</span>';
                    }
                    return data;
                }
            },
            { 
                data: 'dept_name', 
                name: 'dept_name',
                width: '15%',
                render: function(data) {
                    return data || '-';
                }
            },
            { 
                data: 'day_name', 
                name: 'day_name',
                width: '10%',
                render: function(data) {
                    return data || '-';
                }
            },
            { 
                data: 'status_label', 
                name: 'status_label',
                width: '13%',
                render: function(data, type, row) {
                    var badgeClass = 'danger';
                    var status = row.status || '';
                    
                    if (status === 'ABSENT_JUSTIFIED') badgeClass = 'warning';
                    else if (status === 'ON_LEAVE') badgeClass = 'info';
                    
                    return '<span class="badge bg-' + badgeClass + '">' + data + '</span>';
                }
            }
        ],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json"
        },
        pageLength: 50,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tous"]],
        order: [[0, 'desc']],
        responsive: true,
        drawCallback: function(settings) {
            // Mettre en évidence les lignes avec employés non trouvés
            $('span.text-warning').closest('tr').addClass('table-warning');
            
            // Afficher le nombre de résultats
            var api = this.api();
            var pageInfo = api.page.info();
            console.log('Affichage ' + pageInfo.recordsDisplay + ' absences');
        }
    });
    
    // ========== GESTION DES FILTRES ==========
    
    // Appliquer les filtres avec barre de progression
    $('#apply_filters').on('click', function() {
        var startDate = $('#filter_start_date').val();
        var endDate = $('#filter_end_date').val();
        
        // Validation des dates
        if (!startDate || !endDate) {
            showSweetAlert('error', 'Erreur', 'Veuillez sélectionner une période valide.');
            return;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            showSweetAlert('error', 'Erreur', 'La date de début ne peut pas être après la date de fin.');
            return;
        }
        
        var daysDiff = Math.ceil((new Date(endDate) - new Date(startDate)) / (1000 * 60 * 60 * 24)) + 1;
        var details = 'Analyse de ' + daysDiff + ' jours (du ' + startDate + ' au ' + endDate + ')';
        
        // Afficher la barre de progression
        showDataProgress('Chargement des absences...', details);
        
        // Simulation des étapes de progression
        var steps = [
            { progress: 10, message: 'Initialisation de la requête...' },
            { progress: 25, message: 'Récupération des absences...' },
            { progress: 40, message: 'Analyse des données...' },
            { progress: 55, message: 'Association avec les employés...' },
            { progress: 70, message: 'Calcul des statistiques...' },
            { progress: 85, message: 'Préparation du tableau...' }
        ];
        
        var progressInterval = simulateDataProgress(steps);
        
        // Recharger le tableau avec les nouveaux filtres
        table.ajax.reload(function() {
            clearInterval(progressInterval);
            updateDataProgressBar(100, 'Données chargées avec succès !');
            
            setTimeout(function() {
                hideDataProgress();
                showSweetAlert('success', 'Succès', 'Données chargées avec succès.', 2000);
            }, 800);
        });
    });
    
    // Bouton Aujourd'hui
    $('#today_button').on('click', function() {
        $('#filter_start_date').val(todayDate);
        $('#filter_end_date').val(todayDate);
        $('#apply_filters').click(); // Déclencher la même fonction que le bouton Appliquer
    });
    
    // Bouton 7 derniers jours
    $('#last_week_button').on('click', function() {
        var lastWeek = getDateDaysAgo(7);
        $('#filter_start_date').val(lastWeek);
        $('#filter_end_date').val(todayDate);
        $('#apply_filters').click(); // Déclencher la même fonction que le bouton Appliquer
    });
    
    // Bouton Ce mois
    $('#this_month_button').on('click', function() {
        var firstDay = getFirstDayOfMonth();
        $('#filter_start_date').val(firstDay);
        $('#filter_end_date').val(todayDate);
        $('#apply_filters').click(); // Déclencher la même fonction que le bouton Appliquer
    });
    
    // ========== EXPORT PDF AVEC PROGRESSION ==========
    
    $('#exportPdfBtn').click(function() {
        var startDate = $('#filter_start_date').val();
        var endDate = $('#filter_end_date').val();
        var empCode = $('#filter_emp_code').val();
        var department = $('#filter_department').val();
        
        // Validation des dates
        if (!startDate || !endDate) {
            showSweetAlert('error', 'Erreur', 'Veuillez sélectionner une période valide pour l\'export.');
            return;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            showSweetAlert('error', 'Erreur', 'La date de début ne peut pas être après la date de fin.');
            return;
        }
        
        // Vérifier si une génération est déjà en cours
        if (isGeneratingPDF) {
            showSweetAlert('info', 'Opération en cours', 'Un export PDF est déjà en cours. Veuillez patienter.');
            return;
        }
        
        // Afficher la barre de progression PDF
        showPdfProgress();
        var progressInterval = simulatePdfProgress();
        
        // Envoyer la requête AJAX
        $.ajax({
            url: "{{ route('admin.daily-attendance.absence.export-pdf') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                start_date: startDate,
                end_date: endDate,
                emp_code: empCode === 'all' ? '' : empCode,
                department: department === 'all' ? '' : department
            },
            success: function(response) {
                clearInterval(progressInterval);
                
                if (response.success) {
                    updatePdfProgressBar(100, 'PDF généré avec succès !');
                    
                    setTimeout(function() {
                        hidePdfProgress();
                        
                        var link = document.createElement('a');
                        link.href = response.pdf_url;
                        link.download = response.filename || 'absences_report.pdf';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        showSweetAlert('success', 'Succès', 
                            'Le PDF a été généré et téléchargé avec succès!', 
                            3000);
                    }, 500);
                } else {
                    clearInterval(progressInterval);
                    hidePdfProgress();
                    showSweetAlert('error', 'Erreur', response.message || 'Erreur lors de la génération du PDF.');
                }
            },
            error: function(xhr, status, error) {
                clearInterval(progressInterval);
                hidePdfProgress();
                
                var errorMessage = 'Une erreur est survenue lors de la génération du PDF.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.statusText) {
                    errorMessage = 'Erreur ' + xhr.status + ': ' + xhr.statusText;
                }
                
                showSweetAlert('error', 'Erreur', errorMessage);
                console.error('Erreur export PDF:', xhr);
            }
        });
    });
    
    // Recharger automatiquement toutes les 5 minutes
    setInterval(function() {
        table.ajax.reload(null, false);
    }, 300000);
    
    // ========== STYLES DYNAMIQUES ==========
    
    // Ajouter les styles CSS dynamiquement
    var dynamicStyles = `
        /* Styles pour les barres de progression */
        #data-progress-container, #pdf-progress-container {
            border-left: 4px solid;
            animation: pulse-alert 1.5s infinite;
            margin-bottom: 1rem;
        }
        
        #data-progress-container {
            border-left-color: #0d6efd;
        }
        
        #pdf-progress-container {
            border-left-color: #0dcaf0;
        }
        
        @keyframes pulse-alert {
            0% { opacity: 0.8; }
            50% { opacity: 1; }
            100% { opacity: 0.8; }
        }
        
        .progress {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .progress-bar {
            transition: width 0.6s ease;
            font-weight: 600;
            line-height: 25px;
        }
        
        /* Style pour les lignes avec avertissement */
        .table-warning {
            background-color: rgba(255, 193, 7, 0.1) !important;
        }
        
        .table-warning:hover {
            background-color: rgba(255, 193, 7, 0.2) !important;
        }
        
        /* Style pour le texte d'avertissement */
        .text-warning {
            color: #ffc107 !important;
            font-weight: 500;
        }
        
        /* Style pour les badges */
        .badge {
            font-size: 0.85em;
            padding: 0.5em 0.75em;
        }
        
        .alert-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .btn-group .btn {
            margin-right: 5px;
            border-radius: 0.375rem !important;
        }
        
        #exportPdfBtn {
            background-color: #dc3545;
            border-color: #dc3545;
            transition: all 0.3s ease;
        }
        
        #exportPdfBtn:hover {
            background-color: #c82333;
            border-color: #bd2130;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        
        #exportPdfBtn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.3rem;
        }
        
        #summary-section .card {
            transition: transform 0.3s ease;
        }
        
        #summary-section .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        #summary-section .display-6 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0;
        }
        
        @media (max-width: 768px) {
            .btn-group {
                flex-wrap: wrap;
                gap: 5px;
            }
            
            .btn-group .btn {
                margin-left: 0 !important;
                margin-right: 5px;
            }
            
            #exportPdfBtn {
                margin-top: 10px;
                width: 100%;
            }
            
            #summary-section .display-6 {
                font-size: 1.5rem;
            }
            
            .progress {
                height: 20px !important;
            }
            
            .progress-bar {
                font-size: 0.8rem;
                line-height: 20px;
            }
        }
    `;
    
    // Injecter les styles dans le head
    $('<style>').text(dynamicStyles).appendTo('head');
});
</script>

<style>
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    
    #loading-alert {
        margin-bottom: 1rem;
    }
    
    .badge {
        font-size: 0.85em;
        padding: 0.5em 0.75em;
    }
    
    .table-warning {
        background-color: rgba(255, 193, 7, 0.1) !important;
    }
    
    .table-warning:hover {
        background-color: rgba(255, 193, 7, 0.2) !important;
    }
    
    .text-warning {
        color: #ffc107 !important;
        font-weight: 500;
    }
    
    .alert-sm {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
    
    .btn-group .btn {
        margin-right: 5px;
        border-radius: 0.375rem !important;
    }
    
    #exportPdfBtn {
        background-color: #dc3545;
        border-color: #dc3545;
        transition: all 0.3s ease;
    }
    
    #exportPdfBtn:hover {
        background-color: #c82333;
        border-color: #bd2130;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }
    
    #exportPdfBtn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .form-label {
        font-weight: 500;
        margin-bottom: 0.3rem;
    }
    
    #summary-section .card {
        transition: transform 0.3s ease;
    }
    
    #summary-section .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    #summary-section .display-6 {
        font-size: 2rem;
        font-weight: 600;
        margin-bottom: 0;
    }
    
    @media (max-width: 768px) {
        .btn-group {
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .btn-group .btn {
            margin-left: 0 !important;
            margin-right: 5px;
        }
        
        #exportPdfBtn {
            margin-top: 10px;
            width: 100%;
        }
        
        #summary-section .display-6 {
            font-size: 1.5rem;
        }
    }
</style>
@endsection