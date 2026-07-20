@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Gestion des Permissions</h3>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('home') }}">Tableau de board</a>
                                </li>
                                <li class="breadcrumb-item active">Permissions</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <section class="section">

                <!-- Filtres et actions -->
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="employee_filter">Employé</label>
                                    <select class="form-control" id="employee_filter">
                                        <option value="">Tous les employés</option>
                                        @foreach($employees as $employee)
                                            <option value="{{ $employee->id }}">
                                                {{ $employee->first_name }} {{ $employee->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="status_filter">Statut</label>
                                    <select class="form-control" id="status_filter">
                                        <option value="">Tous</option>
                                        <option value="pending">En attente</option>
                                        <option value="approved">Approuvé</option>
                                        <option value="rejected">Rejeté</option>
                                        <option value="canceled">Annulé</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="date_filter">Date</label>
                                    <input type="date" class="form-control" id="date_filter">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label d-block" style="margin-bottom:0px;">&nbsp;</label>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-primary" id="apply_filters">
                                            <i class="bi bi-filter me-1"></i> Filtrer
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="reset_filters">
                                            <i class="bi bi-x-circle me-1"></i> Réinitialiser
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-success" id="create-permission-button" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
                                        <i class="bi bi-plus-circle me-1"></i> Nouvelle Permission
                                    </button>
                                    <!-- <button type="button" class="btn btn-info" id="export-button">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                                    </button> -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="permissions-table" class="table table-striped table-hover dt-responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Employé</th>
                                        <th>Dates</th>
                                        <th>Horaire</th>
                                        <th>Durée</th>
                                        <th>Raison</th>
                                        <th>Statut</th>
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

<!-- Modal de création de permission -->
<div class="modal fade" id="createPermissionModal" tabindex="-1" aria-labelledby="createPermissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createPermissionModalLabel">Nouvelle Permission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createPermissionForm">
                @csrf
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="employee_id" class="form-label">Employé <span class="text-danger">*</span></label>
                            <select class="form-control" id="employee_id" name="employee_id" required>
                                <option value="">Sélectionner un employé</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="employee_id-error"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="date_debut" class="form-label">Date début <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" required>
                            <div class="invalid-feedback" id="date_debut-error"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="date_fin" class="form-label">Date fin <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" required>
                            <div class="invalid-feedback" id="date_fin-error"></div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_time" class="form-label">Heure de début</label>
                            <input type="time" class="form-control" id="start_time" name="start_time">
                            <div class="form-text">Laissez vide pour toute la journée</div>
                            <div class="invalid-feedback" id="start_time-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="end_time" class="form-label">Heure de fin</label>
                            <input type="time" class="form-control" id="end_time" name="end_time">
                            <div class="invalid-feedback" id="end_time-error"></div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="raison" class="form-label">Raison <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="raison" name="raison" rows="3" required maxlength="1000"></textarea>
                            <div class="invalid-feedback" id="raison-error"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-create-permission">
                        <span id="create-permission-text">Créer</span>
                        <span id="create-permission-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal d'édition de permission -->
<div class="modal fade" id="editPermissionModal" tabindex="-1" aria-labelledby="editPermissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPermissionModalLabel">Modifier la Permission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPermissionForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_permission_id" name="id">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_employee_id" class="form-label">Employé <span class="text-danger">*</span></label>
                            <select class="form-control" id="edit_employee_id" name="employee_id" required>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="edit_employee_id-error"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="edit_date_debut" class="form-label">Date début <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_date_debut" name="date_debut" required>
                            <div class="invalid-feedback" id="edit_date_debut-error"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="edit_date_fin" class="form-label">Date fin <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_date_fin" name="date_fin" required>
                            <div class="invalid-feedback" id="edit_date_fin-error"></div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_start_time" class="form-label">Heure de début</label>
                            <input type="time" class="form-control" id="edit_start_time" name="start_time">
                            <div class="form-text">Laissez vide pour toute la journée</div>
                            <div class="invalid-feedback" id="edit_start_time-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_end_time" class="form-label">Heure de fin</label>
                            <input type="time" class="form-control" id="edit_end_time" name="end_time">
                            <div class="invalid-feedback" id="edit_end_time-error"></div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="edit_raison" class="form-label">Raison <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="edit_raison" name="raison" rows="3" required maxlength="1000"></textarea>
                            <div class="invalid-feedback" id="edit_raison-error"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-edit-permission">
                        <span id="edit-permission-text">Enregistrer</span>
                        <span id="edit-permission-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de rejet de permission -->
<div class="modal fade" id="rejectPermissionModal" tabindex="-1" aria-labelledby="rejectPermissionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectPermissionModalLabel">Rejeter la Permission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir rejeter cette permission ?</p>
                <div class="mb-3">
                    <label for="rejection_reason" class="form-label">Raison du rejet (optionnel)</label>
                    <textarea class="form-control" id="rejection_reason" rows="3" maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirm-reject-permission">
                    <span id="reject-permission-text">Rejeter</span>
                    <span id="reject-permission-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de suppression de permission -->
<div class="modal fade" id="deletePermissionModal" tabindex="-1" aria-labelledby="deletePermissionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePermissionModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette permission ?</p>
                <p><strong>Employé:</strong> <span id="delete-permission-employee"></span></p>
                <p><strong>Date:</strong> <span id="delete-permission-date"></span></p>
                <p><strong>Raison:</strong> <span id="delete-permission-raison"></span></p>
                <p class="text-danger"><small>Cette action est irréversible.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-permission">
                    <span id="delete-permission-text">Supprimer</span>
                    <span id="delete-permission-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Variables globales
    let permissionToReject = null;
    let permissionToDelete = null;
    let table;
    
    // Initialiser DataTable
    function initializeDataTable() {
        if ($.fn.DataTable.isDataTable('#permissions-table')) {
            table.destroy();
            $('#permissions-table tbody').empty();
        }
        
        table = $('#permissions-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('authorizations.employee-permissions.index') }}",
                data: function (d) {
                    d.employee_id = $('#employee_filter').val();
                    d.status = $('#status_filter').val();
                    d.date = $('#date_filter').val();
                }
            },
            columns: [
                { 
                    data: 'employee_name',
                    name: 'employee.first_name',
                    width: '10%'
                },
                { 
                    data: 'date_formatted',
                    name: 'date',
                    width: '8%'
                },
                { 
                    data: 'time_range',
                    name: 'start_time',
                    width: '10%'
                },
                { 
                    data: 'duration_formatted',
                    name: 'duration_minutes',
                    width: '8%'
                },
                { 
                    data: 'raison',
                    name: 'raison',
                    width: '20%'
                },
                { 
                    data: 'status_badge',
                    name: 'status',
                    width: '10%'
                },
                { 
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    width: '14%'
                }
            ],
            language: {
                url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json"
            },
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tous"]],
            order: [[1, 'desc']],
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
    }
    
    // Initialiser la table
    initializeDataTable();
    
    // Appliquer les filtres
    $('#apply_filters').on('click', function() {
        table.ajax.reload();
    });
    
    // Réinitialiser les filtres
    $('#reset_filters').on('click', function() {
        $('#employee_filter').val('');
        $('#status_filter').val('');
        $('#date_filter').val('');
        table.ajax.reload();
    });
    
    // Rafraîchir la table
    $('#refresh-button').on('click', function() {
        table.ajax.reload();
        showSweetAlert('success', 'Actualisation', 'Table actualisée avec succès', 2000);
    });

    // Export PDF
$('#export-button').on('click', function() {
    // Récupérer les filtres actuels
    const filters = {
        employee_id: $('#employee_filter').val(),
        status: $('#status_filter').val(),
        date: $('#date_filter').val(),
        type: 'pdf'
    };
    
    // Construire l'URL avec les filtres
    let url = "{{ route('authorizations.employee-permissions.export') }}?";
    let params = [];
    for (const key in filters) {
        if (filters[key]) {
            params.push(key + '=' + encodeURIComponent(filters[key]));
        }
    }
    url += params.join('&');
    
    // Ouvrir dans une nouvelle fenêtre pour télécharger le PDF
    window.open(url, '_blank');
    
    // Message d'information
    showSweetAlert('info', 'Export PDF', 'Génération du PDF en cours...');
});
    
    // Calcul automatique de la durée
    $('#start_time, #end_time').on('change', function() {
        calculateDuration();
    });
    
    $('#edit_start_time, #edit_end_time').on('change', function() {
        calculateEditDuration();
    });
    
    function calculateDuration() {
        const start = $('#start_time').val();
        const end = $('#end_time').val();
        
        if (start && end) {
            const startTime = new Date(`2000-01-01T${start}`);
            const endTime = new Date(`2000-01-01T${end}`);
            const duration = (endTime - startTime) / (1000 * 60); // en minutes
            $('#duration_minutes').val(duration > 0 ? duration : '');
        }
    }
    
    function calculateEditDuration() {
        const start = $('#edit_start_time').val();
        const end = $('#edit_end_time').val();
        
        if (start && end) {
            const startTime = new Date(`2000-01-01T${start}`);
            const endTime = new Date(`2000-01-01T${end}`);
            const duration = (endTime - startTime) / (1000 * 60); // en minutes
            $('#edit_duration_minutes').val(duration > 0 ? duration : '');
        }
    }
    
    // Gestion de la création de permission
    $('#createPermissionForm').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $('#submit-create-permission');
        const spinner = $('#create-permission-spinner');
        const text = $('#create-permission-text');
        
        submitBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        text.text('Création...');
        
        // Réinitialiser les erreurs
        $('.invalid-feedback').text('');
        $('.form-control').removeClass('is-invalid');
        
        $.ajax({
            url: "{{ route('authorizations.employee-permissions.store') }}",
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#createPermissionModal').modal('hide');
                    $('#createPermissionForm')[0].reset();
                    table.ajax.reload();
                    showSweetAlert('success', 'Succès', 'Permission créée avec succès');
                } else {
                    showSweetAlert('error', 'Erreur', response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(function(key) {
                        $(`#${key}-error`).text(errors[key][0]);
                        $(`#${key}`).addClass('is-invalid');
                    });
                    showSweetAlert('error', 'Erreur de validation', 'Veuillez corriger les erreurs du formulaire');
                } else {
                    showSweetAlert('error', 'Erreur', 'Une erreur est survenue');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                spinner.addClass('d-none');
                text.text('Créer');
            }
        });
    });
    
    // Gestion de l'édition de permission
    $(document).on('click', '.edit-btn', function() {
        const permissionId = $(this).data('id');
        
        $.ajax({
            url: "{{ route('authorizations.employee-permissions.index') }}/" + permissionId,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const permission = response.data;
                    // schedule = JSON data from backend
                    function formatForDateInput(isoDate) {
                         return new Date(isoDate.split('.')[0] + 'Z').toISOString().slice(0, 10);
                    }

                    $('#edit_permission_id').val(permission.id);
                    $('#edit_employee_id').val(permission.employee_id);
                    $('#edit_date_debut').val(formatForDateInput(permission.date_debut || permission.date));
                    $('#edit_date_fin').val(formatForDateInput(permission.date_fin || permission.date_debut || permission.date));
                    $('#edit_start_time').val(permission.start_time);
                    $('#edit_end_time').val(permission.end_time);
                    $('#edit_duration_minutes').val(permission.duration_minutes);
                    $('#edit_raison').val(permission.raison);
                    
                    $('#editPermissionModal').modal('show');
                }
            },
            error: function(xhr) {
                if (xhr.status === 404) {
                    showSweetAlert('error', 'Erreur', 'Permission non trouvée');
                } else {
                    showSweetAlert('error', 'Erreur', 'Erreur lors du chargement de la permission');
                }
            }
        });
    });
    
    $('#editPermissionForm').on('submit', function(e) {
        e.preventDefault();
        
        const permissionId = $('#edit_permission_id').val();
        const submitBtn = $('#submit-edit-permission');
        const spinner = $('#edit-permission-spinner');
        const text = $('#edit-permission-text');
        
        submitBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        text.text('Enregistrement...');
        
        // Réinitialiser les erreurs
        $('.invalid-feedback').text('');
        $('.form-control').removeClass('is-invalid');
        
        $.ajax({
            url: "{{ route('authorizations.employee-permissions.index') }}/" + permissionId,
            type: 'POST', // Laravel n'accepte pas PUT par défaut avec AJAX, utiliser POST avec _method
            data: $(this).serialize() + '&_method=PUT',
            success: function(response) {
                if (response.success) {
                    $('#editPermissionModal').modal('hide');
                    table.ajax.reload();
                    showSweetAlert('success', 'Succès', 'Permission modifiée avec succès');
                } else {
                    showSweetAlert('error', 'Erreur', response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(function(key) {
                        $(`#edit_${key}-error`).text(errors[key][0]);
                        $(`#edit_${key}`).addClass('is-invalid');
                    });
                    showSweetAlert('error', 'Erreur de validation', 'Veuillez corriger les erreurs du formulaire');
                } else {
                    showSweetAlert('error', 'Erreur', 'Une erreur est survenue');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                spinner.addClass('d-none');
                text.text('Enregistrer');
            }
        });
    });
    
    // Gestion de l'approbation
    $(document).on('click', '.approve-btn', function() {
        const permissionId = $(this).data('id');
        
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Voulez-vous approuver cette permission ?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Oui, approuver',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('authorizations.employee-permissions.index') }}/" + permissionId + "/approve",
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            showSweetAlert('success', 'Succès', 'Permission approuvée avec succès');
                        } else {
                            showSweetAlert('error', 'Erreur', response.message);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 403) {
                            showSweetAlert('error', 'Erreur', 'Non autorisé');
                        } else {
                            showSweetAlert('error', 'Erreur', 'Erreur lors de l\'approbation');
                        }
                    }
                });
            }
        });
    });
    
    // Gestion du rejet
    $(document).on('click', '.reject-btn', function() {
        permissionToReject = $(this).data('id');
        $('#rejectPermissionModal').modal('show');
    });
    
    $('#confirm-reject-permission').on('click', function() {
        const rejectBtn = $(this);
        const spinner = $('#reject-permission-spinner');
        const text = $('#reject-permission-text');
        const rejectionReason = $('#rejection_reason').val();
        
        rejectBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        text.text('Rejet en cours...');
        
        $.ajax({
            url: "{{ route('authorizations.employee-permissions.index') }}/" + permissionToReject + "/reject",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                rejection_reason: rejectionReason
            },
            success: function(response) {
                if (response.success) {
                    $('#rejectPermissionModal').modal('hide');
                    $('#rejection_reason').val('');
                    table.ajax.reload();
                    showSweetAlert('success', 'Succès', 'Permission rejetée avec succès');
                } else {
                    showSweetAlert('error', 'Erreur', response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 403) {
                    showSweetAlert('error', 'Erreur', 'Non autorisé');
                } else {
                    showSweetAlert('error', 'Erreur', 'Erreur lors du rejet');
                }
            },
            complete: function() {
                rejectBtn.prop('disabled', false);
                spinner.addClass('d-none');
                text.text('Rejeter');
                permissionToReject = null;
            }
        });
    });
    
    // Gestion de la suppression
    $(document).on('click', '.delete-btn', function() {
        permissionToDelete = $(this).data('id');
        
        // Récupérer les informations de la permission
        $.ajax({
            url: "{{ route('authorizations.employee-permissions.index') }}/" + permissionToDelete,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const permission = response.data;
                    $('#delete-permission-employee').text(
                        `${permission.employee.first_name} ${permission.employee.last_name}`
                    );
                    var dStart = permission.date_debut || permission.date;
                    var dEnd = permission.date_fin || permission.date_debut || permission.date;
                    var startStr = dStart ? new Date(dStart).toLocaleDateString('fr-FR') : '';
                    var endStr = dEnd ? new Date(dEnd).toLocaleDateString('fr-FR') : startStr;
                    $('#delete-permission-date').text(
                        startStr === endStr ? startStr : (startStr + ' → ' + endStr)
                    );
                    $('#delete-permission-raison').text(permission.raison);
                    $('#deletePermissionModal').modal('show');
                }
            },
            error: function(xhr) {
                if (xhr.status === 404) {
                    showSweetAlert('error', 'Erreur', 'Permission non trouvée');
                } else {
                    showSweetAlert('error', 'Erreur', 'Erreur lors du chargement des informations');
                }
            }
        });
    });
    
    $('#confirm-delete-permission').on('click', function() {
        const deleteBtn = $(this);
        const spinner = $('#delete-permission-spinner');
        const text = $('#delete-permission-text');
        
        deleteBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        text.text('Suppression...');
        
        $.ajax({
            url: "{{ route('authorizations.employee-permissions.index') }}/" + permissionToDelete,
            type: 'POST', // Laravel n'accepte pas DELETE par défaut avec AJAX, utiliser POST avec _method
            data: {
                _token: "{{ csrf_token() }}",
                _method: 'DELETE'
            },
            success: function(response) {
                if (response.success) {
                    $('#deletePermissionModal').modal('hide');
                    table.ajax.reload();
                    showSweetAlert('success', 'Succès', 'Permission supprimée avec succès');
                } else {
                    showSweetAlert('error', 'Erreur', response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 403) {
                    showSweetAlert('error', 'Erreur', 'Non autorisé');
                } else {
                    showSweetAlert('error', 'Erreur', 'Erreur lors de la suppression');
                }
            },
            complete: function() {
                deleteBtn.prop('disabled', false);
                spinner.addClass('d-none');
                text.text('Supprimer');
                permissionToDelete = null;
            }
        });
    });
    
    // Fonction pour afficher des notifications SweetAlert
    function showSweetAlert(icon, title, text, timer = null) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: timer || 3000,
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
    
    // Initialisation des tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Actualisation automatique toutes les 5 minutes
    setInterval(function() {
        table.ajax.reload(null, false); // false pour garder la pagination
    }, 300000); // 5 minutes
    
    // Réinitialiser le formulaire de création quand le modal est fermé
    $('#createPermissionModal').on('hidden.bs.modal', function() {
        $('#createPermissionForm')[0].reset();
        $('.invalid-feedback').text('');
        $('.form-control').removeClass('is-invalid');
    });
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
    .stat-card {
        transition: transform 0.3s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .form-text {
        font-size: 0.85em;
        color: #6c757d;
    }
</style>
@endsection