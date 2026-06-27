@extends('layouts.app')

@section('content')
@include('attendance::partials.styles')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Historique des pointages</h3>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('home') }}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item active">Pointage</li>
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
                                                           value="{{ date('Y-m-d') }}">
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
                                            
                                            <!-- Terminal -->
                                           <div class="col-md-3">
    <div class="form-group">
        <label for="filter_terminal_alias" class="form-label">Terminal</label>
        <select class="form-control" id="filter_terminal_alias">
            <option value="all">Tous les terminaux</option>
            @foreach($devices as $device)
                <option value="{{ $device->alias ?: $device->terminal_name }}">
                    {{ $device->alias ?: $device->terminal_name }}
                </option>
            @endforeach
        </select>
    </div>
</div> 
                                           
                                            <!-- Employé -->
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="filter_emp_code" class="form-label">Employé</label>
                                                    <select class="form-control search_utilisateur" id="filter_emp_code">
                                                        <option value="all">Tous les employés</option>
                                                        @foreach($employees as $employee)
                                                            <option value="{{ $employee['emp_code'] }}">
                                                                {{ $employee['emp_code'] }} - {{ $employee['full_name'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <!-- Département - Utilisation de dept_name de la table employees -->
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="filter_department" class="form-label">Département</label>
                                                    <select class="form-control" id="filter_department">
                                                        <option value="all">Tous les départements</option>
                                                        @foreach($departments as $department)
                                                            <option value="{{ $department->name }}">
                                                                {{ $department->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            
                                            <!-- Status -->
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="filter_status" class="form-label">Statut</label>
                                                    <select class="form-control" id="filter_status">
                                                        <option value="all">Tous les statuts</option>
                                                        <option value="present">Présent</option>
                                                        <option value="absent">Absent</option>
                                                    </select>
                                                </div>
                                            </div>
                                                       
                                            <!-- Boutons -->
                                            <div class="col-md-8">
                                                <div class="form-group text-start">
                                                    <label class="form-label d-block" style="margin-bottom:0px;">&nbsp;</label>
                                                    <div class="btn-group d-flex flex-wrap" role="group">
                                                        <button type="button" class="btn btn-primary" id="apply_filters">
                                                            <i class="bi bi-funnel me-1"></i> Appliquer
                                                        </button>
                                                        <button type="button" class="btn btn-outline-primary ms-2" id="today_button">
                                                            <i class="bi bi-calendar-check me-1"></i> Aujourd'hui
                                                        </button>
                                                        <button type="button" id="syncDataBtn" class="btn btn-success ms-2">
                                                            <i class="fas fa-sync-alt me-1"></i> Synchroniser
                                                        </button>
                                                        <button type="button" id="exportPdfBtn" class="btn btn-danger ms-2">
                                                            <i class="fas fa-file-pdf me-1"></i> Exporter PDF
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Info -->
                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <div class="alert alert-info alert-sm p-2 mb-0">
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    Les données sont chargées depuis la base de données. Par défaut: données d'aujourd'hui.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <!-- Résultat chargement initial -->
                        <div id="initial-loading-result" class="mb-3">
                            @if($todayData['success'])
                                <div class="alert alert-success">
                                    <h6>✅ Données chargées avec succès</h6>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <p><strong>Date:</strong> {{ $todayData['date'] }}</p>
                                        </div>
                                        <div class="col-md-2">
                                            <p><strong>Pointages:</strong> {{ $todayData['total_attendances'] }}</p>
                                        </div>
                                        <div class="col-md-2">
                                            <p><strong>Employés identifiés:</strong> {{ $todayData['matched_employees'] }}</p>
                                        </div>
                                        <div class="col-md-2">
                                            <p><strong>Absents:</strong> {{ $todayData['stats']['absent_days'] ?? 0 }}</p>
                                        </div>
                                        <div class="col-md-2">
                                            <p><strong>Retards:</strong> {{ $todayData['stats']['late_days'] ?? 0 }}</p>
                                        </div>
                                        <div class="col-md-2">
                                            <p><strong>Terminaux:</strong> {{ $devices->count() }}</p>
                                        </div>
                                    </div>
                                    
                                    @if(isset($todayData['unmatched_employees']) && $todayData['unmatched_employees'] > 0)
                                        <div class="alert alert-warning mt-2 p-2">
                                            <p class="mb-1">
                                                <i class="bi bi-exclamation-triangle me-1"></i>
                                                <strong>Attention:</strong> {{ $todayData['unmatched_employees'] }} employé(s) non trouvé(s) dans la base
                                            </p>
                                            @if(isset($todayData['unmatched_codes']) && count($todayData['unmatched_codes']) > 0)
                                                <p class="mb-0">
                                                    <small>Codes manquants: 
                                                        @if(count($todayData['unmatched_codes']) <= 10)
                                                            {{ implode(', ', $todayData['unmatched_codes']) }}
                                                        @else
                                                            {{ implode(', ', array_slice($todayData['unmatched_codes'], 0, 10)) }} et {{ count($todayData['unmatched_codes']) - 10 }} autres...
                                                        @endif
                                                    </small>
                                                </p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <h6>⚠️ Aucune donnée trouvée pour aujourd'hui</h6>
                                    <p><strong>Message:</strong> {{ $todayData['message'] }}</p>
                                    <p class="mb-0">Utilisez les filtres ci-dessus pour rechercher d'autres dates ou utilisez le bouton de synchronisation.</p>
                                </div>
                            @endif
                        </div>

                        <!-- Barre de progression pour la synchronisation -->
                        <div class="alert alert-success d-none" id="sync-progress-container" role="alert">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-sync-alt fa-spin me-2"></i>
                                <strong id="sync-progress-title">Synchronisation en cours...</strong>
                            </div>
                            <div class="progress" style="height: 25px;">
                                <div id="sync-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                     role="progressbar" style="width: 0%;" 
                                     aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                            </div>
                            <p class="mb-0 small mt-2 text-muted" id="sync-progress-details">
                                <i class="bi bi-info-circle me-1"></i>
                                Récupération des données depuis l'API externe...
                            </p>
                        </div>

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
                        
                        <!-- Tableau -->
                        <div class="table-responsive">
                            <table id="attendances-table" class="table table-striped table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Code</th>
                                        <th>Employé</th>
                                        <th>Pointages</th>
                                        <th>Département</th>
                                        <th>Terminal</th>
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
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" id="cancelPdfBtn" style="display: none;">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.sumoselect/3.0.2/jquery.sumoselect.min.js"></script>
<script>
    $('.search_utilisateur').SumoSelect({search: true, searchText: 'Sélectionner un employé...'});
$(document).ready(function() {
    // ========== VARIABLES GLOBALES ==========
    var isGeneratingPDF = false;
    var isGeneratingReport = false;
    var isSyncing = false;
    
    // ========== FONCTIONS DE PROGRESSION ==========
    
    // Progression pour la synchronisation
    function showSyncProgress(title, details = '') {
        isSyncing = true;
        $('#syncDataBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Synchronisation...');
        
        $('#sync-progress-title').text(title || 'Synchronisation en cours...');
        $('#sync-progress-details').html('<i class="bi bi-info-circle me-1"></i> ' + details);
        $('#sync-progress-container').removeClass('d-none');
        updateSyncProgressBar(0);
        
        // Cacher les autres éléments
        $('#data-progress-container').addClass('d-none');
        $('#pdf-progress-container').addClass('d-none');
        $('#loading-alert').addClass('d-none');
        $('#pdf-loading-alert').addClass('d-none');
        $('#pdfLoadingModal').modal('hide');
    }
    
    function hideSyncProgress() {
        isSyncing = false;
        $('#syncDataBtn').prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i> Synchroniser');
        $('#sync-progress-container').addClass('d-none');
    }
    
    function updateSyncProgressBar(percentage, message = null) {
        percentage = Math.min(100, Math.max(0, percentage));
        $('#sync-progress-bar')
            .css('width', percentage + '%')
            .attr('aria-valuenow', percentage)
            .text(percentage + '%');
        
        if (message) {
            $('#sync-progress-details').html('<i class="bi bi-info-circle me-1"></i> ' + message);
        }
    }
    
    // Simuler la progression pour la synchronisation
    function simulateSyncProgress(callback) {
        var progress = 0;
        var steps = [
            { progress: 10, message: 'Initialisation de la connexion API...' },
            { progress: 25, message: 'Récupération des transactions...' },
            { progress: 40, message: 'Analyse des pointages...' },
            { progress: 60, message: 'Mise à jour des présences...' },
            { progress: 80, message: 'Calcul des statistiques...' },
            { progress: 95, message: 'Finalisation...' }
        ];
        var stepIndex = 0;
        
        var interval = setInterval(function() {
            if (stepIndex < steps.length && progress < steps[stepIndex].progress) {
                progress = steps[stepIndex].progress;
                updateSyncProgressBar(progress, steps[stepIndex].message);
                stepIndex++;
            } else {
                progress += Math.random() * 5;
                if (progress >= 100) {
                    progress = 100;
                    updateSyncProgressBar(progress, 'Synchronisation terminée !');
                    clearInterval(interval);
                    if (callback) setTimeout(callback, 500);
                } else {
                    updateSyncProgressBar(progress, 'Traitement en cours...');
                }
            }
        }, 400);
        
        return interval;
    }
    
    // Progression pour la génération des données
    function showDataProgress(title, details = '') {
        isGeneratingReport = true;
        $('#apply_filters').prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> Traitement...');
        
        $('#data-progress-title').text(title || 'Génération du rapport en cours...');
        $('#data-progress-details').html('<i class="bi bi-info-circle me-1"></i> ' + details);
        $('#data-progress-container').removeClass('d-none');
        updateDataProgressBar(0);
        
        // Cacher les autres éléments
        $('#sync-progress-container').addClass('d-none');
        $('#pdf-progress-container').addClass('d-none');
        $('#loading-alert').addClass('d-none');
        $('#pdf-loading-alert').addClass('d-none');
        $('#pdfLoadingModal').modal('hide');
    }
    
    function hideDataProgress() {
        isGeneratingReport = false;
        $('#apply_filters').prop('disabled', false).html('<i class="bi bi-funnel me-1"></i> Appliquer');
        $('#data-progress-container').addClass('d-none');
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
        $('#sync-progress-container').addClass('d-none');
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
            { progress: 30, message: 'Récupération des pointages...' },
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
    
    // ========== INITIALISATION ==========
    
    // Obtenir et afficher les dates
    var todayDate = getTodayDate();
    
    // Initialiser les filtres de date (uniquement aujourd'hui)
    $('#filter_start_date').val(todayDate);
    $('#filter_end_date').val(todayDate);
    
    console.log("Initialisation avec dates:", {
        start: todayDate,
        end: todayDate
    });
    
    // ========== DATATABLE CONFIGURATION ==========
    
    var table = $('#attendances-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.daily-attendance.data') }}",
            type: 'GET',
            data: function(d) {
                // Récupérer tous les filtres
                var startDate = $('#filter_start_date').val();
                var endDate = $('#filter_end_date').val();
                var terminalSn    = $('#filter_terminal_sn').val();
                var terminalAlias = $('#filter_terminal_alias').val();
                var empCode = $('#filter_emp_code').val();
                var department = $('#filter_department').val();
                var status = $('#filter_status').val();
                
                // Si les dates sont vides, utiliser aujourd'hui
                if (!startDate || startDate.trim() === '') {
                    startDate = todayDate;
                    $('#filter_start_date').val(todayDate);
                }
                if (!endDate || endDate.trim() === '') {
                    endDate = todayDate;
                    $('#filter_end_date').val(todayDate);
                }
                
                // Envoyer les filtres au serveur
                d.start_date = startDate;
                d.end_date = endDate;
                d.terminal_sn    = terminalSn;
                d.terminal_alias = terminalAlias;
                d.emp_code = empCode;
                d.department = department === 'all' ? '' : department;
                d.status = status === 'all' ? '' : status;
                
                console.log("Filtres envoyés:", {
                    start_date: startDate,
                    end_date: endDate,
                    terminal_sn: terminalSn,
                    emp_code: empCode,
                    department: department,
                    status: status,
                    terminal_alias: terminalAlias,
                });
            },
            beforeSend: function() {
                // Ne pas afficher le chargement si la barre de progression est déjà affichée
                if (!$('#data-progress-container').hasClass('d-none')) {
                    // La barre de progression est déjà affichée
                } else {
                    showLoading('Chargement des données ...');
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
            }
        },
        columns: [
            { 
                data: 'date_formatted',
                name: 'date_formatted',
                width: '10%',
                render: function(data) {
                    return data || '-';
                }
            },
            { 
                data: 'emp_code',
                name: 'emp_code',
                width: '8%',
                render: function(data) {
                    return data || '-';
                }
            },
            { 
                data: 'full_name',
                name: 'full_name',
                width: '15%',
                render: function(data, type, row) {
                    if (!data || data === 'Non enregistré' || row.employee_found === 'no') {
                        return '<span class="text-warning" title="Employé non trouvé dans la base de données"><i class="bi bi-exclamation-triangle me-1"></i>Non enregistré</span>';
                    }
                    return data;
                }
            },
            { 
                data: 'all_punches',
                name: 'all_punches',
                width: '15%',
                render: function(data, type, row) {
                    if (data && data.trim() !== '') {
                        var punches = data.split(', ');
                        
                        var displayText = '';
                        if (punches.length <= 4) {
                            displayText = punches.join(', ');
                        } else {
                            displayText = punches[0] + ', ' + punches[1] + ', ..., ' + 
                                         punches[punches.length - 2] + ', ' + punches[punches.length - 1];
                        }
                        
                        var badge = row.has_multiple_punches ? 
                            '<span class="badge bg-info ms-1">' + row.total_punches + '</span>' : '';
                        
                        return '<span title="' + data + '" data-bs-toggle="tooltip">' + 
                               displayText + badge + '</span>';
                    }
                    return '<span class="text-muted">-</span>';
                }
            },
            { 
                data: 'dept_name',
                name: 'dept_name',
                width: '10%',
                render: function(data) {
                    return data || '-';
                }
            },
            { 
    data: 'terminal_alias',
    name: 'terminal_alias',
    width: '12%',
    render: function(data) {
        if (!data || data === 'Non disponible') {
            return '<span class="text-muted">-</span>';
        }
        return '<span class="badge bg-secondary" title="' + data + '">' + 
               (data.length > 20 ? data.substring(0, 18) + '…' : data) + 
               '</span>';
    }
},
            { 
                data: 'status_label',
                name: 'status_label',
                width: '12%',
                render: function(data, type, row) {
                    var badgeClass = 'success';
                    if (row.status === 'ABSENT') {
                        badgeClass = 'danger';
                    } else if (row.status === 'LATE' || row.status === 'EARLY_LEAVE') {
                        badgeClass = 'warning';
                    } else if (row.status === 'OVERTIME') {
                        badgeClass = 'primary';
                    } else if (row.status === 'HALF_DAY') {
                        badgeClass = 'info';
                    }
                    
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
            console.log('Affichage ' + pageInfo.recordsDisplay + ' enregistrements sur ' + pageInfo.recordsTotal);
        },
        initComplete: function() {
            console.log('DataTable initialisé avec succès');
            
            // Masquer le message initial après 3 secondes
            setTimeout(function() {
                $('#initial-loading-result').fadeOut('slow', function() {
                    $(this).addClass('d-none');
                });
            }, 3000);
        }
    });
    
    // ========== GESTION DES FILTRES ==========
    
    // Appliquer les filtres avec barre de progression
    $('#apply_filters').on('click', function() {
        if (isSyncing) {
            showSweetAlert('info', 'Info', 'Une synchronisation est en cours. Veuillez patienter.');
            return;
        }
        
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
        showDataProgress('Chargement des pointages...', details);
        
        // Simulation des étapes de progression
        var steps = [
            { progress: 10, message: 'Initialisation de la requête...' },
            { progress: 25, message: 'Récupération des pointages...' },
            { progress: 40, message: 'Analyse des données...' },
            { progress: 55, message: 'Association avec les employés...' },
            { progress: 70, message: 'Calcul des statuts...' },
            { progress: 85, message: 'Préparation du tableau...' }
        ];
        
        var progressInterval = simulateDataProgress(steps);
        
        // Recharger le tableau
        table.ajax.reload(function() {
            clearInterval(progressInterval);
            updateDataProgressBar(100, 'Données chargées avec succès !');
            
            setTimeout(function() {
                hideDataProgress();
                showSweetAlert('success', 'Succès', 'Données chargées avec succès.', 2000);
            }, 800);
        });
    });
    
    // Appliquer automatiquement les filtres quand on change les valeurs
    $('#filter_start_date, #filter_end_date, #filter_terminal_sn, #filter_terminal_alias, #filter_emp_code, #filter_department, #filter_status').on('change', function() {        
        $('#apply_filters').click();
    });

    // ========== SYNCHRONISATION DES DONNÉES ==========
    
    $('#syncDataBtn').on('click', function() {
        if (isGeneratingPDF || isGeneratingReport) {
            showSweetAlert('info', 'Info', 'Une opération est déjà en cours. Veuillez patienter.');
            return;
        }
        
        // Demander confirmation à l'utilisateur
        Swal.fire({
            title: 'Synchroniser les pointages?',
            html: '<p class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i> Cela peut prendre quelques instants.</p>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-sync-alt me-1"></i> Synchroniser',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                // Afficher la barre de progression
                showSyncProgress('Synchronisation en cours...', 'Récupération des données depuis l\'API...');
                
                var progressInterval = simulateSyncProgress();
                
                // Envoyer la requête AJAX
                $.ajax({
                    url: "{{ route('admin.daily-attendance.sync.data') }}",
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        days_back: 7 // Synchroniser les 7 derniers jours par défaut
                    },
                    success: function(response) {
                        clearInterval(progressInterval);
                        
                        if (response.success) {
                            updateSyncProgressBar(100, 'Synchronisation terminée !');
                            
                            setTimeout(function() {
                                hideSyncProgress();
                                
                                // Afficher les résultats
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Synchronisation réussie!',
                                    html: response.message + '<br>' +
                                          'Présents: ' + response.stats.present + '<br>' +
                                          'Absents: ' + response.stats.absent + '<br>' +
                                          'Total employés: ' + response.stats.total_employees,
                                    timer: 5000,
                                    showConfirmButton: true
                                });
                                
                                // Recharger les données
                                $('#apply_filters').click();
                            }, 500);
                        } else {
                            clearInterval(progressInterval);
                            hideSyncProgress();
                            showSweetAlert('error', 'Erreur', response.message || 'Erreur lors de la synchronisation.');
                        }
                    },
                    error: function(xhr, status, error) {
                        clearInterval(progressInterval);
                        hideSyncProgress();
                        
                        var errorMessage = 'Une erreur est survenue lors de la synchronisation.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        showSweetAlert('error', 'Erreur', errorMessage);
                        console.error('Erreur synchronisation:', xhr);
                    }
                });
            }
        });
    });
    
    // ========== EXPORT PDF AVEC PROGRESSION ==========
    
    // Bouton d'export PDF
    $('#exportPdfBtn').click(function() {
        if (isSyncing || isGeneratingReport) {
            showSweetAlert('info', 'Info', 'Une synchronisation est en cours. Veuillez patienter.');
            return;
        }
        
        // Récupérer les valeurs des filtres
        var startDate = $('#filter_start_date').val();
        var endDate = $('#filter_end_date').val();
        var terminalSn = $('#filter_terminal_sn').val();
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
        
        // Demander confirmation
        Swal.fire({
            title: 'Exporter en PDF?',
            html: '<p>Période: ' + startDate + ' au ' + endDate + '</p>' +
                  '<p class="text-info"><i class="fas fa-info-circle me-1"></i> Cette opération peut prendre quelques instants.</p>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-file-pdf me-1"></i> Exporter PDF',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                // Afficher la barre de progression PDF
                showPdfProgress();
                var progressInterval = simulatePdfProgress();
                
                // Envoyer la requête AJAX
                $.ajax({
                    url: "{{ route('daily-attendance.export-pdf') }}",
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        start_date: startDate,
                        end_date: endDate,
                        terminal_sn: terminalSn,
                        emp_code: empCode,
                        department: department === 'all' ? '' : department
                    },
                    success: function(response) {
                        clearInterval(progressInterval);
                        
                        if (response.success) {
                            updatePdfProgressBar(100, 'PDF généré avec succès !');
                            
                            setTimeout(function() {
                                hidePdfProgress();
                                
                                // Télécharger le fichier
                                var link = document.createElement('a');
                                link.href = response.pdf_url;
                                link.download = response.filename || 'presences_report.pdf';
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
            }
        });
    });
    
    // Annuler la génération PDF
    $('#cancelPdfBtn').click(function() {
        hidePdfProgress();
        showSweetAlert('info', 'Annulé', 'Génération du PDF annulée.');
    });
    
    // ========== BOUTON AUJOURD'HUI ==========
    
    $('#today_button').on('click', function() {
        // Réinitialiser tous les filtres
        $('#filter_start_date').val(todayDate);
        $('#filter_end_date').val(todayDate);
        $('#filter_terminal_sn').val('all');
        $('#filter_emp_code').val('all');
        $('#filter_department').val('all');
        $('#filter_status').val('all');
        
        // Recharger le tableau avec barre de progression
        $('#apply_filters').click();
        
        // Notification
        showSweetAlert('success', 'Succès', 'Filtres réinitialisés à aujourd\'hui', 2000);
    });
    
    // ========== STYLES DYNAMIQUES ==========
    
    // Ajouter les styles CSS dynamiquement
    var dynamicStyles = `
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
            font-size: 0.75em;
            padding: 0.35em 0.65em;
        }
        
        /* Styles pour les barres de progression */
        #sync-progress-container, #data-progress-container, #pdf-progress-container {
            border-left: 4px solid;
            animation: pulse-alert 1.5s infinite;
            margin-bottom: 1rem;
        }
        
        #sync-progress-container {
            border-left-color: #28a745;
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .btn-group {
                flex-direction: column;
                gap: 5px;
            }
            
            #today_button, #syncDataBtn, #exportPdfBtn {
                margin-left: 0 !important;
                margin-top: 5px;
            }
            
            .dataTables_wrapper {
                font-size: 0.9rem;
            }
            
            .progress {
                height: 20px !important;
            }
            
            .progress-bar {
                font-size: 0.8rem;
                line-height: 20px;
            }
        }
        
        /* Styles pour les boutons */
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
        
        #syncDataBtn {
            background-color: #28a745;
            border-color: #28a745;
            transition: all 0.3s ease;
        }
        
        #syncDataBtn:hover {
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        #syncDataBtn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Styles pour les tooltips */
        [data-bs-toggle="tooltip"] {
            cursor: help;
            border-bottom: 1px dotted #666;
        }
    `;
    
    // Injecter les styles dans le head
    $('<style>').text(dynamicStyles).appendTo('head');
    
    // Initialiser les tooltips Bootstrap
    $(function () {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
    
    // Mettre à jour les tooltips quand le tableau est redessiné
    table.on('draw', function () {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
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
    
    .alert-sm {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
    
    .text-subtitle {
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    #initial-loading-result {
        transition: opacity 0.5s ease;
    }
    
    select.form-control option {
        padding: 8px;
    }
    
    .form-label {
        font-weight: 500;
        margin-bottom: 0.3rem;
    }
    
    .btn-group .btn {
        border-radius: 0.375rem !important;
    }
    
    /* Animation pour le spinner */
    @keyframes pulse {
        0% { opacity: 0.6; }
        50% { opacity: 1; }
        100% { opacity: 0.6; }
    }
    
    .fa-spinner {
        animation: pulse 1.5s infinite;
    }
</style>
@endsection