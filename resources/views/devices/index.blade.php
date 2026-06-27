@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Liste des appareils</h3>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('home') }}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item active">Appareils</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <section class="section">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="code_filter">SN Appareil</label>
                                    <input type="text" class="form-control" id="code_filter" placeholder="Rechercher par SN...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="alias_filter">Alias</label>
                                    <input type="text" class="form-control" id="alias_filter" placeholder="Rechercher par alias...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="status_filter">Statut</label>
                                    <select class="form-select" id="status_filter">
                                        <option value="">Tous les statuts</option>
                                        <option value="active">Actif</option>
                                        <option value="inactive">Inactif</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group text-start">
                                    <label class="form-label d-block" style="margin-bottom:0px;">&nbsp;</label>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-primary" id="sync_button">
                                            <i class="bi bi-arrow-repeat me-1"></i> Synchroniser
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="reset_filters">
                                            <i class="bi bi-x-circle me-1"></i> Réinitialiser
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="alert alert-light border" id="sync-status-container" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>Client:</strong> <span id="client-name"></span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Dernière synchro:</strong> <span id="last-sync"></span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Total appareils:</strong> <span id="total-devices"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info alert-dismissible fade show d-none" id="sync-alert" role="alert">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <strong>Synchronisation en cours...</strong> Veuillez patienter.
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="devices-table" class="table table-striped table-hover dt-responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>SN</th>
                                        <th>Alias</th>
                                        <th>IP</th>
                                        <!-- <th>Nom Zone</th> -->
                                        <th>Statut</th>
                                        <th>Dernière activité</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Les données seront chargées via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<script>
$(document).ready(function() {
    // Charger le statut de synchronisation
    loadSyncStatus();
    
    // Timer pour éviter les requêtes trop fréquentes
    var filterTimer = null;
    var debounceDelay = 500; // 500ms de délai
    
    // Initialiser DataTable
    var table = $('#devices-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('devices.local') }}",
            data: function (d) {
                d.device_sn = $('#code_filter').val();
                d.alias = $('#alias_filter').val();
                d.status = $('#status_filter').val();
            },
            error: function(xhr, error, thrown) {
                console.error('Erreur AJAX:', error);
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    showSweetAlert('error', 'Erreur', xhr.responseJSON.error);
                }
            }
        },
        columns: [
            { 
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                width: '5%',
                orderable: false,
                searchable: false
            },
            { 
                data: 'code',
                name: 'device_sn',
                width: '10%'
            },
            { 
                data: 'alias',
                name: 'alias',
                width: '15%'
            },
            { 
                data: 'ip',
                name: 'ip',
                width: '10%'
            },
            // { 
            //     data: 'area_name',
            //     name: 'area_name',
            //     width: '15%'
            // },
            { 
                data: 'status',
                name: 'status',
                width: '10%',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return data;
                }
            },
            { 
                data: 'last_sync',
                name: 'last_sync',
                width: '20%',
                orderable: true,
                searchable: false
            }
        ],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json"
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tous"]],
        order: [[1, 'asc']],
        responsive: true,
        drawCallback: function(settings) {
            // Mettre à jour le nombre de résultats
            var api = this.api();
            var pageInfo = api.page.info();
            $('.dataTables_info').html(
                'Affichage de ' + (pageInfo.start + 1) + ' à ' + 
                (pageInfo.end) + ' sur ' + pageInfo.recordsTotal + ' entrées'
            );
        }
    });

    // Fonction pour appliquer les filtres avec debounce
    function applyFilters() {
        if (filterTimer) {
            clearTimeout(filterTimer);
        }
        filterTimer = setTimeout(function() {
            table.ajax.reload();
        }, debounceDelay);
    }

    // Événements pour les filtres avec debounce
    $('#status_filter').on('change', function() {
        applyFilters();
    });

    $('#code_filter, #alias_filter').on('keyup', function() {
        applyFilters();
    });

    // Réinitialiser les filtres
    $('#reset_filters').on('click', function() {
        $('#code_filter').val('');
        $('#alias_filter').val('');
        $('#status_filter').val('');
        
        // Appliquer immédiatement
        table.ajax.reload();
    });

    // Synchronisation
    $('#sync_button').on('click', function() {
        performSync(false);
    });
    
    // Reset et Sync
    $('#reset_sync').on('click', function() {
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Tous les appareils seront supprimés et resynchronisés. Cette action est irréversible.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui, continuer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                performSync(true);
            }
        });
    });
    
    function performSync(reset = false) {
        var $button = reset ? $('#reset_sync') : $('#sync_button');
        var originalText = $button.html();
        var action = reset ? 'reset-sync' : 'sync';
        
        $button.prop('disabled', true).html('<i class="bi bi-arrow-repeat spin"></i> En cours...');
        $('#sync-alert').removeClass('d-none');
        
        $.ajax({
            url: "{{ url('devices') }}/" + action,
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    showSweetAlert('success', 
                        reset ? 'Reset & Sync réussi' : 'Synchronisation réussie', 
                        response.message
                    );
                    
                    // Recharger les données et le statut
                    table.ajax.reload();
                    setTimeout(function() {
                        loadSyncStatus();
                    }, 1000);
                } else {
                    showSweetAlert('error', 
                        'Erreur', 
                        response.message
                    );
                }
            },
            error: function(xhr) {
                showSweetAlert('error', 
                    'Erreur', 
                    'Erreur lors de l\'opération. ' + 
                    (xhr.responseJSON?.error || 'Veuillez réessayer ultérieurement.')
                );
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
                $('#sync-alert').addClass('d-none');
            }
        });
    }
    
    // Charger le statut de synchronisation
    function loadSyncStatus() {
        $.ajax({
            url: "{{ url('devices/sync-status') }}",
            type: 'GET',
            success: function(response) {
                if (response.client_name !== 'Non associé') {
                    $('#sync-status-container').show();
                    $('#client-name').text(response.client_name);
                    $('#last-sync').text(response.last_sync);
                    $('#total-devices').text(response.total_devices);
                }
            }
        });
    }
    
    function showSweetAlert(icon, title, text, timer = null) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: timer || 5000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        
        Toast.fire({
            icon: icon,
            title: title,
            text: text
        });
    }

    // Style pour l'icône de rotation
    $('<style>').text('.spin { animation: spin 1s linear infinite; } @keyframes spin { 100% { transform: rotate(360deg); } }').appendTo('head');
});
</script>
<style>
    .dataTables_wrapper {
        padding: 10px 0;
    }
    .dataTables_length,
    .dataTables_filter {
        margin-bottom: 15px;
    }
    .dataTables_filter input {
        margin-left: 10px;
    }
    .dt-responsive {
        width: 100% !important;
    }
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    
    .btn-group .btn {
        margin-right: 5px;
        border-radius: 4px !important;
    }
    
    .badge {
        font-size: 0.85em;
        padding: 0.5em 0.8em;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        font-weight: 500;
    }
    
    .badge.bg-success-light {
        background-color: rgba(25, 135, 84, 0.1) !important;
        border: 1px solid rgba(25, 135, 84, 0.2);
    }
    
    .badge.bg-danger-light {
        background-color: rgba(220, 53, 69, 0.1) !important;
        border: 1px solid rgba(220, 53, 69, 0.2);
    }
    
    .badge .bi {
        font-size: 0.8em;
        margin-right: 4px;
    }
    
    .badge.bg-success-light .bi {
        color: #198754;
    }
    
    .badge.bg-danger-light .bi {
        color: #dc3545;
    }
    
    .text-success {
        color: #198754 !important;
    }
    
    .text-danger {
        color: #dc3545 !important;
    }
    
    .text-muted {
        color: #6c757d !important;
    }
    
    #sync-alert {
        margin-bottom: 1rem;
    }
    
    #sync-status-container {
        background-color: #f8f9fa;
        border-left: 4px solid #0d6efd;
    }
    
    .table th, .table td {
        vertical-align: middle;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .badge {
            font-size: 0.75em;
            padding: 0.4em 0.6em;
        }
        
        .badge .bi {
            font-size: 0.7em;
        }
    }
</style>
@endsection