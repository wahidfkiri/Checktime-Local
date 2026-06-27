@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Liste des zones</h3>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('home') }}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item active">Zones</li>
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
                                    <label for="code_filter">Code zone</label>
                                    <input type="text" class="form-control" id="code_filter" placeholder="Rechercher par code...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="name_filter">Nom zone</label>
                                    <input type="text" class="form-control" id="name_filter" placeholder="Rechercher par nom...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group text-start">
                                    <label class="form-label d-block" style="margin-bottom:0px;">&nbsp;</label>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-success" id="create-zone-button" data-bs-toggle="modal" data-bs-target="#createZoneModal">
                                            <i class="bi bi-plus-circle me-1"></i> Créer zone
                                        </button>
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
                        <div class="alert alert-info alert-dismissible fade show d-none" id="sync-alert" role="alert">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <strong>Synchronisation en cours...</strong> Veuillez patienter.
                            </div>
                        </div>
                        
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
                                            <strong>Total zones:</strong> <span id="total-zones"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table id="zones-table" class="table table-striped table-hover dt-responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Nom</th>
                                        <th>Actions</th>
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

<!-- Modal de création de zone -->
<div class="modal fade" id="createZoneModal" tabindex="-1" aria-labelledby="createZoneModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createZoneModalLabel">Créer une nouvelle zone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createZoneForm">
                <div class="modal-body">
                    <!-- <div class="mb-3">
                        <label for="zone_code" class="form-label">Code zone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="zone_code" name="code" required maxlength="50">
                        <div class="form-text">Le code doit être unique.</div>
                        <div class="invalid-feedback" id="code-error"></div>
                    </div> -->
                    <div class="mb-3">
                        <label for="zone_name" class="form-label">Nom zone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="zone_name" name="name" required maxlength="255">
                        <div class="invalid-feedback" id="name-error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-create-zone">
                        <span id="create-zone-text">Créer</span>
                        <span id="create-zone-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal d'édition de zone -->
<div class="modal fade" id="editZoneModal" tabindex="-1" aria-labelledby="editZoneModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editZoneModalLabel">Modifier la zone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editZoneForm">
                <input type="hidden" id="edit_zone_id" name="id">
                <div class="modal-body">
                    <!-- <div class="mb-3">
                        <label for="edit_zone_code" class="form-label">Code zone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_zone_code" name="code" required maxlength="50">
                        <div class="form-text">Le code doit être unique.</div>
                        <div class="invalid-feedback" id="edit-code-error"></div>
                    </div> -->
                    <div class="mb-3">
                        <label for="edit_zone_name" class="form-label">Nom zone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_zone_name" name="name" required maxlength="255">
                        <div class="invalid-feedback" id="edit-name-error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-edit-zone">
                        <span id="edit-zone-text">Enregistrer</span>
                        <span id="edit-zone-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de suppression de zone -->
<div class="modal fade" id="deleteZoneModal" tabindex="-1" aria-labelledby="deleteZoneModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteZoneModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette zone ?</p>
                <p><strong>Code:</strong> <span id="delete-zone-code"></span></p>
                <p><strong>Nom:</strong> <span id="delete-zone-name"></span></p>
                <p class="text-danger"><small>Cette action est irréversible.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-zone">
                    <span id="delete-zone-text">Supprimer</span>
                    <span id="delete-zone-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Variable pour stocker l'ID de la zone à supprimer
    let zoneToDelete = null;
    
    // Charger le statut de synchronisation
    loadSyncStatus();
    
    // Initialiser DataTable
    var table = $('#zones-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('areas.local') }}",
            data: function (d) {
                d.area_code = $('#code_filter').val();
                d.area_name = $('#name_filter').val();
                // Le client_id est automatiquement géré par le contrôleur
            }
        },
        columns: [
            { 
                data: 'code',
                name: 'code',
                width: '15%'
            },
            { 
                data: 'name',
                name: 'name',
                width: '25%'
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false,
                width: '10%',
                render: function(data, type, row) {
                    return `
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-warning edit-zone-btn" data-id="${row.area_id}" data-code="${row.code}" data-name="${row.name}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger delete-zone-btn" data-id="${row.area_id}" data-code="${row.code}" data-name="${row.name}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                }
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
            
            // Réattacher les événements après le redessinage du tableau
            attachActionButtons();
        }
    });

    // Attacher les événements aux boutons d'action
    function attachActionButtons() {
        // Bouton d'édition
        $('.edit-zone-btn').off('click').on('click', function() {
            const zoneId = $(this).data('id');
            const zoneCode = $(this).data('code');
            const zoneName = $(this).data('name');
            
            openEditModal(zoneId, zoneCode, zoneName);
        });
        
        // Bouton de suppression
        $('.delete-zone-btn').off('click').on('click', function() {
            const zoneId = $(this).data('id');
            const zoneCode = $(this).data('code');
            const zoneName = $(this).data('name');
            
            openDeleteModal(zoneId, zoneCode, zoneName);
        });
    }
    
    // Ouvrir la modal d'édition
    function openEditModal(id, code, name) {
        $('#edit_zone_id').val(id);
        $('#edit_zone_code').val(code);
        $('#edit_zone_name').val(name);
        
        // Réinitialiser les erreurs
        $('#edit_zone_code, #edit_zone_name').removeClass('is-invalid');
        $('#edit-code-error, #edit-name-error').text('');
        
        $('#editZoneModal').modal('show');
    }
    
    // Ouvrir la modal de suppression
    function openDeleteModal(id, code, name) {
        zoneToDelete = id;
        $('#delete-zone-code').text(code);
        $('#delete-zone-name').text(name);
        $('#deleteZoneModal').modal('show');
    }
    
    // Appliquer les filtres
    function applyFilters() {
        table.ajax.reload();
    }

    // Événements pour les filtres
    $('#code_filter, #name_filter').on('change keyup', function() {
        applyFilters();
    });

    // Réinitialiser les filtres
    $('#reset_filters').on('click', function() {
        $('#code_filter').val('');
        $('#name_filter').val('');
        applyFilters();
    });

    // Synchronisation
    $('#sync_button').on('click', function() {
        performSync(false);
    });
    
    // Reset et Sync
    $('#reset_sync').on('click', function() {
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Toutes les zones seront supprimées et resynchronisées. Cette action est irréversible.",
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
            url: "{{ url('areas') }}/" + action,
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
            url: "{{ url('areas/sync-status') }}",
            type: 'GET',
            success: function(response) {
                if (response.client_name !== 'Non associé') {
                    $('#sync-status-container').show();
                    $('#client-name').text(response.client_name);
                    $('#last-sync').text(response.last_sync);
                    $('#total-zones').text(response.total_zones);
                }
            }
        });
    }
    
    // Gestion de la création de zone
    $('#createZoneForm').on('submit', function(e) {
        e.preventDefault();
        
        // Récupérer les données du formulaire
        var formData = {
            code: $('#zone_code').val(),
            name: $('#zone_name').val(),
            _token: "{{ csrf_token() }}"
        };
        
        // Désactiver le bouton et afficher le spinner
        $('#submit-create-zone').prop('disabled', true);
        $('#create-zone-text').addClass('d-none');
        $('#create-zone-spinner').removeClass('d-none');
        
        // Réinitialiser les erreurs
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        // Envoyer la requête AJAX
        $.ajax({
            url: "{{ route('areas.store') }}",
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Fermer la modal
                    $('#createZoneModal').modal('hide');
                    
                    // Réinitialiser le formulaire
                    $('#createZoneForm')[0].reset();
                    
                    // Afficher un message de succès
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message || 'Zone créée avec succès',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    
                    // Recharger le tableau
                    table.ajax.reload();
                    
                    // Mettre à jour le statut de synchronisation
                    loadSyncStatus();
                } else {
                    // Afficher les erreurs
                    showSweetAlert('error', 'Erreur', response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Validation errors
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        var input = $('#zone_' + key);
                        input.addClass('is-invalid');
                        input.next('.invalid-feedback').text(value[0]);
                    });
                } else {
                    showSweetAlert('error', 'Erreur', 
                        'Une erreur est survenue lors de la création. ' + 
                        (xhr.responseJSON?.message || 'Veuillez réessayer.')
                    );
                }
            },
            complete: function() {
                // Réactiver le bouton
                $('#submit-create-zone').prop('disabled', false);
                $('#create-zone-text').removeClass('d-none');
                $('#create-zone-spinner').addClass('d-none');
            }
        });
    });
    
    // Gestion de l'édition de zone
    $('#editZoneForm').on('submit', function(e) {
        e.preventDefault();
        
        const zoneId = $('#edit_zone_id').val();
        
        // Récupérer les données du formulaire
        var formData = {
            code: $('#edit_zone_code').val(),
            name: $('#edit_zone_name').val(),
            _token: "{{ csrf_token() }}"
        };
        
        // Désactiver le bouton et afficher le spinner
        $('#submit-edit-zone').prop('disabled', true);
        $('#edit-zone-text').addClass('d-none');
        $('#edit-zone-spinner').removeClass('d-none');
        
        // Réinitialiser les erreurs
        $('#edit_zone_code, #edit_zone_name').removeClass('is-invalid');
        $('#edit-code-error, #edit-name-error').text('');
        
        // Envoyer la requête AJAX
        $.ajax({
            url: "{{ url('areas') }}/" + zoneId,
            type: 'PUT',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Fermer la modal
                    $('#editZoneModal').modal('hide');
                    
                    // Afficher un message de succès
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message || 'Zone modifiée avec succès',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    
                    // Recharger le tableau
                    table.ajax.reload();
                } else {
                    // Afficher les erreurs
                    showSweetAlert('error', 'Erreur', response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Validation errors
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        var input = $('#edit_zone_' + key);
                        input.addClass('is-invalid');
                        input.next('.invalid-feedback').text(value[0]);
                    });
                } else {
                    showSweetAlert('error', 'Erreur', 
                        'Une erreur est survenue lors de la modification. ' + 
                        (xhr.responseJSON?.message || 'Veuillez réessayer.')
                    );
                }
            },
            complete: function() {
                // Réactiver le bouton
                $('#submit-edit-zone').prop('disabled', false);
                $('#edit-zone-text').removeClass('d-none');
                $('#edit-zone-spinner').addClass('d-none');
            }
        });
    });
    
    // Gestion de la suppression de zone
    $('#confirm-delete-zone').on('click', function() {
        if (!zoneToDelete) return;
        
        // Désactiver le bouton et afficher le spinner
        $(this).prop('disabled', true);
        $('#delete-zone-text').addClass('d-none');
        $('#delete-zone-spinner').removeClass('d-none');
        
        // Envoyer la requête AJAX
        $.ajax({
            url: "{{ url('areas') }}/" + zoneToDelete,
            type: 'DELETE',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    // Fermer la modal
                    $('#deleteZoneModal').modal('hide');
                    
                    // Réinitialiser la variable
                    zoneToDelete = null;
                    
                    // Afficher un message de succès
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message || 'Zone supprimée avec succès',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    
                    // Recharger le tableau
                    table.ajax.reload();
                    
                    // Mettre à jour le statut de synchronisation
                    loadSyncStatus();
                } else {
                    // Afficher les erreurs
                    showSweetAlert('error', 'Erreur', response.message);
                    
                    // Fermer la modal
                    $('#deleteZoneModal').modal('hide');
                }
            },
            error: function(xhr) {
                showSweetAlert('error', 'Erreur', 
                    'Une erreur est survenue lors de la suppression. ' + 
                    (xhr.responseJSON?.message || 'Veuillez réessayer.')
                );
                
                // Fermer la modal
                $('#deleteZoneModal').modal('hide');
            },
            complete: function() {
                // Réactiver le bouton
                $('#confirm-delete-zone').prop('disabled', false);
                $('#delete-zone-text').removeClass('d-none');
                $('#delete-zone-spinner').addClass('d-none');
            }
        });
    });
    
    // Réinitialiser le formulaire quand la modal de création se ferme
    $('#createZoneModal').on('hidden.bs.modal', function() {
        $('#createZoneForm')[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#submit-create-zone').prop('disabled', false);
        $('#create-zone-text').removeClass('d-none');
        $('#create-zone-spinner').addClass('d-none');
    });
    
    // Réinitialiser quand la modal d'édition se ferme
    $('#editZoneModal').on('hidden.bs.modal', function() {
        $('#submit-edit-zone').prop('disabled', false);
        $('#edit-zone-text').removeClass('d-none');
        $('#edit-zone-spinner').addClass('d-none');
    });
    
    // Réinitialiser quand la modal de suppression se ferme
    $('#deleteZoneModal').on('hidden.bs.modal', function() {
        zoneToDelete = null;
        $('#confirm-delete-zone').prop('disabled', false);
        $('#delete-zone-text').removeClass('d-none');
        $('#delete-zone-spinner').addClass('d-none');
    });
    
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
        font-size: 0.75em;
        padding: 0.35em 0.65em;
    }
    #sync-alert {
        margin-bottom: 1rem;
    }
    #sync-status-container {
        background-color: #f8f9fa;
        border-left: 4px solid #0d6efd;
    }
    #create-zone-button {
        background-color: #198754;
        border-color: #198754;
    }
    #create-zone-button:hover {
        background-color: #157347;
        border-color: #146c43;
    }
    .modal-content {
        border-radius: 10px;
    }
    .modal-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        border-radius: 10px 10px 0 0;
    }
    .modal-title {
        color: #333;
        font-weight: 600;
    }
    .btn-group .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
    }
    .btn-warning:hover {
        background-color: #e0a800;
        border-color: #d39e00;
    }
</style>
@endsection