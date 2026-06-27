@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Calendrier des Plannings</h3>
                        <p class="text-subtitle text-muted">Assignation des horaires par jour</p>
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
                                <li class="breadcrumb-item active">Calendrier</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <section class="section">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="week_selector">Semaine</label>
                                    <input type="week" class="form-control" id="week_selector" 
                                           value="{{ $startDate->format('Y-\WW') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="department_filter">Département</label>
                                    <select class="form-control" id="department_filter">
                                        <option value="">Tous les départements</option>
                                        @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="area_filter">Zone</label>
                                    <select class="form-control" id="area_filter">
                                        <option value="">Toutes les zones</option>
                                        @foreach($areas as $area)
                                        <option value="{{ $area->id }}">{{ $area->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 text-end">
                                <div class="btn-group">
                                    <button class="btn btn-primary" id="prev_week">
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                    <button class="btn btn-outline-primary" id="current_week">
                                        Cette semaine
                                    </button>
                                    <button class="btn btn-primary" id="next_week">
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                     <!-- Bouton Export PDF -->
                                     <button class="btn btn-danger ms-2" id="export_pdf">
                                         <i class="bi bi-file-earmark-pdf"></i> PDF
                                     </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <!-- Barre d'outils rapide -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="alert alert-light border">
                                    <div class="row align-items-center d-none">
                                        <div class="col-md-4">
                                            <div class="form-group mb-0">
                                                <label class="form-label mb-1">Assignation rapide:</label>
                                                <select class="form-control form-control-sm" id="quick_assign_type">
                                                    <option value="">Sélectionner un horaire</option>
                                                    @foreach($workHourTypes as $type)
                                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-0">
                                                <label class="form-label mb-1">Pour les jours:</label>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach(['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $index => $day)
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input day-checkbox" 
                                                               type="checkbox" 
                                                               id="day_{{ $index + 1 }}" 
                                                               value="{{ $index + 1 }}" checked>
                                                        <label class="form-check-label" for="day_{{ $index + 1 }}">
                                                            {{ $day }}
                                                        </label>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <button class="btn btn-success btn-sm" id="apply_quick_assign">
                                                <i class="bi bi-check-circle me-1"></i> Appliquer
                                            </button>
                                            <button class="btn btn-secondary btn-sm" id="clear_selections">
                                                <i class="bi bi-x-circle me-1"></i> Effacer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tableau calendrier -->
                        <div class="table-responsive">
                            <table class="table table-bordered calendar-table" id="schedule_calendar">
                                <thead>
                                    <tr>
                                        <th width="200px" class="bg-light">Employé</th>
                                        @php
                                            $currentDate = $startDate->copy();
                                            $daysOfWeek = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                                        @endphp
                                        @for($i = 0; $i < 7; $i++)
                                        <th class="text-center {{ $currentDate->isToday() ? 'bg-info text-white' : '' }}">
                                            <div>{{ $daysOfWeek[$i] }}</div>
                                            <div class="fw-bold">{{ $currentDate->format('d/m/Y') }}</div>
                                            @php $currentDate->addDay(); @endphp
                                        </th>
                                        @endfor
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($employees as $employee)
                                    <tr data-employee-id="{{ $employee->id }}" 
                                        data-department-id="{{ $employee->department_id }}"
                                        data-area-id="{{ $employee->area_id }}">
                                        <td class="employee-cell bg-light">
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <i class="bi bi-person-circle"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $employee->full_name }}</div>
                                                    <div class="small text-muted">
                                                        {{ $employee->emp_code }} | 
                                                        {{ $employee->department->name ?? 'N/A' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        @php
                                            $currentDate = $startDate->copy();
                                        @endphp
                                        @for($i = 0; $i < 7; $i++)
                                        <td class="day-cell text-center" 
                                            data-date="{{ $currentDate->format('Y-m-d') }}"
                                            data-employee-id="{{ $employee->id }}"
                                            data-day="{{ $i + 1 }}">
                                            <!-- Contenu chargé via AJAX -->
                                            <div class="schedule-content">
                                                <div class="spinner-border spinner-border-sm text-secondary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </div>
                                        </td>
                                        @php $currentDate->addDay(); @endphp
                                        @endfor
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- Modal d'assignation d'horaire -->
<div class="modal fade" id="assignScheduleModal" tabindex="-1" aria-labelledby="assignScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignScheduleModalLabel">Assigner un horaire</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignScheduleForm">
                <input type="hidden" id="assign_employee_id" name="employee_id">
                <input type="hidden" id="assign_date" name="date">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Employé</label>
                        <input type="text" class="form-control" id="assign_employee_name" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="text" class="form-control" id="assign_date_display" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="assign_work_hour_type_id" class="form-label">Type d'horaire <span class="text-danger">*</span></label>
                        <select class="form-control" id="assign_work_hour_type_id" name="work_hour_type_id" required>
                            <option value="">Sélectionner un horaire</option>
                            @foreach($workHourTypes as $type)
                            <option value="{{ $type->id }}" data-start="{{ $type->start_time }}" data-end="{{ $type->end_time }}">
                                {{ $type->name }} ({{ date('H:i', strtotime($type->start_time)) }} - {{ date('H:i', strtotime($type->end_time)) }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="assign_is_working_day" name="is_working_day" value="1" checked>
                            <label class="form-check-label" for="assign_is_working_day">
                                Jour travaillé
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="assign_notes" class="form-label">Notes (optionnel)</label>
                        <textarea class="form-control" id="assign_notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="btn_remove_schedule" style="display: none;">
                        <i class="bi bi-trash"></i> Supprimer
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn_submit_assign">
                        <span id="assign-text">Assigner</span>
                        <span id="assign-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de sélection multiple -->
<div class="modal fade" id="massSelectionModal" tabindex="-1" aria-labelledby="massSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="massSelectionModalLabel">Sélection multiple</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>{{ count($employees) }}</strong> employé(s) sélectionné(s)
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Type d'horaire</label>
                            <select class="form-control" id="mass_work_hour_type_id">
                                <option value="">Sélectionner un horaire</option>
                                @foreach($workHourTypes as $type)
                                <option value="{{ $type->id }}">
                                    {{ $type->name }} ({{ date('H:i', strtotime($type->start_time)) }} - {{ date('H:i', strtotime($type->end_time)) }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Jour(s) de la semaine</label>
                            <select class="form-control" id="mass_days_of_week" multiple>
                                <option value="1">Lundi</option>
                                <option value="2">Mardi</option>
                                <option value="3">Mercredi</option>
                                <option value="4">Jeudi</option>
                                <option value="5">Vendredi</option>
                                <option value="6">Samedi</option>
                                <option value="7">Dimanche</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Du</label>
                            <input type="date" class="form-control" id="mass_start_date" value="{{ $startDate->format('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Au</label>
                            <input type="date" class="form-control" id="mass_end_date" value="{{ $endDate->format('Y-m-d') }}">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Notes (optionnel)</label>
                    <textarea class="form-control" id="mass_notes" rows="2"></textarea>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="mass_is_working_day" checked>
                        <label class="form-check-label" for="mass_is_working_day">
                            Jour travaillé
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btn_apply_mass_assign">
                    <i class="bi bi-check-circle me-1"></i> Appliquer à la sélection
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'export PDF -->
<div class="modal fade" id="exportPdfModal" tabindex="-1" aria-labelledby="exportPdfModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportPdfModalLabel">Exporter en PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="exportPdfForm" action="{{ route('schedules.export.pdf') }}" method="GET">
                <input type="hidden" id="pdf_start_date" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                <input type="hidden" id="pdf_end_date" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                <input type="hidden" id="pdf_employee_ids" name="employee_ids">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Portée de l'export</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="export_scope" id="export_all" value="all" checked>
                            <label class="form-check-label" for="export_all">
                                Tous les employés ({{ count($employees) }})
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="export_scope" id="export_selected" value="selected">
                            <label class="form-check-label" for="export_selected">
                                Sélection manuelle
                            </label>
                        </div>
                    </div>
                    
                    <div id="selected_employees_section" class="mb-3" style="display: none;">
                        <label class="form-label">Sélectionner les employés</label>
                        <select class="form-control select2-employees" id="selected_employees" multiple style="width: 100%;">
                            @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->emp_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3 d-none">
                        <label class="form-label">Format d'export</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pdf_orientation" id="orientation_landscape" value="landscape" checked>
                            <label class="form-check-label" for="orientation_landscape">
                                Paysage (A4)
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3 d-none">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_weekend" id="include_weekend" checked>
                            <label class="form-check-label" for="include_weekend">
                                Inclure les weekends
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Générer PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    let selectedCells = [];
    let currentWeekStart = "{{ $startDate->format('Y-m-d') }}";
    let currentWeekEnd = "{{ $endDate->format('Y-m-d') }}";
    
    // Initialiser Select2
    // $('#mass_days_of_week').select2({
    //     placeholder: "Tous les jours",
    //     allowClear: true
    // });
    
    // Charger les données initiales
    loadCalendarData();
    
    // Charger les données du calendrier
    function loadCalendarData() {
        $('.day-cell').each(function() {
            const $cell = $(this);
            const employeeId = $cell.data('employee-id');
            const date = $cell.data('date');
            
            $.ajax({
                url: "{{ route('schedules.get-cell-data') }}",
                type: 'GET',
                data: {
                    employee_id: employeeId,
                    date: date
                },
                success: function(response) {
                    if (response.success) {
                        updateCellContent($cell, response.data);
                    }
                },
                error: function() {
                    $cell.find('.schedule-content').html('<span class="text-danger">Erreur</span>');
                }
            });
        });
    }
    
// Mettre à jour le contenu d'une cellule
function updateCellContent($cell, data) {
    let content = '';
    
    if (!data || typeof data !== 'object') {
        content = '<div class="text-danger small">Erreur de données</div>';
        $cell.find('.schedule-content').html(content);
        return;
    }
    
    if (data.schedule) {
        // Déterminer la couleur selon le type
        let bgColor = 'bg-primary';
        let scheduleType = data.schedule.schedule_type || '';
        
        switch(scheduleType) {
            case 'fixe':
                bgColor = 'bg-warning text-dark';
                break;
            case 'planifie':
                bgColor = 'bg-info text-white';
                break;
            case 'rotation':
                bgColor = 'bg-success text-white';
                break;
            default:
                if (!data.schedule.work_hour_type_id && scheduleType === 'fixe') {
                    bgColor = 'bg-warning text-dark';
                }
        }
        
        // Gestion spéciale pour les rotations
        if (scheduleType === 'rotation') {
            // Vérifier si c'est un jour de travail dans la rotation
            if (data.schedule.is_rotation_work_day === false) {
                // Jour de repos dans la rotation (NE JAMAIS AFFICHER WEEKEND)
                content = `<div class="schedule-badge bg-secondary text-white p-1 rounded">
                    <div class="small"><strong>Rotation</strong></div>
                    <div class="small">Repos</div>
                </div>`;
            } else {
                // Jour de travail dans la rotation
                let rotationInfo = '';
                if (data.schedule.rotation_day && data.schedule.rotation_total_days) {
                    rotationInfo = `<div class="small">J${data.schedule.rotation_day}/${data.schedule.rotation_total_days}</div>`;
                }
                
                let durationInfo = '';
                if (data.schedule.total_hours) {
                    durationInfo = `<div class="small"><strong>${data.schedule.total_hours}h</strong></div>`;
                }
                
                let timeInfo = '';
                if (data.schedule.start_time) {
                    timeInfo = `<div class="small">${data.schedule.start_time} - ${data.schedule.end_time || ''}</div>`;
                }
                
                content = `<div class="schedule-badge ${bgColor} p-1 rounded">
                    <div class="small"><strong>${data.schedule.work_hour_type || 'Rotation'}</strong></div>
                    ${timeInfo}
                    ${durationInfo}
                    ${rotationInfo}
                    ${data.schedule.notes ? '<div class="small"><i class="bi bi-chat-text"></i></div>' : ''}
                </div>`;
            }
        } else {
            // Types fixe, planifié et personnalisé
            let durationInfo = '';
            if (data.schedule.total_hours) {
                durationInfo = `<div class="small"><strong>${data.schedule.total_hours}h</strong></div>`;
            }
            
            let timeInfo = '';
            if (data.schedule.start_time) {
                timeInfo = `<div class="small">${data.schedule.start_time} - ${data.schedule.end_time || ''}</div>`;
            }
            
            let typeName = data.schedule.work_hour_type || scheduleType;
            if (!data.schedule.work_hour_type_id && scheduleType === 'fixe') {
                typeName = 'Personnalisé';
            }
            
            content = `<div class="schedule-badge ${bgColor} p-1 rounded">
                <div class="small"><strong>${typeName}</strong></div>
                ${timeInfo}
                ${durationInfo}
                ${data.schedule.notes ? '<div class="small"><i class="bi bi-chat-text"></i></div>' : ''}
            </div>`;
        }
    } else {
        // Aucun planning trouvé
        // IMPORTANT: Ne jamais afficher "Weekend" pour les rotations
        // Mais comment savoir si c'est une rotation? On vérifie si la date est dans une période de rotation
        
        // Pour simplifier, on vérifie toujours si c'est un weekend
        // Mais cette logique sera corrigée côté serveur
        if (data.is_weekend) {
            content = `<div class="text-muted small">
                <div><i class="bi bi-umbrella"></i> Weekend</div>
            </div>`;
        } else if (data.is_holiday) {
            content = `<div class="text-danger small">
                <div><i class="bi bi-balloon"></i> Férié</div>
            </div>`;
        } else {
            content = `<div class="text-muted small">
                <div>Non planifié</div>
            </div>`;
        }
    }
    
    $cell.find('.schedule-content').html(content);
    $cell.data('schedule-id', data.schedule?.id || null);
    $cell.data('work-hour-type-id', data.schedule?.work_hour_type_id || null);
    $cell.data('schedule-type', data.schedule?.schedule_type || null);
}
    
    // Clic sur une cellule
    $(document).on('click', '.day-cell', function(e) {
        if ($(e.target).closest('.schedule-badge').length) {
            return; // Ne pas gérer le clic sur le badge
        }
        
        const $cell = $(this);
        const employeeId = $cell.data('employee-id');
        const date = $cell.data('date');
        const employeeName = $cell.closest('tr').find('.employee-cell .fw-bold').text();
        const scheduleId = $cell.data('schedule-id');
        
        // Remplir le modal
        $('#assign_employee_id').val(employeeId);
        $('#assign_date').val(date);
        $('#assign_employee_name').val(employeeName);
        $('#assign_date_display').val(formatDate(date));
        
        // Si déjà un planning, pré-remplir
        if (scheduleId) {
            $('#assign_work_hour_type_id').val($cell.data('work-hour-type-id'));
            $('#btn_remove_schedule').show();
            $('#btn_submit_assign').html('<span id="assign-text">Modifier</span>');
        } else {
            $('#assign_work_hour_type_id').val('');
            $('#btn_remove_schedule').hide();
            $('#btn_submit_assign').html('<span id="assign-text">Assigner</span>');
        }
        
        // Réinitialiser les notes
        $('#assign_notes').val('');
        
        // Afficher le modal
        $('#assignScheduleModal').modal('show');
    });
    
    // Gestion du formulaire d'assignation
    $('#assignScheduleForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            employee_id: $('#assign_employee_id').val(),
            work_hour_type_id: $('#assign_work_hour_type_id').val(),
            date: $('#assign_date').val(),
            is_working_day: $('#assign_is_working_day').is(':checked') ? 1 : 0,
            notes: $('#assign_notes').val(),
            _token: "{{ csrf_token() }}"
        };
        
        // Désactiver le bouton
        $('#btn_submit_assign').prop('disabled', true);
        $('#assign-text').addClass('d-none');
        $('#assign-spinner').removeClass('d-none');
        
        $.ajax({
            url: "{{ route('schedules.assign') }}",
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#assignScheduleModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Recharger la cellule
                    const $cell = $(`.day-cell[data-employee-id="${formData.employee_id}"][data-date="${formData.date}"]`);
                    loadCellData($cell);
                } else {
                    Swal.fire('Erreur', response.message, 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Erreur', 'Une erreur est survenue', 'error');
            },
            complete: function() {
                $('#btn_submit_assign').prop('disabled', false);
                $('#assign-text').removeClass('d-none');
                $('#assign-spinner').addClass('d-none');
            }
        });
    });
    
    // Supprimer un planning
    $('#btn_remove_schedule').on('click', function() {
        Swal.fire({
            title: 'Supprimer ce planning ?',
            text: "Cette action est irréversible.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                const employeeId = $('#assign_employee_id').val();
                const date = $('#assign_date').val();
                
                $.ajax({
                    url: "{{ route('schedules.remove') }}",
                    type: 'DELETE',
                    data: {
                        employee_id: employeeId,
                        date: date,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#assignScheduleModal').modal('hide');
                            Swal.fire('Succès', response.message, 'success');
                            
                            // Recharger la cellule
                            const $cell = $(`.day-cell[data-employee-id="${employeeId}"][data-date="${date}"]`);
                            loadCellData($cell);
                        } else {
                            Swal.fire('Erreur', response.message, 'error');
                        }
                    }
                });
            }
        });
    });
    
    // Charger les données d'une cellule
    function loadCellData($cell) {
        const employeeId = $cell.data('employee-id');
        const date = $cell.data('date');
        
        $cell.find('.schedule-content').html('<div class="spinner-border spinner-border-sm"></div>');
        
        $.ajax({
            url: "{{ route('schedules.get-cell-data') }}",
            type: 'GET',
            data: { employee_id: employeeId, date: date },
            success: function(response) {
                if (response.success) {
                    updateCellContent($cell, response.data);
                }
            }
        });
    }

    // Gestion de l'export PDF
$('#export_pdf').on('click', function() {
    // Récupérer les employés filtrés
    const visibleEmployeeIds = getVisibleEmployeeIds();
    $('#filtered_count').text(visibleEmployeeIds.length);
    
    // Initialiser Select2 pour la sélection manuelle
    // $('.select2-employees').select2({
    //     placeholder: "Sélectionner des employés",
    //     allowClear: true,
    //     width: '100%'
    // });
    
    // Afficher le modal
    $('#exportPdfModal').modal('show');
});

// Changer la portée de l'export
$('input[name="export_scope"]').on('change', function() {
    const scope = $(this).val();
    if (scope === 'selected') {
        $('#selected_employees_section').show();
        $('#selected_employees').prop('required', true);
    } else {
        $('#selected_employees_section').hide();
        $('#selected_employees').prop('required', false);
    }
});

// Gestion du formulaire d'export
$('#exportPdfForm').on('submit', function(e) {
    const scope = $('input[name="export_scope"]:checked').val();
    let employeeIds = [];
    
    if (scope === 'all') {
        // Tous les employés
        employeeIds = $('tbody tr').map(function() {
            return $(this).data('employee-id');
        }).get();
    } else if (scope === 'filtered') {
        // Employés filtrés
        employeeIds = getVisibleEmployeeIds();
    } else if (scope === 'selected') {
        // Sélection manuelle
        employeeIds = $('#selected_employees').val();
        if (!employeeIds || employeeIds.length === 0) {
            e.preventDefault();
            Swal.fire('Attention', 'Veuillez sélectionner au moins un employé', 'warning');
            return;
        }
    }
    
    // Ajouter les IDs des employés aux paramètres
    $('#pdf_employee_ids').val(JSON.stringify(employeeIds));
});

// Fonction pour obtenir les IDs des employés visibles
function getVisibleEmployeeIds() {
    const ids = [];
    $('tbody tr:visible').each(function() {
        ids.push($(this).data('employee-id'));
    });
    return ids;
}
    
    // Navigation semaine
    $('#prev_week').on('click', function() {
        navigateWeek(-1);
    });
    
    $('#next_week').on('click', function() {
        navigateWeek(1);
    });
    
    $('#current_week').on('click', function() {
        const today = new Date();
        const weekStart = getWeekStartDate(today);
        loadWeek(weekStart);
    });
    
    $('#week_selector').on('change', function() {
        const yearWeek = $(this).val();
        const [year, week] = yearWeek.split('-W');
        const date = new Date(year, 0, 1 + (week - 1) * 7);
        loadWeek(date);
    });
    
    function navigateWeek(direction) {
        const currentDate = new Date(currentWeekStart);
        currentDate.setDate(currentDate.getDate() + (direction * 7));
        loadWeek(currentDate);
    }
    
    function loadWeek(startDate) {
        window.location.href = "{{ route('schedules.calendar') }}?start_date=" + formatDate(startDate);
    }
    
    // Filtres
    $('#department_filter, #area_filter').on('change', function() {
        const deptId = $('#department_filter').val();
        const areaId = $('#area_filter').val();
        
        $('tbody tr').each(function() {
            const $row = $(this);
            const rowDeptId = $row.data('department-id');
            const rowAreaId = $row.data('area-id');
            
            let showRow = true;
            
            if (deptId && rowDeptId != deptId) {
                showRow = false;
            }
            
            if (areaId && rowAreaId != areaId) {
                showRow = false;
            }
            
            $row.toggle(showRow);
        });
    });
    
    // Assignation rapide
    $('#apply_quick_assign').on('click', function() {
        const workHourTypeId = $('#quick_assign_type').val();
        if (!workHourTypeId) {
            Swal.fire('Attention', 'Veuillez sélectionner un type d\'horaire', 'warning');
            return;
        }
        
        const selectedDays = [];
        $('.day-checkbox:checked').each(function() {
            selectedDays.push($(this).val());
        });
        
        if (selectedDays.length === 0) {
            Swal.fire('Attention', 'Veuillez sélectionner au moins un jour', 'warning');
            return;
        }
        
        Swal.fire({
            title: 'Assignation rapide',
            html: `Assigner cet horaire à <strong>tous les employés</strong> pour les jours sélectionnés ?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Oui, assigner',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                performMassAssign({
                    employee_ids: getVisibleEmployeeIds(),
                    work_hour_type_id: workHourTypeId,
                    start_date: currentWeekStart,
                    end_date: currentWeekEnd,
                    days_of_week: selectedDays,
                    is_working_day: 1
                });
            }
        });
    });
    
    // Effacer les sélections
    $('#clear_selections').on('click', function() {
        $('.day-checkbox').prop('checked', true);
        $('#quick_assign_type').val('');
    });
    
    // Fonctions utilitaires
    function formatDate(date) {
        const d = new Date(date);
        return d.toISOString().split('T')[0];
    }
    
    function getWeekStartDate(date) {
        const d = new Date(date);
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1);
        return new Date(d.setDate(diff));
    }
    
    function getVisibleEmployeeIds() {
        const ids = [];
        $('tbody tr:visible').each(function() {
            ids.push($(this).data('employee-id'));
        });
        return ids;
    }
    
    function performMassAssign(data) {
        $.ajax({
            url: "{{ route('schedules.mass-assign') }}",
            type: 'POST',
            data: {
                ...data,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire('Succès', response.message, 'success');
                    loadCalendarData();
                } else {
                    Swal.fire('Erreur', response.message, 'error');
                }
            }
        });
    }
});
</script>

<style>
.calendar-table {
    font-size: 0.85rem;
}

.calendar-table th {
    vertical-align: middle;
    padding: 8px !important;
}

.calendar-table td {
    vertical-align: middle;
    padding: 4px !important;
    height: 80px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.calendar-table td:hover {
    background-color: #f8f9fa;
}

.calendar-table td.selected {
    background-color: #e3f2fd !important;
}

.employee-cell {
    position: sticky;
    left: 0;
    background-color: #f8f9fa;
    z-index: 1;
}

.schedule-badge {
    font-size: 0.75rem;
    cursor: default;
}

.day-checkbox .form-check-label {
    font-size: 0.8rem;
}

/* Styles pour le weekend */
.calendar-table td[data-day="6"],
.calendar-table td[data-day="7"] {
    background-color: #f8f9fa;
}

/* Style pour aujourd'hui */
.calendar-table td.today {
    border: 2px solid #0d6efd !important;
}
.calendar-table {
    font-size: 0.85rem;
}

.calendar-table th {
    vertical-align: middle;
    padding: 8px !important;
}

.calendar-table td {
    vertical-align: middle;
    padding: 4px !important;
    height: 80px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.calendar-table td:hover {
    background-color: #f8f9fa !important;
}

.calendar-table td.selected {
    background-color: #e3f2fd !important;
}

.employee-cell {
    position: sticky;
    left: 0;
    background-color: #f8f9fa;
    z-index: 1;
}

.schedule-badge {
    font-size: 0.75rem;
    cursor: default;
    border: 1px solid rgba(0,0,0,0.1);
}

/* Styles spécifiques par type */
.bg-warning.text-dark {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

.bg-info {
    background-color: #0dcaf0 !important;
}

.bg-success {
    background-color: #198754 !important;
}

.bg-primary {
    background-color: #0d6efd !important;
}

/* Styles pour le weekend */
.calendar-table td[data-day="6"],
.calendar-table td[data-day="7"] {
    background-color: #f8f9fa;
}

/* Style pour aujourd'hui */
.calendar-table td.today {
    border: 2px solid #0d6efd !important;
}

/* Style pour les cellules avec planning */
.has-schedule {
    font-weight: 500;
}

/* Indicateur de notes */
.bi-chat-text {
    font-size: 0.7rem;
}
</style>
@endsection