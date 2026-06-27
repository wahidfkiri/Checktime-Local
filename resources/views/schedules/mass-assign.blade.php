@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Affectation en Masse</h3>
                        <p class="text-subtitle text-muted">Assigner des horaires à plusieurs employés</p>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('dashboard') }}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{ route('plannings.index') }}">Plannings</a>
                                </li>
                                <li class="breadcrumb-item active">Affectation en masse</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <section class="section">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Configuration de l'assignation</h5>
                    </div>
                    <div class="card-body">
                        <form id="massAssignForm">
                            <div class="row">
                                <!-- Sélection des employés -->
                                <div class="col-md-12 mb-4">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">1. Sélection des employés</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">Filtrer par département</label>
                                                    <select class="form-control" id="filter_department">
                                                        <option value="">Tous les départements</option>
                                                        @foreach($departments as $department)
                                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Filtrer par zone</label>
                                                    <select class="form-control" id="filter_area">
                                                        <option value="">Toutes les zones</option>
                                                        @foreach($areas as $area)
                                                        <option value="{{ $area->id }}">{{ $area->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Rechercher</label>
                                                    <input type="text" class="form-control" id="search_employee" placeholder="Nom ou matricule...">
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="alert alert-info d-flex justify-content-between align-items-center">
                                                        <span id="selected_count">0 employé(s) sélectionné(s)</span>
                                                        <div>
                                                            <button type="button" class="btn btn-sm btn-outline-info" id="select_all">
                                                                <i class="bi bi-check-all me-1"></i> Tout sélectionner
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-info" id="deselect_all">
                                                                <i class="bi bi-x-circle me-1"></i> Tout désélectionner
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                                <table class="table table-sm table-hover" id="employees_table">
                                                    <thead>
                                                        <tr>
                                                            <th width="50">
                                                                <input type="checkbox" id="check_all">
                                                            </th>
                                                            <th>Matricule</th>
                                                            <th>Nom</th>
                                                            <th>Département</th>
                                                            <th>Zone</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($employees as $employee)
                                                        <tr data-department-id="{{ $employee->department_id }}" 
                                                            data-area-id="{{ $employee->area_id }}">
                                                            <td>
                                                                <input type="checkbox" class="employee-checkbox" 
                                                                       name="employee_ids[]" 
                                                                       value="{{ $employee->id }}"
                                                                       data-name="{{ $employee->full_name }}">
                                                            </td>
                                                            <td>{{ $employee->emp_code }}</td>
                                                            <td>{{ $employee->full_name }}</td>
                                                            <td>{{ $employee->department->name ?? 'N/A' }}</td>
                                                            <td>{{ $employee->area->name ?? 'N/A' }}</td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Configuration des horaires -->
                                <div class="col-md-12 mb-4">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">2. Configuration des horaires</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="work_hour_type_id" class="form-label">Type d'horaire <span class="text-danger">*</span></label>
                                                        <select class="form-control" id="work_hour_type_id" name="work_hour_type_id" required>
                                                            <option value="">Sélectionner un horaire</option>
                                                            @foreach($workHourTypes as $type)
                                                            <option value="{{ $type->id }}" 
                                                                    data-start="{{ $type->start_time }}" 
                                                                    data-end="{{ $type->end_time }}"
                                                                    data-break="{{ $type->break_minutes }}">
                                                                {{ $type->name }} ({{ date('H:i', strtotime($type->start_time)) }} - {{ date('H:i', strtotime($type->end_time)) }})
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Informations horaire</label>
                                                        <div class="alert alert-light">
                                                            <div id="hour_info" class="small text-muted">
                                                                Sélectionnez un type d'horaire pour voir les détails
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="days_of_week" class="form-label">Jour(s) de la semaine</label>
                                                        <select class="form-control" id="days_of_week" name="days_of_week[]" multiple>
                                                            <option value="1">Lundi</option>
                                                            <option value="2">Mardi</option>
                                                            <option value="3">Mercredi</option>
                                                            <option value="4">Jeudi</option>
                                                            <option value="5">Vendredi</option>
                                                            <option value="6">Samedi</option>
                                                            <option value="7">Dimanche</option>
                                                        </select>
                                                        <div class="form-text">Laissez vide pour tous les jours</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="specific_dates" class="form-label">Ou dates spécifiques</label>
                                                        <input type="text" class="form-control" id="specific_dates" 
                                                               placeholder="Sélectionnez des dates...">
                                                        <div class="form-text">Format: JJ/MM/AAAA, JJ/MM/AAAA, ...</div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="start_date" class="form-label">Date de début <span class="text-danger">*</span></label>
                                                        <input type="date" class="form-control" id="start_date" name="start_date" required 
                                                               value="{{ date('Y-m-d') }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="end_date" class="form-label">Date de fin <span class="text-danger">*</span></label>
                                                        <input type="date" class="form-control" id="end_date" name="end_date" required 
                                                               value="{{ date('Y-m-d', strtotime('+7 days')) }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Options supplémentaires -->
                                <div class="col-md-12 mb-4">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">3. Options supplémentaires</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="notes" class="form-label">Notes (optionnel)</label>
                                                        <textarea class="form-control" id="notes" name="notes" rows="2" 
                                                                  placeholder="Notes pour ces plannings..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="checkbox" id="is_working_day" name="is_working_day" value="1" checked>
                                                            <label class="form-check-label" for="is_working_day">
                                                                Jour travaillé
                                                            </label>
                                                        </div>
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="checkbox" id="override_existing" name="override_existing" value="1">
                                                            <label class="form-check-label" for="override_existing">
                                                                Remplacer les plannings existants
                                                            </label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="include_weekends" name="include_weekends" value="1">
                                                            <label class="form-check-label" for="include_weekends">
                                                                Inclure les weekends
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="alert alert-warning" id="preview_info">
                                                        <i class="bi bi-info-circle me-2"></i>
                                                        <span id="preview_text">Aucun employé sélectionné</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-secondary" id="btn_preview">
                                            <i class="bi bi-eye me-1"></i> Aperçu
                                        </button>
                                        <button type="submit" class="btn btn-success" id="btn_submit">
                                            <i class="bi bi-check-circle me-1"></i> Valider l'assignation
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- Modal d'aperçu -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Aperçu de l'assignation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="preview_table">
                        <thead>
                            <tr>
                                <th>Employé</th>
                                <th>Dates</th>
                                <th>Horaire</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Contenu généré par JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" id="btn_confirm_assign">
                    <i class="bi bi-check-circle me-1"></i> Confirmer l'assignation
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>

<script>
$(document).ready(function() {
    // Initialiser Select2
    $('#days_of_week').select2({
        placeholder: "Tous les jours",
        allowClear: true
    });
    
    // Initialiser Flatpickr pour les dates
    flatpickr("#specific_dates", {
        mode: "multiple",
        dateFormat: "d/m/Y",
        locale: "fr",
        placeholder: "Sélectionnez des dates..."
    });
    
    flatpickr("#start_date, #end_date", {
        dateFormat: "Y-m-d",
        locale: "fr"
    });
    
    // Gérer la sélection des employés
    $('#check_all').on('change', function() {
        $('.employee-checkbox').prop('checked', this.checked);
        updateSelectionCount();
        updatePreview();
    });
    
    $('.employee-checkbox').on('change', function() {
        updateSelectionCount();
        updatePreview();
        
        // Mettre à jour la checkbox "Tout sélectionner"
        const allChecked = $('.employee-checkbox:checked').length === $('.employee-checkbox').length;
        $('#check_all').prop('checked', allChecked);
    });
    
    $('#select_all').on('click', function() {
        $('.employee-checkbox').prop('checked', true);
        $('#check_all').prop('checked', true);
        updateSelectionCount();
        updatePreview();
    });
    
    $('#deselect_all').on('click', function() {
        $('.employee-checkbox').prop('checked', false);
        $('#check_all').prop('checked', false);
        updateSelectionCount();
        updatePreview();
    });
    
    // Filtres des employés
    $('#filter_department, #filter_area, #search_employee').on('change keyup', function() {
        const deptId = $('#filter_department').val();
        const areaId = $('#filter_area').val();
        const search = $('#search_employee').val().toLowerCase();
        
        $('.employee-checkbox').each(function() {
            const $row = $(this).closest('tr');
            const rowDeptId = $row.data('department-id');
            const rowAreaId = $row.data('area-id');
            const employeeName = $row.find('td').eq(2).text().toLowerCase();
            const matricule = $row.find('td').eq(1).text().toLowerCase();
            
            let showRow = true;
            
            if (deptId && rowDeptId != deptId) {
                showRow = false;
            }
            
            if (areaId && rowAreaId != areaId) {
                showRow = false;
            }
            
            if (search && !employeeName.includes(search) && !matricule.includes(search)) {
                showRow = false;
            }
            
            $row.toggle(showRow);
        });
    });
    
    // Afficher les infos de l'horaire sélectionné
    $('#work_hour_type_id').on('change', function() {
        const selected = $(this).find('option:selected');
        if (selected.val()) {
            const startTime = selected.data('start');
            const endTime = selected.data('end');
            const breakMinutes = selected.data('break') || 0;
            
            // Calculer la durée
            const start = new Date('2000-01-01T' + startTime);
            const end = new Date('2000-01-01T' + endTime);
            let duration = (end - start) / (1000 * 60 * 60); // en heures
            if (duration < 0) duration += 24; // pour les horaires de nuit
            
            const workHours = duration - (breakMinutes / 60);
            
            $('#hour_info').html(`
                <strong>${selected.text()}</strong><br>
                Début: ${startTime} | Fin: ${endTime}<br>
                Pause: ${breakMinutes} min | Travail: ${workHours.toFixed(2)}h
            `);
        } else {
            $('#hour_info').html('Sélectionnez un type d\'horaire pour voir les détails');
        }
        
        updatePreview();
    });
    
    // Mettre à jour l'aperçu quand les paramètres changent
    $('#start_date, #end_date, #days_of_week, #specific_dates, #is_working_day, #include_weekends').on('change', function() {
        updatePreview();
    });
    
    // Fonction de mise à jour du compteur
    function updateSelectionCount() {
        const count = $('.employee-checkbox:checked').length;
        $('#selected_count').text(count + ' employé(s) sélectionné(s)');
        return count;
    }
    
    // Fonction de mise à jour de l'aperçu
    function updatePreview() {
        const employeeCount = $('.employee-checkbox:checked').length;
        const workHourType = $('#work_hour_type_id option:selected').text();
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        
        let previewText = '';
        
        if (employeeCount === 0) {
            previewText = 'Aucun employé sélectionné';
        } else if (!workHourType) {
            previewText = employeeCount + ' employé(s) sélectionné(s) - Sélectionnez un horaire';
        } else {
            previewText = `${employeeCount} employé(s) recevront "${workHourType}" du ${formatDate(startDate)} au ${formatDate(endDate)}`;
        }
        
        $('#preview_text').text(previewText);
    }
    
    // Aperçu de l'assignation
    $('#btn_preview').on('click', function() {
        generatePreview();
    });
    
    function generatePreview() {
        const selectedEmployees = [];
        $('.employee-checkbox:checked').each(function() {
            selectedEmployees.push({
                id: $(this).val(),
                name: $(this).data('name')
            });
        });
        
        if (selectedEmployees.length === 0) {
            Swal.fire('Attention', 'Veuillez sélectionner au moins un employé', 'warning');
            return;
        }
        
        if (!$('#work_hour_type_id').val()) {
            Swal.fire('Attention', 'Veuillez sélectionner un type d\'horaire', 'warning');
            return;
        }
        
        // Générer l'aperçu
        const $tbody = $('#preview_table tbody');
        $tbody.empty();
        
        const workHourType = $('#work_hour_type_id option:selected').text();
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        const daysOfWeek = $('#days_of_week').val() || [];
        const specificDates = $('#specific_dates').val();
        
        // Limiter à 5 employés pour l'aperçu
        const previewEmployees = selectedEmployees.slice(0, 5);
        
        previewEmployees.forEach(employee => {
            const datesText = specificDates ? 
                'Dates spécifiques: ' + specificDates :
                `Du ${formatDate(startDate)} au ${formatDate(endDate)}` + 
                (daysOfWeek.length ? ` (${getDaysNames(daysOfWeek)})` : '');
            
            $tbody.append(`
                <tr>
                    <td>${employee.name}</td>
                    <td>${datesText}</td>
                    <td>${workHourType}</td>
                    <td><span class="badge bg-info">À assigner</span></td>
                </tr>
            `);
        });
        
        if (selectedEmployees.length > 5) {
            $tbody.append(`
                <tr>
                    <td colspan="4" class="text-center text-muted">
                        ... et ${selectedEmployees.length - 5} autre(s) employé(s)
                    </td>
                </tr>
            `);
        }
        
        $('#previewModal').modal('show');
    }
    
    // Soumission du formulaire
    $('#massAssignForm').on('submit', function(e) {
        e.preventDefault();
        generatePreview();
    });
    
    // Confirmation de l'assignation
    $('#btn_confirm_assign').on('click', function() {
        const formData = {
            employee_ids: getSelectedEmployeeIds(),
            work_hour_type_id: $('#work_hour_type_id').val(),
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            days_of_week: $('#days_of_week').val() || [],
            specific_dates: $('#specific_dates').val() ? $('#specific_dates').val().split(', ') : [],
            notes: $('#notes').val(),
            is_working_day: $('#is_working_day').is(':checked') ? 1 : 0,
            override_existing: $('#override_existing').is(':checked') ? 1 : 0,
            include_weekends: $('#include_weekends').is(':checked') ? 1 : 0,
            _token: "{{ csrf_token() }}"
        };
        
        // Désactiver le bouton
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Traitement...');
        
        $.ajax({
            url: "{{ route('schedules.mass-assign') }}",
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#previewModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        html: `
                            <p>${response.message}</p>
                            <p class="small text-muted">${response.details || ''}</p>
                        `,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Erreur', response.message, 'error');
                    $('#btn_confirm_assign').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Confirmer l\'assignation');
                }
            },
            error: function(xhr) {
                Swal.fire('Erreur', 'Une erreur est survenue', 'error');
                $('#btn_confirm_assign').prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Confirmer l\'assignation');
            }
        });
    });
    
    // Fonctions utilitaires
    function getSelectedEmployeeIds() {
        const ids = [];
        $('.employee-checkbox:checked').each(function() {
            ids.push($(this).val());
        });
        return ids;
    }
    
    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('fr-FR');
    }
    
    function getDaysNames(days) {
        const dayNames = {
            '1': 'Lundi', '2': 'Mardi', '3': 'Mercredi', '4': 'Jeudi',
            '5': 'Vendredi', '6': 'Samedi', '7': 'Dimanche'
        };
        return days.map(d => dayNames[d]).join(', ');
    }
    
    // Calcul automatique de la date de fin (7 jours par défaut)
    $('#start_date').on('change', function() {
        const startDate = new Date($(this).val());
        if (!isNaN(startDate.getTime())) {
            const endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + 6); // 7 jours
            $('#end_date').val(endDate.toISOString().split('T')[0]);
        }
    });
    
    // Initialiser
    updateSelectionCount();
    updatePreview();
});
</script>

<style>
.select2-container--default .select2-selection--multiple {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}

.flatpickr-input {
    background-color: white !important;
}

#employees_table tbody tr {
    cursor: pointer;
}

#employees_table tbody tr:hover {
    background-color: #f8f9fa;
}

#preview_table td {
    vertical-align: middle;
}

.card-header.bg-light {
    background-color: #f8f9fa !important;
    border-bottom: 1px solid #dee2e6;
}
</style>
@endsection