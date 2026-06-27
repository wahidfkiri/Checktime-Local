@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Liste des départements</h3>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('home') }}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item active">Départements</li>
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
                                    <label for="code_filter">Code Département</label>
                                    <input type="text" class="form-control" id="code_filter" placeholder="Rechercher par code...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="name_filter">Nom Département</label>
                                    <input type="text" class="form-control" id="name_filter" placeholder="Rechercher par nom...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group text-start">
                                    <label class="form-label d-block" style="margin-bottom:0px;">&nbsp;</label>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-success" id="create-department-button" data-bs-toggle="modal" data-bs-target="#createDepartmentModal">
                                            <i class="bi bi-plus-circle me-1"></i> Créer
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
                                            <strong>Total départements:</strong> <span id="total-departments"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table id="departments-table" class="table table-striped table-hover dt-responsive nowrap" style="width:100%">
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

<!-- Modal de création de département -->
<div class="modal fade" id="createDepartmentModal" tabindex="-1" aria-labelledby="createDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createDepartmentModalLabel">Créer un nouveau département</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createDepartmentForm">
                <div class="modal-body">
                    <!-- <div class="mb-3">
                        <label for="department_code" class="form-label">Code département <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="department_code" name="code" required maxlength="50">
                        <div class="form-text">Le code doit être unique.</div>
                        <div class="invalid-feedback" id="code-error"></div>
                    </div> -->
                    <div class="mb-3">
                        <label for="department_name" class="form-label">Nom département <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="department_name" name="name" required maxlength="255">
                        <div class="invalid-feedback" id="name-error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-create-department">
                        <span id="create-department-text">Créer</span>
                        <span id="create-department-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal d'édition de département -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-labelledby="editDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDepartmentModalLabel">Modifier le département</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editDepartmentForm">
                <input type="hidden" id="edit_department_id" name="id">
                <div class="modal-body">
                    <!-- <div class="mb-3">
                        <label for="edit_department_code" class="form-label">Code département <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_department_code" name="code" required maxlength="50">
                        <div class="form-text">Le code doit être unique.</div>
                        <div class="invalid-feedback" id="edit-code-error"></div>
                    </div> -->
                    <div class="mb-3">
                        <label for="edit_department_name" class="form-label">Nom département <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_department_name" name="name" required maxlength="255">
                        <div class="invalid-feedback" id="edit-name-error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-edit-department">
                        <span id="edit-department-text">Enregistrer</span>
                        <span id="edit-department-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de suppression de département -->
<div class="modal fade" id="deleteDepartmentModal" tabindex="-1" aria-labelledby="deleteDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteDepartmentModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce département ?</p>
                <p><strong>Code:</strong> <span id="delete-department-code"></span></p>
                <p><strong>Nom:</strong> <span id="delete-department-name"></span></p>
                <p class="text-danger"><small>Cette action est irréversible.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-department">
                    <span id="delete-department-text">Supprimer</span>
                    <span id="delete-department-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Variable pour stocker l'ID du département à supprimer
    let departmentToDelete = null;
    
    // Charger le statut de synchronisation
    loadSyncStatus();
    
    // Initialiser DataTable
    var table = $('#departments-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('departments.local') }}",
            data: function (d) {
                d.code = $('#code_filter').val();
                d.name = $('#name_filter').val();
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
                data: null,
                orderable: false,
                searchable: false,
                width: '10%',
                render: function(data, type, row) {
                    return `
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-warning edit-department-btn" data-id="${row.department_id}" data-code="${row.code}" data-name="${row.name}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger delete-department-btn" data-id="${row.department_id}" data-code="${row.code}" data-name="${row.name}">
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
        $('.edit-department-btn').off('click').on('click', function() {
            const departmentId = $(this).data('id');
            const departmentCode = $(this).data('code');
            const departmentName = $(this).data('name');
            
            openEditModal(departmentId, departmentCode, departmentName);
        });
        
        // Bouton de suppression
        $('.delete-department-btn').off('click').on('click', function() {
            const departmentId = $(this).data('id');
            const departmentCode = $(this).data('code');
            const departmentName = $(this).data('name');
            
            openDeleteModal(departmentId, departmentCode, departmentName);
        });
    }
    
    // Ouvrir la modal d'édition
    function openEditModal(id, code, name) {
        $('#edit_department_id').val(id);
        $('#edit_department_code').val(code);
        $('#edit_department_name').val(name);
        
        // Réinitialiser les erreurs
        $('#edit_department_code, #edit_department_name').removeClass('is-invalid');
        $('#edit-code-error, #edit-name-error').text('');
        
        $('#editDepartmentModal').modal('show');
    }
    
    // Ouvrir la modal de suppression
    function openDeleteModal(id, code, name) {
        departmentToDelete = id;
        $('#delete-department-code').text(code);
        $('#delete-department-name').text(name);
        $('#deleteDepartmentModal').modal('show');
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
            text: "Tous les départements seront supprimés et resynchronisés. Cette action est irréversible.",
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
            url: "{{ url('departments') }}/" + action,
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
            url: "{{ url('departments/sync-status') }}",
            type: 'GET',
            success: function(response) {
                if (response.client_name !== 'Non associé') {
                    $('#sync-status-container').show();
                    $('#client-name').text(response.client_name);
                    $('#last-sync').text(response.last_sync);
                    $('#total-departments').text(response.total_departments);
                }
            }
        });
    }
    
    // Gestion de la création de département
    $('#createDepartmentForm').on('submit', function(e) {
        e.preventDefault();
        
        // Récupérer les données du formulaire
        var formData = {
            code: $('#department_code').val(),
            name: $('#department_name').val(),
            _token: "{{ csrf_token() }}"
        };
        
        // Désactiver le bouton et afficher le spinner
        $('#submit-create-department').prop('disabled', true);
        $('#create-department-text').addClass('d-none');
        $('#create-department-spinner').removeClass('d-none');
        
        // Réinitialiser les erreurs
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        // Envoyer la requête AJAX
        $.ajax({
            url: "{{ route('departments.store') }}",
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Fermer la modal
                    $('#createDepartmentModal').modal('hide');
                    
                    // Réinitialiser le formulaire
                    $('#createDepartmentForm')[0].reset();
                    
                    // Afficher un message de succès
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message || 'Département créé avec succès',
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
                        var input = $('#department_' + key);
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
                $('#submit-create-department').prop('disabled', false);
                $('#create-department-text').removeClass('d-none');
                $('#create-department-spinner').addClass('d-none');
            }
        });
    });
    
    // Gestion de l'édition de département
    $('#editDepartmentForm').on('submit', function(e) {
        e.preventDefault();
        
        const departmentId = $('#edit_department_id').val();
        
        // Récupérer les données du formulaire
        var formData = {
            code: $('#edit_department_code').val(),
            name: $('#edit_department_name').val(),
            _token: "{{ csrf_token() }}"
        };
        
        // Désactiver le bouton et afficher le spinner
        $('#submit-edit-department').prop('disabled', true);
        $('#edit-department-text').addClass('d-none');
        $('#edit-department-spinner').removeClass('d-none');
        
        // Réinitialiser les erreurs
        $('#edit_department_code, #edit_department_name').removeClass('is-invalid');
        $('#edit-code-error, #edit-name-error').text('');
        
        // Envoyer la requête AJAX
        $.ajax({
            url: "{{ url('departments') }}/" + departmentId,
            type: 'PUT',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Fermer la modal
                    $('#editDepartmentModal').modal('hide');
                    
                    // Afficher un message de succès
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message || 'Département modifié avec succès',
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
                        var input = $('#edit_department_' + key);
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
                $('#submit-edit-department').prop('disabled', false);
                $('#edit-department-text').removeClass('d-none');
                $('#edit-department-spinner').addClass('d-none');
            }
        });
    });
    
    // Gestion de la suppression de département
    $('#confirm-delete-department').on('click', function() {
        if (!departmentToDelete) return;
        
        // Désactiver le bouton et afficher le spinner
        $(this).prop('disabled', true);
        $('#delete-department-text').addClass('d-none');
        $('#delete-department-spinner').removeClass('d-none');
        
        // Envoyer la requête AJAX
        $.ajax({
            url: "{{ url('departments') }}/" + departmentToDelete,
            type: 'DELETE',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    // Fermer la modal
                    $('#deleteDepartmentModal').modal('hide');
                    
                    // Réinitialiser la variable
                    departmentToDelete = null;
                    
                    // Afficher un message de succès
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message || 'Département supprimé avec succès',
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
                    $('#deleteDepartmentModal').modal('hide');
                }
            },
            error: function(xhr) {
                showSweetAlert('error', 'Erreur', 
                    'Une erreur est survenue lors de la suppression. ' + 
                    (xhr.responseJSON?.message || 'Veuillez réessayer.')
                );
                
                // Fermer la modal
                $('#deleteDepartmentModal').modal('hide');
            },
            complete: function() {
                // Réactiver le bouton
                $('#confirm-delete-department').prop('disabled', false);
                $('#delete-department-text').removeClass('d-none');
                $('#delete-department-spinner').addClass('d-none');
            }
        });
    });
    
    // Réinitialiser le formulaire quand la modal de création se ferme
    $('#createDepartmentModal').on('hidden.bs.modal', function() {
        $('#createDepartmentForm')[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#submit-create-department').prop('disabled', false);
        $('#create-department-text').removeClass('d-none');
        $('#create-department-spinner').addClass('d-none');
    });
    
    // Réinitialiser quand la modal d'édition se ferme
    $('#editDepartmentModal').on('hidden.bs.modal', function() {
        $('#submit-edit-department').prop('disabled', false);
        $('#edit-department-text').removeClass('d-none');
        $('#edit-department-spinner').addClass('d-none');
    });
    
    // Réinitialiser quand la modal de suppression se ferme
    $('#deleteDepartmentModal').on('hidden.bs.modal', function() {
        departmentToDelete = null;
        $('#confirm-delete-department').prop('disabled', false);
        $('#delete-department-text').removeClass('d-none');
        $('#delete-department-spinner').addClass('d-none');
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
    #create-department-button {
        background-color: #198754;
        border-color: #198754;
    }
    #create-department-button:hover {
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