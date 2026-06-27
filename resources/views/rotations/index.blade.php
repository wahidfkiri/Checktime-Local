@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Horaires Rotatifs</h3>
                        <p class="text-subtitle text-muted">Gestion des rotations 24h/48h, etc.</p>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('dashboard') }}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{ url('plannings.index') }}">Plannings</a>
                                </li>
                                <li class="breadcrumb-item active">Rotations</li>
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
                                    <label for="employee_filter">Employé</label>
                                    <input type="text" class="form-control" id="employee_filter" placeholder="Nom ou matricule...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="type_filter">Type</label>
                                    <select class="form-control" id="type_filter">
                                        <option value="">Tous</option>
                                        <option value="24_48">24h/48h</option>
                                        <option value="24_72">24h/72h</option>
                                        <option value="12_12">12h/12h</option>
                                        <option value="custom">Personnalisé</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="date_filter">Date</label>
                                    <input type="date" class="form-control" id="date_filter">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group text-start">
                                    <label class="form-label d-block" style="margin-bottom:0px;">&nbsp;</label>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-success" id="create-rotation-button" data-bs-toggle="modal" data-bs-target="#createRotationModal">
                                            <i class="bi bi-plus-circle me-1"></i> Nouvelle rotation
                                        </button>
                                        <!-- <button type="button" class="btn btn-primary" id="generate-rotations-button">
                                            <i class="bi bi-arrow-repeat me-1"></i> Générer rotations
                                        </button> -->
                                        <button type="button" class="btn btn-secondary" id="reset_filters">
                                            <i class="bi bi-x-circle me-1"></i> Réinitialiser
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="rotations-table" class="table table-striped table-hover dt-responsive nowrap" style="width:100%">
                               <thead>
    <tr>
        <th>Employé</th>
        <th>Département</th>
        <th>Zone</th> <!-- Ajoutez cette colonne -->
        <th>Période</th>
        <th>Durée</th>
        <th>Type</th>
        <th>Récurrence</th>
        <th>Statut</th>
        <th>Actions</th>
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
<!-- Modal d'édition de rotation -->
<div class="modal fade" id="editRotationModal" tabindex="-1" aria-labelledby="editRotationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRotationModalLabel">Modifier la rotation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editRotationForm">
                <input type="hidden" id="edit_rotation_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_employee_id" class="form-label">Employé <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_employee_id" name="employee_id" required>
                                    <option value="">Sélectionner un employé</option>
                                    @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">
                                        {{ $employee->matricule }} - {{ $employee->full_name }}
                                    </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="edit-employee_id-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_rotation_type" class="form-label">Type de rotation <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_rotation_type" name="rotation_type" required>
                                    <option value="">Sélectionner un type</option>
                                    <option value="24_48">24h travail / 48h repos</option>
                                    <option value="24_72">24h travail / 72h repos</option>
                                    <option value="12_12">12h travail / 12h repos</option>
                                    <option value="custom">Personnalisé</option>
                                </select>
                                <div class="invalid-feedback" id="edit-rotation_type-error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_start_datetime" class="form-label">Début <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="edit_start_datetime" name="start_datetime" required>
                                <div class="invalid-feedback" id="edit-start_datetime-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_end_datetime" class="form-label">Fin <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="edit_end_datetime" name="end_datetime" required>
                                <div class="invalid-feedback" id="edit-end_datetime-error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="edit_custom_hours_section" style="display: none;">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_work_hours" class="form-label">Heures de travail <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="edit_work_hours" name="work_hours" min="1" max="168" value="24">
                                <div class="invalid-feedback" id="edit-work_hours-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_rest_hours" class="form-label">Heures de repos <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="edit_rest_hours" name="rest_hours" min="1" max="168" value="48">
                                <div class="invalid-feedback" id="edit-rest_hours-error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="edit_is_recurring" name="is_recurring">
                                    <label class="form-check-label" for="edit_is_recurring">Rotation récurrente</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3" id="edit_recurrence_end_date_section" style="display: none;">
                                <label for="edit_recurrence_end_date" class="form-label">Fin de récurrence</label>
                                <input type="date" class="form-control" id="edit_recurrence_end_date" name="recurrence_end_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="edit_description" class="form-label">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                    <label class="form-check-label" for="edit_is_active">Actif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-edit-rotation">
                        <span id="edit-rotation-text">Enregistrer</span>
                        <span id="edit-rotation-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Styles supplémentaires */
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
    .form-check.form-switch {
        padding-left: 3.5em;
    }
    .form-check-input:checked {
        background-color: #198754;
        border-color: #198754;
    }
    .invalid-feedback {
        display: block;
    }
</style>
<!-- Modal création rotation -->
<div class="modal fade" id="createRotationModal" tabindex="-1" aria-labelledby="createRotationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createRotationModalLabel">Créer une nouvelle rotation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createRotationForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee_id" class="form-label">Employé <span class="text-danger">*</span></label>
                                <select class="form-control" id="employee_id" name="employee_id" required>
                                    <option value="">Sélectionner un employé</option>
                                    @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">
                                        {{ $employee->code }} - {{ $employee->first_name }} {{ $employee->last_name }}
                                    </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="employee_id-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rotation_type" class="form-label">Type de rotation <span class="text-danger">*</span></label>
                                <select class="form-control" id="rotation_type" name="rotation_type" required>
                                    <option value="">Sélectionner un type</option>
                                    <option value="24_48">24h travail / 48h repos</option>
                                    <option value="24_72">24h travail / 72h repos</option>
                                    <option value="12_12">12h travail / 12h repos</option>
                                    <option value="custom">Personnalisé</option>
                                </select>
                                <div class="invalid-feedback" id="rotation_type-error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_datetime" class="form-label">Début <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="start_datetime" name="start_datetime" required>
                                <div class="invalid-feedback" id="start_datetime-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_datetime" class="form-label">Fin <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="end_datetime" name="end_datetime" required>
                                <div class="invalid-feedback" id="end_datetime-error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="custom_hours_section" style="display: none;">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="work_hours" class="form-label">Heures de travail <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="work_hours" name="work_hours" min="1" max="168" value="24">
                                <div class="invalid-feedback" id="work_hours-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rest_hours" class="form-label">Heures de repos <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="rest_hours" name="rest_hours" min="1" max="168" value="48">
                                <div class="invalid-feedback" id="rest_hours-error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring">
                                    <label class="form-check-label" for="is_recurring">Rotation récurrente</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3" id="recurrence_end_date_section" style="display: none;">
                                <label for="recurrence_end_date" class="form-label">Fin de récurrence</label>
                                <input type="date" class="form-control" id="recurrence_end_date" name="recurrence_end_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">Actif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-create-rotation">
                        <span id="create-rotation-text">Créer</span>
                        <span id="create-rotation-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal édition rotation (similaire à création) -->
<!-- Modal suppression rotation -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Initialiser DataTable
    var table = $('#rotations-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('rotations.index') }}",
            data: function (d) {
                d.employee_filter = $('#employee_filter').val();
                d.type_filter = $('#type_filter').val();
                d.date_filter = $('#date_filter').val();
            }
        },
        columns: [
    { data: 'employee_name', name: 'employee_name' },
    { data: 'department', name: 'department' },
    { data: 'zone', name: 'zone' }, // Ajoutez cette colonne
    { data: 'formatted_period', name: 'formatted_period' },
    { data: 'duration', name: 'duration' },
    { data: 'rotation_type', name: 'rotation_type' },
    { data: 'recurring_badge', name: 'recurring_badge' },
    { data: 'status_badge', name: 'status_badge' },
    { data: 'actions', name: 'actions' }
],
        language: { url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json" },
        pageLength: 25,
        responsive: true
    });

    // Afficher/masquer les champs personnalisés
    $('#rotation_type').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#custom_hours_section').show();
        } else {
            $('#custom_hours_section').hide();
            // Pré-remplir selon le type
            switch($(this).val()) {
                case '24_48':
                    $('#work_hours').val(24);
                    $('#rest_hours').val(48);
                    break;
                case '24_72':
                    $('#work_hours').val(24);
                    $('#rest_hours').val(72);
                    break;
                case '12_12':
                    $('#work_hours').val(12);
                    $('#rest_hours').val(12);
                    break;
            }
        }
    });

    // Afficher/masquer la date de fin de récurrence
    $('#is_recurring').on('change', function() {
        if ($(this).is(':checked')) {
            $('#recurrence_end_date_section').show();
        } else {
            $('#recurrence_end_date_section').hide();
        }
    });

    // Générer les prochaines rotations
    $('#generate-rotations-button').on('click', function() {
        Swal.fire({
            title: 'Générer les prochaines rotations ?',
            text: "Cela créera automatiquement les rotations récurrentes suivantes.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Oui, générer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('rotations.generate') }}",
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Succès', response.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Erreur', response.message, 'error');
                        }
                    }
                });
            }
        });
    });
// Gestion du formulaire de création
$('#createRotationForm').on('submit', function(e) {
    e.preventDefault();
    
    // Récupérer les données du formulaire
    var formData = {
        employee_id: $('#employee_id').val(),
        rotation_type: $('#rotation_type').val(),
        start_datetime: $('#start_datetime').val(),
        end_datetime: $('#end_datetime').val(),
        work_hours: $('#work_hours').val() || 24,
        rest_hours: $('#rest_hours').val() || 48,
        is_recurring: $('#is_recurring').is(':checked') ? 1 : 0,
        recurrence_end_date: $('#is_recurring').is(':checked') ? $('#recurrence_end_date').val() : null,
        description: $('#description').val(),
        is_active: $('#is_active').is(':checked') ? 1 : 0,
        _token: "{{ csrf_token() }}"
    };
    
    // Désactiver le bouton et afficher le spinner
    $('#submit-create-rotation').prop('disabled', true);
    $('#create-rotation-text').addClass('d-none');
    $('#create-rotation-spinner').removeClass('d-none');
    
    // Réinitialiser les erreurs
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    
    // Envoyer la requête AJAX
    $.ajax({
        url: "{{ route('rotations.store') }}",
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                // Fermer la modal
                $('#createRotationModal').modal('hide');
                
                // Réinitialiser le formulaire
                $('#createRotationForm')[0].reset();
                $('#is_active').prop('checked', true);
                $('#custom_hours_section').hide();
                $('#recurrence_end_date_section').hide();
                
                // Afficher un message de succès
                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: response.message || 'Rotation créée avec succès',
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
                    var input = $('#' + key);
                    if (input.length) {
                        input.addClass('is-invalid');
                        input.next('.invalid-feedback').text(value[0]);
                    } else {
                        // Pour les champs avec des IDs différents
                        if (key.includes('.')) {
                            var field = key.split('.')[1];
                            input = $('#' + field);
                            if (input.length) {
                                input.addClass('is-invalid');
                                input.next('.invalid-feedback').text(value[0]);
                            }
                        }
                    }
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
            $('#submit-create-rotation').prop('disabled', false);
            $('#create-rotation-text').removeClass('d-none');
            $('#create-rotation-spinner').addClass('d-none');
        }
    });
});

// Ouvrir la modal d'édition
$('body').on('click', '.edit-rotation-btn', function() {
    const rotationId = $(this).data('id');
    
    $.ajax({
        url: "{{ url('rotations') }}/" + rotationId + "/edit",
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const rotation = response.data;
                
                // Remplir le formulaire d'édition
                $('#edit_rotation_id').val(rotation.id);
                $('#edit_employee_id').val(rotation.employee_id);
                $('#edit_rotation_type').val(rotation.rotation_type);
                $('#edit_start_datetime').val(rotation.start_datetime.substring(0, 16));
                $('#edit_end_datetime').val(rotation.end_datetime.substring(0, 16));
                $('#edit_work_hours').val(rotation.work_hours);
                $('#edit_rest_hours').val(rotation.rest_hours);
                $('#edit_is_recurring').prop('checked', rotation.is_recurring);
                $('#edit_recurrence_end_date').val(rotation.recurrence_end_date);
                $('#edit_description').val(rotation.description);
                $('#edit_is_active').prop('checked', rotation.is_active);
                
                // Afficher/masquer les sections
                if (rotation.rotation_type === 'custom') {
                    $('#edit_custom_hours_section').show();
                } else {
                    $('#edit_custom_hours_section').hide();
                }
                
                if (rotation.is_recurring) {
                    $('#edit_recurrence_end_date_section').show();
                } else {
                    $('#edit_recurrence_end_date_section').hide();
                }
                
                // Réinitialiser les erreurs
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');
                
                // Afficher la modal
                $('#editRotationModal').modal('show');
            } else {
                showSweetAlert('error', 'Erreur', response.message);
            }
        },
        error: function(xhr) {
            showSweetAlert('error', 'Erreur', 
                'Erreur lors du chargement des données. ' + 
                (xhr.responseJSON?.message || 'Veuillez réessayer.')
            );
        }
    });
});

// Gestion du formulaire d'édition
$('#editRotationForm').on('submit', function(e) {
    e.preventDefault();
    
    const rotationId = $('#edit_rotation_id').val();
    
    // Récupérer les données du formulaire
    var formData = {
        employee_id: $('#edit_employee_id').val(),
        rotation_type: $('#edit_rotation_type').val(),
        start_datetime: $('#edit_start_datetime').val(),
        end_datetime: $('#edit_end_datetime').val(),
        work_hours: $('#edit_work_hours').val() || 24,
        rest_hours: $('#edit_rest_hours').val() || 48,
        is_recurring: $('#edit_is_recurring').is(':checked') ? 1 : 0,
        recurrence_end_date: $('#edit_is_recurring').is(':checked') ? $('#edit_recurrence_end_date').val() : null,
        description: $('#edit_description').val(),
        is_active: $('#edit_is_active').is(':checked') ? 1 : 0,
        _token: "{{ csrf_token() }}"
    };
    
    // Désactiver le bouton et afficher le spinner
    $('#submit-edit-rotation').prop('disabled', true);
    $('#edit-rotation-text').addClass('d-none');
    $('#edit-rotation-spinner').removeClass('d-none');
    
    // Réinitialiser les erreurs
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    
    // Envoyer la requête AJAX
    $.ajax({
        url: "{{ url('rotations') }}/" + rotationId,
        type: 'PUT',
        data: formData,
        success: function(response) {
            if (response.success) {
                // Fermer la modal
                $('#editRotationModal').modal('hide');
                
                // Afficher un message de succès
                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: response.message || 'Rotation modifiée avec succès',
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
                    var input = $('#edit_' + key);
                    if (input.length) {
                        input.addClass('is-invalid');
                        input.next('.invalid-feedback').text(value[0]);
                    } else {
                        // Pour les champs avec des IDs différents
                        if (key.includes('.')) {
                            var field = key.split('.')[1];
                            input = $('#edit_' + field);
                            if (input.length) {
                                input.addClass('is-invalid');
                                input.next('.invalid-feedback').text(value[0]);
                            }
                        }
                    }
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
            $('#submit-edit-rotation').prop('disabled', false);
            $('#edit-rotation-text').removeClass('d-none');
            $('#edit-rotation-spinner').addClass('d-none');
        }
    });
});

// Ouvrir la modal de suppression
$('body').on('click', '.delete-rotation-btn', function() {
    const rotationId = $(this).data('id');
    const employeeName = $(this).data('employee');
    
    Swal.fire({
        title: 'Confirmer la suppression',
        html: `Êtes-vous sûr de vouloir supprimer la rotation de <strong>${employeeName}</strong> ?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            // Désactiver le bouton et afficher le spinner
            $(this).prop('disabled', true);
            $(this).html('<span class="spinner-border spinner-border-sm" role="status"></span>');
            
            $.ajax({
                url: "{{ url('rotations') }}/" + rotationId,
                type: 'DELETE',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        showSweetAlert('success', 'Succès', response.message);
                        table.ajax.reload();
                    } else {
                        showSweetAlert('error', 'Erreur', response.message);
                    }
                },
                error: function(xhr) {
                    showSweetAlert('error', 'Erreur', 
                        'Erreur lors de la suppression. ' + 
                        (xhr.responseJSON?.message || 'Veuillez réessayer.')
                    );
                },
                complete: function() {
                    // Réactiver le bouton
                    $('.delete-rotation-btn[data-id="' + rotationId + '"]')
                        .prop('disabled', false)
                        .html('<i class="bi bi-trash"></i>');
                }
            });
        }
    });
});

// Appliquer les filtres
function applyFilters() {
    table.ajax.reload();
}

// Événements pour les filtres
$('#employee_filter, #type_filter, #date_filter').on('change keyup', function() {
    applyFilters();
});

// Réinitialiser les filtres
$('#reset_filters').on('click', function() {
    $('#employee_filter').val('');
    $('#type_filter').val('');
    $('#date_filter').val('');
    applyFilters();
});

// Afficher/masquer les champs personnalisés dans l'édition
$('#edit_rotation_type').on('change', function() {
    if ($(this).val() === 'custom') {
        $('#edit_custom_hours_section').show();
    } else {
        $('#edit_custom_hours_section').hide();
        // Pré-remplir selon le type
        switch($(this).val()) {
            case '24_48':
                $('#edit_work_hours').val(24);
                $('#edit_rest_hours').val(48);
                break;
            case '24_72':
                $('#edit_work_hours').val(24);
                $('#edit_rest_hours').val(72);
                break;
            case '12_12':
                $('#edit_work_hours').val(12);
                $('#edit_rest_hours').val(12);
                break;
        }
    }
});

// Afficher/masquer la date de fin de récurrence dans l'édition
$('#edit_is_recurring').on('change', function() {
    if ($(this).is(':checked')) {
        $('#edit_recurrence_end_date_section').show();
    } else {
        $('#edit_recurrence_end_date_section').hide();
    }
});

// Réinitialiser le formulaire quand la modal de création se ferme
$('#createRotationModal').on('hidden.bs.modal', function() {
    $('#createRotationForm')[0].reset();
    $('#is_active').prop('checked', true);
    $('#custom_hours_section').hide();
    $('#recurrence_end_date_section').hide();
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    $('#submit-create-rotation').prop('disabled', false);
    $('#create-rotation-text').removeClass('d-none');
    $('#create-rotation-spinner').addClass('d-none');
});

// Réinitialiser quand la modal d'édition se ferme
$('#editRotationModal').on('hidden.bs.modal', function() {
    $('#submit-edit-rotation').prop('disabled', false);
    $('#edit-rotation-text').removeClass('d-none');
    $('#edit-rotation-spinner').addClass('d-none');
});

// Fonction pour afficher les alertes
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

// Calculer automatiquement l'heure de fin basée sur les heures de travail
$('#start_datetime, #work_hours').on('change', function() {
    const startDatetime = $('#start_datetime').val();
    const workHours = parseInt($('#work_hours').val()) || 24;
    
    if (startDatetime) {
        const startDate = new Date(startDatetime);
        const endDate = new Date(startDate.getTime() + (workHours * 60 * 60 * 1000));
        
        // Formater pour l'input datetime-local (YYYY-MM-DDTHH:mm)
        const endDatetime = endDate.toISOString().slice(0, 16);
        $('#end_datetime').val(endDatetime);
    }
});

// Même chose pour l'édition
$('#edit_start_datetime, #edit_work_hours').on('change', function() {
    const startDatetime = $('#edit_start_datetime').val();
    const workHours = parseInt($('#edit_work_hours').val()) || 24;
    
    if (startDatetime) {
        const startDate = new Date(startDatetime);
        const endDate = new Date(startDate.getTime() + (workHours * 60 * 60 * 1000));
        
        // Formater pour l'input datetime-local
        const endDatetime = endDate.toISOString().slice(0, 16);
        $('#edit_end_datetime').val(endDatetime);
    }
});

// Set default dates
const now = new Date();
const defaultStart = now.toISOString().slice(0, 16);
const defaultEnd = new Date(now.getTime() + (24 * 60 * 60 * 1000)).toISOString().slice(0, 16);

$('#start_datetime').val(defaultStart);
$('#end_datetime').val(defaultEnd);
});
</script>
@endsection