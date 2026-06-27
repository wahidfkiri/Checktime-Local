@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Gestion des Missions</h3>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('home') }}">Tableau de board</a>
                                </li>
                                <li class="breadcrumb-item active">Missions</li>
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
                                    <label for="department_filter">Département</label>
                                    <select class="form-control" id="department_filter">
                                        <option value="">Tous les départements</option>
                                        @foreach($departments ?? [] as $department)
                                            <option value="{{ $department->id ?? $department }}">
                                                {{ $department->name ?? $department }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="start_date_filter">Date début</label>
                                    <input type="date" class="form-control" id="start_date_filter">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="end_date_filter">Date fin</label>
                                    <input type="date" class="form-control" id="end_date_filter">
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
                                    <button type="button" class="btn btn-success" id="create-mission-button" data-bs-toggle="modal" data-bs-target="#createMissionModal">
                                        <i class="bi bi-plus-circle me-1"></i> Nouvelle Mission
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
                            <table id="missions-table" class="table table-striped table-hover dt-responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Référence</th>
                                        <th>Employé</th>
                                        <th>Département</th>
                                        <th>Titre</th>
                                        <th>Destination</th>
                                        <th>Période</th>
                                        <th>Durée</th>
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

<!-- Modal de création de mission -->
<div class="modal fade" id="createMissionModal" tabindex="-1" aria-labelledby="createMissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createMissionModalLabel">Nouvelle Mission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createMissionForm">
                @csrf
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="employee_id" class="form-label">Employé <span class="text-danger">*</span></label>
                            <select class="form-control" id="employee_id" name="employee_id" required>
                                <option value="">Sélectionner un employé</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="employee_id-error"></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="reference" class="form-label">Référence <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="reference" name="reference" required placeholder="MISS-20250318-001" readonly>
                            <div class="form-text">Générée automatiquement</div>
                            <div class="invalid-feedback" id="reference-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required maxlength="255">
                            <div class="invalid-feedback" id="title-error"></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2" maxlength="1000"></textarea>
                            <div class="invalid-feedback" id="description-error"></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="destination" class="form-label">Destination</label>
                            <input type="text" class="form-control" id="destination" name="destination" placeholder="Ville, lieu">
                            <div class="invalid-feedback" id="destination-error"></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Date de début <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                            <div class="invalid-feedback" id="start_date-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="end_date" class="form-label">Date de fin <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                            <div class="invalid-feedback" id="end_date-error"></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-1"></i>
                                <span id="duration-preview">Durée: Non définie</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-create-mission">
                        <span id="create-mission-text">Créer</span>
                        <span id="create-mission-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal d'édition de mission -->
<div class="modal fade" id="editMissionModal" tabindex="-1" aria-labelledby="editMissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMissionModalLabel">Modifier la Mission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editMissionForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_mission_id" name="id">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="edit_employee_id" class="form-label">Employé <span class="text-danger">*</span></label>
                            <select class="form-control" id="edit_employee_id" name="employee_id" required>
                                <option value="">Sélectionner un employé</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="edit_employee_id-error"></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_reference" class="form-label">Référence</label>
                            <input type="text" class="form-control" id="edit_reference" name="reference" readonly>
                            <div class="invalid-feedback" id="edit_reference-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_title" class="form-label">Titre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_title" name="title" required maxlength="255">
                            <div class="invalid-feedback" id="edit_title-error"></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="2" maxlength="1000"></textarea>
                            <div class="invalid-feedback" id="edit_description-error"></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_destination" class="form-label">Destination</label>
                            <input type="text" class="form-control" id="edit_destination" name="destination" placeholder="Ville, lieu">
                            <div class="invalid-feedback" id="edit_destination-error"></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_start_date" class="form-label">Date de début <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="edit_start_date" name="start_date" required>
                            <div class="invalid-feedback" id="edit_start_date-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_end_date" class="form-label">Date de fin <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="edit_end_date" name="end_date" required>
                            <div class="invalid-feedback" id="edit_end_date-error"></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-1"></i>
                                <span id="edit-duration-preview">Durée: Non définie</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-edit-mission">
                        <span id="edit-mission-text">Enregistrer</span>
                        <span id="edit-mission-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de détails de mission -->
<div class="modal fade" id="viewMissionModal" tabindex="-1" aria-labelledby="viewMissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewMissionModalLabel">Détails de la Mission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th width="40%">Référence:</th>
                                <td><span id="view_reference"></span></td>
                            </tr>
                            <tr>
                                <th>Employé:</th>
                                <td><span id="view_employee"></span></td>
                            </tr>
                            <tr>
                                <th>Département:</th>
                                <td><span id="view_department"></span></td>
                            </tr>
                            <tr>
                                <th>Titre:</th>
                                <td><span id="view_title"></span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th width="40%">Destination:</th>
                                <td><span id="view_destination"></span></td>
                            </tr>
                            <tr>
                                <th>Date début:</th>
                                <td><span id="view_start_date"></span></td>
                            </tr>
                            <tr>
                                <th>Date fin:</th>
                                <td><span id="view_end_date"></span></td>
                            </tr>
                            <tr>
                                <th>Durée:</th>
                                <td><span id="view_duration"></span></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6>Description:</h6>
                        <p id="view_description" class="border p-3 rounded bg-light"></p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6>Informations complémentaires:</h6>
                        <table class="table table-sm">
                            <tr>
                                <th width="20%">Créée le:</th>
                                <td><span id="view_created_at"></span></td>
                                <th width="20%">Par:</th>
                                <td><span id="view_created_by"></span></td>
                            </tr>
                            <tr>
                                <th>Modifiée le:</th>
                                <td><span id="view_updated_at"></span></td>
                                <th></th>
                                <td></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de suppression de mission -->
<div class="modal fade" id="deleteMissionModal" tabindex="-1" aria-labelledby="deleteMissionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteMissionModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette mission ?</p>
                <p><strong>Référence:</strong> <span id="delete-mission-reference"></span></p>
                <p><strong>Employé:</strong> <span id="delete-mission-employee"></span></p>
                <p><strong>Titre:</strong> <span id="delete-mission-title"></span></p>
                <p class="text-danger"><small>Cette action est irréversible.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-mission">
                    <span id="delete-mission-text">Supprimer</span>
                    <span id="delete-mission-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Variables globales
    let missionToDelete = null;
    let table;
    
    // Générer automatiquement la référence
    function generateReference() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const random = Math.floor(Math.random() * 900 + 100);
        return `MISS-${year}${month}${day}-${random}`;
    }
    
    // Mettre la référence générée dans le champ
    $('#reference').val(generateReference());
    
    // Calculer et afficher la durée
    function calculateDuration(start, end, previewElement) {
        if (start && end) {
            const startDate = new Date(start);
            const endDate = new Date(end);
            
            if (endDate >= startDate) {
                const diffTime = endDate - startDate;
                const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                const diffHours = Math.floor((diffTime % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                
                let durationText = 'Durée: ';
                if (diffDays > 0) {
                    durationText += diffDays + ' jour' + (diffDays > 1 ? 's' : '');
                    if (diffHours > 0) {
                        durationText += ' et ' + diffHours + ' heure' + (diffHours > 1 ? 's' : '');
                    }
                } else {
                    durationText += diffHours + ' heure' + (diffHours > 1 ? 's' : '');
                }
                
                $(previewElement).text(durationText);
                return diffDays + (diffHours > 0 ? '.' + Math.floor(diffHours * 100 / 24) : '');
            } else {
                $(previewElement).text('Durée: La date de fin doit être après la date de début');
                return null;
            }
        } else {
            $(previewElement).text('Durée: Non définie');
            return null;
        }
    }
    
    // Écouteurs pour le calcul de durée
    $('#start_date, #end_date').on('change', function() {
        const start = $('#start_date').val();
        const end = $('#end_date').val();
        calculateDuration(start, end, '#duration-preview');
    });
    
    $('#edit_start_date, #edit_end_date').on('change', function() {
        const start = $('#edit_start_date').val();
        const end = $('#edit_end_date').val();
        calculateDuration(start, end, '#edit-duration-preview');
    });
    
    // Initialiser DataTable
    function initializeDataTable() {
        if ($.fn.DataTable.isDataTable('#missions-table')) {
            table.destroy();
            $('#missions-table tbody').empty();
        }
        
        table = $('#missions-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('missions.index') }}",
                data: function (d) {
                    d.employee_id = $('#employee_filter').val();
                    d.department_id = $('#department_filter').val();
                    d.start_date = $('#start_date_filter').val();
                    d.end_date = $('#end_date_filter').val();
                }
            },
            columns: [
                { 
                    data: 'reference',
                    name: 'reference',
                    width: '10%'
                },
                { 
                    data: 'employee_name',
                    name: 'employee.first_name',
                    width: '10%'
                },
                { 
                    data: 'department_name',
                    name: 'department.name',
                    width: '8%',
                    render: function(data) {
                        return data || '-';
                    }
                },
                { 
                    data: 'title',
                    name: 'title',
                    width: '15%'
                },
                { 
                    data: 'destination',
                    name: 'destination',
                    width: '10%',
                    render: function(data) {
                        return data || '-';
                    }
                },
                { 
                    data: 'period',
                    name: 'start_date',
                    width: '12%'
                },
                { 
                    data: 'duration_formatted',
                    name: 'duration_days',
                    width: '8%'
                },
                { 
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    width: '12%'
                }
            ],
            language: {
                url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json"
            },
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tous"]],
            order: [[5, 'desc']],
            responsive: true,
            drawCallback: function(settings) {
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
        $('#department_filter').val('');
        $('#start_date_filter').val('');
        $('#end_date_filter').val('');
        table.ajax.reload();
    });
    
    // Rafraîchir la table
    $('#refresh-button').on('click', function() {
        table.ajax.reload();
        showSweetAlert('success', 'Actualisation', 'Table actualisée avec succès', 2000);
    });
    
    // Gestion de la création de mission
    $('#createMissionForm').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $('#submit-create-mission');
        const spinner = $('#create-mission-spinner');
        const text = $('#create-mission-text');
        
        submitBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        text.text('Création...');
        
        // Réinitialiser les erreurs
        $('.invalid-feedback').text('');
        $('.form-control').removeClass('is-invalid');
        
        $.ajax({
            url: "{{ route('missions.store') }}",
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#createMissionModal').modal('hide');
                    $('#createMissionForm')[0].reset();
                    $('#reference').val(generateReference());
                    table.ajax.reload();
                    showSweetAlert('success', 'Succès', 'Mission créée avec succès');
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
    
    // Gestion de l'affichage des détails
    $(document).on('click', '.view-btn', function() {
        const missionId = $(this).data('id');
        
        $.ajax({
            url: "{{ route('missions.index') }}/" + missionId,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const mission = response.data;
                    
                    $('#view_reference').text(mission.reference);
                    $('#view_employee').text(mission.employee?.first_name + ' ' + mission.employee?.last_name || '-');
                    $('#view_department').text(mission.department?.name || '-');
                    $('#view_title').text(mission.title);
                    $('#view_destination').text(mission.destination || '-');
                    $('#view_start_date').text(new Date(mission.start_date).toLocaleString('fr-FR'));
                    $('#view_end_date').text(new Date(mission.end_date).toLocaleString('fr-FR'));
                    $('#view_duration').text(mission.duration_formatted || '-');
                    $('#view_description').text(mission.description || 'Aucune description');
                    $('#view_created_at').text(new Date(mission.created_at).toLocaleString('fr-FR'));
                    $('#view_updated_at').text(new Date(mission.updated_at).toLocaleString('fr-FR'));
                    $('#view_created_by').text(mission.creator?.name || 'Système');
                    
                    $('#viewMissionModal').modal('show');
                }
            },
            error: function(xhr) {
                if (xhr.status === 404) {
                    showSweetAlert('error', 'Erreur', 'Mission non trouvée');
                } else {
                    showSweetAlert('error', 'Erreur', 'Erreur lors du chargement de la mission');
                }
            }
        });
    });
    
    // Gestion de l'édition de mission
    $(document).on('click', '.edit-btn', function() {
        const missionId = $(this).data('id');
        
        $.ajax({
            url: "{{ route('missions.index') }}/" + missionId,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const mission = response.data;
                    
                    function formatForDateTimeInput(isoDate) {
                        const date = new Date(isoDate);
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');
                        const hours = String(date.getHours()).padStart(2, '0');
                        const minutes = String(date.getMinutes()).padStart(2, '0');
                        return `${year}-${month}-${day}T${hours}:${minutes}`;
                    }

                    $('#edit_mission_id').val(mission.id);
                    $('#edit_employee_id').val(mission.employee_id);
                    $('#edit_reference').val(mission.reference);
                    $('#edit_title').val(mission.title);
                    $('#edit_description').val(mission.description);
                    $('#edit_destination').val(mission.destination);
                    $('#edit_start_date').val(formatForDateTimeInput(mission.start_date));
                    $('#edit_end_date').val(formatForDateTimeInput(mission.end_date));
                    
                    // Calculer et afficher la durée
                    calculateDuration(
                        $('#edit_start_date').val(), 
                        $('#edit_end_date').val(), 
                        '#edit-duration-preview'
                    );
                    
                    $('#editMissionModal').modal('show');
                }
            },
            error: function(xhr) {
                if (xhr.status === 404) {
                    showSweetAlert('error', 'Erreur', 'Mission non trouvée');
                } else {
                    showSweetAlert('error', 'Erreur', 'Erreur lors du chargement de la mission');
                }
            }
        });
    });
    
    $('#editMissionForm').on('submit', function(e) {
        e.preventDefault();
        
        const missionId = $('#edit_mission_id').val();
        const submitBtn = $('#submit-edit-mission');
        const spinner = $('#edit-mission-spinner');
        const text = $('#edit-mission-text');
        
        submitBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        text.text('Enregistrement...');
        
        // Réinitialiser les erreurs
        $('.invalid-feedback').text('');
        $('.form-control').removeClass('is-invalid');
        
        $.ajax({
            url: "{{ route('missions.index') }}/" + missionId,
            type: 'POST',
            data: $(this).serialize() + '&_method=PUT',
            success: function(response) {
                if (response.success) {
                    $('#editMissionModal').modal('hide');
                    table.ajax.reload();
                    showSweetAlert('success', 'Succès', 'Mission modifiée avec succès');
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
    
    // Gestion de la suppression
    $(document).on('click', '.delete-btn', function() {
        missionToDelete = $(this).data('id');
        
        // Récupérer les informations de la mission
        $.ajax({
            url: "{{ route('missions.index') }}/" + missionToDelete,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const mission = response.data;
                    $('#delete-mission-reference').text(mission.reference);
                    $('#delete-mission-employee').text(
                        mission.employee ? mission.employee.first_name + ' ' + mission.employee.last_name : '-'
                    );
                    $('#delete-mission-title').text(mission.title);
                    $('#deleteMissionModal').modal('show');
                }
            },
            error: function(xhr) {
                if (xhr.status === 404) {
                    showSweetAlert('error', 'Erreur', 'Mission non trouvée');
                } else {
                    showSweetAlert('error', 'Erreur', 'Erreur lors du chargement des informations');
                }
            }
        });
    });
    
    $('#confirm-delete-mission').on('click', function() {
        const deleteBtn = $(this);
        const spinner = $('#delete-mission-spinner');
        const text = $('#delete-mission-text');
        
        deleteBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        text.text('Suppression...');
        
        $.ajax({
            url: "{{ route('missions.index') }}/" + missionToDelete,
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                _method: 'DELETE'
            },
            success: function(response) {
                if (response.success) {
                    $('#deleteMissionModal').modal('hide');
                    table.ajax.reload();
                    showSweetAlert('success', 'Succès', 'Mission supprimée avec succès');
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
                missionToDelete = null;
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
        table.ajax.reload(null, false);
    }, 300000);
    
    // Réinitialiser le formulaire de création quand le modal est fermé
    $('#createMissionModal').on('hidden.bs.modal', function() {
        $('#createMissionForm')[0].reset();
        $('#reference').val(generateReference());
        $('.invalid-feedback').text('');
        $('.form-control').removeClass('is-invalid');
        $('#duration-preview').text('Durée: Non définie');
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
    .table-borderless th {
        font-weight: 600;
    }
</style>
@endsection