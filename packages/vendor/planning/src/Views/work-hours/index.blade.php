@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Gestion des horaires</h3>
                        <p class="text-subtitle text-muted">Types d'horaires de travail</p>
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
                                <li class="breadcrumb-item active">Types d'horaires</li>
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
                                    <label for="code_filter">Code</label>
                                    <input type="text" class="form-control" id="code_filter" placeholder="Rechercher par code...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="name_filter">Nom</label>
                                    <input type="text" class="form-control" id="name_filter" placeholder="Rechercher par nom...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="status_filter">Statut</label>
                                    <select class="form-control" id="status_filter">
                                        <option value="">Tous</option>
                                        <option value="1">Actif</option>
                                        <option value="0">Inactif</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group text-start">
                                    <label class="form-label d-block" style="margin-bottom:0px;">&nbsp;</label>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-success" id="create-hour-button" data-bs-toggle="modal" data-bs-target="#createHourModal">
                                            <i class="bi bi-plus-circle me-1"></i> Nouvel horaire
                                        </button>
                                        <button type="button" class="btn btn-primary" id="export-pdf-button">
                                            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
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
                        <div class="table-responsive">
                            <table id="work-hours-table" class="table table-striped table-hover dt-responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <!-- <th width="5%">#</th> -->
                                        <th width="10%">Code</th>
                                        <th width="20%">Nom</th>
                                        <th width="15%">Horaires</th>
                                        <th width="10%">Pause</th>
                                        <th width="10%">Durée</th>
                                        <th width="10%">Statut</th>
                                        <th width="10%">Nuit</th>
                                        <th width="10%">Actions</th>
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

<!-- Modal de création d'horaire -->
<div class="modal fade" id="createHourModal" tabindex="-1" aria-labelledby="createHourModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createHourModalLabel">Créer un nouveau type d'horaire</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createHourForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="hour_code" class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="hour_code" name="code" required maxlength="50">
                                <div class="form-text">Code unique pour identifier cet horaire (ex: NORMAL, MATIN, NUIT)</div>
                                <div class="invalid-feedback" id="code-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="hour_name" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="hour_name" name="name" required maxlength="100">
                                <div class="form-text">Nom complet de l'horaire (ex: Heure normale, Matinée, Nuit)</div>
                                <div class="invalid-feedback" id="name-error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_time" class="form-label">Heure de début <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required value="08:00">
                                <div class="invalid-feedback" id="start_time-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_time" class="form-label">Heure de fin <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required value="16:30">
                                <div class="invalid-feedback" id="end_time-error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="break_minutes" class="form-label">Pause (minutes) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="break_minutes" name="break_minutes" required min="0" max="240" value="60">
                                <div class="form-text">Durée totale des pauses en minutes</div>
                                <div class="invalid-feedback" id="break_minutes-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_overnight" name="is_overnight" value="1">
                                    <label class="form-check-label" for="is_overnight">Horaire de nuit (dépasse minuit)</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                    <label class="form-check-label" for="is_active">Actif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row d-none">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Information:</strong> 
                                <span id="duration_info">Durée calculée: 7.5h (08:00 - 16:30 avec 60min de pause)</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-create-hour">
                        <span id="create-hour-text">Créer</span>
                        <span id="create-hour-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal d'édition d'horaire -->
<div class="modal fade" id="editHourModal" tabindex="-1" aria-labelledby="editHourModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editHourModalLabel">Modifier le type d'horaire</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editHourForm">
                <input type="hidden" id="edit_hour_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_hour_code" class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_hour_code" name="code" required maxlength="50">
                                <div class="form-text">Code unique pour identifier cet horaire</div>
                                <div class="invalid-feedback" id="edit-code-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_hour_name" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_hour_name" name="name" required maxlength="100">
                                <div class="invalid-feedback" id="edit-name-error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_start_time" class="form-label">Heure de début <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                                <div class="invalid-feedback" id="edit-start_time-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_end_time" class="form-label">Heure de fin <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                                <div class="invalid-feedback" id="edit-end_time-error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_break_minutes" class="form-label">Pause (minutes) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="edit_break_minutes" name="break_minutes" required min="0" max="240">
                                <div class="invalid-feedback" id="edit-break_minutes-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" id="edit_is_overnight" name="is_overnight" value="1">
                                    <label class="form-check-label" for="edit_is_overnight">Horaire de nuit</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                                    <label class="form-check-label" for="edit_is_active">Actif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Information:</strong> 
                                <span id="edit_duration_info">Durée calculée: --</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-edit-hour">
                        <span id="edit-hour-text">Enregistrer</span>
                        <span id="edit-hour-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de suppression d'horaire -->
<div class="modal fade" id="deleteHourModal" tabindex="-1" aria-labelledby="deleteHourModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteHourModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce type d'horaire ?</p>
                <p><strong>Code:</strong> <span id="delete-hour-code"></span></p>
                <p><strong>Nom:</strong> <span id="delete-hour-name"></span></p>
                <p class="text-danger"><small>Cette action est irréversible. Vérifiez qu'il n'est pas utilisé dans des plannings.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-hour">
                    <span id="delete-hour-text">Supprimer</span>
                    <span id="delete-hour-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    let hourToDelete = null;
    
    // Initialiser DataTable
    var table = $('#work-hours-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('work-hours.index') }}",
            data: function (d) {
                d.code_filter = $('#code_filter').val();
                d.name_filter = $('#name_filter').val();
                d.status_filter = $('#status_filter').val();
            }
        },
        columns: [
            // { 
            //     data: 'DT_RowIndex',
            //     name: 'DT_RowIndex',
            //     orderable: false,
            //     searchable: false,
            //     width: '5%'
            // },
            { 
                data: 'code',
                name: 'code',
                width: '10%'
            },
            { 
                data: 'name',
                name: 'name',
                width: '20%'
            },
            { 
                data: 'formatted_hours',
                name: 'formatted_hours',
                orderable: false,
                searchable: false,
                width: '15%'
            },
            { 
                data: 'break_minutes',
                name: 'break_minutes',
                render: function(data) {
                    return data + ' min';
                },
                width: '10%'
            },
            { 
                data: 'total_hours',
                name: 'total_hours',
                orderable: false,
                searchable: false,
                width: '10%'
            },
            { 
                data: 'status_badge',
                name: 'status_badge',
                orderable: false,
                searchable: false,
                width: '10%'
            },
            { 
                data: 'is_overnight',
                name: 'is_overnight',
                render: function(data) {
                    return data ? '<i class="bi bi-moon text-primary"></i>' : '<i class="bi bi-sun text-warning"></i>';
                },
                width: '10%'
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false,
                width: '10%'
            }
        ],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json"
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tous"]],
        order: [[2, 'asc']],
        responsive: true,
        drawCallback: function(settings) {
            attachActionButtons();
        }
    });

    // Attacher les événements aux boutons d'action
    function attachActionButtons() {
        // Bouton d'édition
        $('.edit-hour-btn').off('click').on('click', function() {
            const hourId = $(this).data('id');
            const hourName = $(this).data('name');
            const hourCode = $(this).data('code');
            const startTime = $(this).data('start_time');
            const endTime = $(this).data('end_time');
            const breakMinutes = $(this).data('break_minutes');
            const isOvernight = $(this).data('is_overnight');
            const isActive = $(this).data('is_active');
            
            openEditModal(hourId, hourCode, hourName, startTime, endTime, breakMinutes, isOvernight, isActive);
        });
        
        // Bouton de suppression
        $('.delete-hour-btn').off('click').on('click', function() {
            const hourId = $(this).data('id');
            const hourCode = $(this).data('code');
            const hourName = $(this).data('name');
            
            openDeleteModal(hourId, hourCode, hourName);
        });
    }
    
   // Dans la fonction openEditModal, modifiez ces lignes :
function openEditModal(id, code, name, startTime, endTime, breakMinutes, isOvernight, isActive) {
    $('#edit_hour_id').val(id);
    $('#edit_hour_code').val(code);
    $('#edit_hour_name').val(name);
    
    // Convertir le format MySQL "HH:MM:SS" au format HTML "HH:MM"
    $('#edit_start_time').val(formatTimeForInput(startTime));
    $('#edit_end_time').val(formatTimeForInput(endTime));
    
    $('#edit_break_minutes').val(breakMinutes);
    $('#edit_is_overnight').prop('checked', isOvernight);
    $('#edit_is_active').prop('checked', isActive);
    
    // Calculer et afficher la durée
    calculateDuration('#edit_start_time', '#edit_end_time', '#edit_break_minutes', '#edit_is_overnight', '#edit_duration_info');
    
    // Réinitialiser les erreurs
    $('#edit_hour_code, #edit_hour_name, #edit_start_time, #edit_end_time, #edit_break_minutes').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    
    $('#editHourModal').modal('show');
}

// Fonction pour formater l'heure du format MySQL au format HTML
function formatTimeForInput(mysqlTime) {
    if (!mysqlTime) return '';
    
    // Si c'est déjà au format HH:mm, le retourner tel quel
    if (mysqlTime.match(/^\d{2}:\d{2}$/)) {
        return mysqlTime;
    }
    
    // Si c'est au format HH:mm:ss, extraire les heures et minutes
    if (mysqlTime.match(/^\d{2}:\d{2}:\d{2}$/)) {
        return mysqlTime.substring(0, 5);
    }
    
    // Si c'est un objet Date ou timestamp, le formater
    if (mysqlTime instanceof Date) {
        return mysqlTime.toTimeString().substring(0, 5);
    }
    
    return mysqlTime;
}

    
    // Ouvrir la modal de suppression
    function openDeleteModal(id, code, name) {
        hourToDelete = id;
        $('#delete-hour-code').text(code);
        $('#delete-hour-name').text(name);
        $('#deleteHourModal').modal('show');
    }
    
    // Appliquer les filtres
    function applyFilters() {
        table.ajax.reload();
    }

    // Événements pour les filtres
    $('#code_filter, #name_filter, #status_filter').on('change keyup', function() {
        applyFilters();
    });

    // Réinitialiser les filtres
    $('#reset_filters').on('click', function() {
        $('#code_filter').val('');
        $('#name_filter').val('');
        $('#status_filter').val('');
        applyFilters();
    });
    
    // Calculer la durée en temps réel
    function calculateDuration(startSelector, endSelector, breakSelector, overnightSelector, infoSelector) {
        const start = $(startSelector).val();
        const end = $(endSelector).val();
        const breakMinutes = $(breakSelector).val() || 0;
        const isOvernight = $(overnightSelector).is(':checked');
        
        if (start && end) {
            const startTime = new Date('2000-01-01T' + start);
            let endTime = new Date('2000-01-01T' + end);
            
            // Si horaire de nuit et end < start, ajouter 24h
            if (isOvernight && endTime < startTime) {
                endTime = new Date(endTime.getTime() + (24 * 60 * 60 * 1000));
            }
            
            // Calculer la durée totale en minutes
            const totalMinutes = (endTime - startTime) / (1000 * 60);
            const workMinutes = totalMinutes - parseInt(breakMinutes);
            
            // Convertir en heures décimales
            const workHours = workMinutes / 60;
            
            // Formater l'affichage
            $(infoSelector).text(`Durée calculée: ${workHours.toFixed(2)}h (${start} - ${end} avec ${breakMinutes}min de pause)`);
        }
    }
    
    // Écouter les changements dans le formulaire de création
    $('#start_time, #end_time, #break_minutes').on('change keyup', function() {
        calculateDuration('#start_time', '#end_time', '#break_minutes', '#is_overnight', '#duration_info');
    });
    
    $('#is_overnight').on('change', function() {
        calculateDuration('#start_time', '#end_time', '#break_minutes', '#is_overnight', '#duration_info');
    });
    
    // Écouter les changements dans le formulaire d'édition
    $('#edit_start_time, #edit_end_time, #edit_break_minutes').on('change keyup', function() {
        calculateDuration('#edit_start_time', '#edit_end_time', '#edit_break_minutes', '#edit_is_overnight', '#edit_duration_info');
    });
    
    $('#edit_is_overnight').on('change', function() {
        calculateDuration('#edit_start_time', '#edit_end_time', '#edit_break_minutes', '#edit_is_overnight', '#edit_duration_info');
    });
    
    // Calcul initial pour le formulaire de création
    calculateDuration('#start_time', '#end_time', '#break_minutes', '#is_overnight', '#duration_info');
    
    // Gestion de la création d'horaire
    $('#createHourForm').on('submit', function(e) {
        e.preventDefault();
        
        // Récupérer les données du formulaire
        var formData = {
            code: $('#hour_code').val(),
            name: $('#hour_name').val(),
            start_time: $('#start_time').val(),
            end_time: $('#end_time').val(),
            break_minutes: $('#break_minutes').val(),
            is_overnight: $('#is_overnight').is(':checked') ? 1 : 0,
            is_active: $('#is_active').is(':checked') ? 1 : 0,
            _token: "{{ csrf_token() }}"
        };
        
        // Désactiver le bouton et afficher le spinner
        $('#submit-create-hour').prop('disabled', true);
        $('#create-hour-text').addClass('d-none');
        $('#create-hour-spinner').removeClass('d-none');
        
        // Réinitialiser les erreurs
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        // Envoyer la requête AJAX
        $.ajax({
            url: "{{ route('work-hours.store') }}",
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Fermer la modal
                    $('#createHourModal').modal('hide');
                    
                    // Réinitialiser le formulaire
                    $('#createHourForm')[0].reset();
                    $('#is_active').prop('checked', true);
                    
                    // Recalculer la durée
                    setTimeout(() => {
                        calculateDuration('#start_time', '#end_time', '#break_minutes', '#is_overnight', '#duration_info');
                    }, 100);
                    
                    // Afficher un message de succès
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message || 'Type d\'horaire créé avec succès',
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
                        var input = $('#' + (key === 'is_overnight' || key === 'is_active' ? key : 'hour_' + key));
                        if (input.length) {
                            input.addClass('is-invalid');
                            input.next('.invalid-feedback').text(value[0]);
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
                $('#submit-create-hour').prop('disabled', false);
                $('#create-hour-text').removeClass('d-none');
                $('#create-hour-spinner').addClass('d-none');
            }
        });
    });
    
    // Gestion de l'édition d'horaire
    $('#editHourForm').on('submit', function(e) {
        e.preventDefault();
        
        const hourId = $('#edit_hour_id').val();
        
        // Récupérer les données du formulaire
        var formData = {
            code: $('#edit_hour_code').val(),
            name: $('#edit_hour_name').val(),
            start_time: $('#edit_start_time').val(),
            end_time: $('#edit_end_time').val(),
            break_minutes: $('#edit_break_minutes').val(),
            is_overnight: $('#edit_is_overnight').is(':checked') ? 1 : 0,
            is_active: $('#edit_is_active').is(':checked') ? 1 : 0,
            _token: "{{ csrf_token() }}"
        };
        
        // Désactiver le bouton et afficher le spinner
        $('#submit-edit-hour').prop('disabled', true);
        $('#edit-hour-text').addClass('d-none');
        $('#edit-hour-spinner').removeClass('d-none');
        
        // Réinitialiser les erreurs
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        // Envoyer la requête AJAX
        $.ajax({
            url: "{{ url('work-hours') }}/" + hourId,
            type: 'PUT',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Fermer la modal
                    $('#editHourModal').modal('hide');
                    
                    // Afficher un message de succès
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message || 'Type d\'horaire modifié avec succès',
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
                        var input = $('#edit_' + (key === 'is_overnight' || key === 'is_active' ? key : 'hour_' + key));
                        if (input.length) {
                            input.addClass('is-invalid');
                            input.next('.invalid-feedback').text(value[0]);
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
                $('#submit-edit-hour').prop('disabled', false);
                $('#edit-hour-text').removeClass('d-none');
                $('#edit-hour-spinner').addClass('d-none');
            }
        });
    });
    
    // Gestion de la suppression d'horaire
    $('#confirm-delete-hour').on('click', function() {
        if (!hourToDelete) return;
        
        // Désactiver le bouton et afficher le spinner
        $(this).prop('disabled', true);
        $('#delete-hour-text').addClass('d-none');
        $('#delete-hour-spinner').removeClass('d-none');
        
        // Envoyer la requête AJAX
        $.ajax({
            url: "{{ url('work-hours') }}/" + hourToDelete,
            type: 'DELETE',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    // Fermer la modal
                    $('#deleteHourModal').modal('hide');
                    
                    // Réinitialiser la variable
                    hourToDelete = null;
                    
                    // Afficher un message de succès
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message || 'Type d\'horaire supprimé avec succès',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    
                    // Recharger le tableau
                    table.ajax.reload();
                } else {
                    // Afficher les erreurs
                    showSweetAlert('error', 'Erreur', response.message);
                    
                    // Fermer la modal
                    $('#deleteHourModal').modal('hide');
                }
            },
            error: function(xhr) {
                showSweetAlert('error', 'Erreur', 
                    'Une erreur est survenue lors de la suppression. ' + 
                    (xhr.responseJSON?.message || 'Veuillez réessayer.')
                );
                
                // Fermer la modal
                $('#deleteHourModal').modal('hide');
            },
            complete: function() {
                // Réactiver le bouton
                $('#confirm-delete-hour').prop('disabled', false);
                $('#delete-hour-text').removeClass('d-none');
                $('#delete-hour-spinner').addClass('d-none');
            }
        });
    });
    
    // Export PDF
    $('#export-pdf-button').on('click', function() {
        // Récupérer les filtres actuels
        const filters = {
            code: $('#code_filter').val(),
            name: $('#name_filter').val(),
            status: $('#status_filter').val(),
            type: 'pdf'
        };
        
        // Construire l'URL avec les filtres
        let url = "{{ route('work-hours.export') }}?";
        let params = [];
        for (const key in filters) {
            if (filters[key]) {
                params.push(key + '=' + encodeURIComponent(filters[key]));
            }
        }
        url += params.join('&');
        
        // Ouvrir dans une nouvelle fenêtre pour télécharger le PDF
        window.open(url, '_blank');
    });
    
    // Réinitialiser le formulaire quand la modal de création se ferme
    $('#createHourModal').on('hidden.bs.modal', function() {
        $('#createHourForm')[0].reset();
        $('#is_active').prop('checked', true);
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#submit-create-hour').prop('disabled', false);
        $('#create-hour-text').removeClass('d-none');
        $('#create-hour-spinner').addClass('d-none');
        setTimeout(() => {
            calculateDuration('#start_time', '#end_time', '#break_minutes', '#is_overnight', '#duration_info');
        }, 100);
    });
    
    // Réinitialiser quand la modal d'édition se ferme
    $('#editHourModal').on('hidden.bs.modal', function() {
        $('#submit-edit-hour').prop('disabled', false);
        $('#edit-hour-text').removeClass('d-none');
        $('#edit-hour-spinner').addClass('d-none');
    });
    
    // Réinitialiser quand la modal de suppression se ferme
    $('#deleteHourModal').on('hidden.bs.modal', function() {
        hourToDelete = null;
        $('#confirm-delete-hour').prop('disabled', false);
        $('#delete-hour-text').removeClass('d-none');
        $('#delete-hour-spinner').addClass('d-none');
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
    .form-check.form-switch {
        padding-left: 3.5em;
    }
    .form-check-input:checked {
        background-color: #198754;
        border-color: #198754;
    }
</style>
@endsection