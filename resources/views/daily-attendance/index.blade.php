@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Historique des pointages</h3>
                        <!-- <p class="text-subtitle text-muted">Données chargées directement depuis l'API</p> -->
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
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="filter_start_date" class="form-label">Date début</label>
                                                    <input type="date" class="form-control" id="filter_start_date" 
                                                           value="{{ date('Y-m-d') }}">
                                                </div>
                                            </div>
                                            
                                            <!-- Date fin - Modifié pour ajouter 1 jour -->
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="filter_end_date" class="form-label">Date fin</label>
                                                    <input type="date" class="form-control" id="filter_end_date" 
                                                           value="{{ date('Y-m-d', strtotime('+1 day')) }}">
                                                </div>
                                            </div>
                                            
                                            <!-- Terminal -->
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="filter_terminal_sn" class="form-label">Terminal</label>
                                                    <select class="form-control" id="filter_terminal_sn">
                                                        <option value="all">Tous les terminaux</option>
                                                        @foreach($devices as $device)
                                                            <option value="{{ $device->device_sn }}">
                                                                {{ $device->terminal_name ?: $device->device_sn }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <!-- Employé -->
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="filter_emp_code" class="form-label">Employé</label>
                                                    <select class="form-control" id="filter_emp_code">
                                                        <option value="all">Tous les employés</option>
                                                        @foreach($employees as $employee)
                                                            <option value="{{ $employee['emp_code'] }}">
                                                                {{ $employee['emp_code'] }} - {{ $employee['full_name'] }}
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
                                                  Les données sont chargées directement depuis le Terminal Biométrique. Par défaut: données d'aujourd'hui.
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
                                        <div class="col-md-3">
                                            <p><strong>Date:</strong> {{ $todayData['date'] }}</p>
                                        </div>
                                        <div class="col-md-3">
                                            <p><strong>Pointages:</strong> {{ $todayData['total_attendances'] }}</p>
                                        </div>
                                        <div class="col-md-3">
                                            <p><strong>Employés identifiés:</strong> {{ $todayData['matched_employees'] }}</p>
                                        </div>
                                        <div class="col-md-3">
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
                                    <p class="mb-0">Utilisez les filtres ci-dessus pour rechercher d'autres dates ou terminaux.</p>
                                </div>
                            @endif
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
                                        <th>Durée</th>
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

<script>
$(document).ready(function() {
    // ========== FONCTIONS UTILITAIRES ==========
    
    // Obtenir la date d'aujourd'hui
    function getTodayDate() {
        var today = new Date();
        var year = today.getFullYear();
        var month = String(today.getMonth() + 1).padStart(2, '0');
        var day = String(today.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }
    
    // Obtenir la date de demain (aujourd'hui + 1 jour)
    function getTomorrowDate() {
        var tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        var year = tomorrow.getFullYear();
        var month = String(tomorrow.getMonth() + 1).padStart(2, '0');
        var day = String(tomorrow.getDate()).padStart(2, '0');
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
    
    // Afficher le chargement PDF
    function showPdfLoading(message) {
        $('#pdfModalMessage').text(message || 'Génération du PDF en cours...');
        $('#pdfLoadingModal').modal('show');
    }
    
    // Masquer le chargement PDF
    function hidePdfLoading() {
        $('#pdfLoadingModal').modal('hide');
    }
    
    // Mettre à jour l'heure de la dernière requête
    function updateLastRequestTime() {
        var now = new Date();
        var timeString = now.toLocaleTimeString('fr-FR', { 
            hour: '2-digit', 
            minute: '2-digit',
            second: '2-digit'
        });
        $('#last-request').text('Dernière requête: ' + timeString);
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
    
    // Télécharger un fichier depuis une URL
    function downloadFile(url, filename) {
        var a = document.createElement('a');
        a.href = url;
        a.download = filename || 'download';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
    
    // ========== INITIALISATION ==========
    
    // Obtenir et afficher les dates
    var todayDate = getTodayDate();
    var tomorrowDate = getTomorrowDate();
    
    // Initialiser les filtres de date
    $('#filter_start_date').val(todayDate);
    $('#filter_end_date').val(tomorrowDate);
    
    console.log("Initialisation avec dates:", {
        start: todayDate,
        end: tomorrowDate
    });
    
    // ========== DATATABLE CONFIGURATION ==========
    
    var table = $('#attendances-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('daily-attendance.data') }}",
            type: 'GET',
            data: function(d) {
                // Récupérer tous les filtres
                var startDate = $('#filter_start_date').val();
                var endDate = $('#filter_end_date').val();
                var terminalSn = $('#filter_terminal_sn').val();
                var empCode = $('#filter_emp_code').val();
                
                // Si les dates sont vides, utiliser aujourd'hui et demain
                if (!startDate || startDate.trim() === '') {
                    startDate = todayDate;
                    $('#filter_start_date').val(todayDate);
                }
                if (!endDate || endDate.trim() === '') {
                    endDate = tomorrowDate;
                    $('#filter_end_date').val(tomorrowDate);
                }
                
                // Envoyer les filtres au serveur
                d.start_date = startDate;
                d.end_date = endDate;
                d.terminal_sn = terminalSn;
                d.emp_code = empCode;
                
                console.log("Filtres envoyés:", {
                    start_date: startDate,
                    end_date: endDate,
                    terminal_sn: terminalSn,
                    emp_code: empCode
                });
            },
            beforeSend: function() {
                showLoading('Chargement des données ...');
            },
            complete: function() {
                hideLoading();
                updateLastRequestTime();
            },
            error: function(xhr, error, thrown) {
                hideLoading();
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
                data: 'date',
                name: 'date',
                width: '10%',
                render: function(data) {
                    if (!data) return '-';
                    try {
                        return new Date(data).toLocaleDateString('fr-FR');
                    } catch (e) {
                        return data;
                    }
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
                data: 'employee.full_name',
                name: 'employee.full_name',
                width: '15%',
                render: function(data, type, row) {
                    if (!data || data === 'Non enregistré' || data === 'Non enregistré') {
                        return '<span class="text-warning" title="Employé non trouvé dans la base de données"><i class="bi bi-exclamation-triangle me-1"></i>Non enregistré</span>';
                    }
                    return data;
                }
            },
            { 
                data: null,
                name: 'attendance_times',
                width: '15%',
                render: function(data, type, row) {
                    if (row.all_punches && row.all_punches.trim() !== '') {
                        // Afficher simplement toutes les heures séparées par des virgules
                        var punches = row.all_punches.split(', ');
                        
                        // Format compact pour l'affichage
                        var displayText = '';
                        if (punches.length <= 4) {
                            // Afficher tous si 4 ou moins
                            displayText = punches.join(', ');
                        } else {
                            // Afficher les 2 premiers, "...", les 2 derniers
                            displayText = punches[0] + ', ' + punches[1] + ', ..., ' + 
                                         punches[punches.length - 2] + ', ' + punches[punches.length - 1];
                        }
                        
                        return '<span title="' + row.all_punches + '" data-bs-toggle="tooltip">' + 
                               displayText + '</span>';
                    }
                    return '<span class="text-muted">-</span>';
                }
            },
            { 
                data: 'total_work_hours',
                name: 'total_work_hours',
                width: '10%',
                render: function(data) {
                    if (!data && data !== 0) return '-';
                    // Afficher en heures avec le format "X.XX h"
                    return data + ' h';
                }
            },
            { 
                data: 'status',
                name: 'status',
                width: '12%',
                render: function(data) {
                    var badgeClass = 'secondary';
                    var text = data;
                    
                    switch(data) {
                        case 'present':
                            badgeClass = 'success';
                            text = 'Présent';
                            break;
                        case 'absent':
                            badgeClass = 'danger';
                            text = 'Absent';
                            break;
                        case 'late':
                            badgeClass = 'warning';
                            text = 'En retard';
                            break;
                        case 'early_leave':
                            badgeClass = 'info';
                            text = 'Départ anticipé';
                            break;
                        default:
                            badgeClass = 'secondary';
                            text = data || 'N/A';
                    }
                    
                    return '<span class="badge bg-' + badgeClass + '">' + text + '</span>';
                }
            }
        ],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json"
        },
        pageLength: 500,
        lengthMenu: [[10, 25, 50, 100, 500, -1], [10, 25, 50, 100, 500, "Tous"]],
        order: [[0, 'desc']],
        responsive: true,
        drawCallback: function(settings) {
            updateLastRequestTime();
            
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
    
    // Appliquer les filtres
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
        
        // Recharger le tableau avec les nouveaux filtres
        table.ajax.reload();
    });
    
    // Appliquer automatiquement les filtres quand on change les valeurs
    $('#filter_start_date, #filter_end_date, #filter_terminal_sn, #filter_emp_code').on('change', function() {
        $('#apply_filters').click();
    });

    // ========== EXPORT PDF AVEC AJAX ==========
    
    // Bouton d'export PDF
    $('#exportPdfBtn').click(function() {
        // Récupérer les valeurs des filtres
        var startDate = $('#filter_start_date').val();
        var endDate = $('#filter_end_date').val();
        var terminalSn = $('#filter_terminal_sn').val();
        var empCode = $('#filter_emp_code').val();
        
        // Validation des dates
        if (!startDate || !endDate) {
            showSweetAlert('error', 'Erreur', 'Veuillez sélectionner une période valide pour l\'export.');
            return;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            showSweetAlert('error', 'Erreur', 'La date de début ne peut pas être après la date de fin.');
            return;
        }
        
        // Sauvegarder le texte original du bouton
        var originalButtonHtml = $('#exportPdfBtn').html();
        
        // Afficher le loader
        $('#exportPdfBtn').html('<i class="fas fa-spinner fa-spin me-1"></i> Génération...');
        $('#exportPdfBtn').prop('disabled', true);
        
        // Afficher le modal de chargement
        showPdfLoading('Préparation du rapport PDF...');
        
        // Envoyer la requête AJAX
        $.ajax({
            url: "{{ route('daily-attendance.export-pdf') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                start_date: startDate,
                end_date: endDate,
                terminal_sn: terminalSn,
                emp_code: empCode
            },
            xhr: function() {
                var xhr = new XMLHttpRequest();
                
                // Écouter l'événement de progression
                xhr.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        var percentComplete = (e.loaded / e.total) * 100;
                        console.log('Progression: ' + percentComplete + '%');
                    }
                });
                
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    // Mettre à jour le message
                    $('#pdfModalMessage').html(
                        'PDF généré avec succès!<br>' +
                        '<small>Téléchargement en cours...</small>'
                    );
                    
                    // Télécharger le fichier
                    setTimeout(function() {
                        var link = document.createElement('a');
                        link.href = response.pdf_url;
                        link.download = response.filename || 'presences_report.pdf';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        // Cacher le modal après un délai
                        setTimeout(function() {
                            hidePdfLoading();
                            showSweetAlert('success', 'Succès', 
                                'Le PDF a été généré et téléchargé avec succès!', 
                                3000);
                        }, 500);
                    }, 1000);
                } else {
                    hidePdfLoading();
                    showSweetAlert('error', 'Erreur', response.message || 'Erreur lors de la génération du PDF.');
                }
            },
            error: function(xhr, status, error) {
                hidePdfLoading();
                
                var errorMessage = 'Une erreur est survenue lors de la génération du PDF.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.statusText) {
                    errorMessage = 'Erreur ' + xhr.status + ': ' + xhr.statusText;
                }
                
                showSweetAlert('error', 'Erreur', errorMessage);
                console.error('Erreur export PDF:', xhr);
            },
            complete: function() {
                // Réinitialiser le bouton
                $('#exportPdfBtn').html(originalButtonHtml);
                $('#exportPdfBtn').prop('disabled', false);
            }
        });
    });
    
    // Annuler la génération PDF
    $('#cancelPdfBtn').click(function() {
        // Ici vous pourriez ajouter une logique pour annuler la requête AJAX
        hidePdfLoading();
        showSweetAlert('info', 'Annulé', 'Génération du PDF annulée.');
    });
    
    // ========== BOUTON AUJOURD'HUI ==========
    
    $('#today_button').on('click', function() {
        // Réinitialiser tous les filtres
        $('#filter_start_date').val(todayDate);
        $('#filter_end_date').val(tomorrowDate);
        $('#filter_terminal_sn').val('all');
        $('#filter_emp_code').val('all');
        
        // Recharger le tableau
        table.ajax.reload();
        
        // Notification
        showSweetAlert('success', 'Succès', 'Filtres réinitialisés à aujourd\'hui', 2000);
    });
    
    // ========== STYLES DYNAMIQUES ==========
    
    // Ajouter les styles CSS dynamiquement
    var dynamicStyles = `
        /* Animation de rotation */
        .spin { 
            animation: spin 1s linear infinite; 
        }
        
        @keyframes spin { 
            100% { 
                transform: rotate(360deg); 
            } 
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
            font-size: 0.75em;
            padding: 0.35em 0.65em;
        }
        
        /* Style pour le modal de chargement PDF */
        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .btn-group {
                flex-direction: column;
                gap: 5px;
            }
            
            #today_button, #exportPdfBtn {
                margin-left: 0 !important;
                margin-top: 5px;
            }
            
            .dataTables_wrapper {
                font-size: 0.9rem;
            }
            
            .modal-dialog {
                margin: 10px;
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
    
    // ========== LOG DE DÉMARRAGE ==========
    console.log('Application de pointage initialisée');
    console.log('URL API:', '{{ route("daily-attendance.data") }}');
    console.log('Date initiale:', todayDate);
    console.log('Date de fin initiale (demain):', tomorrowDate);
    console.log('Pagination: 500 lignes par page');
});
</script>

<style>
    /* Styles complémentaires */
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
    
    /* Styles pour le conteneur des pointages */
    .punch-times-container {
        max-height: 80px;
        overflow-y: auto;
        padding: 5px;
        background-color: #f8f9fa;
        border-radius: 4px;
        border: 1px solid #e9ecef;
    }
    
    .punch-time-item {
        padding: 2px 5px;
        margin: 2px 0;
        background-color: white;
        border-radius: 3px;
        border-left: 3px solid #0d6efd;
        font-size: 0.85rem;
    }
    
    /* Styles responsives */
    @media print {
        .btn-group, .card-header, .modal {
            display: none !important;
        }
    }
</style>
@endsection