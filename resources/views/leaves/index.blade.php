@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Gestion des Congés</h3>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('home') }}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item active">Congés</li>
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
                                    <select class="form-control" id="employee_filter">
                                        <option value="">Tous les employés</option>
                                        @foreach($employees as $employee)
                                            <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="type_filter">Type de congé</label>
                                    <select class="form-control" id="type_filter">
                                        <option value="">Tous les types</option>
                                        @foreach($leaveTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="status_filter">Statut</label>
                                    <select class="form-control" id="status_filter">
                                        <option value="">Tous les statuts</option>
                                        <option value="pending">En attente</option>
                                        <option value="approved">Approuvé</option>
                                        <option value="rejected">Rejeté</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group text-start">
                                    <label class="form-label d-block" style="margin-bottom:0px;">&nbsp;</label>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-success" id="create-leave-button">
                                            <i class="bi bi-plus-circle me-1"></i> Nouveau congé
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
                            <table id="leaves-table" class="table table-striped table-hover dt-responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Employé</th>
                                        <th>Type</th>
                                        <th>Début</th>
                                        <th>Fin</th>
                                        <th>Durée</th>
                                        <th>Statut</th>
                                        <th>Créé le</th>
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

<!-- Modal de création/édition de congé -->
<div class="modal fade" id="leaveModal" tabindex="-1" aria-labelledby="leaveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leaveModalLabel">Nouveau congé</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="leaveForm">
                <input type="hidden" id="leave_id" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="employee_id" class="form-label">Employé <span class="text-danger">*</span></label>
                        <select class="form-control" id="employee_id" name="employee_id" required>
                            <option value="">Sélectionner un employé</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="employee-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="type_id" class="form-label">Type de congé <span class="text-danger">*</span></label>
                        <select class="form-control" id="type_id" name="type_id" required>
                            <option value="">Sélectionner un type</option>
                            @foreach($leaveTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="type-error"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Date de début <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                            <div class="invalid-feedback" id="start-date-error"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">Date de fin <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                            <div class="invalid-feedback" id="end-date-error"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="">Sélectionner un status</option>
                            <option value="pending">En attente</option>
                            <option value="approved">Approuvé</option>
                            <option value="rejected">Rejeté</option>
                        </select>
                        <div class="invalid-feedback" id="status-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Raison <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required maxlength="500"></textarea>
                        <div class="form-text">Maximum 500 caractères</div>
                        <div class="invalid-feedback" id="reason-error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-leave">
                        <span id="leave-text">Enregistrer</span>
                        <span id="leave-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce congé ?</p>
                <p><strong>Raison :</strong> <span id="delete-reason"></span></p>
                <p class="text-danger"><small>Cette action est irréversible.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirm-delete">
                    <span id="delete-text">Supprimer</span>
                    <span id="delete-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'approbation/rejet -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">Changer le statut</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="statusForm">
                <input type="hidden" id="status_leave_id" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="status" class="form-label">Nouveau statut <span class="text-danger">*</span></label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="pending">En attente</option>
                            <option value="approved">Approuvé</option>
                            <option value="rejected">Rejeté</option>
                        </select>
                        <div class="invalid-feedback" id="status-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="comments" class="form-label">Commentaires (optionnel)</label>
                        <textarea class="form-control" id="comments" name="comments" rows="3" maxlength="500"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-status">
                        <span id="status-text">Mettre à jour</span>
                        <span id="status-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Variable pour stocker l'ID à supprimer
    let leaveToDelete = null;
    let table;
    let isSubmitting = false; // Variable pour empêcher les soumissions multiples

    // Initialiser DataTable avec AJAX
    function initializeDataTable() {
        if ($.fn.DataTable.isDataTable('#leaves-table')) {
            $('#leaves-table').DataTable().destroy();
        }

        table = $('#leaves-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('leaves.datatable') }}",
                type: "GET",
                data: function(d) {
                    d.employee_filter = $('#employee_filter').val();
                    d.type_filter = $('#type_filter').val();
                    d.status_filter = $('#status_filter').val();
                    d.client_id = "1";
                }
            },
            columns: [
                { 
                    data: 'id',
                    name: 'id',
                    width: '5%'
                },
                { 
                    data: 'employee_name',
                    name: 'employee.first_name',
                    width: '15%',
                    render: function(data, type, row) {
                        return data || '<span class="text-muted">N/A</span>';
                    }
                },
                { 
                    data: 'type_name',
                    name: 'leaveType.name',
                    width: '10%',
                    render: function(data, type, row) {
                        return data ? '<span class="badge bg-info">' + data + '</span>' : '<span class="text-muted">N/A</span>';
                    }
                },
                { 
                    data: 'start_date',
                    name: 'start_date',
                    width: '10%',
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString('fr-FR') : '';
                    }
                },
                { 
                    data: 'end_date',
                    name: 'end_date',
                    width: '10%',
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString('fr-FR') : '';
                    }
                },
                { 
                    data: 'duration',
                    name: 'duration',
                    width: '8%',
                    render: function(data) {
                        return data + ' jour(s)';
                    }
                },
                { 
                    data: 'status',
                    name: 'status',
                    width: '10%',
                    render: function(data) {
                        let badgeClass = 'bg-secondary';
                        if (data.toLowerCase() === 'approved') {
                            badgeClass = 'bg-success';
                        } else if (data.toLowerCase() === 'rejected') {
                            badgeClass = 'bg-danger';
                        } else if (data.toLowerCase() === 'pending') {
                            badgeClass = 'bg-warning text-dark';
                        }
                        return '<span class="badge ' + badgeClass + '">' + data + '</span>';
                    }
                },
                { 
                    data: 'created_at',
                    name: 'created_at',
                    width: '12%',
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString('fr-FR') : '';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    width: '15%',
                    render: function(data, type, row) {
                        let buttons = '<div class="btn-group" role="group">';
                        
                        // Bouton d'édition
                        buttons += '<button type="button" class="btn btn-sm btn-warning edit-btn" data-id="' + row.id + '" title="Modifier">';
                        buttons += '<i class="bi bi-pencil"></i>';
                        buttons += '</button>';
                        
                        // Bouton de changement de statut
                        buttons += '<button type="button" class="btn btn-sm btn-info status-btn" data-id="' + row.id + '" title="Changer statut">';
                        buttons += '<i class="bi bi-gear"></i>';
                        buttons += '</button>';
                        
                        // Bouton de suppression
                        buttons += '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' + row.id + '" data-reason="' + (row.reason || '') + '" title="Supprimer">';
                        buttons += '<i class="bi bi-trash"></i>';
                        buttons += '</button>';
                        
                        buttons += '</div>';
                        return buttons;
                    }
                }
            ],
            language: {
                url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json"
            },
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tous"]],
            order: [[0, 'desc']],
            responsive: true,
            drawCallback: function(settings) {
                // Réattacher les événements après chaque redessinage
                attachButtonEvents();
            }
        });
    }

    // Initialiser DataTable
    initializeDataTable();

    // Attacher les événements aux boutons (une seule fois)
    function attachButtonEvents() {
        // Boutons d'édition - utiliser .off() pour éviter les doublons
        $('#leaves-table').off('click', '.edit-btn').on('click', '.edit-btn', function() {
            const leaveId = $(this).data('id');
            loadLeaveData(leaveId);
        });

        // Boutons de statut
        $('#leaves-table').off('click', '.status-btn').on('click', '.status-btn', function() {
            const leaveId = $(this).data('id');
            openStatusModal(leaveId);
        });

        // Boutons de suppression
        $('#leaves-table').off('click', '.delete-btn').on('click', '.delete-btn', function() {
            leaveToDelete = $(this).data('id');
            $('#delete-reason').text($(this).data('reason'));
            $('#deleteModal').modal('show');
        });
    }

    // Attacher les événements globaux une seule fois
    $('#create-leave-button').on('click', function() {
        openLeaveModal();
    });

    // Ouvrir modal de création/édition
    function openLeaveModal(isEdit = false) {
        resetForm('#leaveForm');
        if (!isEdit) {
            $('#leaveModalLabel').text('Nouveau congé');
            $('#leave_id').val('');
        }
        $('#leaveModal').modal('show');
    }

    // Charger les données pour l'édition
    function loadLeaveData(leaveId) {
        $.ajax({
            url: "{{ url('leaves') }}/" + leaveId + "/edit",
            type: "GET",
            beforeSend: function() {
                showLoading('#leaveModal');
            },
            success: function(response) {
                if (response.success) {
                    $('#leaveModalLabel').text('Modifier le congé');
                    $('#leave_id').val(response.data.id);
                    $('#employee_id').val(response.data.employee_id);
                    $('#type_id').val(response.data.type_id);
                    $('#start_date').val(response.data.start_date);
                    $('#end_date').val(response.data.end_date);
                    $('#status').val(response.data.status);
                    $('#reason').val(response.data.reason);
                    $('#leaveModal').modal('show');
                } else {
                    showError('Erreur lors du chargement des données');
                }
            },
            error: function(xhr) {
                showError('Erreur lors du chargement des données');
            },
            complete: function() {
                hideLoading('#leaveModal');
            }
        });
    }

    // Ouvrir modal de statut
    function openStatusModal(leaveId) {
        resetForm('#statusForm');
        $('#status_leave_id').val(leaveId);
        $('#statusModal').modal('show');
    }

    // Appliquer les filtres
    function applyFilters() {
        table.ajax.reload();
    }

    // Événements pour les filtres
    $('#employee_filter, #type_filter, #status_filter').on('change', function() {
        applyFilters();
    });

    // Réinitialiser les filtres
    $('#reset_filters').on('click', function() {
        $('#employee_filter').val('');
        $('#type_filter').val('');
        $('#status_filter').val('');
        applyFilters();
    });

    // Fonction pour soumettre le formulaire
    function submitForm(options) {
        if (isSubmitting) return; // Empêcher les soumissions multiples
        
        isSubmitting = true;
        
        const $form = $(options.form);
        const $submitBtn = $form.find('button[type="submit"]');
        const $spinner = $submitBtn.find('.spinner-border');
        const $text = $submitBtn.find('span:not(.spinner-border)');
        
        // Réinitialiser les erreurs
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');
        
        // Désactiver le bouton et afficher le spinner
        $submitBtn.prop('disabled', true);
        $text.addClass('d-none');
        $spinner.removeClass('d-none');
        
        // Préparer les données avec le token CSRF
        let formData = $form.serialize();
        
        // Ajouter le token CSRF si pas déjà présent
        if (!formData.includes('_token=')) {
            formData += '&_token=' + "{{ csrf_token() }}";
        }
        
        $.ajax({
            url: options.url,
            type: options.method,
            data: formData,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    if (options.successCallback) {
                        options.successCallback(response);
                    }
                } else {
                    showError(response.message || 'Une erreur est survenue');
                }
            },
            error: function(xhr) {
                if (xhr.status === 419) {
                    showError('Session expirée. Veuillez rafraîchir la page et réessayer.');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        const $input = $form.find('[name="' + key + '"]');
                        const $error = $form.find('#' + key + '-error');
                        
                        if ($input.length) {
                            $input.addClass('is-invalid');
                        }
                        if ($error.length) {
                            $error.text(value[0]);
                        } else {
                            // Créer un élément d'erreur si non existant
                            $input.after('<div class="invalid-feedback" id="' + key + '-error">' + value[0] + '</div>');
                        }
                    });
                } else {
                    showError('Une erreur est survenue lors de l\'opération (Code: ' + xhr.status + ')');
                }
            },
            complete: function() {
                // Réactiver le bouton et réinitialiser l'état
                $submitBtn.prop('disabled', false);
                $text.removeClass('d-none');
                $spinner.addClass('d-none');
                isSubmitting = false;
            }
        });
    }

    // Fonction pour soumettre une requête simple
    function submitRequest(options) {
        if (isSubmitting) return;
        
        isSubmitting = true;
        
        const $button = $(options.button);
        const $spinner = $(options.spinner);
        const $text = $(options.text);
        
        $button.prop('disabled', true);
        $text.addClass('d-none');
        $spinner.removeClass('d-none');
        
        // Préparer les données avec token CSRF
        const data = {
            _token: "{{ csrf_token() }}",
            ...options.data
        };
        
        $.ajax({
            url: options.url,
            type: options.method,
            data: data,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    if (options.successCallback) {
                        options.successCallback(response);
                    }
                } else {
                    showError(response.message || 'Une erreur est survenue');
                }
            },
            error: function(xhr) {
                if (xhr.status === 419) {
                    showError('Session expirée. Veuillez rafraîchir la page et réessayer.');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showError('Une erreur est survenue lors de l\'opération');
                }
            },
            complete: function() {
                $button.prop('disabled', false);
                $text.removeClass('d-none');
                $spinner.addClass('d-none');
                isSubmitting = false;
            }
        });
    }

    // Soumission du formulaire de congé (UNIQUEMENT UN ÉVÉNEMENT)
    $('#leaveForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        
        const leaveId = $('#leave_id').val();
        const isEdit = leaveId !== '';
        const url = isEdit ? "{{ url('leaves') }}/" + leaveId : "{{ route('leaves.store') }}";
        const method = isEdit ? 'PUT' : 'POST';
        
        submitForm({
            form: '#leaveForm',
            url: url,
            method: method,
            successCallback: function(response) {
                $('#leaveModal').modal('hide');
                table.ajax.reload();
                showSuccess(response.message || 'Opération effectuée avec succès');
            }
        });
    });

    // Soumission du formulaire de statut (UNIQUEMENT UN ÉVÉNEMENT)
    $('#statusForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const leaveId = $('#status_leave_id').val();
        
        submitForm({
            form: '#statusForm',
            url: "{{ url('leaves') }}/" + leaveId + "/status",
            method: 'PUT',
            successCallback: function(response) {
                $('#statusModal').modal('hide');
                table.ajax.reload();
                showSuccess(response.message || 'Statut mis à jour avec succès');
            }
        });
    });

    // Confirmation de suppression (UNIQUEMENT UN ÉVÉNEMENT)
    $('#confirm-delete').off('click').on('click', function() {
        if (!leaveToDelete) return;
        
        submitRequest({
            url: "{{ url('leaves') }}/" + leaveToDelete,
            method: 'DELETE',
            button: '#confirm-delete',
            spinner: '#delete-spinner',
            text: '#delete-text',
            successCallback: function(response) {
                $('#deleteModal').modal('hide');
                leaveToDelete = null;
                table.ajax.reload();
                showSuccess(response.message || 'Congé supprimé avec succès');
            }
        });
    });

    // Fonctions utilitaires
    function resetForm(formSelector) {
        $(formSelector)[0].reset();
        $(formSelector).find('.is-invalid').removeClass('is-invalid');
        $(formSelector).find('.invalid-feedback').text('');
    }

    function showLoading(modalSelector) {
        $(modalSelector).find('.modal-content').addClass('opacity-50');
        $(modalSelector).find('button, input, select, textarea').prop('disabled', true);
    }

    function hideLoading(modalSelector) {
        $(modalSelector).find('.modal-content').removeClass('opacity-50');
        $(modalSelector).find('button, input, select, textarea').prop('disabled', false);
    }

    function showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'Succès',
            text: message,
            timer: 3000,
            showConfirmButton: false
        });
    }

    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: message,
            timer: 5000
        });
    }

    // Réinitialiser les modales quand elles se ferment
    $('#leaveModal, #statusModal, #deleteModal').on('hidden.bs.modal', function() {
        resetForm('form');
        leaveToDelete = null;
    });

    // Initialiser les événements au chargement
    attachButtonEvents();
});
</script>
<style>
    .dataTables_wrapper {
        padding: 10px 0;
    }
    .btn-group .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        margin-right: 2px;
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
    table.dataTable tbody td {
        vertical-align: middle;
    }
    .opacity-50 {
        opacity: 0.5;
    }
    #leaves-table_wrapper {
        margin-top: 10px;
    }
</style>
@endsection